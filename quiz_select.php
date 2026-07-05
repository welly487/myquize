<?php
require_once __DIR__ . '/session_init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include __DIR__ . '/database.php';

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
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: "Noto Sans TC", sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    color: #333;
    padding: 20px;
  }

  .container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
  }

  .header {
    text-align: center;
    margin-bottom: 40px;
    padding-top: 40px;
  }

  .header h1 {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
  }

  .header p {
    color: rgba(255,255,255,0.9);
    font-size: 1.1rem;
    font-weight: 300;
  }

  .search-section {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    border: 1px solid rgba(255,255,255,0.2);
  }

  .search-form {
    display: flex;
    gap: 15px;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
  }

  .search-input-wrapper {
    position: relative;
    flex: 1;
    max-width: 400px;
  }

  .search-input-wrapper i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
    font-size: 1.1rem;
  }

  input[type="text"] {
    width: 100%;
    padding: 15px 15px 15px 45px;
    border-radius: 25px;
    border: 2px solid #e1e8ed;
    font-size: 16px;
    font-family: inherit;
    transition: all 0.3s ease;
    background: white;
  }

  input[type="text"]:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
  }

  .search-btn {
    padding: 15px 25px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 25px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 120px;
    justify-content: center;
  }

  .search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
  }

  .search-btn:active {
    transform: translateY(0);
  }

  .quiz-list {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    margin-bottom: 30px;
  }

  .quiz-list h2 {
    color: #333;
    font-size: 1.5rem;
    margin-bottom: 25px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  ul {
    list-style: none;
    padding: 0;
  }

  li {
    margin-bottom: 15px;
    transition: all 0.3s ease;
  }

  .quiz-item {
    background: white;
    padding: 20px 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
  }

  .quiz-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    transform: scaleX(0);
    transition: transform 0.3s ease;
  }

  .quiz-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    border-color: rgba(102, 126, 234, 0.2);
  }

  .quiz-item:hover::before {
    transform: scaleX(1);
  }

  .quiz-link {
    text-decoration: none;
    color: #333;
    font-size: 1.1rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: color 0.3s ease;
  }

  .quiz-link:hover {
    color: #667eea;
  }

  .quiz-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
  }

  .quiz-content {
    flex: 1;
  }

  .quiz-title {
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 5px;
  }

  .quiz-subtitle {
    color: #666;
    font-size: 0.9rem;
    font-weight: 300;
  }

  .arrow {
    color: #ccc;
    font-size: 1.2rem;
    transition: all 0.3s ease;
  }

  .quiz-item:hover .arrow {
    color: #667eea;
    transform: translateX(5px);
  }

  .no-results {
    text-align: center;
    padding: 60px 20px;
    color: #666;
  }

  .no-results i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
    display: block;
  }

  .no-results h3 {
    font-size: 1.3rem;
    margin-bottom: 10px;
    color: #555;
  }

  .no-results p {
    font-size: 1rem;
    color: #777;
  }

  .back-section {
    text-align: center;
    margin-bottom: 40px;
  }

  .back-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: white;
    text-decoration: none;
    font-size: 1.1rem;
    font-weight: 500;
    padding: 12px 25px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border-radius: 25px;
    border: 1px solid rgba(255,255,255,0.3);
    transition: all 0.3s ease;
  }

  .back-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
  }

  /* 響應式設計 */
  @media (max-width: 768px) {
    .container {
      padding: 0 15px;
    }

    .header h1 {
      font-size: 2rem;
    }

    .search-form {
      flex-direction: column;
      gap: 15px;
    }

    .search-input-wrapper {
      max-width: 100%;
    }

    .search-btn {
      width: 100%;
      max-width: 200px;
    }

    .quiz-item {
      padding: 15px 20px;
    }

    .quiz-link {
      font-size: 1rem;
    }

    .quiz-icon {
      width: 40px;
      height: 40px;
      font-size: 1rem;
    }
  }

  @media (max-width: 480px) {
    body {
      padding: 10px;
    }

    .search-section,
    .quiz-list {
      padding: 20px;
    }

    .header {
      padding-top: 20px;
      margin-bottom: 30px;
    }

    .quiz-link {
      flex-direction: column;
      text-align: center;
      gap: 10px;
    }

    .arrow {
      display: none;
    }
  }

  /* 動畫效果 */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .quiz-item {
    animation: fadeInUp 0.6s ease forwards;
  }

  .quiz-item:nth-child(2) { animation-delay: 0.1s; }
  .quiz-item:nth-child(3) { animation-delay: 0.2s; }
  .quiz-item:nth-child(4) { animation-delay: 0.3s; }
  .quiz-item:nth-child(5) { animation-delay: 0.4s; }
</style>
</head>
<body>
<div class="container">
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