# Requirements Document

## Introduction

Shise-Cal（施設カルテシステム）が複雑化してきたため、シンプルな構造にリファクタリングします。現在の13個のコントローラー、8個のサービスクラス、複雑なルート構造を整理し、保守性と可読性を向上させます。機能を統合し、重複を排除し、一貫性のあるアーキテクチャに再構築します。

## Requirements

### Requirement 1

**User Story:** 開発者として、コントローラーの数を削減したい、保守性を向上させるため

#### Acceptance Criteria

1. WHEN 現在の13個のコントローラーを分析する THEN システムは機能的に関連するコントローラーを特定する
2. WHEN 関連するコントローラーを統合する THEN システムは以下の統合を実施する：
   - FacilityController + LandInfoController → FacilityController（土地情報を施設管理に統合）
   - CommentController + FacilityCommentController → CommentController（コメント機能を統一）
   - PdfExportController + CsvExportController → ExportController（出力機能を統合）
3. WHEN コントローラーを統合する THEN システムは既存の機能を維持する
4. WHEN 統合後のコントローラー数を確認する THEN システムは8個以下のコントローラーになる

### Requirement 2

**User Story:** 開発者として、サービスクラスを整理したい、責任の分離を明確にするため

#### Acceptance Criteria

1. WHEN 現在の8個のサービスクラスを分析する THEN システムは各サービスの責任範囲を明確化する
2. WHEN 関連するサービスを統合する THEN システムは以下の統合を実施する：
   - LandInfoService + LandCalculationService → FacilityService（施設関連処理を統合）
   - BatchPdfService + SecurePdfService → ExportService（出力処理を統合）
3. WHEN サービスクラスを統合する THEN システムは単一責任原則を維持する
4. WHEN 統合後のサービス数を確認する THEN システムは5個以下のサービスクラスになる

### Requirement 3

**User Story:** 開発者として、ルート構造を簡素化したい、理解しやすいURL構造にするため

#### Acceptance Criteria

1. WHEN 現在のルート定義を分析する THEN システムは重複や不整合なルートを特定する
2. WHEN ルートを整理する THEN システムは以下のRESTfulな構造に統一する：
   - `/facilities` - 施設管理（基本情報・土地情報を含む）
   - `/export` - 出力機能（CSV・PDF）
   - `/comments` - コメント管理
   - `/maintenance` - 修繕履歴
   - `/notifications` - 通知
   - `/my-page` - マイページ
   - `/admin` - 管理機能
3. WHEN ルートを統合する THEN システムは既存のURL互換性を可能な限り維持する
4. WHEN ルート数を確認する THEN システムは現在の50%以下のルート数になる

### Requirement 4

**User Story:** 開発者として、ディレクトリ構造を整理したい、ファイルの場所を予測しやすくするため

#### Acceptance Criteria

1. WHEN 現在のディレクトリ構造を分析する THEN システムは機能別の整理方針を決定する
2. WHEN ビューファイルを整理する THEN システムは以下の構造に統一する：
   - `resources/views/facilities/` - 施設関連（基本情報・土地情報・詳細）
   - `resources/views/export/` - 出力関連（CSV・PDF）
   - `resources/views/comments/` - コメント関連
   - `resources/views/shared/` - 共通コンポーネント
3. WHEN CSSファイルを整理する THEN システムは機能別に分割し、共通スタイルを抽出する
4. WHEN JavaScriptファイルを整理する THEN システムは機能別モジュールに分割し、共通処理を抽出する

### Requirement 5

**User Story:** 開発者として、重複コードを排除したい、保守コストを削減するため

#### Acceptance Criteria

1. WHEN 現在のコードベースを分析する THEN システムは重複する処理を特定する
2. WHEN 重複する認証・認可処理を統合する THEN システムは共通のミドルウェアまたはトレイトに集約する
3. WHEN 重複するバリデーション処理を統合する THEN システムは共通のFormRequestクラスに集約する
4. WHEN 重複するビューコンポーネントを統合する THEN システムは再利用可能なBladeコンポーネントに集約する
5. WHEN 重複するJavaScript処理を統合する THEN システムは共通のユーティリティ関数に集約する

### Requirement 6

**User Story:** 開発者として、設定ファイルを整理したい、設定の管理を簡素化するため

#### Acceptance Criteria

