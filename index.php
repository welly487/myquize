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
            max-width: 1000px;
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
            position: relative;
        }

        .welcome-info {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .welcome-info a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            padding: 5px 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .welcome-info a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 140px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .btn-success {
            background: linear-gradient(135deg, #51cf66, #40c057);
        }

        .btn-info {
            background: linear-gradient(135deg, #22d3ee, #06b6d4);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-success:hover {
            box-shadow: 0 8px 25px rgba(81, 207, 102, 0.4);
        }

        .btn-info:hover {
            box-shadow: 0 8px 25px rgba(34, 211, 238, 0.4);
        }

        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            list-style: none;
        }

        .quiz-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .quiz-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .quiz-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .quiz-description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 20px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .quiz-actions {
            display: flex;
            gap: 10px;
        }

        .quiz-btn {
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-align: center;
            flex: 1;
        }

        .quiz-btn-edit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .quiz-btn-delete {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }

        .quiz-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .no-quizzes {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-quizzes-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .no-quizzes h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #333;
        }

        .no-quizzes p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            min-width: 120px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header {
                padding: 20px;
            }
            
            .welcome-info {
                position: static;
                text-align: center;
                margin-bottom: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .content {
                padding: 20px;
            }
            
            .actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
            }
            
            .quiz-grid {
                grid-template-columns: 1fr;
            }
            
            .stats {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
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