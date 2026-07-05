<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include __DIR__ . '/database.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $quiz_title = '匯入試卷 ' . date('Y-m-d H:i:s');

    if (($handle = fopen($file, 'r')) !== false) {
        fgetcsv($handle); // Skip header

        // 建立新試卷
        $stmt_quiz = $conn->prepare("INSERT INTO quizzes (title, user_id) VALUES (?, ?)");
        $stmt_quiz->bind_param("si", $quiz_title, $user_id);
        $stmt_quiz->execute();
        $quiz_id = $stmt_quiz->insert_id;

        while (($data = fgetcsv($handle)) !== false) {
            list($question, $a, $b, $c, $d, $answer) = $data;

            $stmt = $conn->prepare("INSERT INTO questions (question, option_a, option_b, option_c, option_d, answer, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $question, $a, $b, $c, $d, $answer, $user_id);
            $stmt->execute();
            $question_id = $stmt->insert_id;

            // 關聯到試卷
            $stmt_link = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_id) VALUES (?, ?)");
            $stmt_link->bind_param("ii", $quiz_id, $question_id);
            $stmt_link->execute();
        }

        fclose($handle);
        $success = true;
    } else {
        $error = "無法開啟檔案";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>匯入題庫 - 智能測驗系統</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        * {
            font-family: 'Noto Sans TC', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }

        .main-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .hero-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .card-header-custom {
            background: var(--primary-gradient);
            color: white;
            padding: 30px;
            text-align: center;
            border: none;
        }

        .card-header-custom h1 {
            margin: 0;
            font-weight: 600;
            font-size: 2rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-header-custom .subtitle {
            margin-top: 10px;
            opacity: 0.9;
            font-size: 1.1rem;
            font-weight: 300;
        }

        .card-body-custom {
            padding: 40px;
        }

        .alert-custom {
            border: none;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-success-custom {
            background: var(--success-gradient);
            color: white;
        }

        .alert-danger-custom {
            background: var(--danger-gradient);
            color: white;
        }

        .info-section {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }

        .info-section h5 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .csv-example {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 15px 0;
            overflow-x: auto;
        }

        .download-btn {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            border-radius: 50px;
            padding: 12px 25px;
            color: white;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.3);
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
            color: white;
        }

        .file-upload-area {
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 30px;
        }

        .file-upload-area:hover {
            border-color: #764ba2;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        .file-upload-area.dragover {
            border-color: #4facfe;
            background: linear-gradient(135deg, rgba(79, 172, 254, 0.1) 0%, rgba(0, 242, 254, 0.1) 100%);
        }

        .upload-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }

        .file-input-custom {
            display: none;
        }

        .upload-text {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .file-info {
            color: #667eea;
            font-weight: 500;
            margin-top: 10px;
        }

        .btn-custom {
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 500;
            border: none;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-secondary-custom {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }

        .btn-outline-custom {
            background: transparent;
            border: 2px solid #6c757d;
            color: #6c757d;
        }

        .btn-outline-custom:hover {
            background: #6c757d;
            color: white;
        }

        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .card-header-custom {
                padding: 20px;
            }
            
            .card-header-custom h1 {
                font-size: 1.5rem;
            }
            
            .card-body-custom {
                padding: 20px;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <div class="card hero-card">
            <div class="card-header-custom">
                <h1><i class="fas fa-cloud-upload-alt me-3"></i>匯入題庫</h1>
                <div class="subtitle">輕鬆匯入 CSV 格式的測驗題目</div>
            </div>
            
            <div class="card-body-custom">
                <?php if (!empty($success)): ?>
                    <div class="alert-custom alert-success-custom">
                        <i class="fas fa-check-circle me-2"></i>
                        匯入成功！試卷已成功建立，您可以開始使用了！
                    </div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert-custom alert-danger-custom">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="info-section">
                    <h5><i class="fas fa-info-circle me-2"></i>檔案格式說明</h5>
                    <p class="mb-3">請上傳包含以下欄位的 CSV 檔案（需包含標題列）：</p>
                    
                    <div class="csv-example">
題目, A選項, B選項, C選項, D選項, 正確答案
世界上最大的大陸是什麼？, 亞洲, 非洲, 歐洲, 南極洲, A
太陽是什麼類型的天體？, 星星, 行星, 彗星, 衛星, A
                    </div>

                    <a href="sample.csv" class="download-btn">
                        <i class="fas fa-download"></i>
                        下載範例格式
                    </a>
                </div>

                <form method="post" enctype="multipart/form-data" id="uploadForm">
                    <div class="file-upload-area" id="fileUploadArea">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">
                            <strong>點擊選擇檔案</strong> 或拖拽檔案到此處
                        </div>
                        <div class="text-muted">支援 CSV 格式檔案</div>
                        <div class="file-info" id="fileInfo" style="display: none;"></div>
                        <input type="file" name="csv_file" id="csv_file" class="file-input-custom" accept=".csv" required>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn-custom btn-primary-custom">
                            <i class="fas fa-upload"></i>
                            上傳並匯入
                        </button>
                        <a href="index.php" class="btn-custom btn-secondary-custom">
                            <i class="fas fa-home"></i>
                            回首頁
                        </a>
                        <a href="quiz_select.php" class="btn-custom btn-outline-custom">
                            <i class="fas fa-list"></i>
                            回試卷選單
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 檔案上傳區域互動
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('csv_file');
        const fileInfo = document.getElementById('fileInfo');

        fileUploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                fileInfo.style.display = 'block';
                fileInfo.innerHTML = `<i class="fas fa-file-csv me-2"></i>已選擇: ${file.name}`;
                fileUploadArea.style.borderColor = '#4facfe';
            }
        });

        // 拖拽功能
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].name.endsWith('.csv')) {
                fileInput.files = files;
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        });

        // 表單提交動畫
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>處理中...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>