# フォルダ削除エラー修正

## 問題の概要

ドキュメント管理システムでフォルダを削除しようとすると、500 Internal Server Errorが発生していました。

### エラーログ
```
DELETE http://127.0.0.1:8000/facilities/102/documents/folders/1 500 (Internal Server Error)
API Error: フォルダの削除に失敗しました。
```

## 原因分析

### 1. 実際の問題
フォルダID 1には7つの子フォルダが存在していました：
- 子フォルダ数: 7
- ファイル数: 0

### 2. システムの動作
`DocumentService::deleteFolder()`メソッドは、`DocumentFolder::canDelete()`メソッドを使用して削除可能性をチェックします：

```php
public function canDelete(): bool
{
    $cacheKey = "folder_can_delete_{$this->id}";
    return cache()->remember($cacheKey, 60, function () {
        $hasChildren = $this->children()->exists();
        $hasFiles = $this->files()->exists();
        
        return !$hasChildren && !$hasFiles;
    });
}
```

子フォルダまたはファイルが存在する場合、`canDelete()`は`false`を返し、例外がスローされます：
```
このフォルダは削除できません。サブフォルダまたはファイルが存在します。
```

### 3. エラーハンドリングの問題
`DocumentController::deleteFolder()`メソッドでは、この検証エラーが一般的な例外として処理され、500エラーとして返されていました。

## 修正内容

### 変更ファイル
- `app/Http/Controllers/DocumentController.php`

### 修正内容
検証エラー（フォルダが空でない）と実際のシステムエラーを区別するように、エラーハンドリングを改善しました：

```php
public function deleteFolder(Facility $facility, DocumentFolder $folder): JsonResponse
{
    try {
        // ... 既存のコード ...
        
    } catch (Exception $e) {
        Log::error('Folder deletion failed', [
            'folder_id' => $folder->id,
            'facility_id' => $folder->facility_id,
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
        ]);

        // 検証エラー（フォルダが空でない）をチェック
        if (strpos($e->getMessage(), 'サブフォルダまたはファイルが存在') !== false) {
            return response()->json([
                'success' => false,
                'message' => 'フォルダを削除できません。サブフォルダまたはファイルが存在します。先に中身を削除してください。',
            ], 400); // 400 Bad Request
        }

        return response()->json([
            'success' => false,
            'message' => 'フォルダの削除に失敗しました。',
        ], 500); // 500 Internal Server Error
    }
}
```

## 修正後の動作

### 1. フォルダが空でない場合
- **HTTPステータス**: 400 Bad Request（以前は500）
- **メッセージ**: 「フォルダを削除できません。サブフォルダまたはファイルが存在します。先に中身を削除してください。」
- **ユーザー体験**: より明確なエラーメッセージで、何をすべきかが分かる

### 2. システムエラーの場合
- **HTTPステータス**: 500 Internal Server Error
- **メッセージ**: 「フォルダの削除に失敗しました。」
- **ログ**: 詳細なエラー情報が記録される

## テスト方法

### 1. 空でないフォルダの削除を試みる
```javascript
// ブラウザのコンソールで
const response = await fetch('/facilities/102/documents/folders/1', {
    method: 'DELETE',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json'
    }
});
const data = await response.json();
console.log(response.status, data); // 400, { success: false, message: "..." }
```

### 2. 空のフォルダの削除
```javascript
// 空のフォルダIDを使用
const response = await fetch('/facilities/102/documents/folders/[empty_folder_id]', {
    method: 'DELETE',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json'
    }
});
const data = await response.json();
console.log(response.status, data); // 200, { success: true, message: "フォルダを削除しました。" }
```

## 今後の改善案

### 1. 再帰的削除オプション
ユーザーが明示的に選択した場合、サブフォルダとファイルを含めて削除できる機能を追加：

```php
public function deleteFolder(Request $request, Facility $facility, DocumentFolder $folder): JsonResponse
{
    $recursive = $request->input('recursive', false);
    
    if ($recursive) {
        // 再帰的削除の実装
        $result = $this->documentService->deleteFolderRecursive($folder, auth()->user());
    } else {
        // 通常の削除（空のフォルダのみ）
        $result = $this->documentService->deleteFolder($folder, auth()->user());
    }
}
```

### 2. フロントエンドでの事前チェック
削除ボタンをクリックする前に、フォルダの内容を確認し、適切な警告を表示：

```javascript
async handleDeleteFolder(folderId) {
    // フォルダの内容を確認
    const folderInfo = await this.getFolderInfo(folderId);
    
    if (folderInfo.hasChildren || folderInfo.hasFiles) {
        const confirmed = confirm(
            'このフォルダにはサブフォルダまたはファイルが含まれています。\n' +
            'すべての内容を削除しますか？'
        );
        
        if (!confirmed) return;
        
        // 再帰的削除を実行
        await this.deleteFolder(folderId, { recursive: true });
    } else {
        // 通常の削除
        await this.deleteFolder(folderId);
    }
}
```

### 3. バッチ削除機能
`DocumentService`には既に`deleteFolderBatch()`メソッドが実装されているため、これを活用して大量のファイル/フォルダを効率的に削除できます。

## まとめ

この修正により：
1. ✅ エラーメッセージがより明確になった
2. ✅ 適切なHTTPステータスコードが返される（400 vs 500）
3. ✅ ユーザーが次に何をすべきかが分かる
4. ✅ システムエラーと検証エラーが区別される

システムは設計通りに動作しており、空でないフォルダの削除を防いでいます。今回の修正は、エラーハンドリングとユーザー体験の改善に焦点を当てています。
