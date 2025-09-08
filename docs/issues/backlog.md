# 問題バックログ

未解決の問題一覧です。優先度順に並べています。

## Critical 優先度

現在、Critical レベルの問題はありません。

## High 優先度

### ISSUE-2025-09-08-001: FacilityComment モデルの機能不足
- **カテゴリ**: Models
- **ステータス**: Open
- **発見日**: 2025-09-08
- **概要**: FacilityComment モデルに status, assigned_to フィールドが不足しており、コメントのワークフロー機能が実装できない
- **影響**: コメントの割り当て機能、ステータス管理機能が使用不可
- **関連ファイル**: 
  - `app/Models/FacilityComment.php`
  - `database/migrations/2025_09_03_172553_create_facility_comments_table.php`
  - `tests/Unit/Services/NotificationServiceTest.php`

### ISSUE-2025-09-08-002: テスト分離の問題
- **カテゴリ**: Testing
- **ステータス**: Open
- **発見日**: 2025-09-08
- **概要**: NotificationServiceTest でテスト間のデータ分離ができておらず、テストが不安定
- **影響**: CI/CDでのテスト実行が不安定になる可能性
- **関連ファイル**: 
  - `tests/Unit/Services/NotificationServiceTest.php`

## Medium 優先度

### ISSUE-2025-09-08-003: 非アクティブユーザーのログイン制御
- **カテゴリ**: Authentication
- **ステータス**: Open
- **発見日**: 2025-09-08
- **概要**: 非アクティブユーザーのログイン制御が実装されていない
- **影響**: セキュリティ上のリスク
- **関連ファイル**: 
  - `app/Http/Controllers/Auth/LoginController.php`
  - `tests/Feature/AuthenticationTest.php`

### ISSUE-2025-09-08-004: Comment と FacilityComment の役割重複
- **カテゴリ**: Models
- **ステータス**: Open
- **発見日**: 2025-09-08
- **概要**: Comment モデルと FacilityComment モデルが混在しており、役割が不明確
- **影響**: 開発効率の低下、バグの原因
- **関連ファイル**: 
  - `app/Models/Comment.php`
  - `app/Models/FacilityComment.php`

## Low 優先度

### ISSUE-2025-09-08-005: テストカバレッジの向上
- **カテゴリ**: Testing
- **ステータス**: Open
- **発見日**: 2025-09-08
- **概要**: 一部のテストがスキップされており、テストカバレッジが不十分
- **影響**: 品質保証の観点で改善の余地
- **関連ファイル**: 各種テストファイル

### ISSUE-2025-09-08-006: パフォーマンス最適化
- **カテゴリ**: Performance
- **ステータス**: Open
- **発見日**: 2025-09-08
- **概要**: データベースクエリの最適化、キャッシュ戦略の見直しが必要
- **影響**: ユーザーエクスペリエンスの向上
- **関連ファイル**: 各種コントローラー、サービスクラス

## 新規問題追加時の手順

1. 上記テンプレートに従って問題を記録
2. 優先度に応じて適切なセクションに追加
3. 関連するカテゴリファイルにも記録
4. 必要に応じて `in-progress.md` に移動