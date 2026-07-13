<?php
require_once __DIR__ . '/session_init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once __DIR__ . '/database.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM quizzes WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>試卷列表</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="page-shell">
        <div class="header">
            <div class="welcome-info">
                歡迎，<?= htmlspecialchars($_SESSION['username']) ?>
                <a href="logout.php">登出</a>
            </div>
            <h1>📚 試卷管理系統</h1>
            <p class="subtitle">管理您的所有試卷，輕鬆建立測驗</p>
        </div>

        <div class="content">
            <?php 
            $total_quizzes = $result->num_rows;
            ?>
            
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $total_quizzes ?></span>
                    <div class="stat-label">總試卷數</div>
                </div>
            </div>

            <div class="actions">
                <a href="quiz_create.php" class="btn btn-primary">➕ 新增試卷</a>
                <a href="import.php" class="btn btn-success">📥 匯入題庫</a>
                <a href="quiz_select.php" class="btn btn-info">🎯 模擬考試</a>
            </div>

            <?php if ($result->num_rows > 0): ?>
            <ul class="quiz-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <li class="quiz-card">
                        <h3 class="quiz-title"><?= htmlspecialchars($row['title']) ?></h3>
                        <?php if ($row['description']): ?>
                        <div class="quiz-description"><?= nl2br(htmlspecialchars($row['description'])) ?></div>
                        <?php endif; ?>
                        <div class="quiz-actions">
                            <a href="quiz_edit.php?id=<?= $row['id'] ?>" class="quiz-btn quiz-btn-edit">✏️ 編輯</a>
                            <form method="post" action="quiz_delete.php" style="flex:1;margin:0;"
                                  onsubmit="return confirm('確定刪除此試卷嗎？這個操作無法復原！');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="quiz-btn quiz-btn-delete" style="width:100%;border:none;cursor:pointer;">🗑️ 刪除</button>
                            </form>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
            <?php else: ?>
            <div class="no-quizzes">
                <div class="no-quizzes-icon">📝</div>
                <h3>還沒有任何試卷</h3>
                <p>開始建立您的第一份試卷吧！</p>
                <a href="quiz_create.php" class="btn btn-primary">立即新增試卷</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>