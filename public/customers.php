<?php
require_once __DIR__ . '/../app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $name = post_text('name');
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Το email δεν είναι έγκυρο.');
        $statement = $db->prepare('INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)');
        $statement->execute([$name, $email, $phone]);
        flash('Ο πελάτης προστέθηκε.');
        redirect('customers.php');
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
$customers = all($db, "SELECT c.*, COUNT(o.id) AS order_count, COALESCE(SUM(CASE WHEN o.status='Ολοκληρωμένη' THEN o.total ELSE 0 END),0) AS purchases FROM customers c LEFT JOIN orders o ON o.customer_id=c.id GROUP BY c.id ORDER BY c.name");
page_header('Πελάτες', 'customers');
page_heading('ΕΠΑΦΕΣ', 'Πελάτες', 'Στοιχεία επικοινωνίας και ιστορικό αγορών.', '<a class="button primary" href="customers.php?action=new">＋ Νέος πελάτης</a>');
if (isset($error)): ?><div class="notice error"><?= e($error) ?></div><?php endif;
if (isset($_GET['action']) || isset($error)): ?><section class="panel form-panel"><h2>Νέος πελάτης</h2><form method="post" class="form-grid"><?= csrf_field() ?><label>Όνομα / Επωνυμία<input name="name" required maxlength="150"></label><label>Email<input type="email" name="email" maxlength="150"></label><label>Τηλέφωνο<input name="phone" maxlength="50"></label><div class="actions full"><a class="button" href="customers.php">Ακύρωση</a><button class="button primary">Αποθήκευση</button></div></form></section><?php endif; ?>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Πελάτης</th><th>Email</th><th>Τηλέφωνο</th><th class="number">Παραγγελίες</th><th class="number">Αγορές</th></tr></thead><tbody><?php foreach ($customers as $customer): ?><tr><td><strong><?= e($customer['name']) ?></strong></td><td><?= e($customer['email'] ?: '—') ?></td><td><?= e($customer['phone'] ?: '—') ?></td><td class="number"><?= (int)$customer['order_count'] ?></td><td class="number"><?= euro($customer['purchases']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php page_footer(); ?>
