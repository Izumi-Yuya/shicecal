# 契約書ドキュメント管理機能 デバッグガイド

## 概要
このドキュメントは、契約書ドキュメント管理機能のデバッグ方法と一般的な問題の解決方法を記載しています。

## 1. ブラウザ開発者ツールの使用

### 1.1 コンソールタブ
JavaScriptエラーやログメッセージを確認します。

**確認手順:**
1. ブラウザで F12 キーを押して開発者ツールを開く
2. 「Console」タブを選択
3. ページをリロードして、エラーメッセージを確認

**よくあるエラー:**
```javascript
// ContractDocumentManagerが見つからない
Uncaught ReferenceError: ContractDocumentManager is not defined

// 解決方法: app-unified.jsでContractDocumentManagerが正しくインポートされているか確認
```

```javascript
// API通信エラー
Failed to fetch: TypeError: Failed to fetch

// 解決方法: ネットワークタブでAPIエンドポイントとレスポンスを確認
```

```javascript
// モーダルが表示されない
Uncaught TypeError: Cannot read property 'show' of null

// 解決方法: モーダル要素のIDが正しいか、Bootstrapが正しく読み込まれているか確認
```

### 1.2 ネットワークタブ
API通信の状態を確認します。

**確認手順:**
1. 開発者ツールの「Network」タブを選択
2. 「XHR」フィルターを選択
3. ドキュメント操作を実行
4. API通信のステータスコードとレスポンスを確認

**確認項目:**
- **ステータスコード**: 200, 201, 204 (成功), 403 (権限エラー), 404 (Not Found), 500 (サーバーエラー)
- **リクエストURL**: `/facilities/{facility_id}/contract-documents`
- **リクエストメソッド**: GET, POST, PUT, PATCH, DELETE
- **レスポンスボディ**: JSON形式のレスポンス

**例: ドキュメント一覧取得**
```
Request URL: http://localhost:8000/facilities/1/contract-documents
Request Method: GET
Status Code: 200 OK

Response:
{
  "success": true,
  "message": "ドキュメント一覧を取得しました。",
  "data": {
    "folders": [...],
    "files": [...],
    "breadcrumbs": [...]
  }
}
```

### 1.3 Elementsタブ
DOM構造とCSSスタイルを確認します。

**確認手順:**
1. 開発者ツールの「Elements」タブを選択
2. 要素を選択して、スタイルを確認
3. モーダルのz-indexやdisplayプロパティを確認

**よくある問題:**
- モーダルが背面に隠れる → z-indexを確認
- 要素が表示されない → displayプロパティを確認
- レイアウトが崩れる → CSSクラスが正しく適用されているか確認

## 2. サーバーサイドのデバッグ

### 2.1 Laravelログの確認
サーバーサイドのエラーを確認します。

**ログファイルの場所:**
```bash
storage/logs/laravel.log
```

**ログの確認方法:**
```bash
# 最新のログを表示
tail -f storage/logs/laravel.log

# エラーのみを表示
tail -f storage/logs/laravel.log | grep ERROR

# 契約書ドキュメント関連のログを表示
tail -f storage/logs/laravel.log | grep "ContractDoc"
```

**よくあるエラー:**
```
[2025-10-16 13:00:00] local.ERROR: Contract document operation failed
{"operation":"upload_contract_file","facility_id":1,"error":"File too large"}

// 解決方法: ファイルサイズ制限を確認
```

### 2.2 データベースクエリの確認
実行されているSQLクエリを確認します。

**方法1: クエリログの有効化**
```php
// app/Providers/AppServiceProvider.php の boot() メソッドに追加
DB::listen(function($query) {
    Log::info('Query: ' . $query->sql);
    Log::info('Bindings: ' . json_encode($query->bindings));
    Log::info('Time: ' . $query->time);
});
```

**方法2: Tinkerでクエリを確認**
```bash
php artisan tinker

# クエリのSQL文を確認
>>> DocumentFolder::contracts()->where('facility_id', 1)->toSql()
=> "select * from `document_folders` where `category` = ? and `facility_id` = ?"

# クエリを実行
>>> DocumentFolder::contracts()->where('facility_id', 1)->get()
```

### 2.3 ルートの確認
ルートが正しく定義されているか確認します。

```bash
# すべてのルートを表示
php artisan route:list

# 契約書ドキュメント関連のルートのみ表示
php artisan route:list | grep contract-documents
```

