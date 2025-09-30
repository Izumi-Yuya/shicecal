# ドキュメント管理バリデーションガイド

## 概要

このドキュメントは、施設ドキュメント管理システムで使用されるバリデーション機能について説明します。

## バリデーションクラス

### 1. CreateFolderRequest

新しいフォルダ作成時のバリデーションを行います。

**使用例:**
```php
public function createFolder(CreateFolderRequest $request, Facility $facility)
{
    $validated = $request->validated();
    // フォルダ作成処理
}
```

**バリデーションルール:**
- `name`: 必須、文字列、有効なフォルダ名、同一階層での重複なし
- `parent_id`: オプション、整数、存在するフォルダID、同一施設内

### 2. RenameFolderRequest

既存フォルダの名前変更時のバリデーションを行います。

**使用例:**
```php
public function renameFolder(RenameFolderRequest $request, DocumentFolder $folder)
{
    $validated = $request->validated();
    // フォルダ名変更処理
}
```

**バリデーションルール:**
- `name`: 必須、文字列、有効なフォルダ名、同一階層での重複なし（自身を除く）

### 3. UploadFileRequest

ファイルアップロード時のバリデーションを行います。

**使用例:**
```php
public function uploadFile(UploadFileRequest $request, Facility $facility)
{
    $validated = $request->validated();
    // ファイルアップロード処理
}
```

**バリデーションルール:**
- `files`: 必須、配列、最大10ファイル
- `files.*`: 必須、セキュアなファイル
- `folder_id`: オプション、整数、存在するフォルダID、同一施設内

## カスタムバリデーションルール

### 1. ValidFolderName

フォルダ名の有効性をチェックします。

**チェック項目:**
- 空文字・空白のみでない
- 禁止文字が含まれていない（`/ \ : * ? " < > |`）
- システム予約名でない（`CON`, `PRN`, `AUX`, `NUL`, `COM1-9`, `LPT1-9`）
- ドット・スペースで開始・終了していない
- 制御文字が含まれていない
- 最大長を超えていない

**使用例:**
```php
$validator = Validator::make($data, [
    'name' => ['required', new ValidFolderName()],
]);
```

### 2. UniqueFolderName

フォルダ名の重複をチェックします。

**パラメータ:**
- `$facilityId`: 施設ID
- `$parentId`: 親フォルダID（null = ルート）
- `$excludeId`: 除外するフォルダID（名前変更時）

**使用例:**
```php
$validator = Validator::make($data, [
    'name' => [new UniqueFolderName($facilityId, $parentId, $excludeId)],
]);
```

### 3. SecureFileUpload

ファイルアップロードのセキュリティをチェックします。

**チェック項目:**
- 有効なアップロードファイル
- 許可された拡張子
- 許可されたMIMEタイプ
- 危険なファイル名でない
- ファイルサイズ制限内
- 実行可能コンテンツでない

**使用例:**
```php
$validator = Validator::make($data, [
    'file' => ['required', new SecureFileUpload()],
]);
```

## 設定ファイル

### config/facility-document.php

ドキュメント管理システムの設定を管理します。

**主要設定項目:**
```php
'max_file_size' => 10240, // KB (10MB)
'max_files_per_upload' => 10,
'allowed_mime_types' => [...],
'allowed_extensions' => [...],
'forbidden_folder_names' => [...],
'forbidden_folder_characters' => [...],
```

## エラーメッセージ

すべてのバリデーションクラスは日本語のエラーメッセージを提供します。

**例:**
- `フォルダ名は必須です。`
- `このフォルダ名は既に存在します。別の名前を入力してください。`
- `ファイルサイズは10MB以下にしてください。`
- `対応しているファイル形式は PDF、Word (DOC, DOCX)、Excel (XLS, XLSX) です。`

## テスト

バリデーション機能のテストは以下のファイルで実装されています：

- `tests/Unit/Requests/DocumentValidationTest.php`: カスタムルールのテスト
- `tests/Unit/Requests/ValidationRequestsTest.php`: リクエストクラスのテスト

**テスト実行:**
```bash
php artisan test tests/Unit/Requests/DocumentValidationTest.php
php artisan test tests/Unit/Requests/ValidationRequestsTest.php
```

## 使用上の注意

1. **ルート依存**: CreateFolderRequest と UploadFileRequest は `facility` ルートパラメータに依存します
2. **モデル依存**: RenameFolderRequest は `folder` ルートパラメータに依存します
3. **設定依存**: SecureFileUpload は `config/facility-document.php` の設定に依存します
4. **データベース依存**: UniqueFolderName は DocumentFolder モデルに依存します

## セキュリティ考慮事項

1. **ファイルタイプ検証**: 拡張子とMIMEタイプの両方をチェック
2. **ファイル名検証**: パストラバーサル攻撃を防止
3. **ファイルサイズ制限**: DoS攻撃を防止
4. **実行可能ファイル検出**: 悪意のあるファイルを検出
5. **制御文字除去**: ファイル名の制御文字を検出

このバリデーションシステムにより、セキュアで信頼性の高いドキュメント管理機能を提供できます。