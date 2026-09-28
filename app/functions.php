<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function euro(float|int|string $value): string
{
    return number_format((float)$value, 2, ',', '.') . ' €';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('Μη έγκυρο αίτημα. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function post_text(string $name, int $maxLength = 150): string
{
    $value = trim((string)($_POST[$name] ?? ''));
    if ($value === '' || strlen($value) > $maxLength * 4) {
        throw new InvalidArgumentException('Συμπληρώστε σωστά το πεδίο «' . $name . '».');
    }
    return $value;
}

function first_letter(string $value): string
{
    return preg_match('/^./u', $value, $match) ? $match[0] : '?';
}

function post_int(string $name, int $minimum = 0): int
{
    $value = filter_input(INPUT_POST, $name, FILTER_VALIDATE_INT);
    if ($value === false || $value === null || $value < $minimum || $value > 1000000) {
        throw new InvalidArgumentException('Το πεδίο «' . $name . '» πρέπει να είναι ακέραιος αριθμός.');
    }
    return $value;
}

function post_money(string $name): float
{
    $raw = str_replace(',', '.', trim((string)($_POST[$name] ?? '')));
    if (!preg_match('/^\d{1,7}(?:\.\d{1,2})?$/', $raw)) {
        throw new InvalidArgumentException('Η τιμή πρέπει να έχει έως δύο δεκαδικά ψηφία.');
    }
    return round((float)$raw, 2);
}

function one(PDO $db, string $sql, array $params = []): ?array
{
    $statement = $db->prepare($sql);
    $statement->execute($params);
    $row = $statement->fetch();
    return $row === false ? null : $row;
}

function all(PDO $db, string $sql, array $params = []): array
{
    $statement = $db->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function page_header(string $title, string $active): void
{
    $flash = take_flash();
    $navigation = [
        'dashboard' => ['Αρχική', 'index.php'],
        'products' => ['Προϊόντα', 'products.php'],
        'inventory' => ['Απόθεμα', 'inventory.php'],
        'orders' => ['Παραγγελίες', 'orders.php'],
        'customers' => ['Πελάτες', 'customers.php'],
        'suppliers' => ['Προμηθευτές', 'suppliers.php'],
    ];
    ?><!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Απλό σύστημα διαχείρισης αποθήκης με PHP και SQLite.">
    <title><?= e($title) ?> · <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/overrides.css">
    <script defer src="assets/app.js"></script>
</head>
<body>
<a class="skip" href="#content">Μετάβαση στο περιεχόμενο</a>
<aside class="sidebar">
    <a class="brand" href="index.php"><span>▦</span> <?= APP_NAME ?></a>
    <nav aria-label="Κύρια πλοήγηση">
        <?php foreach ($navigation as $key => [$label, $url]): ?>
            <a href="<?= $url ?>" class="<?= $active === $key ? 'active' : '' ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <small>PHP + SQLite<br>Portfolio Project</small>
</aside>
<main id="content">
    <div class="topbar"><span>Workspace / <strong><?= e($title) ?></strong></span><span><?= date('d/m/Y') ?></span></div>
    <div class="content">
        <?php if ($flash): ?><div class="notice <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
<?php
}

function page_footer(): void
{
    ?></div></main></body></html><?php
}

function page_heading(string $eyebrow, string $title, string $description, string $action = ''): void
{
    ?><header class="page-heading"><div><span class="eyebrow"><?= e($eyebrow) ?></span><h1><?= e($title) ?></h1><p><?= e($description) ?></p></div><?= $action ?></header><?php
}
