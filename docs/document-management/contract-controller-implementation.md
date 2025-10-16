# ContractDocumentController 実装完了

## 概要
契約書ドキュメント管理のためのコントローラーを実装しました。既存のMaintenanceDocumentControllerとLifelineDocumentControllerのパターンに従い、統一されたAPIエンドポイントを提供します。

## 実装内容

### 基本構造
- **トレイト**: `HandlesApiResponses` を使用した統一されたレスポンス処理
- **依存性注入**: 
  - `ContractDocumentService`: 契約書ドキュメント管理のビジネスロジック
  - `DocumentService`: 共通ドキュメント処理

### 実装されたエンドポイント

#### 1. ドキュメント一覧取得 (`index`)
- **メソッド**: GET
- **認可**: `FacilityContract::view`
- **機能**: 契約書カテゴリのドキュメント一覧を取得
- **パラメータ**: folder_id, per_page, sort_by, sort_order

#### 2. ファイルアップロード (`uploadFile`)
- **メソッド**: POST
- **認可**: `FacilityContract::update`
- **バリデーション**: 
  - file: required, max:51200 (50MB)
  - folder_id: nullable, integer, exists
- **機能**: 契約書カテゴリにファイルをアップロード

#### 3. フォルダ作成 (`createFolder`)
- **メソッド**: POST
- **認可**: `FacilityContract::update`
- **バリデーション**:
  - name: required, string, max:255
  - parent_id: nullable, integer, exists
- **機能**: 契約書カテゴリにフォルダを作成

#### 4. ファイルダウンロード (`downloadFile`)
- **メソッド**: GET
- **認可**: `FacilityContract::view`
- **機能**: ファイルをダウンロード
- **セキュリティ**: 施設所有権とカテゴリの確認

#### 5. ファイル削除 (`deleteFile`)
- **メソッド**: DELETE
- **認可**: `FacilityContract::update`
- **機能**: ファイルを削除
- **セキュリティ**: 施設所有権とカテゴリの確認

#### 6. フォルダ削除 (`deleteFolder`)
- **メソッド**: DELETE
- **認可**: `FacilityContract::update`
- **機能**: フォルダを削除（サブフォルダとファイルも含む）
- **セキュリティ**: 施設所有権とカテゴリの確認

#### 7. ファイル名変更 (`renameFile`)
- **メソッド**: PATCH
- **認可**: `FacilityContract::update`
- **バリデーション**: name: required, string, max:255
- **機能**: ファイル名を変更
- **セキュリティ**: 施設所有権とカテゴリの確認

#### 8. フォルダ名変更 (`renameFolder`)
- **メソッド**: PATCH
- **認可**: `FacilityContract::update`
- **バリデーション**: name: required, string, max:255
- **機能**: フォルダ名を変更
- **セキュリティ**: 施設所有権とカテゴリの確認

## セキュリティ機能

### 認可チェック
- すべてのエンドポイントで`ContractPolicy`を使用した認可チェック
- 閲覧操作: `view`メソッド
- 編集操作: `update`メソッド

### データ検証
1. **施設所有権確認**: すべての操作でファイル/フォルダが指定施設に属することを確認
2. **カテゴリ確認**: すべての操作でカテゴリが'contracts'であることを確認
3. **入力バリデーション**: Laravel標準のバリデーション機能を使用

### エラーハンドリング
- `HandlesApiResponses`トレイトによる統一されたエラーレスポンス
- 適切なHTTPステータスコード（403, 404, 422, 500）
- 詳細なログ出力（操作内容、ユーザーID、エラーメッセージ）

## レスポンス形式

### 成功レスポンス
```json
{
  "success": true,
  "message": "操作が完了しました。",
  "data": { ... }
}
```

### エラーレスポンス
```json
{
  "success": false,
  "message": "エラーメッセージ",
  "errors": { ... }  // バリデーションエラーの場合
}
```

## 次のステップ
1. ルート定義の追加（Task 4）
2. Bladeコンポーネントの実装（Task 5）
3. JavaScriptモジュールの実装（Task 6）
4. 契約書タブへの統合（Task 7）
5. テストの実装（Task 8）

## 参考実装
- `app/Http/Controllers/MaintenanceDocumentController.php`
- `app/Http/Controllers/LifelineDocumentController.php`
- `app/Services/ContractDocumentService.php`

## 実装日
2025年1月

## 要件対応
- Requirement 1.1, 1.4, 1.5: ドキュメント管理機能
- Requirement 2.1, 2.2: フォルダ管理
- Requirement 3.1, 3.2, 3.3: ファイルアップロード
- Requirement 4.1, 4.2, 4.3: ファイルダウンロード
- Requirement 7.1, 7.2, 7.3, 7.4: 削除機能
- Requirement 8.1, 8.2, 8.4: 名前変更機能
- Requirement 10.5: RESTful API設計
