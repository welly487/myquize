<?php
require_once __DIR__ . '/session_init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once __DIR__ . '/database.php';

$user_id = $_SESSION['user_id'];
$keyword = $_GET['search'] ?? '';

$sql = "SELECT id, title FROM quizzes WHERE user_id = ? AND title LIKE ? ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$search_param = '%' . $keyword . '%';
$stmt->bind_param("is", $user_id, $search_param);
$stmt->execute();
$quizzes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>選擇試卷</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container page-wrap">
  <div class="header">
    <h1><i class="fas fa-clipboard-list"></i> 測驗中心</h1>
    <p>選擇一張試卷開始您的學習之旅</p>
  </div>

  <div class="search-section">
    <form method="get" class="search-form">
      <div class="search-input-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="搜尋試卷名稱..." value="<?=htmlspecialchars($keyword)?>">
      </div>
      <button type="submit" class="search-btn">
        <i class="fas fa-search"></i>
        搜尋
      </button>
    </form>
  </div>

  <div class="quiz-list">
    <h2>
      <i class="fas fa-list-ul"></i>
      <?php if ($keyword): ?>
        搜尋結果: "<?=htmlspecialchars($keyword)?>"
      <?php else: ?>
        所有試卷
      <?php endif; ?>
    </h2>

    <ul>
    <?php if ($quizzes->num_rows === 0): ?>
        <li class="no-results">
          <i class="fas fa-search-minus"></i>
          <h3>找不到符合的試卷</h3>
          <p>請嘗試其他關鍵字或檢查拼寫</p>
        </li>
    <?php else: ?>
        <?php while ($quiz = $quizzes->fetch_assoc()): ?>
            <li>
              <div class="quiz-item">
                <a href="quiz_take.php?quiz_id=<?=$quiz['id']?>" class="quiz-link">
                  <div class="quiz-icon">
                    <i class="fas fa-file-alt"></i>
                  </div>
                  <div class="quiz-content">
                    <div class="quiz-title"><?=htmlspecialchars($quiz['title'])?></div>
                    <div class="quiz-subtitle">點擊開始作答</div>
                  </div>
                  <i class="fas fa-chevron-right arrow"></i>
                </a>
              </div>
            </li>
        <?php endwhile; ?>
    <?php endif; ?>
    </ul>
  </div>

  <div class="back-section">
    <a href="index.php" class="back-btn">
      <i class="fas fa-arrow-left"></i>
      回到首頁
    </a>
  </div>
</div>
</body>
</html>