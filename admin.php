<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約投稿管理</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .nav-links {
            text-align: center;
            margin-bottom: 30px;
        }
        .nav-links a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .nav-links a:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status.completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status.failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .message {
            max-width: 200px;
            word-wrap: break-word;
        }
        .no-posts {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>予約投稿管理</h1>
        
        <div class="nav-links">
            <a href="form.html">新規投稿</a>
            <a href="admin.php">予約投稿管理</a>
        </div>

        <?php
        require_once __DIR__ . '/post.php';
        
        try {
            $multiPoster = new MultiPoster();
            $scheduledPosts = $multiPoster->getScheduledPosts();
            
            if (empty($scheduledPosts)) {
                echo '<div class="no-posts">予約投稿はありません</div>';
            } else {
                // 予約時刻の降順（新しい順）でソート
                usort($scheduledPosts, function($a, $b) {
                    return strtotime($b['schedule_time']) - strtotime($a['schedule_time']);
                });
                
                echo '<table>';
                echo '<thead>';
                echo '<tr>';
                echo '<th>ID</th>';
                echo '<th>メッセージ</th>';
                echo '<th>投稿先</th>';
                echo '<th>予約時刻</th>';
                echo '<th>状態</th>';
                echo '<th>作成日時</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($scheduledPosts as $post) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($post['id']) . '</td>';
                    echo '<td class="message">' . htmlspecialchars($post['message']) . '</td>';
                    echo '<td>' . htmlspecialchars(implode(', ', $post['services'])) . '</td>';
                    echo '<td>' . htmlspecialchars($post['schedule_time']) . '</td>';
                    echo '<td><span class="status ' . htmlspecialchars($post['status']) . '">' . htmlspecialchars($post['status']) . '</span></td>';
                    echo '<td>' . htmlspecialchars($post['created_at']) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
            }
        } catch (Exception $e) {
            echo '<div style="color: red; padding: 20px; background-color: #f8d7da; border-radius: 4px;">';
            echo 'エラー: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>