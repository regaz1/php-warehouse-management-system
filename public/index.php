<?php
require_once __DIR__ . '/../app/bootstrap.php';

$stats = one($db, "SELECT
    (SELECT COUNT(*) FROM products) AS products,
    (SELECT COUNT(*) FROM products WHERE stock < minimum_stock) AS low_stock,
    (SELECT COALESCE(SUM(stock * purchase_price), 0) FROM products) AS stock_value,
    (SELECT COUNT(*) FROM orders) AS orders,
    (SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'Ολοκληρωμένη') AS revenue");
$lowProducts = all($db, 'SELECT * FROM products WHERE stock < minimum_stock ORDER BY stock ASC LIMIT 5');
$recentOrders = all($db, 'SELECT o.*, c.name AS customer_name FROM orders o JOIN customers c ON c.id = o.customer_id ORDER BY o.id DESC LIMIT 5');
$topProducts = all($db, "SELECT oi.product_name, SUM(oi.quantity) AS units
    FROM order_items oi JOIN orders o ON o.id = oi.order_id
    WHERE o.status = 'Ολοκληρωμένη'
    GROUP BY oi.product_id, oi.product_name ORDER BY units DESC LIMIT 5");
$maxUnits = max(array_column($topProducts, 'units') ?: [1]);

page_header('Αρχική', 'dashboard');
page_heading('ΣΥΝΟΨΗ', 'Η αποθήκη σήμερα', 'Τα βασικά στοιχεία της επιχείρησης σε μία σελίδα.', '<a class="button primary" href="orders.php?action=new">＋ Νέα παραγγελία</a>');
?>
<section class="stats">
    <article><span>Προϊόντα</span><strong><?= (int)$stats['products'] ?></strong><small>στον κατάλογο</small></article>
    <article><span>Χαμηλό απόθεμα</span><strong><?= (int)$stats['low_stock'] ?></strong><small>χρειάζονται προσοχή</small></article>
    <article><span>Αξία αποθέματος</span><strong><?= euro($stats['stock_value']) ?></strong><small>σε τιμές αγοράς</small></article>
    <article class="highlight"><span>Συνολικά έσοδα</span><strong><?= euro($stats['revenue']) ?></strong><small><?= (int)$stats['orders'] ?> παραγγελίες</small></article>
</section>

<div class="grid two">
    <section class="panel">
        <div class="panel-title"><div><h2>Προϊόντα με χαμηλό απόθεμα</h2><p>Stock μικρότερο από το ελάχιστο όριο</p></div><a href="products.php?low=1">Προβολή όλων →</a></div>
        <?php if (!$lowProducts): ?><p class="empty">Δεν υπάρχει προϊόν με χαμηλό απόθεμα.</p><?php endif; ?>
        <?php foreach ($lowProducts as $product): ?>
            <div class="attention"><span class="initial"><?= e(first_letter($product['name'])) ?></span><div><strong><?= e($product['name']) ?></strong><small><?= e($product['sku']) ?> · Ελάχιστο <?= (int)$product['minimum_stock'] ?></small></div><b><?= (int)$product['stock'] ?><small>τεμ.</small></b></div>
        <?php endforeach; ?>
    </section>
    <section class="panel">
        <div class="panel-title"><div><h2>Δημοφιλή προϊόντα</h2><p>Με βάση τις πωλημένες μονάδες</p></div></div>
        <div class="bars">
            <?php foreach ($topProducts as $product): ?>
                <div class="bar-row"><span><?= e($product['product_name']) ?></span><div><i style="width: <?= round(((int)$product['units'] / $maxUnits) * 100) ?>%"></i></div><strong><?= (int)$product['units'] ?></strong></div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<section class="panel">
    <div class="panel-title"><div><h2>Πρόσφατες παραγγελίες</h2><p>Οι τελευταίες κινήσεις πωλήσεων</p></div><a href="orders.php">Όλες οι παραγγελίες →</a></div>
    <div class="table-wrap"><table><thead><tr><th>Κωδικός</th><th>Πελάτης</th><th>Ημερομηνία</th><th>Κατάσταση</th><th class="number">Σύνολο</th></tr></thead><tbody>
    <?php foreach ($recentOrders as $order): ?><tr><td><a href="orders.php?id=<?= (int)$order['id'] ?>">#<?= str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT) ?></a></td><td><?= e($order['customer_name']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td><td><span class="badge success"><?= e($order['status']) ?></span></td><td class="number"><?= euro($order['total']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<?php page_footer(); ?>
