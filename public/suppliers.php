<?php
require_once __DIR__ . '/../app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $name = post_text('name');
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Το email δεν είναι έγκυρο.');
        $statement = $db->prepare('INSERT INTO suppliers (name, email, phone) VALUES (?, ?, ?)');
        $statement->execute([$name, $email, $phone]);
        flash('Ο προμηθευτής προστέθηκε.');
        redirect('suppliers.php');
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
$suppliers = all($db, 'SELECT s.*, COUNT(p.id) AS product_count FROM suppliers s LEFT JOIN products p ON p.supplier_id=s.id GROUP BY s.id ORDER BY s.name');
page_header('Προμηθευτές', 'suppliers');
page_heading('ΣΥΝΕΡΓΑΤΕΣ', 'Προμηθευτές', 'Οι εταιρείες που προμηθεύουν τα προϊόντα.', '<a class="button primary" href="suppliers.php?action=new">＋ Νέος προμηθευτής</a>');
if (isset($error)): ?><div class="notice error"><?= e($error) ?></div><?php endif;
if (isset($_GET['action']) || isset($error)): ?><section class="panel form-panel"><h2>Νέος προμηθευτής</h2><form method="post" class="form-grid"><?= csrf_field() ?><label>Επωνυμία<input name="name" required maxlength="150"></label><label>Email<input type="email" name="email" maxlength="150"></label><label>Τηλέφωνο<input name="phone" maxlength="50"></label><div class="actions full"><a class="button" href="suppliers.php">Ακύρωση</a><button class="button primary">Αποθήκευση</button></div></form></section><?php endif; ?>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Προμηθευτής</th><th>Email</th><th>Τηλέφωνο</th><th class="number">Προϊόντα</th></tr></thead><tbody><?php foreach ($suppliers as $supplier): ?><tr><td><strong><?= e($supplier['name']) ?></strong></td><td><?= e($supplier['email'] ?: '—') ?></td><td><?= e($supplier['phone'] ?: '—') ?></td><td class="number"><?= (int)$supplier['product_count'] ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php page_footer(); ?>
