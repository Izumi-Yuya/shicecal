# 契約書ドキュメント統合 - 実装タスク

## 実装タスク一覧

- [x] 1. Bladeビューファイルの修正
  - 契約書タブ表示画面（index.blade.php）に統一ドキュメント管理セクションを追加し、各サブタブのドキュメントセクションを削除する
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 1.1 統一ドキュメント管理セクションの追加
  - `resources/views/facilities/contracts/index.blade.php`のサブタブナビゲーション直前に統一ドキュメント管理セクションを追加
  - 折りたたみ可能なセクションとして実装（初期状態は折りたたまれている）
  - 展開/折りたたみボタンを配置
  - `contract-document-manager`コンポーネントを埋め込む
  - _Requirements: 1.1, 1.4, 1.5, 4.1, 4.2_

- [x] 1.2 サブタブ内ドキュメントセクションの削除
  - その他契約書タブの`#others-documents-section`を削除
  - 給食契約書タブの`#meal-service-documents-section`を削除
  - 駐車場契約書タブの`#parking-documents-section`を削除
  - 各セクションの折りたたみボタンとトグル処理を削除
  - _Requirements: 1.2_

- [x] 2. Bladeコンポーネントの修正
  - `contract-document-manager`コンポーネントを単一インスタンスとして動作するように修正する
  - _Requirements: 1.3, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

- [x] 2.1 コンポーネントIDの統一
  - すべてのHTML要素IDから`-{{ $category }}`接尾辞を削除
  - `document-management-container-{{ $category }}`を`contract-document-management-container`に変更
  - モーダルIDを統一（`create-folder-modal-contracts`など）
  - フォームIDを統一（`create-folder-form-contracts`など）
  - _Requirements: 1.3_

- [x] 2.2 カテゴリ属性の削除
  - `data-contract-category`属性を削除
  - カテゴリは固定値`'contracts'`として扱う
  - _Requirements: 1.3_

- [x] 2.3 初期化スクリプトの修正
  - コンポーネント内の初期化スクリプトを単一インスタンス用に修正
  - カテゴリパラメータを削除
  - グローバル変数名を`contractDocManager`に統一
  - _Requirements: 1.3_

- [x] 3. JavaScriptクラスの修正
  - `ContractDocumentManager`クラスを単一インスタンスとして動作するように修正する
  - _Requirements: 1.3, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

- [x] 3.1 コンストラクタの修正
  - `constructor(facilityId, category)`から`constructor(facilityId)`に変更
  - `this.category`を固定値`'contracts'`に設定
  - グローバル登録を`window.contractDocManager`に統一
  - _Requirements: 1.3_

- [x] 3.2 要素キャッシュの修正
  - `cacheElements()`メソッド内のすべてのID参照を更新
  - カテゴリ接尾辞を削除（例: `create-folder-btn-contracts`）
  - 統一されたIDを使用
  - _Requirements: 1.3_

- [x] 3.3 API呼び出しの確認
  - すべてのAPI呼び出しが正しいエンドポイントを使用していることを確認
  - `/facilities/${this.facilityId}/contract-documents`形式
  - カテゴリパラメータが不要であることを確認
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

- [x] 4. CSSスタイルの修正
  - 統一ドキュメント管理セクション用のスタイルを追加し、サブタブ固有のスタイルを削除する
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 5.1, 5.2, 5.3, 5.4_

- [x] 4.1 統一セクション用スタイルの追加
  - `.unified-contract-documents-section`スタイルを追加
  - `.unified-documents-toggle`ボタンスタイルを追加
  - 折りたたみアニメーションを追加
  - モーダルz-index修正を追加
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 4.2 サブタブ固有スタイルの削除
  - `#others-documents-section`関連スタイルを削除
  - `#meal-service-documents-section`関連スタイルを削除
  - `#parking-documents-section`関連スタイルを削除
  - カテゴリ別カラーテーマを削除
  - _Requirements: 1.2_

- [x] 4.3 レスポンシブスタイルの追加
  - モバイルデバイス用スタイル（768px以下）
  - タブレットデバイス用スタイル（768px-1024px）
  - デスクトップデバイス用スタイル（1024px以上）
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 4.4 アクセシビリティスタイルの追加
  - フォーカスインジケーター
  - 高コントラストモード対応
  - 動きを減らす設定対応
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 5. 折りたたみ機能の実装
  - 統一ドキュメント管理セクションの折りたたみ/展開機能を実装する
  - _Requirements: 1.4, 1.5, 4.3, 4.4, 7.2_

- [x] 5.1 折りたたみボタンの実装
  - Bootstrap Collapseを使用
  - `data-bs-toggle="collapse"`属性を設定
  - `data-bs-target="#unified-documents-section"`を設定
  - `aria-expanded`属性を設定
  - _Requirements: 1.4, 6.1, 6.2_

- [x] 5.2 ボタンテキストの動的変更
  - 折りたたまれている時: "ドキュメントを表示"
  - 展開されている時: "ドキュメントを非表示"
  - アイコンも連動して変更（folder-open ⇔ folder）
  - _Requirements: 4.4_

- [x] 5.3 初期状態の設定
  - 初期状態は折りたたまれている（`collapse`クラス）
  - `aria-expanded="false"`を設定
  - _Requirements: 1.5_

- [x] 5.4 展開時のドキュメント読み込み（遅延ロード）
  - 展開時に初めてドキュメント一覧を読み込む（遅延ロード）
  - `shown.bs.collapse`イベントをリッスン
  - 初回展開時のみAPIを呼び出す
  - ContractDocumentManagerクラスに遅延ロード機能を追加
  - _Requirements: 7.1, 7.2_

