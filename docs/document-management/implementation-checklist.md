# ドキュメント管理システム実装チェックリスト

## 現在の実装状況

### ✅ 完了項目

#### ルート分離
- ✅ メインドキュメント: `/facilities/{facility}/documents/*`
- ✅ ライフライン設備: `/facilities/{facility}/lifeline-documents/*`
- ✅ 修繕履歴: `/facilities/{facility}/maintenance-documents/*`

#### コントローラー分離
- ✅ `DocumentController` - メインドキュメント管理
- ✅ `LifelineDocumentController` - ライフライン設備ドキュメント
- ✅ `MaintenanceDocumentController` - 修繕履歴ドキュメント

#### サービス層分離
- ✅ `DocumentService` - メインドキュメント処理
- ✅ `LifelineDocumentService` - ライフライン設備処理
- ✅ `MaintenanceDocumentService` - 修繕履歴処理

#### JavaScript分離
- ✅ `DocumentManager` - メインドキュメントUI
- ✅ `LifelineDocumentManager` - ライフライン設備UI
- ✅ `MaintenanceDocumentManager` - 修繕履歴UI

#### ビュー分離
- ✅ `resources/views/facilities/documents/` - メイン
- ✅ `resources/views/components/lifeline-equipment-documents.blade.php` - ライフライン
- ✅ `resources/views/components/maintenance-document-manager.blade.php` - 修繕履歴

### 🔄 確認が必要な項目

#### データベーステーブル分離
- [ ] `document_folders` テーブルの存在確認
- [ ] `document_files` テーブルの存在確認
- [ ] `lifeline_equipment_folders` テーブルの存在確認
- [ ] `lifeline_equipment_files` テーブルの存在確認
- [ ] `maintenance_folders` テーブルの存在確認
- [ ] `maintenance_files` テーブルの存在確認

#### モデル分離
- [ ] `DocumentFolder` モデルの存在確認
- [ ] `DocumentFile` モデルの存在確認
- [ ] ライフライン設備用モデルの確認
- [ ] 修繕履歴用モデルの確認

#### ストレージパス分離
- [ ] メインドキュメント: `storage/app/public/facility_{id}/`
- [ ] ライフライン: `storage/app/public/lifeline/{category}/`
- [ ] 修繕履歴: `storage/app/public/maintenance/{category}/`

#### ポリシー分離
- [ ] `DocumentFolderPolicy` の存在確認
- [ ] `DocumentFilePolicy` の存在確認
- [ ] ライフライン設備用ポリシーの確認
- [ ] 修繕履歴用ポリシーの確認

### ❌ 未実装項目

#### テスト
- [ ] メインドキュメント管理の機能テスト
- [ ] ライフライン設備ドキュメントの機能テスト
- [ ] 修繕履歴ドキュメントの機能テスト
- [ ] 統合テスト（3システムの独立性確認）

#### ドキュメント
- [x] 分離戦略ドキュメント
- [ ] API仕様書（各システム別）
- [ ] ユーザーマニュアル（各システム別）

## 実装確認手順

### 1. データベーステーブルの確認

```bash
# マイグレーションファイルの確認
ls -la database/migrations/*document*.php

# テーブル構造の確認
php artisan tinker --execute="
Schema::hasTable('document_folders') ? 'document_folders: OK' : 'document_folders: NG';
Schema::hasTable('document_files') ? 'document_files: OK' : 'document_files: NG';
"
```

### 2. モデルの確認

```bash
# モデルファイルの存在確認
ls -la app/Models/Document*.php

# モデルの動作確認
php artisan tinker --execute="
class_exists('App\Models\DocumentFolder') ? 'DocumentFolder: OK' : 'DocumentFolder: NG';
class_exists('App\Models\DocumentFile') ? 'DocumentFile: OK' : 'DocumentFile: NG';
"
```

### 3. ルートの確認

