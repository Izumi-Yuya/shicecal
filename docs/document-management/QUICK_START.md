# 契約書ドキュメント管理機能 クイックスタートガイド

## 🚀 すぐに始める

### 1. 環境の起動
```bash
# 開発サーバーを起動
php artisan serve

# 別のターミナルでアセットをビルド（開発モード）
npm run dev
```

### 2. ブラウザでアクセス
1. http://localhost:8000 にアクセス
2. ログイン（編集権限を持つユーザー）
3. 施設詳細画面を開く
4. 「契約書」タブをクリック

### 3. ドキュメント管理を開く
各サブタブ（給食、駐車場、その他）で：
- 「ドキュメントを表示」ボタンをクリック
- ドキュメント管理UIが展開されます

## 📋 基本操作

### フォルダを作成
1. 「新しいフォルダ」ボタンをクリック
2. フォルダ名を入力
3. 「作成」ボタンをクリック

### ファイルをアップロード
1. 「ファイルアップロード」ボタンをクリック
2. ファイルを選択（最大50MB）
3. 「アップロード」ボタンをクリック

### ファイルをダウンロード
- ファイル名をクリック

### 名前を変更
1. 項目の「...」ボタンをクリック
2. 「名前変更」を選択
3. 新しい名前を入力
4. 「変更」ボタンをクリック

### 削除
1. 項目の「...」ボタンをクリック
2. 「削除」を選択
3. 確認ダイアログで「OK」をクリック

## 🔍 検証方法

### 自動検証スクリプト
```bash
php scripts/verify-contract-document-management.php
```

### ブラウザコンソールで確認
1. F12キーで開発者ツールを開く
2. Consoleタブでエラーがないか確認
3. Networkタブで API通信を確認

## 📚 詳細ドキュメント

- **動作確認チェックリスト**: [contract-document-verification-checklist.md](./contract-document-verification-checklist.md)
- **デバッグガイド**: [contract-document-debugging-guide.md](./contract-document-debugging-guide.md)
- **検証サマリー**: [contract-document-verification-summary.md](./contract-document-verification-summary.md)
- **ユーザーガイド**: [contract-document-user-guide.md](./contract-document-user-guide.md)
- **開発者ガイド**: [contract-document-developer-guide.md](./contract-document-developer-guide.md)
- **APIリファレンス**: [contract-document-api-reference.md](./contract-document-api-reference.md)

## ⚠️ トラブルシューティング

### モーダルが表示されない
- ブラウザコンソールでエラーを確認
- Bootstrapが正しく読み込まれているか確認

### ファイルアップロードが失敗する
```bash
# ストレージの権限を確認
chmod -R 775 storage/app/public/
php artisan storage:link
```

### API通信エラー
- ネットワークタブでステータスコードを確認
- サーバーログを確認: `tail -f storage/logs/laravel.log`

## 🎯 確認すべき主要機能

- [ ] フォルダ作成
- [ ] ファイルアップロード
- [ ] ファイルダウンロード
- [ ] 名前変更
- [ ] 削除
- [ ] 検索
- [ ] カテゴリ分離（給食、駐車場、その他が独立）

## 💡 ヒント

- **カテゴリ分離**: 各サブタブのドキュメントは独立しています
- **権限制御**: 編集権限がない場合は閲覧のみ可能
- **ファイルサイズ**: 最大50MBまでアップロード可能
- **検索**: ファイル名とフォルダ名を部分一致で検索

## 🆘 サポート

問題が発生した場合は、以下を確認してください：
1. [デバッグガイド](./contract-document-debugging-guide.md)
2. ブラウザコンソールのエラーメッセージ
3. サーバーログ: `storage/logs/laravel.log`

---

**準備完了！** 契約書ドキュメント管理機能を使い始めましょう 🎉
