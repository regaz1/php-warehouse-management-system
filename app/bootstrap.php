<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'; img-src 'self' data:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('Cache-Control: no-store');

const APP_NAME = 'Η Αποθήκη μου';
const APP_ROOT = __DIR__ . '/..';
const DATA_DIR = APP_ROOT . '/data';

if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0775, true) && !is_dir(DATA_DIR)) {
    throw new RuntimeException('Δεν ήταν δυνατή η δημιουργία του φακέλου data.');
}

$databaseFile = getenv('WAREHOUSE_DB') ?: DATA_DIR . '/warehouse.sqlite';
$isNewDatabase = !file_exists($databaseFile);

try {
    $db = new PDO('sqlite:' . $databaseFile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    exit('Απαιτείται η επέκταση PDO SQLite της PHP.');
}

$db->exec('PRAGMA foreign_keys = ON');
$db->exec('PRAGMA busy_timeout = 5000');
$schema = file_get_contents(__DIR__ . '/schema.sql');
if ($schema === false) {
    throw new RuntimeException('Δεν βρέθηκε το schema.sql.');
}
$db->exec($schema);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/services.php';

if ($isNewDatabase) {
    seed_demo($db);
}
