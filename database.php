<?php
require_once __DIR__ . '/db_compat.php';
require_once __DIR__ . '/config.php';

$config = app_config();
$dbConfig = $config['db'];

/**
 * 支援兩種設定方式：
 * 1. 直接給一整包 DATABASE_URL（Render / Supabase 常見格式）
 *    postgres://user:pass@host:port/dbname?sslmode=require
 * 2. 分開的 DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS
 */
if (!empty($dbConfig['url'])) {
    $parts = parse_url($dbConfig['url']);
    if ($parts === false) {
        error_log('DATABASE_URL 格式錯誤');
        die('伺服器設定錯誤，請稍後再試。');
    }
    $host = $parts['host'] ?? '127.0.0.1';
    $port = $parts['port'] ?? 5432;
    $name = ltrim($parts['path'] ?? '', '/');
    $user = $parts['user'] ?? '';
    $pass = $parts['pass'] ?? '';
    parse_str($parts['query'] ?? '', $query);
    $sslmode = $query['sslmode'] ?? $dbConfig['sslmode'];
} else {
    $host    = $dbConfig['host'];
    $port    = $dbConfig['port'];
    $name    = $dbConfig['name'];
    $user    = $dbConfig['user'];
    $pass    = $dbConfig['pass'];
    $sslmode = $dbConfig['sslmode'];
}

$dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode={$sslmode}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("SET NAMES 'UTF8'");
} catch (PDOException $e) {
    error_log('資料庫連線失敗: ' . $e->getMessage());
    die('伺服器忙碌中，請稍後再試。');
}

// $conn 維持跟舊版 mysqli 相容的介面，讓其餘檔案不用大改
$conn = new CompatConnection($pdo);