**期待される出力:**
```
GET|HEAD  facilities/{facility}/contract-documents ............... facilities.contract-documents.index
POST      facilities/{facility}/contract-documents/upload ....... facilities.contract-documents.upload
POST      facilities/{facility}/contract-documents/folders ..... facilities.contract-documents.create-folder
GET|HEAD  facilities/{facility}/contract-documents/files/{file}/download ... facilities.contract-documents.download-file
DELETE    facilities/{facility}/contract-documents/files/{file} ... facilities.contract-documents.delete-file
PATCH     facilities/{facility}/contract-documents/files/{file}/rename ... facilities.contract-documents.rename-file
DELETE    facilities/{facility}/contract-documents/folders/{folder} ... facilities.contract-documents.delete-folder
PATCH     facilities/{facility}/contract-documents/folders/{folder}/rename ... facilities.contract-documents.rename-folder
```

## 3. 一般的な問題と解決方法

### 3.1 ドキュメント一覧が表示されない

**症状:**
- ローディング表示が続く
- 空の状態が表示される
- エラーメッセージが表示される

**確認項目:**
1. ブラウザコンソールでJavaScriptエラーを確認
2. ネットワークタブでAPI通信を確認
3. サーバーログでエラーを確認

**解決方法:**
```javascript
// ContractDocumentManager.jsのloadDocuments()メソッドにログを追加
async loadDocuments(folderId = null) {
    console.log('[ContractDoc] Loading documents, folderId:', folderId);
    try {
        // ... 既存のコード
        console.log('[ContractDoc] Response:', result);
    } catch (error) {
        console.error('[ContractDoc] Load documents error:', error);
    }
}
```

### 3.2 ファイルアップロードが失敗する

**症状:**
- アップロード中にエラーが発生する
- ファイルがリストに表示されない

**確認項目:**
1. ファイルサイズが50MB以下か確認
2. ファイル形式が許可されているか確認
3. ストレージディレクトリの権限を確認

**解決方法:**
```bash
# ストレージディレクトリの権限を確認
ls -la storage/app/public/

# 権限がない場合は付与
chmod -R 775 storage/app/public/
chown -R www-data:www-data storage/app/public/

# シンボリックリンクを作成
php artisan storage:link
```

### 3.3 モーダルが表示されない

**症状:**
- ボタンをクリックしてもモーダルが表示されない
- モーダルが背面に隠れる

**確認項目:**
1. モーダル要素のIDが正しいか確認
2. Bootstrapが正しく読み込まれているか確認
3. z-indexが正しく設定されているか確認

**解決方法:**
```javascript
// モーダルhoisting処理を確認
function hoistModals(container) {
    if (!container) return;
    container.querySelectorAll('.modal').forEach(function(modal) {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });
}

// z-index強制設定を確認
document.addEventListener('show.bs.modal', function(ev) {
    var modalEl = ev.target;
    if (modalEl) {
        modalEl.style.zIndex = '2010';
    }
});
```

### 3.4 カテゴリ分離が機能しない

**症状:**
- 他のカテゴリのドキュメントが表示される
- ドキュメントが混在する

**確認項目:**
1. DocumentFolderとDocumentFileのcategoryカラムを確認
2. contracts()スコープが正しく動作しているか確認

**解決方法:**
```bash
# Tinkerでカテゴリを確認
php artisan tinker

>>> $facility = Facility::find(1);
>>> $folders = DocumentFolder::where('facility_id', $facility->id)->get();
>>> $folders->pluck('category', 'name');
=> [
     "契約書" => "contracts",
     "修繕履歴" => "maintenance",
   ]

# カテゴリが正しく設定されていない場合は修正
>>> DocumentFolder::where('facility_id', $facility->id)
       ->whereNull('category')
       ->update(['category' => 'contracts']);
```

### 3.5 検索が機能しない

**症状:**
- 検索結果が表示されない
- 検索がエラーになる

**確認項目:**
1. 検索クエリが正しく送信されているか確認
2. サーバーサイドで検索処理が実装されているか確認

**解決方法:**
```javascript
// ContractDocumentManager.jsのhandleSearch()メソッドにログを追加
async handleSearch() {
    const query = this.elements.searchInput.value.trim();
    console.log('[ContractDoc] Search query:', query);
    
    if (!query) {
        this.loadDocuments(this.currentFolderId);
        return;
    }
    
    // ... 既存のコード
}
```

### 3.6 権限エラーが発生する

**症状:**
- 403 Forbiddenエラーが表示される
- 操作が実行できない

**確認項目:**
1. ユーザーの権限を確認
2. ポリシーが正しく設定されているか確認

**解決方法:**
```bash
# Tinkerでユーザー権限を確認
php artisan tinker

>>> $user = User::find(1);
>>> $facility = Facility::find(1);
>>> $user->canEditFacility($facility->id);
=> true

# ポリシーを確認
>>> Gate::allows('update', [FacilityContract::class, $facility]);
=> true
```

## 4. デバッグツールの活用

### 4.1 Laravel Debugbar
開発環境でLaravel Debugbarを使用すると、クエリやログを簡単に確認できます。

