<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $id = post_int('id');
        $supplierId = post_int('supplier_id', 1);
        if (!one($db, 'SELECT id FROM suppliers WHERE id = ?', [$supplierId])) {
            throw new InvalidArgumentException('Επιλέξτε προμηθευτή.');
        }
        $values = [
            post_text('name'), strtoupper(post_text('sku', 40)), post_text('category', 80),
            $supplierId, post_money('purchase_price'), post_money('sale_price'), post_int('minimum_stock'),
        ];
        if ($id > 0) {
            $statement = $db->prepare('UPDATE products SET name=?, sku=?, category=?, supplier_id=?, purchase_price=?, sale_price=?, minimum_stock=? WHERE id=?');
            $statement->execute([...$values, $id]);
        } else {
            $initialStock = post_int('initial_stock');
            $db->beginTransaction();
            $statement = $db->prepare('INSERT INTO products (name, sku, category, supplier_id, purchase_price, sale_price, stock, minimum_stock) VALUES (?, ?, ?, ?, ?, ?, 0, ?)');
            $statement->execute($values);
            $productId = (int)$db->lastInsertId();
            if ($initialStock > 0) {
                record_stock($db, $productId, 'IN', $initialStock, 'Αρχικό απόθεμα');
            }
            $db->commit();
        }
        flash('Το προϊόν αποθηκεύτηκε.');
        redirect('products.php');
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        $error = $exception instanceof PDOException ? 'Το SKU υπάρχει ήδη ή τα στοιχεία δεν είναι έγκυρα.' : $exception->getMessage();
    }
}

$editing = isset($_GET['edit']) ? one($db, 'SELECT * FROM products WHERE id = ?', [(int)$_GET['edit']]) : null;
$showForm = isset($_GET['action']) || $editing || isset($error);
$search = trim((string)($_GET['q'] ?? ''));
$params = [];
$where = [];
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if (isset($_GET['low'])) $where[] = 'p.stock < p.minimum_stock';
$sql = 'SELECT p.*, s.name AS supplier_name FROM products p JOIN suppliers s ON s.id=p.supplier_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$products = all($db, $sql . ' ORDER BY p.name', $params);
$suppliers = all($db, 'SELECT * FROM suppliers ORDER BY name');

page_header('Προϊόντα', 'products');
page_heading('ΚΑΤΑΛΟΓΟΣ', 'Προϊόντα', 'Προσθήκη, επεξεργασία και έλεγχος αποθέματος.', '<a class="button primary" href="products.php?action=new">＋ Νέο προϊόν</a>');
if (isset($error)): ?><div class="notice error"><?= e($error) ?></div><?php endif;
if ($showForm): ?>
<section class="panel form-panel"><h2><?= $editing ? 'Επεξεργασία προϊόντος' : 'Νέο προϊόν' ?></h2><form method="post" class="form-grid">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <label>Όνομα<input name="name" required maxlength="150" value="<?= e($_POST['name'] ?? $editing['name'] ?? '') ?>"></label>
    <label>SKU<input name="sku" required maxlength="40" value="<?= e($_POST['sku'] ?? $editing['sku'] ?? '') ?>"></label>
    <label>Κατηγορία<input name="category" required maxlength="80" value="<?= e($_POST['category'] ?? $editing['category'] ?? '') ?>"></label>
    <label>Προμηθευτής<select name="supplier_id" required><option value="">Επιλογή</option><?php foreach ($suppliers as $supplier): ?><option value="<?= (int)$supplier['id'] ?>" <?= (string)($_POST['supplier_id'] ?? $editing['supplier_id'] ?? '') === (string)$supplier['id'] ? 'selected' : '' ?>><?= e($supplier['name']) ?></option><?php endforeach; ?></select></label>
    <label>Τιμή αγοράς<input type="number" name="purchase_price" min="0" step="0.01" required value="<?= e($_POST['purchase_price'] ?? $editing['purchase_price'] ?? '') ?>"></label>
    <label>Τιμή πώλησης<input type="number" name="sale_price" min="0" step="0.01" required value="<?= e($_POST['sale_price'] ?? $editing['sale_price'] ?? '') ?>"></label>
    <label>Ελάχιστο απόθεμα<input type="number" name="minimum_stock" min="0" required value="<?= e($_POST['minimum_stock'] ?? $editing['minimum_stock'] ?? 5) ?>"></label>
    <?php if (!$editing): ?><label>Αρχικό απόθεμα<input type="number" name="initial_stock" min="0" required value="<?= e($_POST['initial_stock'] ?? 0) ?>"></label><?php endif; ?>
    <div class="actions full"><a class="button" href="products.php">Ακύρωση</a><button class="button primary">Αποθήκευση</button></div>
</form></section>
<?php endif; ?>
<section class="panel"><form class="filters"><label>Αναζήτηση<input name="q" placeholder="Όνομα ή SKU" value="<?= e($search) ?>"></label><button class="button">Αναζήτηση</button><a href="products.php">Καθαρισμός</a></form>
<div class="table-wrap"><table><thead><tr><th>Προϊόν</th><th>Κατηγορία</th><th>Προμηθευτής</th><th class="number">Τιμή</th><th class="number">Stock / Min</th><th>Κατάσταση</th><th></th></tr></thead><tbody>
<?php foreach ($products as $product): $low = (int)$product['stock'] < (int)$product['minimum_stock']; ?><tr><td><strong><?= e($product['name']) ?></strong><small><?= e($product['sku']) ?></small></td><td><?= e($product['category']) ?></td><td><?= e($product['supplier_name']) ?></td><td class="number"><?= euro($product['sale_price']) ?></td><td class="number"><?= (int)$product['stock'] ?> / <?= (int)$product['minimum_stock'] ?></td><td><span class="badge <?= $low ? 'warning' : 'success' ?>"><?= $low ? 'Χαμηλό' : 'Επαρκές' ?></span></td><td><a href="products.php?edit=<?= (int)$product['id'] ?>">Επεξεργασία</a></td></tr><?php endforeach; ?>
<?php if (!$products): ?><tr><td colspan="7" class="empty">Δεν βρέθηκαν προϊόντα.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php page_footer(); ?>
