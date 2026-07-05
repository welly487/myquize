<?php
session_start();
include __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$quiz_id = (int)($_GET['id'] ?? 0);

$error = '';
$success = '';

// 取得該試卷基本資料，確認歸屬該使用者
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $quiz_id, $user_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
if (!$quiz) {
    die("找不到該試卷或沒有權限");
}

// 取得要編輯的題目ID
$edit_question = null;
$edit_id = (int)($_GET['edit'] ?? 0);
if ($edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM questions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $edit_question = $stmt->get_result()->fetch_assoc();
    if (!$edit_question) {
        $error = '找不到該題目或沒有權限編輯';
        $edit_question = null;
    }
}

// 新增題目功能
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 修改試卷標題與描述
    if (isset($_POST['update_quiz'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title) {
            $stmt = $conn->prepare("UPDATE quizzes SET title = ?, description = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssii", $title, $description, $quiz_id, $user_id);
            if ($stmt->execute()) {
                $success = '✅ 試卷資訊更新成功';
                header("Location: ?id=$quiz_id");
                exit;
            } else {
                $error = '❌ 試卷更新失敗';
            }
        } else {
            $error = '❗請填寫試卷標題';
        }
    }

    // 修改題目
    if (isset($_POST['edit_question_id'])) {
        $edit_question_id = (int)$_POST['edit_question_id'];
        $question = trim($_POST['question'] ?? '');
        $option_a = trim($_POST['option_a'] ?? '');
        $option_b = trim($_POST['option_b'] ?? '');
        $option_c = trim($_POST['option_c'] ?? '');
        $option_d = trim($_POST['option_d'] ?? '');
        $answer = $_POST['answer'] ?? '';

        if ($question && $option_a && $option_b && $option_c && $option_d && in_array($answer, ['A','B','C','D'])) {
            $stmt = $conn->prepare("UPDATE questions SET question=?, option_a=?, option_b=?, option_c=?, option_d=?, answer=? WHERE id=? AND user_id=?");
            $stmt->bind_param("ssssssii", $question, $option_a, $option_b, $option_c, $option_d, $answer, $edit_question_id, $user_id);
            if ($stmt->execute()) {
                $success = '✅ 題目修改成功';
                header("Location: ?id=$quiz_id");
                exit;
            } else {
                $error = '❌ 題目修改失敗';
            }
        } else {
            $error = '❗請完整填寫題目與選項，並選擇正確答案';
        }
    }

    // 新增題目
    if (isset($_POST['add_question'])) {
        $question = trim($_POST['question'] ?? '');
        $option_a = trim($_POST['option_a'] ?? '');
        $option_b = trim($_POST['option_b'] ?? '');
        $option_c = trim($_POST['option_c'] ?? '');
        $option_d = trim($_POST['option_d'] ?? '');
        $answer = $_POST['answer'] ?? '';

        if ($question && $option_a && $option_b && $option_c && $option_d && in_array($answer, ['A','B','C','D'])) {
            $stmt = $conn->prepare("INSERT INTO questions (user_id, question, option_a, option_b, option_c, option_d, answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $user_id, $question, $option_a, $option_b, $option_c, $option_d, $answer);
            if ($stmt->execute()) {
                $question_id = $stmt->insert_id;
                $stmt2 = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_id) VALUES (?, ?)");
                $stmt2->bind_param("ii", $quiz_id, $question_id);
                $stmt2->execute();
                $success = '✅ 新增題目成功';
            } else {
                $error = '❌ 新增題目失敗';
            }
        } else {
            $error = '❗請完整填寫題目與選項，並選擇正確答案';
        }
    }
}

