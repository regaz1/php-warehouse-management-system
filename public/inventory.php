<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $productId = post_int('product_id', 1);
        $type = (string)($_POST['type'] ?? '');
        $quantity = post_int('quantity', 1);
        $reason = post_text('reason', 200);
        $db->beginTransaction();
        record_stock($db, $productId, $type, $quantity, $reason);
        $db->commit();
        flash('Η κίνηση αποθέματος καταγράφηκε.');
        redirect('inventory.php');
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        $error = $exception->getMessage();
    }
}
$products = all($db, 'SELECT * FROM products ORDER BY name');
$transactions = all($db, 'SELECT t.*, p.name AS product_name, p.sku FROM inventory_transactions t JOIN products p ON p.id=t.product_id ORDER BY t.id DESC LIMIT 100');
page_header('Απόθεμα', 'inventory');
page_heading('ΚΙΝΗΣΕΙΣ', 'Διαχείριση αποθέματος', 'Κάθε παραλαβή και έξοδος αποθηκεύεται στο ιστορικό.');
if (isset($error)): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<section class="panel form-panel"><h2>Νέα κίνηση</h2><form method="post" class="form-grid"><?= csrf_field() ?>
    <label>Προϊόν<select name="product_id" required><option value="">Επιλογή προϊόντος</option><?php foreach ($products as $product): ?><option value="<?= (int)$product['id'] ?>"><?= e($product['name']) ?> · <?= (int)$product['stock'] ?> τεμ.</option><?php endforeach; ?></select></label>
    <label>Τύπος<select name="type"><option value="IN">IN · Παραλαβή</option><option value="OUT">OUT · Έξοδος</option></select></label>
    <label>Ποσότητα<input type="number" name="quantity" min="1" required></label>
    <label>Αιτιολογία<input name="reason" maxlength="200" required placeholder="π.χ. Παραλαβή προμηθευτή"></label>
    <div class="actions full"><button class="button primary">Καταχώριση</button></div>
</form></section>
<section class="panel"><div class="panel-title"><div><h2>Ιστορικό κινήσεων</h2><p>Οι τελευταίες 100 κινήσεις</p></div></div><div class="table-wrap"><table><thead><tr><th>Ημερομηνία</th><th>Προϊόν</th><th>Τύπος</th><th class="number">Ποσότητα</th><th>Αιτιολογία</th></tr></thead><tbody>
<?php foreach ($transactions as $transaction): ?><tr><td><?= e(date('d/m/Y H:i', strtotime($transaction['created_at']))) ?></td><td><?= e($transaction['product_name']) ?><small><?= e($transaction['sku']) ?></small></td><td><span class="badge <?= $transaction['type'] === 'IN' ? 'success' : 'info' ?>"><?= e($transaction['type']) ?></span></td><td class="number"><?= $transaction['type'] === 'IN' ? '+' : '−' ?><?= (int)$transaction['quantity'] ?></td><td><?= e($transaction['reason']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php page_footer(); ?>
