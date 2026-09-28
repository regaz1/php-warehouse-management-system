<?php
declare(strict_types=1);

function record_stock(PDO $db, int $productId, string $type, int $quantity, string $reason, ?int $orderId = null): void
{
    if (!in_array($type, ['IN', 'OUT'], true) || $quantity < 1 || trim($reason) === '') {
        throw new InvalidArgumentException('Μη έγκυρη κίνηση αποθέματος.');
    }
    $operator = $type === 'IN' ? '+' : '-';
    $sql = "UPDATE products SET stock = stock {$operator} :quantity WHERE id = :id";
    if ($type === 'OUT') {
        $sql .= ' AND stock >= :required';
    }
    $statement = $db->prepare($sql);
    $params = ['quantity' => $quantity, 'id' => $productId];
    if ($type === 'OUT') {
        $params['required'] = $quantity;
    }
    $statement->execute($params);
    if ($statement->rowCount() !== 1) {
        throw new RuntimeException('Δεν υπάρχει αρκετό απόθεμα για την κίνηση.');
    }
    $statement = $db->prepare('INSERT INTO inventory_transactions (product_id, order_id, type, quantity, reason) VALUES (?, ?, ?, ?, ?)');
    $statement->execute([$productId, $orderId, $type, $quantity, trim($reason)]);
}

function create_order(PDO $db, int $customerId, array $productIds, array $quantities): int
{
    if (count($productIds) !== count($quantities) || count($productIds) === 0 || count($productIds) > 20) {
        throw new InvalidArgumentException('Προσθέστε τουλάχιστον ένα προϊόν.');
    }
    if (!one($db, 'SELECT id FROM customers WHERE id = ?', [$customerId])) {
        throw new InvalidArgumentException('Επιλέξτε πελάτη.');
    }
    $lines = [];
    foreach ($productIds as $index => $rawProductId) {
        $productId = filter_var($rawProductId, FILTER_VALIDATE_INT);
        $quantity = filter_var($quantities[$index], FILTER_VALIDATE_INT);
        if (!$productId || !$quantity || $quantity < 1 || $quantity > 1000000) {
            throw new InvalidArgumentException('Ελέγξτε τα προϊόντα και τις ποσότητες.');
        }
        $lines[$productId] = ($lines[$productId] ?? 0) + $quantity;
    }

    $db->beginTransaction();
    try {
        $details = [];
        $total = 0.0;
        foreach ($lines as $productId => $quantity) {
            $product = one($db, 'SELECT * FROM products WHERE id = ?', [$productId]);
            if (!$product || (int)$product['stock'] < $quantity) {
                throw new RuntimeException('Δεν υπάρχει αρκετό απόθεμα για όλα τα προϊόντα.');
            }
            $subtotal = round((float)$product['sale_price'] * $quantity, 2);
            $total = round($total + $subtotal, 2);
            $details[] = [$product, $quantity];
        }

        $statement = $db->prepare("INSERT INTO orders (customer_id, total, status) VALUES (?, ?, 'Ολοκληρωμένη')");
        $statement->execute([$customerId, $total]);
        $orderId = (int)$db->lastInsertId();
        $itemStatement = $db->prepare('INSERT INTO order_items (order_id, product_id, product_name, sku, quantity, unit_price) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($details as [$product, $quantity]) {
            $itemStatement->execute([$orderId, $product['id'], $product['name'], $product['sku'], $quantity, $product['sale_price']]);
            record_stock($db, (int)$product['id'], 'OUT', $quantity, 'Παραγγελία #' . $orderId, $orderId);
        }
        $db->commit();
        return $orderId;
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}

function seed_demo(PDO $db): void
{
    $db->beginTransaction();
    try {
        $supplierStatement = $db->prepare('INSERT INTO suppliers (name, email, phone) VALUES (?, ?, ?)');
        foreach ([
            ['Tech Supply AE', 'sales@techsupply.example', '210 555 0101'],
            ['Pack Solutions', 'orders@packsolutions.example', '210 555 0102'],
        ] as $supplier) {
            $supplierStatement->execute($supplier);
        }
        $customerStatement = $db->prepare('INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)');
        foreach ([
            ['Alpha Εμπορική', 'info@alpha.example', '210 555 0201'],
            ['Beta Logistics', 'orders@beta.example', '210 555 0202'],
            ['Gamma Market', 'hello@gamma.example', '210 555 0203'],
        ] as $customer) {
            $customerStatement->execute($customer);
        }
        $products = [
            ['Scanner Barcode', 'EQ-1001', 'Εξοπλισμός', 1, 38.00, 64.90, 35, 10],
            ['Αισθητήρας θερμοκρασίας', 'EL-1002', 'Ηλεκτρονικά', 1, 18.50, 32.90, 8, 12],
            ['Ψηφιακή ζυγαριά', 'EQ-1003', 'Εξοπλισμός', 1, 64.00, 99.50, 18, 5],
            ['Χαρτοκιβώτιο μεγάλο', 'PK-2001', 'Συσκευασία', 2, 1.10, 2.50, 120, 30],
            ['Ταινία συσκευασίας', 'PK-2002', 'Συσκευασία', 2, 3.30, 6.90, 75, 20],
            ['Ετικέτες θερμικές', 'PK-2003', 'Συσκευασία', 2, 7.40, 12.90, 42, 15],
        ];
        $productStatement = $db->prepare('INSERT INTO products (name, sku, category, supplier_id, purchase_price, sale_price, stock, minimum_stock) VALUES (?, ?, ?, ?, ?, ?, 0, ?)');
        foreach ($products as [$name, $sku, $category, $supplierId, $purchase, $sale, $stock, $minimum]) {
            $productStatement->execute([$name, $sku, $category, $supplierId, $purchase, $sale, $minimum]);
            record_stock($db, (int)$db->lastInsertId(), 'IN', $stock, 'Αρχικό απόθεμα');
        }
        $db->commit();
        create_order($db, 1, [1, 4], [2, 10]);
        create_order($db, 2, [3, 5], [1, 4]);
        create_order($db, 3, [2, 6], [2, 3]);
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}

