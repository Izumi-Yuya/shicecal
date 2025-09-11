# 実装計画

## 完了済みタスク

- [x] 1. 共通レイアウトコンポーネントの作成
  - FacilityEditLayoutコンポーネントを作成し、ヘッダー、パンくずリスト、施設情報カード、フォームアクションを含む標準レイアウト構造を実装
  - FormSectionコンポーネントを作成し、アイコン付きヘッダーとカードベースのセクション表示を実装
  - FacilityInfoCardコンポーネントを作成し、施設名、住所、タイプを表示する統一カードを実装
  - FormActionsコンポーネントを作成し、キャンセルと保存ボタンの標準レイアウトを実装
  - FormErrorsコンポーネントとFieldErrorコンポーネントを作成し、エラー表示を統一
  - _要件: 2.1, 2.2, 2.3_

- [x] 2. CSS スタイリングシステムの実装
  - facility-form.cssファイルを作成し、CSS変数とレスポンシブデザインを定義
  - 既存のland-info.cssと統合し、一貫したスタイリングを適用
  - モバイル対応のメディアクエリとタッチフレンドリーなインタラクションを実装
  - アクセシビリティ対応のスタイリング（フォーカス管理、色のコントラスト）を実装
  - _要件: 1.1, 1.3, 5.1, 5.2, 5.3_

- [x] 3. JavaScript機能の実装
  - facility-form-layout.jsモジュールを作成し、折りたたみ機能とフォームバリデーションを実装
  - レスポンシブ機能とモバイル最適化のJavaScriptを実装
  - アクセシビリティ機能（キーボードナビゲーション、スクリーンリーダー対応）を実装
  - 既存のland-info.jsと統合し、共通機能を抽出
  - _要件: 1.2, 5.1, 5.2, 5.3_

- [x] 4. 土地情報編集フォームの更新
  - land-info/edit.blade.phpを新しいFacilityEditLayoutコンポーネントを使用するように更新
  - 各セクション（基本情報、面積情報、自社物件情報、賃借物件情報、管理会社情報、オーナー情報、関連書類）をFormSectionコンポーネントで包装
  - 既存の機能を保持しながら新しいレイアウト構造に適応
  - _要件: 3.1, 3.2, 3.3_

- [x] 5. 基本情報編集フォームの更新
  - basic-info/edit.blade.phpを新しいFacilityEditLayoutコンポーネントを使用するように更新
  - 各セクション（基本情報、住所・連絡先、開設・建物情報、施設情報、サービス情報）をFormSectionコンポーネントで包装
  - 既存の機能を保持しながら新しいレイアウト構造に適応
  - _要件: 1.1, 1.2, 3.1_

- [x] 6. 設定ファイルとヘルパーの作成
  - config/facility-form.phpを作成し、アイコン、色、レイアウト設定を定義
  - FacilityFormHelperクラスを作成し、パンくずリスト生成やセクション設定のヘルパー関数を実装
  - エラーフィールドマッピング機能を実装し、セクションレベルのエラー表示を支援
  - _要件: 2.2, 4.2_

- [x] 7. バリデーションとエラーハンドリングの統合
  - 共通エラー表示コンポーネントを作成し、フォームレベルとフィールドレベルのエラーを処理
  - 既存のLandInfoRequestバリデーションと統合し、新しいレイアウトでエラー表示を確認
  - セクションレベルのエラーインジケーターを実装
  - _要件: 3.3, 1.3_

- [x] 8. アクセシビリティ対応の実装
  - すべてのコンポーネントに適切なARIA属性とロールを追加
  - キーボードナビゲーションとスクリーンリーダー対応を実装
  - 色のコントラストとフォーカス管理を確保
  - スキップリンクとライブリージョンを実装
  - _要件: 5.2, 5.3_

- [x] 9. テストの作成と実行
  - FacilityEditLayoutTest.phpを作成し、コンポーネントのレンダリングとプロパティ受け渡しをテスト
  - FacilityFormLayoutTest.phpを作成し、土地情報編集フォームの統合テストを実装
  - レスポンシブデザインとアクセシビリティのテストを作成
  - 既存のテストが新しいレイアウトで正常に動作することを確認
  - _要件: 4.1, 4.2, 4.3_

- [x] 10. ドキュメントとガイドラインの作成
  - コンポーネント使用ガイドを作成し、開発者向けドキュメントを整備
  - 新しい編集フォーム作成時のベストプラクティスを文書化
  - 既存フォームの移行ガイドを作成
  - _要件: 2.1, 2.3, 4.2_

## 実装状況

すべてのタスクが完了しています。施設フォームレイアウト標準化システムは以下の要素で構成されています：

### 実装済みコンポーネント
- `resources/views/components/facility/edit-layout.blade.php` - メインレイアウトコンポーネント
- `resources/views/components/facility/info-card.blade.php` - 施設情報カード
- `resources/views/components/form/section.blade.php` - フォームセクション
- `resources/views/components/form/actions.blade.php` - フォームアクション

### 実装済みスタイリング
- `resources/css/components/facility-form.css` - 完全なCSS実装（1480行）
- レスポンシブデザイン、アクセシビリティ、モバイル最適化を含む

### 実装済みJavaScript
- `resources/js/modules/facility-form-layout.js` - 完全なJS実装（1563行）
- 折りたたみ機能、バリデーション、アクセシビリティ機能を含む

### 実装済み設定とヘルパー
- `config/facility-form.php` - 設定ファイル
- `app/Helpers/FacilityFormHelper.php` - ヘルパークラス

### 更新済みフォーム
- `resources/views/facilities/land-info/edit.blade.php` - 新レイアウト使用
- `resources/views/facilities/basic-info/edit.blade.php` - 新レイアウト使用

### 実装済みテスト
- `tests/Feature/Components/FacilityEditLayoutTest.php` - コンポーネントテスト
- `tests/Feature/FacilityFormLayoutTest.php` - 統合テスト

### 実装済みドキュメント
- `docs/components/facility-form-layout-components.md` - コンポーネント使用ガイド
- `docs/development/facility-form-developer-guide.md` - 開発者ガイド
- `docs/development/facility-form-best-practices.md` - ベストプラクティス
- `docs/migration/facility-form-migration-guide.md` - 移行ガイド

## 次のステップ

このスペックは完了しています。システムは本番環境で使用可能な状態です。新しい編集フォームを作成する際は、実装済みのコンポーネントとドキュメントを参照してください。