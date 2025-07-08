# ブランチプロテクションルール設定手順

このドキュメントでは、`main`ブランチへのマージ時にテストの成功を必須にするためのブランチプロテクションルール設定手順を説明します。

## 設定手順

### 1. リポジトリ設定へのアクセス

1. GitHubのリポジトリページにアクセス
2. 「Settings」タブをクリック
3. 左サイドバーの「Branches」をクリック

### 2. ブランチプロテクションルールの追加

1. 「Add branch protection rule」ボタンをクリック
2. 以下の設定を行う：

#### 基本設定
- **Branch name pattern**: `main`

#### プロテクション設定
- ✅ **Require a pull request before merging**
  - ✅ Require approvals (推奨: 1名以上)
  - ✅ Dismiss stale PR approvals when new commits are pushed
  - ✅ Require review from code owners (CODEOWNERS ファイルがある場合)

- ✅ **Require status checks to pass before merging**
  - ✅ Require branches to be up to date before merging
  - **Required status checks**: `Run Tests / run-tests` を選択
    - これは `.github/workflows/test.yml` の `Run Tests` ジョブに対応します

- ✅ **Require conversation resolution before merging**

- ✅ **Restrict pushes that create files**
  - 大きなファイルやバイナリファイルのコミットを防ぐ

#### 管理者設定
- **Include administrators**: 有効にするかはチームのポリシーに従う
- **Allow force pushes**: 無効（推奨）
- **Allow deletions**: 無効（推奨）

### 3. 設定の保存

「Create」ボタンをクリックして設定を保存します。

## 設定確認

設定が正しく動作していることを確認するため：

1. テストが失敗するPRを作成
2. マージボタンが無効になっていることを確認
3. テストが成功するとマージが可能になることを確認

## 重要な注意事項

- **管理者権限が必要**: ブランチプロテクションルールの設定にはリポジトリの管理者権限が必要です
- **Status Check名の一致**: GitHub ActionsのジョブID (`run-tests`) とワークフロー名 (`Run Tests`) を正確に指定してください
- **既存のPR**: 設定後、既存のPRも新しいルールの対象になります

## トラブルシューティング

### Status Checkが表示されない場合
1. ワークフローが少なくとも1回は実行されていることを確認
2. ジョブ名が正確であることを確認
3. ワークフローがPRイベントで実行されることを確認

### マージできない場合
1. すべてのRequired status checksが緑色になっていることを確認
2. コンフリクトが解決されていることを確認
3. レビューが承認されていることを確認（設定している場合）