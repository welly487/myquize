<?php
require_once __DIR__ . '/session_init.php';
include_once __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title) {
        $stmt = $conn->prepare("INSERT INTO quizzes (user_id, title, description) VALUES (?, ?, ?) RETURNING id");
        $stmt->bind_param("iss", $user_id, $title, $description);
        if ($stmt->execute()) {
            $quiz_id = $stmt->insert_id;
            header("Location: quiz_edit.php?id=$quiz_id");
            exit;
        } else {
            $error = '新增試卷失敗';
        }
    } else {
        $error = '請輸入試卷標題';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增試卷 - 線上測驗系統</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container panel-card">
        <div class="header">
            <div class="icon">
                <i class="fas fa-file-plus"></i>
            </div>
            <h1>建立新試卷</h1>
            <p>設計您的專屬測驗內容</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" id="quizForm">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="title">
                    <i class="fas fa-heading"></i>
                    試卷標題
                </label>
                <input type="text" id="title" name="title" class="form-input" placeholder="請輸入具有吸引力的試卷標題..." required>
            </div>

            <div class="form-group">
                <label for="description">
                    <i class="fas fa-align-left"></i>
                    試卷說明 <span style="color: #95a5a6; font-weight: normal;">(可選)</span>
                </label>
                <textarea id="description" name="description" class="form-input" rows="4" placeholder="描述這份試卷的目的、適用對象或注意事項..."></textarea>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                <i class="fas fa-rocket"></i>
                建立試卷
            </button>
        </form>

        <a class="back-link" href="index.php">
            <i class="fas fa-arrow-left"></i>
            返回題庫列表
        </a>
    </div>

    <script>
        // 優化的 JavaScript 代碼
        (function() {
            'use strict';
            
            const form = document.getElementById('quizForm');
            const submitBtn = document.getElementById('submitBtn');
            const titleInput = document.getElementById('title');
            
            // 使用事件委託和防抖優化
            let submitInProgress = false;
            
            form.addEventListener('submit', function(e) {
                if (submitInProgress) {
                    e.preventDefault();
                    return;
                }
                
                const title = titleInput.value.trim();
                if (!title) {
                    e.preventDefault();
                    titleInput.focus();
                    titleInput.style.borderColor = '#e74c3c';
                    setTimeout(() => {
                        titleInput.style.borderColor = '#e0e6ed';
                    }, 2000);
                    return;
                }
                
                // 提交狀態管理
                submitInProgress = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 建立中...';
                submitBtn.disabled = true;
            });
            
            // 優化的輸入驗證（使用節流）
            let validationTimeout;
            titleInput.addEventListener('input', function() {
                clearTimeout(validationTimeout);
                validationTimeout = setTimeout(() => {
                    if (this.style.borderColor === 'rgb(231, 76, 60)') {
                        this.style.borderColor = '#e0e6ed';
                    }
                }, 300);
            });
            
        })();
    </script>
</body>
</html>