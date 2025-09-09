# API リファレンス

## 概要

Shise-Cal（シセカル）のREST API仕様書です。簡素化されたアーキテクチャに基づく統合されたAPIエンドポイントを説明します。

## 認証

### 認証方式
- **セッション認証**: Web UI用
- **Sanctum トークン**: API用（将来実装予定）

### 認証ヘッダー
```http
Cookie: laravel_session=<session_token>
X-CSRF-TOKEN: <csrf_token>
```

## 施設管理 API

### 基本的なCRUD操作

#### 施設一覧取得
```http
GET /facilities
```

**レスポンス例**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "施設名",
      "address": "住所",
      "created_at": "2025-01-01T00:00:00Z",
      "updated_at": "2025-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 100
  }
}
```

#### 施設詳細取得
```http
GET /facilities/{id}
```

#### 施設作成
```http
POST /facilities
Content-Type: application/json

{
  "name": "新しい施設",
  "address": "東京都...",
  "facility_type": "office"
}
```

#### 施設更新
```http
PUT /facilities/{id}
Content-Type: application/json

{
  "name": "更新された施設名",
  "address": "更新された住所"
}
```

#### 施設削除
```http
DELETE /facilities/{id}
```

### 基本情報管理

#### 基本情報表示
```http
GET /facilities/{id}/basic-info
```

#### 基本情報更新
```http
PUT /facilities/{id}/basic-info
Content-Type: application/json

{
  "building_structure": "RC造",
  "total_floor_area": 1000.5,
  "construction_year": 2020
}
```

### 土地情報管理

#### 土地情報表示
```http
GET /facilities/{id}/land-info
```

**レスポンス例**:
```json
{
  "id": 1,
  "facility_id": 1,
  "ownership_type": "owned",
  "land_area": 500.0,
  "unit_price": 100000,
  "purchase_price": 50000000,
  "contract_start_date": "2020-01-01",
  "contract_end_date": "2030-12-31",
  "approval_status": "pending",
  "created_at": "2025-01-01T00:00:00Z",
  "updated_at": "2025-01-01T00:00:00Z"
}
```

#### 土地情報更新
```http
PUT /facilities/{id}/land-info
Content-Type: application/json

{
  "ownership_type": "leased",
  "land_area": 600.0,
  "purchase_price": 60000000,
  "contract_start_date": "2025-01-01",
  "contract_end_date": "2035-12-31"
}
```

#### 土地情報計算
```http
POST /facilities/{id}/land-info/calculate
Content-Type: application/json

{
  "purchase_price": 50000000,
  "land_area": 500.0
}
```

**レスポンス例**:
```json
{
  "unit_price": 100000,
  "formatted_unit_price": "¥100,000",
  "contract_years": 10
}
```

#### 土地情報承認
```http
POST /facilities/{id}/land-info/approve
Content-Type: application/json

{
  "approval_comment": "承認します"
}
```

#### 土地情報差戻し
```http
POST /facilities/{id}/land-info/reject
Content-Type: application/json

{
  "rejection_reason": "データに不備があります",
  "rejection_comment": "面積の再確認をお願いします"
}
```

### ドキュメント管理

#### ドキュメント一覧取得
```http
GET /facilities/{id}/documents
```

#### ドキュメントアップロード
```http
POST /facilities/{id}/documents
Content-Type: multipart/form-data

files[]: <file1>
files[]: <file2>
document_type: "contract"
```

#### ドキュメントダウンロード
```http
GET /facilities/{id}/documents/{fileId}
```

#### ドキュメント削除
```http
DELETE /facilities/{id}/documents/{fileId}
```

## 出力機能 API

### PDF出力

#### PDF出力メニュー
```http
GET /export/pdf
```

#### 単一施設PDF生成
```http
POST /export/pdf/single/{facilityId}
Content-Type: application/json

{
  "include_land_info": true,
  "include_maintenance_history": false,
  "format": "A4"
}
```

#### セキュアPDF生成
```http
POST /export/pdf/secure/{facilityId}
Content-Type: application/json

{
  "password": "secure123",
  "watermark": true,
  "include_land_info": true
}
```

#### 一括PDF生成
```http
POST /export/pdf/batch
Content-Type: application/json

{
  "facility_ids": [1, 2, 3],
  "include_land_info": true,
  "format": "A4"
}
```

**レスポンス例**:
```json
{
  "batch_id": "batch_20250101_123456",
  "status": "processing",
  "total_facilities": 3,
  "estimated_completion": "2025-01-01T12:35:00Z"
}
```

#### 一括処理進捗確認
```http
GET /export/pdf/batch/{batchId}/progress
```

**レスポンス例**:
```json
{
  "batch_id": "batch_20250101_123456",
  "status": "completed",
  "progress": 100,
  "completed_facilities": 3,
  "total_facilities": 3,
  "download_url": "/storage/temp/batch_20250101_123456.zip"
}
```

### CSV出力

#### CSV出力メニュー
```http
GET /export/csv
```

#### フィールドプレビュー
```http
POST /export/csv/preview
Content-Type: application/json

{
  "facility_ids": [1, 2, 3],
  "fields": ["name", "address", "land_area", "purchase_price"]
}
```

#### CSV生成
```http
POST /export/csv/generate
Content-Type: application/json

