<?php
require_once __DIR__ . '/session_init.php';
include __DIR__ . '/database.php';

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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif, "微軟正黑體";
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 500px;
            opacity: 0;
            transform: translateY(20px);
            animation: slideUp 0.6s ease-out forwards;
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header .icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
            font-size: 0.95rem;
        }

        .form-group label i {
            margin-right: 8px;
            color: #667eea;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e6ed;
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-input {
            resize: vertical;
            min-height: 100px;
            line-height: 1.5;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .submit-btn i {
            margin-right: 8px;
        }

        .error {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            border-left: 4px solid #c0392b;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-top: 25px;
            padding: 12px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            border: 2px solid #667eea;
            border-radius: 10px;
            transition: all 0.2s ease;
            background: transparent;
        }

        .back-link:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .back-link i {
            margin-right: 8px;
        }

        /* 響應式設計 */
        @media (max-width: 768px) {
            .container {
                padding: 30px 25px;
                margin: 10px;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .header .icon {
                font-size: 2.5rem;
            }
        }

        /* 高性能模式：減少動畫 */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* 優化渲染層 */
        .container, .submit-btn, .back-link {
            will-change: transform;
            transform: translateZ(0);
        }
    </style>
</head>
<body>
    <div class="container">
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