# Slack & Discord 同時投稿フォーム

テキストメッセージと画像をSlack・Discordに同時投稿するWebアプリケーションです。

## 機能

- HTMLフォームからテキストメッセージと画像を投稿
- Slackチャンネルへの自動投稿
- Discordチャンネルへの自動投稿
- 画像のクリップボード貼り付け対応
- ドラッグ&ドロップ対応
- 画像プレビュー機能
- 包括的なユニットテスト

## セットアップ

### 1. プロジェクトファイルをWebサーバーのディレクトリに配置

### 2. Composerで依存関係をインストール

```bash
composer install
```

### 3. 環境変数の設定

`.env`ファイルを作成し、Webhook URLを設定：

```env
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR_SLACK_WEBHOOK_URL
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_DISCORD_WEBHOOK_URL
```

## 使い方

1. `form.html`をブラウザで開く
2. メッセージを入力
3. 画像を選択またはクリップボードから貼り付け
4. 「投稿する」ボタンをクリック

## テスト

### テストの実行

```bash
./vendor/bin/phpunit
```

### テストの内容

- コンストラクタのテスト
- 無効なHTTPメソッドの処理
- 空のメッセージの処理
- 画像アップロードの検証（無効な形式、サイズ制限）
- 環境変数の読み込み
- ベースURLの生成
- Webhook URL未設定時の処理
- mainブランチにプッシュされたタイミングで、テストを実施

## ファイル構成

```
/
├── form.html             # 投稿フォーム
├── post.php              # PHP処理ファイル
├── uploads/              # 画像アップロード先
│   └── .gitkeep         # ディレクトリ構造を保持
├── tests/                # テストファイル
│   └── MultiPosterTest.php
├── vendor/               # Composer依存関係
├── .env                  # 環境変数設定（要作成）
├── .gitignore           # Git除外設定
├── composer.json        # Composer設定
├── phpunit.xml          # PHPUnit設定
└── README.md
```

## 要件

- PHP 8.0以上
- Composer
- cURL拡張機能が有効
- Webサーバー（Apache、Nginx等）

## 開発環境

### 依存関係

- **PHPUnit**: テストフレームワーク
- **Composer**: 依存関係管理

### コード品質

- ユニットテストで主要機能をカバー
- 適切な例外処理
- 環境変数による設定管理

## 注意事項

- `uploads/`ディレクトリがWebから読み取り可能である必要があります
- 画像ファイルサイズ制限: 5MB
- 対応画像形式: JPEG, PNG, GIF, WebP
- `.env`ファイルは公開しないでください（`.gitignore`で除外済み）
- `vendor/`ディレクトリは本番環境でも必要です

## セキュリティ

- WebhookURLは環境変数で管理
- アップロードファイルの検証
- 適切な`.gitignore`設定でセンシティブファイルを除外

## トラブルシューティング

### テストが失敗する場合

1. PHP 8.0以上がインストールされているか確認
2. `composer install`が実行されているか確認
3. `uploads/`ディレクトリの書き込み権限を確認
