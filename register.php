<?php
include __DIR__ . '/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param('ss', $username, $hash);
        if ($stmt->execute()) {
            header('Location: login.php');
            exit;
        } else {
            $error = '註冊失敗，可能使用者名稱已存在';
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>會員註冊</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: "Noto Sans TC", "Microsoft JhengHei", Arial, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    position: relative;
    overflow-x: hidden;
  }

  /* 背景動畫效果 */
  body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1.5" fill="rgba(255,255,255,0.03)"/><circle cx="30" cy="30" r="1" fill="rgba(255,255,255,0.02)"/><circle cx="70" cy="20" r="2" fill="rgba(255,255,255,0.04)"/><circle cx="50" cy="70" r="1.5" fill="rgba(255,255,255,0.03)"/><circle cx="90" cy="60" r="1" fill="rgba(255,255,255,0.02)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    pointer-events: none;
  }

  .container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    max-width: 420px;
    width: 100%;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 
      0 20px 40px rgba(0, 0, 0, 0.1),
      0 8px 32px rgba(0, 0, 0, 0.08),
      inset 0 1px 0 rgba(255, 255, 255, 0.4);
    position: relative;
    animation: slideIn 0.6s ease-out;
  }

  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .logo {
    text-align: center;
    margin-bottom: 30px;
  }

  .logo-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    color: white;
    font-size: 24px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
  }

  h1 {
    text-align: center;
    color: #2d3748;
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 8px;
  }

  .subtitle {
    text-align: center;
    color: #718096;
    font-size: 14px;
    margin-bottom: 30px;
  }

  .form-group {
    margin-bottom: 25px;
    position: relative;
  }

  label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #4a5568;
    font-size: 14px;
  }

  .input-wrapper {
    position: relative;
  }

  input[type="text"],
  input[type="password"] {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #f7fafc;
    color: #2d3748;
    outline: none;
  }

  input[type="text"]:focus,
  input[type="password"]:focus {
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-1px);
  }

  input[type="text"]:hover,
  input[type="password"]:hover {
    border-color: #cbd5e0;
  }

  button {
    width: 100%;
    padding: 16px;
    font-size: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    position: relative;
    overflow: hidden;
  }

  button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
  }

  button:hover::before {
    left: 100%;
  }

  button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
  }

  button:active {
    transform: translateY(0);
  }

  .login-link {
    text-align: center;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
  }

  .login-link a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
  }

  .login-link a::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: -3px;
    left: 50%;
    background: #667eea;
    transition: all 0.3s ease;
  }

  .login-link a:hover::after {
    width: 100%;
    left: 0;
  }

  .login-link a:hover {
    color: #5a67d8;
  }

  .error {
    color: #e53e3e;
    background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 500;
    border: 1px solid #fc8181;
    animation: shake 0.5s ease-in-out;
  }

  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
  }

  /* 響應式設計 */
  @media (max-width: 480px) {
    .container {
      margin: 10px;
      padding: 30px 25px;
    }
    
    h1 {
      font-size: 24px;
    }
    
    .logo-icon {
      width: 50px;
      height: 50px;
      font-size: 20px;
    }
  }

  /* 載入動畫 */
  .loading {
    pointer-events: none;
    opacity: 0.7;
  }

  .loading button {
    background: #a0aec0;
    cursor: not-allowed;
  }

  .loading button::after {
    content: '';
    width: 20px;
    height: 20px;
    border: 2px solid transparent;
    border-top: 2px solid white;
    border-radius: 50%;
    display: inline-block;
    animation: spin 1s linear infinite;
    margin-left: 10px;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
</style>
</head>
<body>
<div class="container">
  <div class="logo">
    <div class="logo-icon">註</div>
    <h1>會員註冊</h1>
    <div class="subtitle">加入我們，開始您的旅程</div>
  </div>

  <?php if ($error): ?>
    <div class="error"><?=htmlspecialchars($error)?></div>
  <?php endif; ?>

  <form method="post" action="" id="registerForm">
    <div class="form-group">
      <label for="username">使用者名稱</label>
      <div class="input-wrapper">
        <input type="text" id="username" name="username" required autofocus 
               placeholder="請輸入使用者名稱">
      </div>
    </div>
    
    <div class="form-group">
      <label for="password">密碼</label>
      <div class="input-wrapper">
        <input type="password" id="password" name="password" required 
               placeholder="請輸入密碼">
      </div>
    </div>
    
    <button type="submit" id="submitBtn">立即註冊</button>
  </form>

  <div class="login-link">
    <a href="login.php">已有帳號？立即登入</a>
  </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function() {
  const form = this;
  const submitBtn = document.getElementById('submitBtn');
  
  // 添加載入狀態
  form.classList.add('loading');
  submitBtn.innerHTML = '註冊中...';
  
  // 防止重複提交
  submitBtn.disabled = true;
});

// 輸入框動畫效果
document.querySelectorAll('input').forEach(input => {
  input.addEventListener('focus', function() {
    this.parentElement.style.transform = 'scale(1.02)';
  });
  
  input.addEventListener('blur', function() {
    this.parentElement.style.transform = 'scale(1)';
  });
});
</script>
</body>
</html>