```bash
# メインドキュメント管理のルート
php artisan route:list --name=documents

# ライフライン設備のルート
php artisan route:list --name=lifeline-documents

# 修繕履歴のルート
php artisan route:list --name=maintenance-documents
```

### 4. ストレージディレクトリの確認

```bash
# ストレージ構造の確認
tree storage/app/public/ -L 3
```

### 5. JavaScriptクラスの動作確認

ブラウザのコンソールで以下を実行：

```javascript
// メインドキュメント管理
console.log('DocumentManager:', typeof DocumentManager);

// ライフライン設備
console.log('LifelineDocumentManager:', typeof LifelineDocumentManager);

// 修繕履歴
console.log('MaintenanceDocumentManager:', typeof MaintenanceDocumentManager);

// インスタンスの確認
console.log('documentManager:', window.documentManager);
console.log('lifelineDocumentManager:', window.shiseCalApp?.modules);
```

## 分離の検証項目

### データの独立性
- [ ] メインドキュメントのフォルダ/ファイルがライフライン設備に表示されない
- [ ] ライフライン設備のドキュメントが修繕履歴に表示されない
- [ ] 修繕履歴のドキュメントがメインドキュメントに表示されない

### 機能の独立性
- [ ] メインドキュメントでフォルダ作成しても他システムに影響しない
- [ ] ライフライン設備でファイルアップロードしても他システムに影響しない
- [ ] 修繕履歴でファイル削除しても他システムに影響しない

### UIの独立性
- [ ] 各システムが独自のUIコンポーネントを持つ
- [ ] モーダルやボタンのIDが重複しない
- [ ] JavaScriptイベントが他システムに干渉しない

### パフォーマンスの独立性
- [ ] メインドキュメントの大量データが他システムに影響しない
- [ ] 各システムが独立してキャッシュを管理できる
- [ ] 各システムが独立してページネーションを実装できる

## 問題が発生した場合の対処

### データが混在している場合

**症状**: 異なるシステムのドキュメントが表示される

**原因**: データベーステーブルまたはストレージパスが共有されている

**対処**:
1. テーブル構造を確認
2. 外部キー制約を確認
3. ストレージパスを確認
4. コントローラーのクエリを確認

### UIが干渉している場合

**症状**: ボタンクリックで意図しないモーダルが開く

**原因**: HTML要素のIDが重複している

**対処**:
1. モーダルIDにプレフィックスを追加（例: `main-`, `lifeline-`, `maintenance-`）
2. JavaScriptのイベントリスナーをスコープ内に限定
3. CSSセレクタを具体的にする

### JavaScriptエラーが発生する場合

**症状**: コンソールに「undefined」エラーが表示される

**原因**: グローバル変数の競合または初期化順序の問題

**対処**:
1. 各システムのJavaScriptを名前空間で分離
2. 初期化順序を確認
3. 依存関係を明確にする

## 今後の改善計画

### 短期（1-2週間）
1. [ ] データベーステーブルの完全分離確認
2. [ ] モデルとポリシーの実装確認
3. [ ] 基本的な機能テストの作成

### 中期（1-2ヶ月）
1. [ ] 包括的な機能テストの実装
2. [ ] パフォーマンステストの実施
3. [ ] ユーザーマニュアルの作成

### 長期（3-6ヶ月）
1. [ ] 各システムの独立した機能強化
2. [ ] 高度な検索・フィルタリング機能
3. [ ] レポート機能の追加

## まとめ

現在の実装状況：
- ✅ **ルート分離**: 完了
- ✅ **コントローラー分離**: 完了
- ✅ **サービス層分離**: 完了
- ✅ **JavaScript分離**: 完了
- ✅ **ビュー分離**: 完了
- 🔄 **データベース分離**: 確認中
- 🔄 **モデル分離**: 確認中
- ❌ **テスト**: 未実装

次のステップ：
1. データベーステーブルとモデルの確認
2. ポリシーの実装確認
3. 基本的な機能テストの作成
4. 統合テストによる独立性の検証
