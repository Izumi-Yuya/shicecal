# 契約書ドキュメント管理 API リファレンス

## 概要

契約書ドキュメント管理APIは、施設に関連する契約書類の管理機能を提供するRESTful APIです。

**ベースURL**: `/facilities/{facility}/contract-documents`

**認証**: すべてのエンドポイントで認証が必要です（Laravel Sanctum）

**認可**: FacilityContractポリシーに基づく権限チェック

## エンドポイント一覧

| メソッド | エンドポイント | 説明 |
|---------|--------------|------|
| GET | `/` | ドキュメント一覧取得 |
| POST | `/upload` | ファイルアップロード |
| POST | `/folders` | フォルダ作成 |
| GET | `/files/{file}/download` | ファイルダウンロード |
| DELETE | `/files/{file}` | ファイル削除 |
| DELETE | `/folders/{folder}` | フォルダ削除 |
| PATCH | `/files/{file}/rename` | ファイル名変更 |
| PATCH | `/folders/{folder}/rename` | フォルダ名変更 |

## 詳細仕様

### 1. ドキュメント一覧取得

指定されたフォルダ内のドキュメント（フォルダとファイル）一覧を取得します。

**エンドポイント**: `GET /facilities/{facility}/contract-documents`

**パラメータ**:
- `folder_id` (optional, integer): フォルダID。指定しない場合はルートフォルダ
- `per_page` (optional, integer): ページあたりの件数（デフォルト: 50）

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "folders": [
      {
        "id": 1,
        "name": "契約書",
        "path": "/契約書/契約書",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "creator": {
          "id": 1,
          "name": "山田太郎"
        },
        "files_count": 5
      }
    ],
    "files": {
      "data": [
        {
          "id": 1,
          "original_name": "契約書_2024.pdf",
          "file_size": 1024000,
          "mime_type": "application/pdf",
          "created_at": "2024-01-01T00:00:00.000000Z",
          "uploader": {
            "id": 1,
            "name": "山田太郎"
          }
        }
      ],
      "current_page": 1,
      "per_page": 50,
      "total": 10
    },
    "breadcrumbs": [
      {
        "id": null,
        "name": "契約書",
        "path": "/"
      },
      {
        "id": 1,
        "name": "契約書",
        "path": "/契約書"
      }
    ],
    "current_folder": {
      "id": 1,
      "name": "契約書",
      "path": "/契約書/契約書"
    }
  }
}
```

**エラーレスポンス**:
- `403 Forbidden`: 権限がありません
- `404 Not Found`: 施設またはフォルダが見つかりません
- `500 Internal Server Error`: サーバーエラー

---

### 2. ファイルアップロード

指定されたフォルダにファイルをアップロードします。

**エンドポイント**: `POST /facilities/{facility}/contract-documents/upload`

**Content-Type**: `multipart/form-data`

**パラメータ**:
- `file` (required, file): アップロードするファイル
- `folder_id` (optional, integer): アップロード先フォルダID

**バリデーションルール**:
- ファイルサイズ: 最大50MB
- ファイル形式: pdf, jpg, jpeg, png, gif, doc, docx, xls, xlsx

**レスポンス**:
```json
{
  "success": true,
  "message": "ファイルをアップロードしました。",
  "data": {
    "file": {
      "id": 1,
      "original_name": "契約書_2024.pdf",
      "stored_name": "20240101_120000_契約書_2024.pdf",
      "file_path": "documents/contracts/20240101_120000_契約書_2024.pdf",
      "file_size": 1024000,
      "mime_type": "application/pdf",
      "file_extension": "pdf",
      "folder_id": 1,
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

**エラーレスポンス**:
- `403 Forbidden`: 権限がありません
- `422 Unprocessable Entity`: バリデーションエラー
- `500 Internal Server Error`: アップロード失敗

---

### 3. フォルダ作成

新しいフォルダを作成します。

**エンドポイント**: `POST /facilities/{facility}/contract-documents/folders`

**Content-Type**: `application/json`

**パラメータ**:
```json
{
  "name": "新しいフォルダ",
  "parent_id": 1
}
```

- `name` (required, string): フォルダ名（最大255文字）
- `parent_id` (optional, integer): 親フォルダID

**バリデーションルール**:
- フォルダ名: 必須、最大255文字
- 同じ親フォルダ内に同名のフォルダは作成不可

**レスポンス**:
```json
{
  "success": true,
  "message": "フォルダを作成しました。",
  "data": {
    "folder": {
      "id": 2,
      "name": "新しいフォルダ",
      "path": "/契約書/新しいフォルダ",
      "parent_id": 1,
      "category": "contracts",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "creator": {
        "id": 1,
        "name": "山田太郎"
      }
    }
  }
}
```

**エラーレスポンス**:
- `403 Forbidden`: 権限がありません
- `422 Unprocessable Entity`: バリデーションエラー
- `500 Internal Server Error`: 作成失敗

---

### 4. ファイルダウンロード

指定されたファイルをダウンロードします。

**エンドポイント**: `GET /facilities/{facility}/contract-documents/files/{file}/download`

**パラメータ**: なし

**レスポンス**: ファイルのバイナリデータ

**レスポンスヘッダー**:
```
Content-Type: application/pdf (ファイルのMIMEタイプ)
Content-Disposition: attachment; filename="契約書_2024.pdf"
```

**エラーレスポンス**:
- `403 Forbidden`: 権限がありません
- `404 Not Found`: ファイルが見つかりません
- `500 Internal Server Error`: ダウンロード失敗

---

### 5. ファイル削除

指定されたファイルを削除します。

**エンドポイント**: `DELETE /facilities/{facility}/contract-documents/files/{file}`

**パラメータ**: なし

**レスポンス**:
```json
{
  "success": true,
  "message": "ファイルを削除しました。"
}
```

**エラーレスポンス**:
- `403 Forbidden`: 権限がありません
- `404 Not Found`: ファイルが見つかりません
- `500 Internal Server Error`: 削除失敗

---

### 6. フォルダ削除

指定されたフォルダとその中のすべてのファイル・サブフォルダを削除します。

**エンドポイント**: `DELETE /facilities/{facility}/contract-documents/folders/{folder}`

**パラメータ**: なし

**レスポンス**:
```json
{
  "success": true,
  "message": "フォルダを削除しました。"
}
```

**エラーレスポンス**:
- `403 Forbidden`: 権限がありません
- `404 Not Found`: フォルダが見つかりません
- `500 Internal Server Error`: 削除失敗

---

### 7. ファイル名変更

指定されたファイルの名前を変更します。

**エンドポイント**: `PATCH /facilities/{facility}/contract-documents/files/{file}/rename`

**Content-Type**: `application/json`

**パラメータ**:
```json
{
  "name": "新しいファイル名.pdf"
}
```

- `name` (required, string): 新しいファイル名（最大255文字）

**バリデーションルール**:
- ファイル名: 必須、最大255文字
- 同じフォルダ内に同名のファイルは作成不可

**レスポンス**:
```json
{
  "success": true,
  "message": "ファイル名を変更しました。",
  "data": {
    "file": {
      "id": 1,
      "original_name": "新しいファイル名.pdf",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

**エラーレスポンス**:
- `403 Forbidden`: 権限がありません
- `404 Not Found`: ファイルが見つかりません
- `422 Unprocessable Entity`: バリデーションエラー
- `500 Internal Server Error`: 変更失敗

---

### 8. フォルダ名変更

指定されたフォルダの名前を変更します。

**エンドポイント**: `PATCH /facilities/{facility}/contract-documents/folders/{folder}/rename`

**Content-Type**: `application/json`

**パラメータ**:
```json
{
  "name": "新しいフォルダ名"
}
```

- `name` (required, string): 新しいフォルダ名（最大255文字）

**バリデーションルール**:
- フォルダ名: 必須、最大255文字
- 同じ親フォルダ内に同名のフォルダは作成不可

**レスポンス**:
```json
{
  "success": true,
  "message": "フォルダ名を変更しました。",
  "data": {
    "folder": {
      "id": 1,
      "name": "新しいフォルダ名",
      "path": "/契約書/新しいフォルダ名",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

**エラーレスポンス**:
- `403 Forbidden`: 権限がありません
- `404 Not Found`: フォルダが見つかりません
- `422 Unprocessable Entity`: バリデーションエラー
- `500 Internal Server Error`: 変更失敗

---

## 共通エラーレスポンス

すべてのエンドポイントで以下の形式のエラーレスポンスが返される可能性があります：

```json
{
  "success": false,
  "message": "エラーメッセージ",
  "errors": {
    "field_name": [
      "バリデーションエラーメッセージ"
    ]
  }
}
```

## HTTPステータスコード

| コード | 説明 |
|-------|------|
| 200 | 成功 |
| 201 | 作成成功 |
| 400 | 不正なリクエスト |
| 401 | 認証エラー |
| 403 | 権限エラー |
| 404 | リソースが見つからない |
| 422 | バリデーションエラー |
| 500 | サーバーエラー |

## レート制限

現在、レート制限は設定されていません。将来的に実装される可能性があります。

## 使用例

### cURLでのファイルアップロード

```bash
curl -X POST \
  https://example.com/facilities/1/contract-documents/upload \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -F 'file=@/path/to/file.pdf' \
  -F 'folder_id=1'
```

### JavaScriptでのファイルアップロード

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('folder_id', 1);

const response = await fetch('/facilities/1/contract-documents/upload', {
  method: 'POST',
  headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: formData
});

const result = await response.json();
console.log(result);
```

### JavaScriptでのフォルダ作成

```javascript
const response = await fetch('/facilities/1/contract-documents/folders', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({
    name: '新しいフォルダ',
    parent_id: 1
  })
});

const result = await response.json();
console.log(result);
```

### JavaScriptでのファイル削除

```javascript
const response = await fetch('/facilities/1/contract-documents/files/1', {
  method: 'DELETE',
  headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  }
});

const result = await response.json();
console.log(result);
```

## セキュリティ考慮事項

1. **認証**: すべてのリクエストで有効な認証トークンが必要
2. **認可**: ユーザーの権限に基づいてアクセス制御
3. **CSRF保護**: POSTリクエストではCSRFトークンが必要
4. **ファイルバリデーション**: アップロードファイルのサイズと形式を検証
5. **パストラバーサル対策**: ファイルパスのサニタイゼーション

## バージョニング

現在のAPIバージョン: v1

将来的にAPIに破壊的変更が加えられる場合は、新しいバージョンが作成されます。

## サポート

APIに関する質問や問題がある場合は、開発チームにお問い合わせください。
