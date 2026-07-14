<?php
require_once __DIR__ . '/session_init.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include_once __DIR__ . '/database.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = false;
$success_detail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    csrf_verify();

    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = '檔案上傳失敗，請再試一次';
    } elseif (strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $error = '只接受 .csv 檔案';
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        $quiz_title = '匯入試卷 ' . date('Y-m-d H:i:s');

        if (($handle = fopen($file, 'r')) !== false) {
            fgetcsv($handle); // Skip header

            $imported = 0;
            $skipped = 0;
            $skipped_rows = [];
            $row_num = 1; // 從 1 開始（不含表頭）

            try {
                $conn->beginTransaction();

                // 建立新試卷
                $stmt_quiz = $conn->prepare("INSERT INTO quizzes (title, user_id) VALUES (?, ?) RETURNING id");
                $stmt_quiz->bind_param("si", $quiz_title, $user_id);
                $stmt_quiz->execute();
                $quiz_id = $stmt_quiz->insert_id;

                while (($data = fgetcsv($handle)) !== false) {
                    $row_num++;

                    if (count($data) < 6) {
                        $skipped++;
                        $skipped_rows[] = "第 {$row_num} 列：欄位不齊全";
                        continue;
                    }

                    // 清掉頭尾空白與換行殘留字元（常見於 Excel 匯出的 CSV）
                    [$question, $a, $b, $c, $d, $answer] = array_map(
                        fn($v) => trim($v, " \t\n\r\0\x0B"),
                        array_slice($data, 0, 6)
                    );
                    $answer = strtoupper($answer);

                    if ($question === '' || $a === '' || $b === '' || $c === '' || $d === '') {
                        $skipped++;
                        $skipped_rows[] = "第 {$row_num} 列：題目或選項空白";
                        continue;
                    }

                    if (!in_array($answer, ['A', 'B', 'C', 'D'], true)) {
                        $skipped++;
                        $skipped_rows[] = "第 {$row_num} 列：答案「{$answer}」不是 A/B/C/D";
                        continue;
                    }

                    $stmt = $conn->prepare("INSERT INTO questions (question, option_a, option_b, option_c, option_d, answer, user_id) VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id");
                    $stmt->bind_param("ssssssi", $question, $a, $b, $c, $d, $answer, $user_id);
                    $stmt->execute();
                    $question_id = $stmt->insert_id;

                    // 關聯到試卷
                    $stmt_link = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_id) VALUES (?, ?)");
                    $stmt_link->bind_param("ii", $quiz_id, $question_id);
                    $stmt_link->execute();

                    $imported++;
                }

                if ($imported === 0) {
                    // 一題都沒成功，整份匯入沒有意義，連試卷本身都不要留下
                    $conn->rollBack();
                    $error = '沒有任何一列資料格式正確，請檢查 CSV 內容後再試一次。';
                    if ($skipped_rows) {
                        $error .= ' (' . implode('；', array_slice($skipped_rows, 0, 5)) . ')';
                    }
                } else {
                    $conn->commit();
                    $success = true;
                    if ($skipped > 0) {
                        $success_detail = "已匯入 {$imported} 題，略過 {$skipped} 筆格式錯誤的資料：" .
                                 implode('；', array_slice($skipped_rows, 0, 5)) .
                                 ($skipped > 5 ? '…' : '');
                    }
                }
            } catch (PDOException $e) {
                $conn->rollBack();
                $error = '匯入過程發生資料庫錯誤，請確認 CSV 格式是否正確，或稍後再試一次。';
            }

            fclose($handle);
        } else {
            $error = "無法開啟檔案";
        }
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
    <link rel="stylesheet" href="css/style.css?v=3">
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
                        <?php if (!empty($success_detail)): ?>
                            <?= htmlspecialchars($success_detail) ?>
                        <?php else: ?>
                            匯入成功！試卷已成功建立，您可以開始使用了！
                        <?php endif; ?>
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
                    <?= csrf_field() ?>
                    <div class="file-upload-area" id="fileUploadArea">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">
                            <strong>點擊選擇檔案</strong> 或拖拽檔案到此處
                        </div>
                        <div class="text-muted">支援 CSV 格式檔案</div>
                        <div class="file-info" id="fileInfo" style="display: none;"></div>
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