<?php
session_start();
// 清除所有 session 變數
$_SESSION = [];
// 刪除 session cookie (若有使用 cookie 方式管理 session)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
// 最後徹底銷毀 session
session_destroy();

header('Location: login.php');
exit;