- [x] 6. モーダルz-index問題の修正
  - 折りたたみ領域内のモーダルが正しく表示されるようにz-indexを修正する
  - _Requirements: 1.1, 1.3_

- [x] 6.1 モーダルhoisting処理の実装
  - 折りたたみ内のモーダルを`<body>`直下に移動
  - `shown.bs.collapse`イベント時に実行
  - _Requirements: 1.1_

- [x] 6.2 z-index強制設定
  - `.modal-backdrop`を2000に設定
  - `.modal`を2010に設定
  - CSSで`!important`を使用
  - _Requirements: 1.1_

- [x] 6.3 overflow設定
  - `#unified-documents-section`に`overflow: visible`を設定
  - 折りたたみ領域がクリッピングコンテキストを作成しないようにする
  - _Requirements: 1.1_

- [x] 7. エラーハンドリングの実装
  - ドキュメント操作時のエラーハンドリングを実装する（ContractDocumentManagerクラスに既に実装済み）
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [x] 7.1 エラーメッセージの表示
  - ファイルアップロードエラー時のメッセージ表示
  - フォルダ作成エラー時のメッセージ表示
  - ファイル削除エラー時のメッセージ表示
  - ネットワークエラー時のメッセージ表示
  - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [x] 7.2 エラーログの記録
  - すべてのエラーをコンソールに記録
  - サーバーサイドでエラーログを記録
  - エラー発生時のコンテキスト情報を含める
  - _Requirements: 8.5_

- [x] 7.3 再試行機能の実装（オプション）
  - ネットワークエラー時に再試行ボタンを表示
  - 再試行回数の制限（最大3回）
  - 指数バックオフの実装
  - _Requirements: 8.4_

- [x] 8. 既存データの互換性確認（手動テスト）
  - 既存のドキュメントが統合後も正常にアクセスできることを確認する
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 8.1 既存ドキュメントの表示確認
  - 既存のフォルダが正しく表示される
  - 既存のファイルが正しく表示される
  - フォルダ階層が維持されている
  - _Requirements: 3.1, 3.2_

- [x] 8.2 既存ドキュメントの操作確認
  - 既存ファイルのダウンロードが動作する
  - 既存フォルダの開閉が動作する
  - 既存ファイルの削除が動作する
  - 既存フォルダの削除が動作する
  - _Requirements: 3.1, 3.4_

- [x] 8.3 メタデータの確認
  - アップロード日時が正しく表示される
  - アップロード者が正しく表示される
  - ファイルサイズが正しく表示される
  - _Requirements: 3.3_

- [x] 9. 統合テストの実装
  - 統合機能の動作を検証するテストを実装する
  - _Requirements: すべて_

- [x] 9.1 既存単体テストの動作確認
  - `ContractDocumentServiceTest`の既存テストが動作することを確認
  - `ContractDocumentControllerTest`の既存テストが動作することを確認
  - 新規テストは不要（既存機能の変更なし）
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

- [x] 9.2 統一セクション表示テストの実装
  - 統一ドキュメントセクションが表示されることを確認
  - サブタブ内にドキュメントセクションが存在しないことを確認
  - 折りたたみ機能が動作することを確認
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 9.3 ドキュメント操作テストの実装
  - 統一セクションからのフォルダ作成テスト
  - 統一セクションからのファイルアップロードテスト
  - 統一セクションからのファイル削除テスト
  - 既存ドキュメントへのアクセステスト
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 3.1, 3.2, 3.3, 3.4_

## 実装順序

1. **Phase 1: Bladeビューとコンポーネントの修正** (タスク 1, 2) ✅ 完了
   - 統一ドキュメント管理セクションの追加
   - サブタブ内ドキュメントセクションの削除
   - コンポーネントIDの統一

2. **Phase 2: JavaScriptとCSSの修正** (タスク 3, 4) ✅ 完了
   - JavaScriptクラスの修正
   - CSSスタイルの追加と削除

3. **Phase 3: 機能実装** (タスク 5, 6, 7) ✅ ほぼ完了（遅延ロードのみ残り）
   - 折りたたみ機能の実装
   - モーダルz-index問題の修正
   - エラーハンドリングの実装

4. **Phase 4: 検証とテスト** (タスク 8, 9) 🔄 進行中
   - 既存データの互換性確認
   - テストの実装と実行

## 実装状況サマリー

### 完了した項目 ✅
- 統一ドキュメント管理セクションの追加（折りたたみ機能付き）
- サブタブ内ドキュメントセクションの削除（該当セクションは元々存在しなかった）
- Bladeコンポーネントの単一インスタンス化
- JavaScriptクラスの単一インスタンス化
- CSSスタイルの追加（レスポンシブ、アクセシビリティ対応含む）
- モーダルz-index問題の修正（hoisting処理実装済み）
- エラーハンドリングの実装（ContractDocumentManagerクラスに実装済み）
- 折りたたみボタンのテキスト動的変更

### 残りの項目 🔄
- 遅延ロード機能の実装（展開時に初めてドキュメント読み込み）
- 既存データの互換性確認（手動テスト）
- 統合テストの実装

## 注意事項

- 既存のドキュメントデータは変更しない（データベースマイグレーション不要）
- 既存のAPI エンドポイントは変更しない
- 既存の`ContractDocumentService`と`ContractDocumentController`は変更しない
- モーダルz-index問題は既に修正済み（折りたたみ領域内のモーダル）
- レスポンシブデザインは実装済み（モバイル、タブレット、デスクトップ）
- アクセシビリティは実装済み（ARIA属性、キーボード操作、高コントラストモード対応）
- パフォーマンス最適化として遅延ロードの実装が推奨される
