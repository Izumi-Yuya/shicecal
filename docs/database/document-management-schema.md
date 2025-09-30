# ドキュメント管理システム データベース設計

## 概要

施設ドキュメント管理システムのためのデータベーススキーマ設計と既存システムとの互換性について説明します。

## 新規テーブル

### document_folders テーブル

階層的なフォルダ構造を管理するためのテーブル。

```sql
CREATE TABLE document_folders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    facility_id BIGINT UNSIGNED NOT NULL COMMENT '施設ID',
    parent_id BIGINT UNSIGNED NULL COMMENT '親フォルダID',
    name VARCHAR(255) NOT NULL COMMENT 'フォルダ名',
    path TEXT NOT NULL COMMENT 'フォルダパス',
    created_by BIGINT UNSIGNED NOT NULL COMMENT '作成者ID',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES document_folders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);
```

#### インデックス設計
- `idx_document_folders_facility_id`: 施設別フォルダ検索用
- `idx_document_folders_parent_id`: 親フォルダ別検索用
- `idx_document_folders_facility_parent`: 施設・親フォルダ複合検索用
- `idx_document_folders_path`: パス検索用
- `idx_document_folders_created_by`: 作成者別検索用
- `unique_folder_name_per_parent`: 同一親フォルダ内でのフォルダ名重複防止

### document_files テーブル

ドキュメント管理システム専用のファイル情報を管理するテーブル。

```sql
CREATE TABLE document_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    facility_id BIGINT UNSIGNED NOT NULL COMMENT '施設ID',
    folder_id BIGINT UNSIGNED NULL COMMENT 'フォルダID（nullの場合はルートフォルダ）',
    original_name VARCHAR(255) NOT NULL COMMENT '元のファイル名',
    stored_name VARCHAR(255) NOT NULL COMMENT '保存時のファイル名',
    file_path TEXT NOT NULL COMMENT 'ファイルパス',
    file_size BIGINT UNSIGNED NOT NULL COMMENT 'ファイルサイズ（バイト）',
    mime_type VARCHAR(100) NOT NULL COMMENT 'MIMEタイプ',
    file_extension VARCHAR(10) NOT NULL COMMENT 'ファイル拡張子',
    uploaded_by BIGINT UNSIGNED NOT NULL COMMENT 'アップロード者ID',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
    FOREIGN KEY (folder_id) REFERENCES document_folders(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT
);
```

#### インデックス設計
- `idx_document_files_facility_id`: 施設別ファイル検索用
- `idx_document_files_folder_id`: フォルダ別ファイル検索用
- `idx_document_files_facility_folder`: 施設・フォルダ複合検索用
- `idx_document_files_extension`: ファイル拡張子別検索用
- `idx_document_files_created_at`: 作成日時ソート用
- `idx_document_files_uploaded_by`: アップロード者別検索用
- `idx_document_files_size`: ファイルサイズ集計用
- `idx_document_files_original_name`: ファイル名ソート用

## 既存システムとの互換性

### 既存 files テーブルとの関係

既存の `files` テーブルは以下の構造を持ちます：

```sql
files (
    id, facility_id, original_name, file_path, file_size, 
    mime_type, file_type, uploaded_by, created_at, updated_at, 
    land_document_type
)
```

#### 互換性の確保

1. **テーブル分離**: 新しいドキュメント管理システムは独立したテーブル（`document_files`）を使用
2. **データ重複なし**: 既存の `files` テーブルのデータは影響を受けない
3. **外部キー互換**: 両テーブルとも `facilities` と `users` テーブルを参照
4. **フィールド互換**: 類似フィールドは同じデータ型を使用

#### 主な違い

| 項目 | files テーブル | document_files テーブル |
|------|----------------|-------------------------|
| 用途 | 土地情報・ライフライン設備等 | ドキュメント管理システム専用 |
| フォルダ管理 | なし | folder_id で階層管理 |
| ファイル分類 | file_type enum | フォルダ構造で分類 |
| 保存ファイル名 | なし | stored_name で管理 |
| 拡張子管理 | なし | file_extension で明示的管理 |

### マイグレーション実行結果

```
✓ 2025_09_29_172019_create_document_folders_table - 10ms DONE
✓ 2025_09_29_172032_create_document_files_table - 6ms DONE
```

### 検証結果

- ✅ document_folders テーブル作成成功
- ✅ document_files テーブル作成成功  
- ✅ 外部キー制約正常動作
- ✅ インデックス設定完了
- ✅ 既存システムとの競合なし

## パフォーマンス最適化

### インデックス戦略

1. **複合インデックス**: 頻繁に使用される検索条件の組み合わせ
2. **単一インデックス**: ソートや個別検索用
3. **ユニーク制約**: データ整合性確保

### クエリ最適化

- N+1問題回避のためのEager Loading対応
- 大量データ対応のためのページネーション準備
- ファイルサイズ集計用インデックス

## セキュリティ考慮事項

### 外部キー制約

- `ON DELETE CASCADE`: 施設・フォルダ削除時の関連データ自動削除
- `ON DELETE RESTRICT`: ユーザー削除時の参照整合性保護

### データ整合性

- フォルダ名重複防止のユニーク制約
- 必須フィールドのNOT NULL制約
- 適切なデータ型とサイズ制限

## 今後の拡張性

### 予想される機能拡張

1. **ファイルバージョン管理**: document_file_versions テーブル追加
2. **アクセス権限管理**: document_permissions テーブル追加
3. **タグ機能**: document_tags, document_file_tags テーブル追加
4. **全文検索**: 検索インデックステーブル追加

### 設計上の配慮

- JSON型フィールドの将来的な追加余地
- メタデータ拡張のための柔軟な設計
- 大容量ファイル対応のためのストレージ分離準備

## 運用上の注意点

### バックアップ戦略

- ファイルデータとメタデータの同期バックアップ必須
- フォルダ階層の整合性確保

### メンテナンス

- 定期的なインデックス最適化
- 不要ファイルの自動削除機能
- ストレージ使用量監視

## 実装日時

- 作成日: 2025年9月29日
- マイグレーション実行: 2025年9月29日 17:20
- 検証完了: 2025年9月29日 17:25