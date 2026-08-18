<?php

declare(strict_types=1);

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/config.example.php';
}

$config = require $configFile;

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
if (!defined('APP_URL')) {
    define('APP_URL', rtrim($config['app']['url'] ?? '', '/'));
}
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', (bool)($config['app']['debug'] ?? false));
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $config;

    $db = $config['db'];
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['name'],
        $db['charset'] ?? 'utf8mb4'
    );

    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ]);
        try {
            $pdo->exec('SET SESSION query_cache_type = OFF');
        } catch (Throwable $e) {
            // MySQL 8+ has no query cache; ignore.
        }
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
        }
        die('Database connection failed. Please try again later.');
    }

    return $pdo;
}

function app_config(?string $key = null, $default = null)
{
    global $config;
    if ($key === null) {
        return $config;
    }
    $parts = explode('.', $key);
    $value = $config;
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}
