<?php
declare(strict_types=1);

$localConfig = is_file(__DIR__ . '/config.local.php')
    ? require __DIR__ . '/config.local.php'
    : [];

define('DB_HOST', $localConfig['host'] ?? '127.0.0.1');
define('DB_PORT', $localConfig['port'] ?? '3307');
define('DB_NAME', $localConfig['name'] ?? 'petrostation_dbms');
define('DB_USER', $localConfig['user'] ?? 'root');
define('DB_PASS', $localConfig['pass'] ?? '');

function db_dsn(?string $database = DB_NAME): string
{
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
    if ($database !== null && $database !== '') {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . $database . ';charset=utf8mb4';
    }
    return $dsn;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = db_dsn(DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function db_server(): PDO
{
    $dsn = db_dsn(null);
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