// 取得該試卷所有題目
$stmt = $conn->prepare("
    SELECT q.id, q.question, q.option_a, q.option_b, q.option_c, q.option_d, q.answer 
    FROM questions q
    JOIN quiz_questions qq ON q.id = qq.question_id
    WHERE qq.quiz_id = ?
    ORDER BY qq.id ASC
");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$questions = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯試卷 - <?=htmlspecialchars($quiz['title'])?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Microsoft JhengHei', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 10px;
        }

        .content {
            padding: 30px;
        }

        .section {
            margin-bottom: 40px;
        }

        .section h2 {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
            display: inline-block;
        }

        .message {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 10px;
            font-weight: 500;
            text-align: center;
        }

        .error { 
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .success { 
            background: linear-gradient(135deg, #51cf66, #40c057);
            color: white;
            box-shadow: 0 4px 15px rgba(81, 207, 102, 0.3);
        }

        .form-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 0.95rem;
        }

        textarea, input[type="text"], select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        textarea:focus, input[type="text"]:focus, select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-cancel {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }

        .btn-cancel:hover {
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }

        .question-list {
            list-style: none;
        }

        .question-item {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }

        .question-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .question-content {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .option {
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .answer {
            display: inline-block;
            background: linear-gradient(135deg, #51cf66, #40c057);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-right: 15px;
        }

        .edit-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            padding: 5px 15px;
            border-radius: 20px;
            border: 2px solid #667eea;
            transition: all 0.3s ease;
        }

        .edit-link:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 10px 20px;
            border-radius: 25px;
            border: 2px solid #667eea;
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .content {
                padding: 20px;
            }
            
            .options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📝 編輯試卷</h1>
        <h2><?=htmlspecialchars($quiz['title'])?></h2>
        <?php if($quiz['description']): ?>
        <p><?=nl2br(htmlspecialchars($quiz['description']))?></p>
        <?php endif; ?>
    </div>

    <div class="content">
        <?php if ($error): ?>
            <div class="message error"><?= $error ?></div>
        <?php elseif ($success): ?>
            <div class="message success"><?= $success ?></div>
        <?php endif; ?>

        <div class="section">
            <h2>🛠️ 修改試卷資訊</h2>
            <div class="form-card">
                <form method="post" action="">
                    <input type="hidden" name="update_quiz" value="1">
                    <div class="form-group">
                        <label>試卷標題：</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($quiz['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>描述：</label>
                        <textarea name="description" rows="3" placeholder="輸入試卷描述..."><?= htmlspecialchars($quiz['description']) ?></textarea>
                    </div>
                    <button type="submit" class="btn">更新試卷資訊</button>
                </form>
            </div>
        </div>

        <?php if ($edit_question): ?>
        <div class="section">
            <h2>✏️ 修改題目</h2>
            <div class="form-card">
                <form method="post" action="">
                    <input type="hidden" name="edit_question_id" value="<?= $edit_question['id'] ?>">
                    <div class="form-group">
                        <label>題目內容：</label>
                        <textarea name="question" rows="3" required><?= htmlspecialchars($edit_question['question']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>選項 A：</label>
                        <input type="text" name="option_a" value="<?= htmlspecialchars($edit_question['option_a']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>選項 B：</label>
                        <input type="text" name="option_b" value="<?= htmlspecialchars($edit_question['option_b']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>選項 C：</label>
                        <input type="text" name="option_c" value="<?= htmlspecialchars($edit_question['option_c']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>選項 D：</label>
                        <input type="text" name="option_d" value="<?= htmlspecialchars($edit_question['option_d']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>正確答案：</label>
                        <select name="answer" required>
                            <option value="">請選擇</option>
                            <option value="A" <?= $edit_question['answer']=='A' ? 'selected' : '' ?>>A</option>
                            <option value="B" <?= $edit_question['answer']=='B' ? 'selected' : '' ?>>B</option>
                            <option value="C" <?= $edit_question['answer']=='C' ? 'selected' : '' ?>>C</option>
                            <option value="D" <?= $edit_question['answer']=='D' ? 'selected' : '' ?>>D</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">更新題目</button>
                    <a href="?id=<?= $quiz_id ?>" class="btn btn-cancel">取消</a>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="section">
            <h2>➕ 新增題目</h2>
            <div class="form-card">
                <form method="post" action="">
                    <input type="hidden" name="add_question" value="1">
                    <div class="form-group">
                        <label>題目內容：</label>
                        <textarea name="question" rows="3" placeholder="輸入題目內容..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>選項 A：</label>
                        <input type="text" name="option_a" placeholder="輸入選項 A" required>
                    </div>
                    <div class="form-group">
                        <label>選項 B：</label>
                        <input type="text" name="option_b" placeholder="輸入選項 B" required>
                    </div>
                    <div class="form-group">
                        <label>選項 C：</label>
                        <input type="text" name="option_c" placeholder="輸入選項 C" required>
                    </div>
                    <div class="form-group">
                        <label>選項 D：</label>
                        <input type="text" name="option_d" placeholder="輸入選項 D" required>
                    </div>
                    <div class="form-group">
                        <label>正確答案：</label>
                        <select name="answer" required>
                            <option value="">請選擇正確答案</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">新增題目</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="section">
            <h2>📋 題目列表</h2>
            <ul class="question-list">
                <?php while ($row = $questions->fetch_assoc()): ?>
                <li class="question-item">
                    <div class="question-content">
                        <?= htmlspecialchars($row['question']) ?>
                    </div>
                    <div class="options">
                        <div class="option"><strong>A.</strong> <?= htmlspecialchars($row['option_a']) ?></div>
                        <div class="option"><strong>B.</strong> <?= htmlspecialchars($row['option_b']) ?></div>
                        <div class="option"><strong>C.</strong> <?= htmlspecialchars($row['option_c']) ?></div>
                        <div class="option"><strong>D.</strong> <?= htmlspecialchars($row['option_d']) ?></div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span class="answer">正確答案：<?= $row['answer'] ?></span>
                        <a href="?id=<?= $quiz_id ?>&edit=<?= $row['id'] ?>" class="edit-link">修改題目</a>
                    </div>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <div class="back-link">
            <a href="index.php">← 返回試卷列表</a>
        </div>
    </div>
</div>

</body>
</html>