<?php
require_once __DIR__ . '/session_init.php';
include_once __DIR__ . '/database.php';

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
    csrf_verify();
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
            $stmt = $conn->prepare("INSERT INTO questions (user_id, question, option_a, option_b, option_c, option_d, answer) VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id");
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=3">
</head>
<body>

<div class="container page-shell">
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
                    <?= csrf_field() ?>
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
                    <?= csrf_field() ?>
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
                    <?= csrf_field() ?>
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