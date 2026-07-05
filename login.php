<?php
session_start();
require_once __DIR__ . '/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $username;
                    header('Location: index.php');
                    exit;
                } else {
                    $error = '密碼錯誤';
                }
            } else {
                $error = '無此使用者';
            }
        } else {
            $error = '資料庫錯誤，請稍後再試';
        }
    } else {
        $error = '請輸入使用者名稱與密碼';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>會員登入</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "微軟正黑體", sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
        /* 移除複雜的偽元素動畫，減少GPU負擔 */
    }

    .login-container {
        background: rgba(255, 255, 255, 0.95);
        /* 簡化濾鏡效果，提升性能 */
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 40px 30px;
        border-radius: 16px;
        /* 簡化陰影效果 */
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
        text-align: center;
        /* 減少不必要的transform */
        transition: box-shadow 0.2s ease;
        /* 添加硬體加速 */
        will-change: box-shadow;
    }

    /* 簡化hover效果，減少重繪 */
    @media (hover: hover) {
        .login-container:hover {
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }
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
        /* 簡化陰影 */
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
    }

    h1 {
        margin-bottom: 8px;
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.8rem;
    }

    .subtitle {
        color: #7f8c8d;
        margin-bottom: 30px;
        font-size: 0.9rem;
    }

    .form-group {
        position: relative;
        margin-bottom: 20px;
        text-align: left;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        color: #555;
        font-weight: 500;
        font-size: 0.85rem;
    }

    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 12px 15px;
        font-size: 1rem;
        border: 2px solid #e1e8ed;
        border-radius: 10px;
        /* 簡化transition */
        transition: border-color 0.2s ease;
        background: #f8f9fa;
        font-family: inherit;
        /* 移除可能造成重排的transform */
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
        border-color: #667eea;
        outline: none;
        background: white;
        /* 簡化focus效果 */
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
    }

    button {
        width: 100%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 14px;
        font-size: 1rem;
        border-radius: 10px;
        cursor: pointer;
        transition: transform 0.1s ease, box-shadow 0.2s ease;
        font-weight: 600;
        font-family: inherit;
        margin-top: 10px;
        /* 優化渲染 */
        will-change: transform;
    }

    /* 只在支援hover的設備上啟用hover效果 */
    @media (hover: hover) {
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
    }

    button:active {
        transform: translateY(0);
    }

    .error-msg {
        background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        color: white;
        padding: 10px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
        font-size: 0.9rem;
        /* 簡化動畫 */
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .register-link {
        margin-top: 25px;
        font-size: 0.9rem;
        color: #7f8c8d;
    }

    .register-link a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        /* 簡化link效果 */
        transition: color 0.2s ease;
    }

    .register-link a:hover {
        color: #5a67d8;
        text-decoration: underline;
    }

    /* 優化移動端響應式 */
    @media (max-width: 768px) {
        body {
            padding: 15px;
        }
        
        .login-container {
            padding: 30px 20px;
            border-radius: 12px;
            /* 移動端減少濾鏡效果 */
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }
        
        h1 {
            font-size: 1.6rem;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            font-size: 1.6rem;
        }

        input[type="text"],
        input[type="password"] {
            padding: 10px 12px;
            font-size: 16px; /* 防止iOS縮放 */
        }

        button {
            padding: 12px;
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        .login-container {
            padding: 25px 15px;
        }
        
        h1 {
            font-size: 1.5rem;
        }
    }

    /* 簡化載入動畫 */
    .login-container {
        animation: slideUp 0.4s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* 簡化輸入框圖標 */
    .input-icon {
        position: absolute;
        right: 12px;
        top: 65%;
        transform: translateY(-50%);
        color: #bbb;
        font-size: 1rem;
        pointer-events: none;
    }

    /* 優化性能的額外設置 */
    * {
        -webkit-tap-highlight-color: transparent;
        -webkit-touch-callout: none;
    }

    /* 減少重繪 */
    input, button {
        -webkit-appearance: none;
        appearance: none;
    }
</style>
</head>
<body>
<div class="login-container">
    <div class="logo">🔐</div>
    <h1>歡迎回來</h1>
    <p class="subtitle">請登入您的帳戶</p>
    
    <?php if ($error): ?>
        <div class="error-msg"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="form-group">
            <label for="username">使用者名稱</label>
            <input type="text" id="username" name="username" placeholder="請輸入使用者名稱" required autofocus />
            <div class="input-icon">👤</div>
        </div>
        
        <div class="form-group">
            <label for="password">密碼</label>
            <input type="password" id="password" name="password" placeholder="請輸入密碼" required />
            <div class="input-icon">🔒</div>
        </div>
        
        <button type="submit">立即登入</button>
    </form>
    
    <p class="register-link">
        還沒有帳號？<a href="register.php">立即註冊</a>
    </p>
</div>
</body>
</html>