1. WHEN 現在の設定ファイルを分析する THEN システムは不要または重複する設定を特定する
2. WHEN 設定を統合する THEN システムは機能別に設定をグループ化する
3. WHEN 環境別設定を整理する THEN システムは`.env`ファイルの項目を最小限に削減する
4. WHEN 設定の命名を統一する THEN システムは一貫した命名規則を適用する

### Requirement 7

**User Story:** 開発者として、テストファイルを整理したい、テストの実行と保守を効率化するため

#### Acceptance Criteria

1. WHEN 現在のテストファイルを分析する THEN システムは重複や不整合なテストを特定する
2. WHEN テストを統合する THEN システムは機能別にテストファイルを再編成する
3. WHEN 共通テストロジックを抽出する THEN システムは基底テストクラスまたはトレイトに集約する
4. WHEN テストデータを整理する THEN システムはFactoryとSeederを統合・最適化する

### Requirement 8

**User Story:** 開発者として、データベース構造を最適化したい、パフォーマンスと保守性を向上させるため

#### Acceptance Criteria

1. WHEN 現在のマイグレーションファイルを分析する THEN システムは不要または重複するマイグレーションを特定する
2. WHEN マイグレーションを整理する THEN システムは論理的な順序で再編成する
3. WHEN インデックスを最適化する THEN システムは実際のクエリパターンに基づいてインデックスを見直す
4. WHEN 外部キー制約を整理する THEN システムは一貫した制約ルールを適用する

### Requirement 9

**User Story:** 開発者として、ドキュメントを整理したい、プロジェクトの理解を容易にするため

#### Acceptance Criteria

1. WHEN 現在のドキュメントを分析する THEN システムは古い・重複・不正確なドキュメントを特定する
2. WHEN ドキュメントを統合する THEN システムは以下の構造に整理する：
   - `README.md` - プロジェクト概要・セットアップ
   - `docs/architecture.md` - アーキテクチャ概要
   - `docs/api.md` - API仕様
   - `docs/deployment.md` - デプロイメント手順
3. WHEN コードコメントを整理する THEN システムは必要最小限の有用なコメントのみを残す
4. WHEN 変更履歴を整理する THEN システムは`CHANGELOG.md`を作成し、重要な変更を記録する

### Requirement 10

**User Story:** 開発者として、BladeテンプレートからCSSとJavaScriptを分離したい、コードの可読性と保守性を向上させるため

#### Acceptance Criteria

1. WHEN 現在のBladeファイルを分析する THEN システムは`<style>`タグと`<script>`タグを含むファイルを特定する
2. WHEN インラインCSSを分離する THEN システムは以下の処理を実施する：
   - `@push('styles')`内の`<style>`タグを専用CSSファイルに移動
   - 機能別にCSSファイルを作成（例：`resources/css/facilities.css`, `resources/css/notifications.css`）
   - Viteの設定を更新してCSSファイルをビルドプロセスに含める
3. WHEN インラインJavaScriptを分離する THEN システムは以下の処理を実施する：
   - `@push('scripts')`内の`<script>`タグを専用JSファイルに移動
   - 機能別にJavaScriptファイルを作成（例：`resources/js/facilities.js`, `resources/js/notifications.js`）
   - ES6モジュール形式でJavaScriptを構造化
4. WHEN 分離後のファイルを確認する THEN システムは以下の条件を満たす：
   - Bladeファイル内のインラインCSSが90%以上削減される
   - Bladeファイル内のインラインJavaScriptが90%以上削減される
   - 各機能のCSSとJavaScriptが独立したファイルに整理される
5. WHEN 共通処理を抽出する THEN システムは以下の処理を実施する：
   - 複数のファイルで使用される共通CSSを`resources/css/shared.css`に集約
   - 複数のファイルで使用される共通JavaScript関数を`resources/js/utils.js`に集約
   - Bootstrap拡張やカスタムコンポーネントを`resources/css/components.css`に整理

### Requirement 11

**User Story:** 開発者として、依存関係を最適化したい、プロジェクトのサイズと複雑さを削減するため

#### Acceptance Criteria

1. WHEN 現在の依存関係を分析する THEN システムは不要または重複するパッケージを特定する
2. WHEN 依存関係を削減する THEN システムは使用されていないComposerパッケージを削除する
3. WHEN 依存関係を削減する THEN システムは使用されていないnpmパッケージを削除する
4. WHEN 依存関係のバージョンを統一する THEN システムは互換性のある最新バージョンに更新する
5. WHEN セキュリティ脆弱性を確認する THEN システムは`composer audit`と`npm audit`を実行し、問題を解決する