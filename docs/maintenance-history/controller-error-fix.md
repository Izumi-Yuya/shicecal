# 修繕履歴ドキュメント管理コントローラーエラー修正

## 問題の概要

修繕履歴（外装、内装、その他）のドキュメント管理機能で以下のエラーが発生していました：

1. **500 Internal Server Error** - ドキュメント一覧取得時
2. **500 Internal Server Error** - フォルダ作成時
3. **400 Bad Request** - 2回目以降のフォルダ作成試行時

## 根本原因

`MaintenanceDocumentController` が存在しないメソッド `handleControllerException()` を呼び出していました。

### 問題のコード
```php
class MaintenanceDocumentController extends Controller
{
    use HandlesApiResponses;
    
    // ...
    
    } catch (\Exception $e) {
        return $this->handleControllerException($e, '...', [...]);  // ← 存在しないメソッド
    }
}
```

## 修正内容

### 1. 既存トレイトの活用

`HandlesApiResponses` トレイトに既に `handleException()` メソッドが存在していたため、それを正しく使用するように修正：

```php
use App\Http\Traits\HandlesApiResponses;

class MaintenanceDocumentController extends Controller
{
    use HandlesApiResponses;
```

### 2. メソッド呼び出しの修正

すべての `handleControllerException()` 呼び出しを `handleException()` に変更し、正しいシグネチャに合わせる：

```php
// 修正前
return $this->handleControllerException($e, 'エラーメッセージ', [
    'facility_id' => $facility->id,
    'category' => $category,
]);

// 修正後
return $this->handleException($e, $request, [
    'operation' => 'operation_name',
    'facility_id' => $facility->id,
    'category' => $category,
]);
```

### 3. Request パラメータの追加

`deleteFile()` と `deleteFolder()` メソッドに `Request $request` パラメータを追加：

```php
// 修正前
public function deleteFile(Facility $facility, string $category, int $file)

// 修正後
public function deleteFile(Request $request, Facility $facility, string $category, int $file)
```

### 4. successResponse() パラメータの修正

`successResponse()` メソッドの呼び出しで、パラメータの順序を修正：

```php
// successResponse のシグネチャ
protected function successResponse(string $message, $data = null, int $status = 200)

// 修正前（パラメータが逆）
return $this->successResponse($result['data'], $result['message']);
return $this->successResponse(['file' => $file], 'ファイル名を変更しました。');
return $this->successResponse(null, 'ファイルを削除しました。');

// 修正後（正しい順序）
return $this->successResponse($result['message'], $result['data']);
return $this->successResponse('ファイル名を変更しました。', ['file' => $file]);
return $this->successResponse('ファイルを削除しました。');
```

### 3. 修正箇所一覧

以下のメソッドで修正を実施：

1. `index()` - ドキュメント一覧取得
   - Operation: `get_maintenance_documents`

2. `uploadFile()` - ファイルアップロード
   - Operation: `upload_maintenance_file`

3. `createFolder()` - フォルダ作成
   - Operation: `create_maintenance_folder`

4. `deleteFile()` - ファイル削除
   - Operation: `delete_maintenance_file`

5. `deleteFolder()` - フォルダ削除
   - Operation: `delete_maintenance_folder`

6. `renameFile()` - ファイル名変更
   - Operation: `rename_maintenance_file`

7. `renameFolder()` - フォルダ名変更
   - Operation: `rename_maintenance_folder`

## HandlesApiResponses トレイトの handleException() メソッド

このメソッドは以下の機能を提供します：

### 1. メソッドシグネチャ

```php
protected function handleException(
    \Exception $e, 
    Request $request, 
    array $context = []
): JsonResponse
```

### 2. 例外タイプ別の処理

- **AuthorizationException**: 権限エラー（403）
- **ValidationException**: バリデーションエラー（422）
- **一般的な例外**: システムエラー（500）

### 3. 自動ログ記録

すべての例外が以下の情報とともにログに記録されます：

- Exception クラス名
- Exception メッセージ
- ファイルと行番号
- User ID
- Request URL
- HTTP Method
- カスタムコンテキスト（operation名、facility_id等）

### 4. JSON レスポンス

常にJSON形式のエラーレスポンスを返します：

```json
{
    "success": false,
    "message": "エラーメッセージ",
    "timestamp": "2025-10-14T..."
}
```

## 期待される効果

### 1. エラーの解消

- ドキュメント一覧が正常に表示される
- フォルダ作成が正常に動作する
- ファイルアップロードが正常に動作する

### 2. 改善されたエラーハンドリング

- 詳細なエラーログが記録される
- ユーザーフレンドリーなエラーメッセージが表示される
- 開発環境では詳細なエラー情報が表示される
- 本番環境では安全なエラーメッセージが表示される

### 3. 監査証跡の強化

- すべての操作が適切にログに記録される
- トラブルシューティングが容易になる

## テスト方法

### 1. ドキュメント一覧の表示

```bash
# ブラウザで施設詳細ページにアクセス
# 修繕履歴タブを開く
# 外装/内装/その他のドキュメントセクションを展開
```

期待結果：
- エラーなくドキュメント一覧が表示される
- 空の場合は「ファイルがありません」メッセージが表示される

### 2. フォルダ作成

```bash
# 「新しいフォルダ」ボタンをクリック
# フォルダ名を入力して作成
```

期待結果：
- フォルダが正常に作成される
- 成功メッセージが表示される
- 一覧に新しいフォルダが表示される

### 3. ファイルアップロード

```bash
# 「ファイルアップロード」ボタンをクリック
# ファイルを選択してアップロード
```

期待結果：
- ファイルが正常にアップロードされる
- 成功メッセージが表示される
- 一覧に新しいファイルが表示される

## ログの確認

修正後、以下のログが適切に記録されることを確認：

```bash
# Laravel ログを確認
tail -f storage/logs/laravel.log

# 成功時のログ例
[INFO] Operation successful: create_maintenance_folder
{
    "user_id": 1,
    "facility_id": 102,
    "category": "exterior",
    "timestamp": "2025-10-14T..."
}

# エラー時のログ例
[ERROR] Controller exception occurred
{
    "operation": "create_maintenance_folder",
    "user_id": 1,
    "facility_id": 102,
    "category": "exterior",
    "exception": "Exception",
    "message": "...",
    "file": "...",
    "line": 123,
    "trace": "..."
}
```

## 関連ファイル

- `app/Http/Controllers/MaintenanceDocumentController.php` - 修正されたコントローラー
- `app/Http/Traits/HandlesControllerErrors.php` - エラーハンドリングトレイト
- `app/Http/Traits/HandlesApiResponses.php` - APIレスポンストレイト
- `app/Services/MaintenanceDocumentService.php` - ビジネスロジック

## 今後の推奨事項

### 1. 他のコントローラーの確認

同様の問題が他のコントローラーにないか確認：

```bash
# handleControllerException を使用している箇所を検索
grep -r "handleControllerException" app/Http/Controllers/
```

### 2. テストの追加

修繕履歴ドキュメント管理の機能テストを追加：

```php
// tests/Feature/MaintenanceDocumentTest.php
public function test_can_list_maintenance_documents()
{
    // テスト実装
}

public function test_can_create_maintenance_folder()
{
    // テスト実装
}

public function test_can_upload_maintenance_file()
{
    // テスト実装
}
```

### 3. エラーハンドリングの標準化

すべてのコントローラーで `HandlesControllerErrors` トレイトを使用するように統一。

## まとめ

この修正により、修繕履歴のドキュメント管理機能が正常に動作するようになりました。エラーハンドリングも改善され、より堅牢で保守しやすいコードになっています。
