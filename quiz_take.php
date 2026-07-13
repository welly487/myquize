<?php
require_once __DIR__ . '/session_init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once __DIR__ . '/database.php';

$user_id = $_SESSION['user_id'];

if (!isset($_GET['quiz_id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: quiz_select.php');
    exit;
}

// 作答送出後
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $score = 0;
    $total = count($_POST['answers'] ?? []);
    $quiz_id = (int)$_POST['quiz_id'];

    // 確認這份試卷確實屬於目前登入的使用者，避免用別人的 quiz_id 取得答案
    $ownStmt = $conn->prepare("SELECT id FROM quizzes WHERE id = ? AND user_id = ?");
    $ownStmt->bind_param("ii", $quiz_id, $user_id);
    $ownStmt->execute();
    if ($ownStmt->get_result()->num_rows === 0) {
        die('找不到該試卷或沒有權限作答');
    }

    $incorrect = [];

    foreach ($_POST['answers'] as $id => $user_answer) {
        $id = (int)$id;
        $stmt = $conn->prepare("SELECT q.question, q.option_a, q.option_b, q.option_c, q.option_d, q.answer 
            FROM questions q 
            JOIN quiz_questions qq ON q.id = qq.question_id 
            WHERE q.id = ? AND qq.quiz_id = ?");
        $stmt->bind_param("ii", $id, $quiz_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $correct = $row['answer'] ?? null;

        if ($correct !== null && $user_answer === $correct) {
            $score++;
        } else {
            $incorrect[] = [
                'question' => $row['question'],
                'options' => [
                    'A' => $row['option_a'],
                    'B' => $row['option_b'],
                    'C' => $row['option_c'],
                    'D' => $row['option_d']
                ],
                'correct' => $correct,
                'your_answer' => $user_answer
            ];
        }
        $stmt->close();
    }

    // 計算分數百分比和等級
    $percentage = round(($score / $total) * 100);
    $grade = '';
    $grade_color = '';
    if ($percentage >= 90) {
        $grade = '優秀';
        $grade_color = '#27ae60';
    } elseif ($percentage >= 80) {
        $grade = '良好';
        $grade_color = '#3498db';
    } elseif ($percentage >= 70) {
        $grade = '及格';
        $grade_color = '#f39c12';
    } else {
        $grade = '需加油';
        $grade_color = '#e74c3c';
    }

    // 顯示成績與錯題解析
    echo "<!DOCTYPE html><html lang='zh-TW'><head><meta charset='UTF-8'><title>測驗結果</title>
    <link href='https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='css/style.css'>
    <style>:root { --grade: $grade_color; --pct: " . ($percentage * 3.6) . "deg; }</style>
    </head><body>";

    echo "<div class='container page-wrap'>";
    echo "<div class='header'>";
    echo "<h1><i class='fas fa-chart-line'></i> 測驗結果</h1>";
    echo "</div>";

    echo "<div class='result-card'>";
    echo "<div class='score-circle'>";
    echo "<div class='score-text'>$percentage%</div>";
    echo "</div>";
    echo "<div class='grade-badge'>$grade</div>";
    echo "<div class='stats'>";
    echo "<div class='stat-item'><div class='stat-number'>$score</div><div class='stat-label'>答對題數</div></div>";
    echo "<div class='stat-item'><div class='stat-number'>$total</div><div class='stat-label'>總題數</div></div>";
    echo "<div class='stat-item'><div class='stat-number'>" . count($incorrect) . "</div><div class='stat-label'>錯誤題數</div></div>";
    echo "</div>";
    echo "</div>";

    if (count($incorrect) > 0) {
        echo "<div class='wrong-questions'>";
        echo "<h2><i class='fas fa-exclamation-triangle'></i> 錯誤題目解析</h2>";
        foreach ($incorrect as $idx => $item) {
            echo "<div class='question-item'>";
            echo "<div class='question-title'>第 " . ($idx + 1) . " 題：" . htmlspecialchars($item['question']) . "</div>";
            foreach ($item['options'] as $opt => $text) {
                $class = '';
                $icon = '';
                if ($opt === $item['correct']) {
                    $class = 'correct';
                    $icon = '<i class="fas fa-check option-icon"></i>';
                } elseif ($opt === $item['your_answer'] && $opt !== $item['correct']) {
                    $class = 'wrong';
                    $icon = '<i class="fas fa-times option-icon"></i>';
                }
                echo "<div class='option $class'>";
                echo "<span class='option-label'>[$opt]</span>";
                echo "<span>" . htmlspecialchars($text) . "</span>";
                echo $icon;
                echo "</div>";
            }
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<div class='perfect-score'>";
        echo "<h2><i class='fas fa-trophy'></i> 完美表現！</h2>";
        echo "<p>🎉 恭喜你全部答對！太厲害了！</p>";
        echo "</div>";
    }

    echo "<div class='action-buttons'>";
    echo '<a href="quiz_select.php" class="btn btn-primary"><i class="fas fa-redo"></i> 再測一次</a>';
    echo '<a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> 回首頁</a>';
    echo "</div>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

// 抓取指定試卷的題目
$quiz_id = intval($_GET['quiz_id']);

// 確認這份試卷確實屬於目前登入的使用者，避免用別人的 quiz_id 偷看題目
$ownStmt = $conn->prepare("SELECT id FROM quizzes WHERE id = ? AND user_id = ?");
$ownStmt->bind_param("ii", $quiz_id, $user_id);
$ownStmt->execute();
if ($ownStmt->get_result()->num_rows === 0) {
    die('找不到該試卷或沒有權限作答');
}

$stmt = $conn->prepare("SELECT q.* 
    FROM questions q 
    JOIN quiz_questions qq ON q.id = qq.question_id 
    WHERE qq.quiz_id = ? 
    ORDER BY RANDOM()");
$stmt->bind_param('i', $quiz_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<title>模擬考試</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="progress-bar" id="progressBar"></div>

<div class="container page-wrap">
  <div class="header">
    <h1><i class="fas fa-graduation-cap"></i> 模擬考試</h1>
    <p>請仔細閱讀每個題目，並選擇最合適的答案</p>
  </div>

  <div class="quiz-form">
    <form method="post" id="quizForm">
      <?= csrf_field() ?>
      <input type="hidden" name="quiz_id" value="<?=$quiz_id?>">
      
      <?php 
      $question_count = 0;
      $questions = [];
      while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
      }
      
      foreach ($questions as $index => $row): 
        $question_count++;
      ?>
        <div class="question-card">
          <div class="question-number">第 <?=$question_count?> 題</div>
          <div class="question-text"><?=htmlspecialchars($row['question'])?></div>
          
          <div class="options-container">
            <div class="option-wrapper">
              <input type="radio" class="option-input" name="answers[<?=$row['id']?>]" value="A" id="q<?=$row['id']?>_a" required>
              <label class="option-label" for="q<?=$row['id']?>_a">
                <span class="option-letter">A</span>
                <span class="option-text"><?=htmlspecialchars($row['option_a'])?></span>
              </label>
            </div>
            
            <div class="option-wrapper">
              <input type="radio" class="option-input" name="answers[<?=$row['id']?>]" value="B" id="q<?=$row['id']?>_b">
              <label class="option-label" for="q<?=$row['id']?>_b">
                <span class="option-letter">B</span>
                <span class="option-text"><?=htmlspecialchars($row['option_b'])?></span>
              </label>
            </div>
            
            <div class="option-wrapper">
              <input type="radio" class="option-input" name="answers[<?=$row['id']?>]" value="C" id="q<?=$row['id']?>_c">
              <label class="option-label" for="q<?=$row['id']?>_c">
                <span class="option-letter">C</span>
                <span class="option-text"><?=htmlspecialchars($row['option_c'])?></span>
              </label>
            </div>
            
            <div class="option-wrapper">
              <input type="radio" class="option-input" name="answers[<?=$row['id']?>]" value="D" id="q<?=$row['id']?>_d">
              <label class="option-label" for="q<?=$row['id']?>_d">
                <span class="option-letter">D</span>
                <span class="option-text"><?=htmlspecialchars($row['option_d'])?></span>
              </label>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      
      <div class="submit-section">
        <button type="submit" class="submit-btn">
          <i class="fas fa-paper-plane"></i>
          送出答案
        </button>
        <br>
        <a href="quiz_select.php" class="back-link">
          <i class="fas fa-arrow-left"></i>
          回試卷選單
        </a>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('quizForm');
    const progressBar = document.getElementById('progressBar');
    const questions = document.querySelectorAll('.question-card');
    const totalQuestions = questions.length;
    
    // 更新進度條
    function updateProgress() {
        const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;
        const progress = (answeredQuestions / totalQuestions) * 100;
        progressBar.style.width = progress + '%';
    }
    
    // 監聽選項變化
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', updateProgress);
    });
    
    // 表單提交確認
    form.addEventListener('submit', function(e) {
        const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;
        
        if (answeredQuestions < totalQuestions) {
            e.preventDefault();
            const unanswered = totalQuestions - answeredQuestions;
            if (!confirm(`還有 ${unanswered} 題尚未作答，確定要送出嗎？`)) {
                return false;
            }
        }
        
        // 顯示提交動畫
        const submitBtn = document.querySelector('.submit-btn');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 處理中...';
        submitBtn.disabled = true;
    });
    
    // 平滑滾動到未答題目
    function scrollToFirstUnanswered() {
        for (let question of questions) {
            const radios = question.querySelectorAll('input[type="radio"]');
            const isAnswered = Array.from(radios).some(radio => radio.checked);
            
            if (!isAnswered) {
                question.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                break;
            }
        }
    }
    
    // 快捷鍵支持
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            form.submit();
        }
    });
    
    // 初始化進度條
    updateProgress();
});
</script>
</body>
</html>