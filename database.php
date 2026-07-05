<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'quizdb';

// 使用 try-catch 與 mysqli 報錯機制
$conn = new mysqli($host, $user, $pass, $dbname);

// 檢查連線是否失敗
if ($conn->connect_error) {
    error_log("資料庫連線失敗: ({$conn->connect_errno}) {$conn->connect_error}");
    die("伺服器忙碌中，請稍後再試。");
}

// 設定字元集為 utf8mb4
if (!$conn->set_charset("utf8mb4")) {
    error_log("設定字元集失敗: {$conn->error}");
    die("資料庫字元集設定失敗。");
}
?>
