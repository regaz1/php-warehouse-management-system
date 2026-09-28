<?php
declare(strict_types=1);

$testDatabase = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'warehouse-test-' . bin2hex(random_bytes(8)) . '.sqlite';
putenv('WAREHOUSE_DB=' . $testDatabase);
require __DIR__ . '/../app/bootstrap.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('Αποτυχία: ' . $message);
    }
    echo "✓ {$message}\n";
}

try {
    check((int)one($db, 'SELECT COUNT(*) AS n FROM products')['n'] === 6, 'δημιουργήθηκαν 6 demo προϊόντα');
    check((int)one($db, 'SELECT COUNT(*) AS n FROM customers')['n'] === 3, 'δημιουργήθηκαν 3 πελάτες');
    check((int)one($db, 'SELECT COUNT(*) AS n FROM orders')['n'] === 3, 'δημιουργήθηκαν 3 παραγγελίες');

    $before = (int)one($db, 'SELECT stock FROM products WHERE id=1')['stock'];
    $orderId = create_order($db, 1, [1], [3]);
    $after = (int)one($db, 'SELECT stock FROM products WHERE id=1')['stock'];
    check($after === $before - 3, 'η παραγγελία αφαιρεί το σωστό stock');
    $order = one($db, 'SELECT total FROM orders WHERE id=?', [$orderId]);
    check(abs((float)$order['total'] - 194.70) < 0.001, 'το σύνολο παραγγελίας υπολογίζεται σωστά');

    $orderCount = (int)one($db, 'SELECT COUNT(*) AS n FROM orders')['n'];
    $stockSnapshot = (int)one($db, 'SELECT stock FROM products WHERE id=1')['stock'];
    try {
        create_order($db, 1, [1, 2], [1, 999]);
        throw new RuntimeException('Η ανεπαρκής ποσότητα έγινε δεκτή.');
    } catch (RuntimeException $expected) {
        check(str_contains($expected->getMessage(), 'αρκετό απόθεμα'), 'η ανεπαρκής ποσότητα απορρίπτεται');
    }
    check((int)one($db, 'SELECT COUNT(*) AS n FROM orders')['n'] === $orderCount, 'η αποτυχημένη παραγγελία δεν αποθηκεύεται');
    check((int)one($db, 'SELECT stock FROM products WHERE id=1')['stock'] === $stockSnapshot, 'η αποτυχημένη παραγγελία δεν αλλάζει stock');

    $mismatches = all($db, "SELECT p.id FROM products p LEFT JOIN inventory_transactions t ON t.product_id=p.id GROUP BY p.id HAVING p.stock != COALESCE(SUM(CASE WHEN t.type='IN' THEN t.quantity ELSE -t.quantity END),0)");
    check(count($mismatches) === 0, 'όλα τα υπόλοιπα συμφωνούν με το ιστορικό κινήσεων');
    echo "\nΌλοι οι λειτουργικοί έλεγχοι πέρασαν.\n";
} finally {
    $db = null;
    @unlink($testDatabase);
    @unlink($testDatabase . '-shm');
    @unlink($testDatabase . '-wal');
}
