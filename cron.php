<?php
/**
 * 予約投稿を実行するためのcronスクリプト
 * 
 * crontabの設定例:
 * * * * * * /usr/bin/php /path/to/your/project/cron.php
 * 
 * 毎分実行される設定。実際の運用では5分間隔程度が推奨
 */

require_once __DIR__ . '/post.php';

try {
    $multiPoster = new MultiPoster();
    $multiPoster->processPendingPosts();
    
    // ログを残すかどうかはオプション
    echo "[" . date('Y-m-d H:i:s') . "] 予約投稿チェック完了\n";
} catch ( Exception $e ) {
    error_log( "[" . date('Y-m-d H:i:s') . "] 予約投稿処理エラー: " . $e->getMessage() );
    echo "[" . date('Y-m-d H:i:s') . "] エラー: " . $e->getMessage() . "\n";
}