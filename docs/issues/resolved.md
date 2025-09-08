# 解決済み問題一覧

解決済みの問題を記録しています。今後の参考や振り返りに活用します。

## 2025年9月8日 解決分

### ISSUE-2025-09-08-R001: FacilityTest でのモデル不整合
- **優先度**: High
- **カテゴリ**: Testing
- **解決日**: 2025-09-08
- **問題**: FacilityTest で Comment モデルを使用していたが、実際は FacilityComment モデルを使用すべき
- **解決方法**: 
  - `tests/Unit/Models/FacilityTest.php` で `Comment` を `FacilityComment` に変更
  - テストデータの作成方法を FacilityComment の構造に合わせて修正
- **変更ファイル**: 
  - `tests/Unit/Models/FacilityTest.php`
- **学んだこと**: モデルの統一性を保つことの重要性

### ISSUE-2025-09-08-R002: LandInfoPolicyTest での department 未設定
- **優先度**: High
- **カテゴリ**: Testing
- **解決日**: 2025-09-08
- **問題**: LandInfoPolicyTest でユーザーの department フィールドが設定されておらず、権限チェックが失敗
- **解決方法**: 
  - テストでユーザー作成時に適切な department を設定
  - 各ロールに応じた department の設定を追加
- **変更ファイル**: 
  - `tests/Unit/Policies/LandInfoPolicyTest.php`
- **学んだこと**: テストデータの完全性の重要性

### ISSUE-2025-09-08-R003: NotificationService でのモデル型不整合
- **優先度**: High
- **カテゴリ**: Models
- **解決日**: 2025-09-08
- **問題**: NotificationService が Comment モデルを期待していたが、FacilityComment が渡される
- **解決方法**: 
  - `app/Services/NotificationService.php` の型ヒントを FacilityComment に変更
  - 関連するフィールド名を FacilityComment の構造に合わせて修正
- **変更ファイル**: 
  - `app/Services/NotificationService.php`
  - `tests/Unit/Services/NotificationServiceTest.php`
- **学んだこと**: 型安全性の重要性

### ISSUE-2025-09-08-R004: AnnualConfirmationController の認可不備
- **優先度**: High
- **カテゴリ**: Authentication
- **解決日**: 2025-09-08
- **問題**: AnnualConfirmationController で管理者以外がアクセスできてしまう
- **解決方法**: 
  - create, store メソッドに管理者チェックを追加
  - 適切な 403 エラーレスポンスを実装
- **変更ファイル**: 
  - `app/Http/Controllers/AnnualConfirmationController.php`
- **学んだこと**: 認可チェックの重要性

### ISSUE-2025-09-08-R005: 年次確認機能のルート不足
- **優先度**: Medium
- **カテゴリ**: Deployment
- **解決日**: 2025-09-08
- **問題**: 年次確認機能で必要なルート（respond, resolve, facilities）が定義されていない
- **解決方法**: 
  - 不足しているルートを `routes/web.php` に追加
  - 適切な HTTP メソッドを設定
- **変更ファイル**: 
  - `routes/web.php`
- **学んだこと**: 機能実装時のルート定義の重要性

### ISSUE-2025-09-08-R006: CommentController でのモデル不整合
- **優先度**: High
- **カテゴリ**: Models
- **解決日**: 2025-09-08
- **問題**: CommentController が Comment モデルを使用していたが、FacilityComment を使用すべき
- **解決方法**: 
  - CommentController 全体を FacilityComment モデルに対応
  - フィールド名の変更（posted_by → user_id など）
  - 存在しないフィールド（status, assigned_to）の処理を修正
- **変更ファイル**: 
  - `app/Http/Controllers/CommentController.php`
  - `tests/Feature/CommentPostingTest.php`
- **学んだこと**: モデル変更時の影響範囲の広さ

### ISSUE-2025-09-08-R007: FacilityController でのリレーション不整合
- **優先度**: Medium
- **カテゴリ**: Models
- **解決日**: 2025-09-08
- **問題**: FacilityController で存在しない assignee リレーションを読み込もうとしていた
- **解決方法**: 
  - 存在しない assignee リレーションの読み込みを削除
  - FacilityComment の実際の構造に合わせて修正
- **変更ファイル**: 
  - `app/Http/Controllers/FacilityController.php`
- **学んだこと**: リレーション定義の一貫性の重要性

## 解決パターンの分析

### よくある問題パターン
1. **モデル不整合**: Comment と FacilityComment の混在
2. **テストデータ不備**: 必要なフィールドの未設定
3. **認可チェック不足**: セキュリティ上の問題
4. **ルート定義不足**: 機能実装時の見落とし

### 予防策
1. **モデル統一**: プロジェクト全体でのモデル使用方針の明確化
2. **テストデータ完全性**: ファクトリーでの必須フィールド設定
3. **認可チェックリスト**: 新機能実装時のセキュリティチェック
4. **ルート管理**: 機能実装時のルート定義チェックリスト