{
  "facility_ids": [1, 2, 3],
  "fields": ["name", "address", "land_area", "purchase_price"],
  "include_headers": true,
  "encoding": "UTF-8"
}
```

### お気に入り機能

#### お気に入り一覧
```http
GET /export/favorites
```

#### お気に入り保存
```http
POST /export/favorites
Content-Type: application/json

{
  "name": "月次レポート用",
  "fields": ["name", "address", "land_area"],
  "filters": {
    "facility_type": "office"
  }
}
```

#### お気に入り読み込み
```http
GET /export/favorites/{id}
```

#### お気に入り更新
```http
PUT /export/favorites/{id}
Content-Type: application/json

{
  "name": "更新された月次レポート用",
  "fields": ["name", "address", "land_area", "purchase_price"]
}
```

#### お気に入り削除
```http
DELETE /export/favorites/{id}
```

## コメント機能 API

### 基本的なCRUD操作

#### コメント一覧取得
```http
GET /comments?facility_id={facilityId}
```

#### コメント作成
```http
POST /comments
Content-Type: application/json

{
  "facility_id": 1,
  "content": "確認が必要です",
  "assigned_to": 2,
  "priority": "high"
}
```

#### コメント更新
```http
PUT /comments/{id}
Content-Type: application/json

{
  "content": "更新されたコメント",
  "status": "in_progress"
}
```

#### コメント削除
```http
DELETE /comments/{id}
```

### ステータス管理

#### 自分のコメント
```http
GET /comments/my-comments
```

#### 担当コメント
```http
GET /comments/assigned
```

#### ステータスダッシュボード
```http
GET /comments/dashboard
```

#### ステータス更新
```http
PUT /comments/{id}/status
Content-Type: application/json

{
  "status": "completed",
  "resolution_comment": "対応完了しました"
}
```

#### 一括ステータス更新
```http
PUT /comments/bulk-status
Content-Type: application/json

{
  "comment_ids": [1, 2, 3],
  "status": "in_progress"
}
```

## エラーレスポンス

### エラー形式
```json
{
  "success": false,
  "message": "エラーメッセージ",
  "error_code": "ERROR_CODE",
  "errors": {
    "field_name": ["フィールド固有のエラーメッセージ"]
  }
}
```

### エラーコード一覧

| コード | 説明 | HTTPステータス |
|--------|------|----------------|
| `VALIDATION_ERROR` | バリデーションエラー | 422 |
| `AUTHORIZATION_ERROR` | 認可エラー | 403 |
| `NOT_FOUND` | リソースが見つからない | 404 |
| `GENERAL_ERROR` | 一般的なエラー | 500 |
| `FACILITY_NOT_FOUND` | 施設が見つからない | 404 |
| `LAND_INFO_NOT_FOUND` | 土地情報が見つからない | 404 |
| `EXPORT_FAILED` | 出力処理失敗 | 500 |
| `FILE_UPLOAD_FAILED` | ファイルアップロード失敗 | 400 |

## レート制限

### 制限値
- **一般API**: 60リクエスト/分
- **出力API**: 10リクエスト/分
- **ファイルアップロード**: 5リクエスト/分

### レート制限ヘッダー
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
```

## ページネーション

### リクエストパラメータ
```http
GET /facilities?page=2&per_page=20&sort=name&order=asc
```

### レスポンス形式
```json
{
  "data": [...],
  "meta": {
    "current_page": 2,
    "from": 21,
    "last_page": 5,
    "per_page": 20,
    "to": 40,
    "total": 100
  },
  "links": {
    "first": "/facilities?page=1",
    "last": "/facilities?page=5",
    "prev": "/facilities?page=1",
    "next": "/facilities?page=3"
  }
}
```

## フィルタリング・検索

### 施設検索
```http
GET /facilities?search=東京&facility_type=office&status=active
```

### 土地情報フィルタ
```http
GET /facilities?land_area_min=100&land_area_max=1000&ownership_type=owned
```

### 日付範囲フィルタ
```http
GET /facilities?created_from=2025-01-01&created_to=2025-12-31
```

## WebSocket（将来実装予定）

### リアルタイム通知
```javascript
// 接続例
const socket = new WebSocket('ws://localhost:8000/ws');

// 通知受信
socket.onmessage = function(event) {
  const notification = JSON.parse(event.data);
  console.log('新しい通知:', notification);
};
```

## SDK・ライブラリ（将来実装予定）

### JavaScript SDK
```javascript
import ShiseCalAPI from '@shisecal/api-client';

const api = new ShiseCalAPI({
  baseURL: 'https://api.shisecal.com',
  token: 'your-api-token'
});

// 施設取得
const facilities = await api.facilities.list();
```

## 変更履歴

### v2.0.0 (2025-02-01)
- プロジェクト簡素化に伴うAPI統合
- 土地情報APIを施設APIに統合
- 出力APIの統合（PDF・CSV）
- コメントAPIの統合

### v1.9.9 (2025-01-31)
- 簡素化前の最終バージョン
- 個別コントローラーによるAPI提供

---

**注意**: このAPI仕様書は簡素化されたアーキテクチャに基づいています。旧APIとの互換性については[マイグレーションガイド](../migration/PROJECT_SIMPLIFICATION_GUIDE.md)を参照してください。