**インストール:**
```bash
composer require barryvdh/laravel-debugbar --dev
```

**使用方法:**
- ページ下部にデバッグバーが表示される
- クエリ、ログ、リクエスト情報を確認できる

### 4.2 Vue DevTools / React DevTools
JavaScriptフレームワークを使用している場合は、専用の開発者ツールを使用します。

**注意:** このプロジェクトはVanilla JavaScriptを使用しているため、これらのツールは不要です。

### 4.3 Postman / Insomnia
API通信を直接テストする場合は、PostmanやInsomniaを使用します。

**使用例:**
```
GET http://localhost:8000/facilities/1/contract-documents
Headers:
  Accept: application/json
  X-Requested-With: XMLHttpRequest
  Cookie: laravel_session=...
```

## 5. パフォーマンスのデバッグ

### 5.1 ページ読み込み時間の確認
ブラウザの開発者ツールで読み込み時間を確認します。

**確認手順:**
1. 開発者ツールの「Network」タブを選択
2. ページをリロード
3. 「DOMContentLoaded」と「Load」の時間を確認

**目標:**
- DOMContentLoaded: 1秒以内
- Load: 3秒以内

### 5.2 クエリのパフォーマンス確認
N+1問題などのクエリパフォーマンス問題を確認します。

**方法:**
```php
// ContractDocumentService.php
public function getCategoryDocuments(Facility $facility, array $options = [])
{
    DB::enableQueryLog();
    
    // ... 既存のコード
    
    $queries = DB::getQueryLog();
    Log::info('Query count: ' . count($queries));
    
    return $result;
}
```

### 5.3 ファイルサイズの確認
JavaScriptとCSSファイルのサイズを確認します。

```bash
# ビルド後のファイルサイズを確認
ls -lh public/build/assets/

# 圧縮前後のサイズを比較
npm run build
```

## 6. テストの実行

### 6.1 機能テストの実行
```bash
# すべてのテストを実行
php artisan test

# 契約書ドキュメント関連のテストのみ実行
php artisan test --filter=ContractDocument

# 特定のテストクラスを実行
php artisan test tests/Feature/ContractDocumentControllerTest.php
```

### 6.2 単体テストの実行
```bash
# サービスクラスのテストを実行
php artisan test tests/Unit/Services/ContractDocumentServiceTest.php

# モデルのテストを実行
php artisan test tests/Unit/Models/DocumentContractsCategoryScopeTest.php
```

### 6.3 統合テストの実行
```bash
# 統合テストを実行
php artisan test tests/Feature/ContractDocumentIntegrationTest.php
```

## 7. トラブルシューティングチェックリスト

### 基本確認
- [ ] ローカル開発環境が起動している
- [ ] データベースが正常に動作している
- [ ] ストレージディレクトリの権限が正しい
- [ ] シンボリックリンクが作成されている
- [ ] アセットがビルドされている

### フロントエンド確認
- [ ] ブラウザコンソールにエラーがない
- [ ] ネットワークタブでAPI通信が成功している
- [ ] モーダルが正しく表示される
- [ ] JavaScriptファイルが正しく読み込まれている
- [ ] CSSファイルが正しく読み込まれている

### バックエンド確認
- [ ] ルートが正しく定義されている
- [ ] コントローラーメソッドが実装されている
- [ ] サービスクラスが正しく動作している
- [ ] ポリシーが正しく設定されている
- [ ] データベースマイグレーションが実行されている

### データ確認
- [ ] テスト用の施設データが存在する
- [ ] ユーザーに適切な権限がある
- [ ] カテゴリが正しく設定されている
- [ ] ファイルが正しく保存されている

## 8. サポートとリソース

### ドキュメント
- [契約書ドキュメント管理 ユーザーガイド](./contract-document-user-guide.md)
- [契約書ドキュメント管理 開発者ガイド](./contract-document-developer-guide.md)
- [契約書ドキュメント管理 API リファレンス](./contract-document-api-reference.md)

### 関連ファイル
- コントローラー: `app/Http/Controllers/ContractDocumentController.php`
- サービス: `app/Services/ContractDocumentService.php`
- JavaScript: `resources/js/modules/ContractDocumentManager.js`
- ビュー: `resources/views/components/contract-document-manager.blade.php`
- CSS: `resources/css/contract-document-management.css`

### ログファイル
- アプリケーションログ: `storage/logs/laravel.log`
- Webサーバーログ: `/var/log/nginx/error.log` (Nginx) または `/var/log/apache2/error.log` (Apache)

## まとめ

このデバッグガイドを使用して、契約書ドキュメント管理機能の問題を効率的に特定し、解決してください。問題が解決しない場合は、上記のチェックリストを確認し、必要に応じて開発チームに相談してください。
