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
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Noto Sans TC', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .result-card {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .result-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            background: conic-gradient(from 0deg, $grade_color 0%, $grade_color " . ($percentage * 3.6) . "deg, #e0e0e0 " . ($percentage * 3.6) . "deg, #e0e0e0 360deg);
            position: relative;
        }
        
        .score-circle::after {
            content: '';
            position: absolute;
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            z-index: 1;
        }
        
        .score-text {
            position: relative;
            z-index: 2;
            color: $grade_color;
        }
        
        .grade-badge {
            background: $grade_color;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 500;
            font-size: 1.1rem;
            margin-top: 15px;
            display: inline-block;
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .wrong-questions {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .wrong-questions h2 {
            color: #e74c3c;
            margin-bottom: 25px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .question-item {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #e74c3c;
            transition: transform 0.2s ease;
        }
        
        .question-item:hover {
            transform: translateY(-2px);
        }
        
        .question-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .option {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            margin: 8px 0;
            border-radius: 10px;
            background: white;
            transition: all 0.2s ease;
        }
        
        .option.correct {
            background: linear-gradient(135deg, #d5f4e6, #a8e6cf);
            border: 2px solid #27ae60;
            color: #155724;
        }
        
        .option.wrong {
            background: linear-gradient(135deg, #ffeaa7, #fab1a0);
            border: 2px solid #e74c3c;
            color: #721c24;
        }
        
        .option-label {
            font-weight: 600;
            margin-right: 10px;
            min-width: 25px;
        }
        
        .option-icon {
            margin-left: auto;
            font-size: 1.2rem;
        }
        
        .perfect-score {
            background: linear-gradient(135deg, #ffecd2, #fcb69f);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .perfect-score h2 {
            color: #d68e00;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .perfect-score p {
            font-size: 1.2rem;
            color: #8b6f00;
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .result-card {
                padding: 25px;
            }
            
            .score-circle {
                width: 120px;
                height: 120px;
                font-size: 2rem;
            }
            
            .score-circle::after {
                width: 95px;
                height: 95px;
            }
            
            .stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
    </head><body>";

    echo "<div class='container'>";
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
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: 'Noto Sans TC', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
  }

  .container {
    max-width: 900px;
    margin: 0 auto;
  }

  .header {
    text-align: center;
    color: white;
    margin-bottom: 40px;
  }

  .header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
  }

  .header p {
    font-size: 1.1rem;
    opacity: 0.9;
  }

  .quiz-form {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
  }

  .quiz-form::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #667eea, #764ba2);
  }

  .question-card {
    background: linear-gradient(145deg, #f8f9fa, #ffffff);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    position: relative;
  }

  .question-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
  }

  .question-number {
    position: absolute;
    top: -10px;
    left: 20px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
  }

  .question-text {
    font-size: 1.2rem;
    font-weight: 500;
    color: #2c3e50;
    margin-bottom: 20px;
    margin-top: 10px;
    line-height: 1.6;
  }

  .options-container {
    display: grid;
    gap: 12px;
  }

  .option-wrapper {
    position: relative;
  }

  .option-input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
  }

  .option-label {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 400;
    position: relative;
    overflow: hidden;
  }

  .option-label::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 0;
    height: 100%;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    transition: width 0.3s ease;
    z-index: 0;
  }

  .option-label:hover {
    border-color: #667eea;
    transform: translateY(-1px);
  }

  .option-label:hover::before {
    width: 100%;
  }

  .option-input:checked + .option-label {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: #667eea;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
  }

  .option-letter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    background: #e9ecef;
    border-radius: 50%;
    margin-right: 15px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
  }

  .option-input:checked + .option-label .option-letter {
    background: rgba(255, 255, 255, 0.2);
    color: white;
  }

  .option-text {
    flex: 1;
    position: relative;
    z-index: 1;
  }

  .submit-section {
    text-align: center;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid #e9ecef;
  }

  .submit-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 18px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-width: 200px;
    justify-content: center;
  }

  .submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
  }

  .submit-btn:active {
    transform: translateY(-1px);
  }

  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #6c757d;
    text-decoration: none;
    margin-top: 20px;
    font-weight: 500;
    transition: color 0.3s ease;
  }

  .back-link:hover {
    color: #495057;
    text-decoration: none;
  }

  .progress-bar {
    position: fixed;
    top: 0;
    left: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transition: width 0.3s ease;
    z-index: 1000;
  }

  @media (max-width: 768px) {
    .container {
      padding: 10px;
    }

    .header h1 {
      font-size: 2rem;
    }

    .quiz-form {
      padding: 25px;
    }

    .question-card {
      padding: 20px;
    }

    .question-text {
      font-size: 1.1rem;
    }

    .option-label {
      padding: 12px 15px;
    }

    .submit-btn {
      width: 100%;
      padding: 15px;
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

  .question-card {
    animation: fadeInUp 0.6s ease forwards;
  }

  .question-card:nth-child(even) {
    animation-delay: 0.1s;
  }

  .question-card:nth-child(odd) {
    animation-delay: 0.2s;
  }
</style>
</head>
<body>
<div class="progress-bar" id="progressBar"></div>

<div class="container">
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