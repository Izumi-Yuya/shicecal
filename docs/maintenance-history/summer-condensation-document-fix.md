# 夏型結露タブのドキュメント読み込みエラー修正

## 問題の概要
夏型結露タブのドキュメント管理機能でエラーが発生していた。

## 原因
`MaintenanceDocumentService`の`CATEGORY_FOLDER_MAPPING`定数に`summer_condensation`カテゴリが定義されていなかった。

### 修正前のコード
```php
const CATEGORY_FOLDER_MAPPING = [
    'exterior' => '外装',
    'interior' => '内装リニューアル',
    'other' => 'その他',
];
```

## 修正内容
`summer_condensation`カテゴリを`CATEGORY_FOLDER_MAPPING`に追加した。

### 修正後のコード
```php
const CATEGORY_FOLDER_MAPPING = [
    'exterior' => '外装',
    'interior' => '内装リニューアル',
    'summer_condensation' => '夏型結露',
    'other' => 'その他',
];
```

## 影響範囲
- **修正ファイル**: `app/Services/MaintenanceDocumentService.php`
- **影響する機能**: 夏型結露タブのドキュメント管理機能全般
  - ドキュメント一覧の取得
  - ファイルアップロード
  - フォルダ作成
  - ファイル/フォルダの削除・名前変更

## 動作確認
修正後、以下の操作が正常に動作することを確認：
1. 夏型結露タブのドキュメントボタンをクリック
2. ドキュメント管理モーダルが開く
3. ドキュメント一覧が正常に読み込まれる
4. ファイルアップロード、フォルダ作成などの操作が可能

## 関連ファイル
- `app/Services/MaintenanceDocumentService.php` - カテゴリマッピング定義
- `app/Http/Controllers/MaintenanceDocumentController.php` - カテゴリ検証処理
- `resources/views/facilities/repair-history/index.blade.php` - ドキュメントモーダル表示
- `resources/views/components/maintenance-document-manager.blade.php` - ドキュメント管理UI
- `resources/js/modules/MaintenanceDocumentManager.js` - フロントエンド処理

## 今後の注意点
新しい修繕履歴カテゴリを追加する場合は、以下の箇所を更新する必要がある：
1. `MaintenanceDocumentService::CATEGORY_FOLDER_MAPPING` - カテゴリマッピング
2. `resources/views/facilities/repair-history/index.blade.php` - タブとモーダルの追加
3. データベースマイグレーション（必要に応じて）

## 修正日時
2025年10月15日

## 修正者
Kiro AI Assistant
