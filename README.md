# Slack & Discord 同時投稿フォーム

テキストメッセージと画像をSlack・Discordに同時投稿するWebアプリケーションです。

## 機能

- HTMLフォームからテキストメッセージと画像を投稿
- Slackチャンネルへの自動投稿
- Discordチャンネルへの自動投稿
- 画像のクリップボード貼り付け対応
- ドラッグ&ドロップ対応
- 画像プレビュー機能

## セットアップ

1. プロジェクトファイルをWebサーバーのディレクトリに配置
2. `.env.example`をコピーして`.env`を作成
3. `.env`にWebhook URLを設定

```bash
cp .env.example .env
```

`.env`の設定例：
```
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR_SLACK_WEBHOOK_URL
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_DISCORD_WEBHOOK_URL
```

## 使い方

1. `form.html`をブラウザで開く
2. メッセージを入力
3. 画像を選択またはクリップボードから貼り付け
4. 「投稿する」ボタンをクリック

## ファイル構成

```
/
├── form.html          # 投稿フォーム
├── post.php           # PHP処理ファイル
├── uploads/           # 画像アップロード先
├── .env              # Webhook URL設定（要作成）
└── .env.example      # 設定テンプレート
```

## 要件

- PHP 7.0以上
- cURL拡張機能が有効
- Webサーバー（Apache、Nginx等）

## 注意事項

- `uploads/`ディレクトリがWebから読み取り可能である必要があります
- 画像ファイルサイズ制限: 5MB
- 対応画像形式: JPEG, PNG, GIF, WebP
- `.env`ファイルは公開しないでください