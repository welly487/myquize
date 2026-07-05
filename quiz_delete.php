<?php
session_start();
include __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? null;

if ($id && is_numeric($id)) {
    // 確認試卷歸屬該使用者
    $stmt = $conn->prepare("SELECT id FROM quizzes WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();

        // 1. 找出該試卷所有題目 ID
        $stmt = $conn->prepare("SELECT question_id FROM quiz_questions WHERE quiz_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $question_ids = [];
        while ($row = $res->fetch_assoc()) {
            $question_ids[] = $row['question_id'];
        }
        $stmt->close();

        // 2. 刪除 quiz_questions 試卷與題目關聯
        $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        // 3. 刪除題目本身
        if (!empty($question_ids)) {
            $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
            $types = str_repeat('i', count($question_ids));

            // 動態綁定多個參數需要用 call_user_func_array，這裡稍微包一下
            $stmt = $conn->prepare("DELETE FROM questions WHERE id IN ($placeholders)");
            $stmt_params = [];
            $stmt_params[] = & $types;
            foreach ($question_ids as $key => $id_val) {
                $stmt_params[] = & $question_ids[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $stmt_params);
            $stmt->execute();
            $stmt->close();
        }

        // 4. 刪除試卷
        $stmt = $conn->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
        // 可加錯誤提示：沒有權限或試卷不存在
    }
}

header('Location: index.php');
exit;
