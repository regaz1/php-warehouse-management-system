<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $orderId = create_order($db, post_int('customer_id', 1), (array)($_POST['product_id'] ?? []), (array)($_POST['quantity'] ?? []));
        flash('Η παραγγελία #' . $orderId . ' ολοκληρώθηκε και το απόθεμα ενημερώθηκε.');
        redirect('orders.php?id=' . $orderId);
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}

$customers = all($db, 'SELECT * FROM customers ORDER BY name');
$products = all($db, 'SELECT * FROM products ORDER BY name');
$orders = all($db, 'SELECT o.*, c.name AS customer_name FROM orders o JOIN customers c ON c.id=o.customer_id ORDER BY o.id DESC');
$selectedOrder = isset($_GET['id']) ? one($db, 'SELECT o.*, c.name AS customer_name, c.email FROM orders o JOIN customers c ON c.id=o.customer_id WHERE o.id=?', [(int)$_GET['id']]) : null;
$orderItems = $selectedOrder ? all($db, 'SELECT * FROM order_items WHERE order_id=? ORDER BY id', [$selectedOrder['id']]) : [];
$showForm = ($_GET['action'] ?? '') === 'new' || isset($error);

page_header('Παραγγελίες', 'orders');
page_heading('ΠΩΛΗΣΕΙΣ', 'Παραγγελίες', 'Η παραγγελία αφαιρεί άμεσα τις μονάδες από το απόθεμα.', '<a class="button primary" href="orders.php?action=new">＋ Νέα παραγγελία</a>');
if (isset($error)): ?><div class="notice error"><?= e($error) ?></div><?php endif;
if ($showForm): ?>
<section class="panel form-panel"><h2>Νέα παραγγελία</h2><form method="post" id="order-form"><?= csrf_field() ?>
    <label>Πελάτης<select name="customer_id" required><option value="">Επιλογή πελάτη</option><?php foreach ($customers as $customer): ?><option value="<?= (int)$customer['id'] ?>"><?= e($customer['name']) ?></option><?php endforeach; ?></select></label>
    <div id="order-lines" class="order-lines"><div class="order-line"><label>Προϊόν<select name="product_id[]" required><option value="" data-price="0">Επιλογή προϊόντος</option><?php foreach ($products as $product): ?><option value="<?= (int)$product['id'] ?>" data-price="<?= e($product['sale_price']) ?>"><?= e($product['name']) ?> · <?= euro($product['sale_price']) ?> · <?= (int)$product['stock'] ?> τεμ.</option><?php endforeach; ?></select></label><label>Ποσότητα<input type="number" name="quantity[]" min="1" value="1" required></label><span class="line-total">0,00 €</span><button type="button" class="remove" aria-label="Αφαίρεση γραμμής">×</button></div></div>
    <button type="button" class="button" id="add-line">＋ Προσθήκη προϊόντος</button>
    <div class="order-total"><span>Σύνολο</span><strong id="order-total">0,00 €</strong></div>
    <div class="actions"><a class="button" href="orders.php">Ακύρωση</a><button class="button primary">Ολοκλήρωση παραγγελίας</button></div>
</form></section>
<?php endif; ?>
<?php if ($selectedOrder): ?><section class="panel"><div class="panel-title"><div><h2>Παραγγελία #<?= str_pad((string)$selectedOrder['id'], 4, '0', STR_PAD_LEFT) ?></h2><p><?= e($selectedOrder['customer_name']) ?> · <?= e($selectedOrder['email']) ?> · <?= e(date('d/m/Y H:i', strtotime($selectedOrder['created_at']))) ?></p></div><span class="badge success"><?= e($selectedOrder['status']) ?></span></div><div class="table-wrap"><table><thead><tr><th>Προϊόν</th><th>SKU</th><th class="number">Ποσότητα</th><th class="number">Τιμή</th><th class="number">Υποσύνολο</th></tr></thead><tbody><?php foreach ($orderItems as $item): ?><tr><td><?= e($item['product_name']) ?></td><td><?= e($item['sku']) ?></td><td class="number"><?= (int)$item['quantity'] ?></td><td class="number"><?= euro($item['unit_price']) ?></td><td class="number"><?= euro((float)$item['unit_price'] * (int)$item['quantity']) ?></td></tr><?php endforeach; ?></tbody></table></div><div class="order-total"><span>Τελικό σύνολο</span><strong><?= euro($selectedOrder['total']) ?></strong></div></section><?php endif; ?>
<section class="panel"><div class="panel-title"><div><h2>Ιστορικό παραγγελιών</h2><p><?= count($orders) ?> εγγραφές</p></div></div><div class="table-wrap"><table><thead><tr><th>Κωδικός</th><th>Πελάτης</th><th>Ημερομηνία</th><th>Κατάσταση</th><th class="number">Σύνολο</th></tr></thead><tbody><?php foreach ($orders as $order): ?><tr><td><a href="orders.php?id=<?= (int)$order['id'] ?>">#<?= str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT) ?></a></td><td><?= e($order['customer_name']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td><td><span class="badge success"><?= e($order['status']) ?></span></td><td class="number"><?= euro($order['total']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php page_footer(); ?>
