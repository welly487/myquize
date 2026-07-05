<?php
require_once __DIR__ . '/session_init.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>會員登入</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "微軟正黑體", sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .login-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 40px 30px;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
        text-align: center;
    }

    .logo {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 50%;
        margin: 0 auto 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
        font-weight: bold;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
    }

    h1 { margin-bottom: 8px; color: #2c3e50; font-weight: 700; font-size: 1.8rem; }
    .subtitle { color: #7f8c8d; margin-bottom: 30px; font-size: 0.9rem; }

    .error-msg {
        background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        color: white;
        padding: 10px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .google-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        background: white;
        color: #3c4043;
        border: 1px solid #dadce0;
        padding: 14px;
        font-size: 1rem;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        font-family: inherit;
        text-decoration: none;
        transition: box-shadow 0.2s ease, background 0.2s ease;
    }

    .google-btn:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        background: #f8f9fa;
    }

    .google-btn svg { width: 20px; height: 20px; }
</style>
</head>
<body>
<div class="login-container">
    <div class="logo">📚</div>
    <h1>歡迎回來</h1>
    <p class="subtitle">使用 Google 帳號登入試卷管理系統</p>

    <?php if ($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <a href="auth_google.php" class="google-btn">
        <svg viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.1 8 3l6-6C34.6 5.1 29.6 3 24 3 12.4 3 3 12.4 3 24s9.4 21 21 21 21-9.4 21-21c0-1.2-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.5 16 19 13 24 13c3.1 0 5.8 1.1 8 3l6-6C34.6 5.1 29.6 3 24 3 16.3 3 9.7 7.4 6.3 14.7z"/><path fill="#4CAF50" d="M24 45c5.5 0 10.4-1.9 14.3-5.1l-6.6-5.4C29.7 36.4 27 37 24 37c-5.2 0-9.6-3.3-11.3-7.9l-6.6 5.1C9.6 40.5 16.3 45 24 45z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.2-4.1 5.5l6.6 5.4C41.5 36 44 30.5 44 24c0-1.2-.1-2.4-.4-3.5z"/></svg>
        使用 Google 帳號登入
    </a>
</div>
</body>
</html>
