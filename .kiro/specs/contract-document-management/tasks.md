# Implementation Plan

- [x] 1. データベースとモデルの準備
  - DocumentFolderモデルにcontracts()スコープを追加
  - DocumentFileモデルにcontracts()スコープを追加
  - カテゴリ分離のテストを作成
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [x] 2. ContractDocumentServiceの実装
  - [x] 2.1 サービスクラスの基本構造を作成
    - ContractDocumentServiceクラスを作成
    - カテゴリ定数とデフォルトサブフォルダ定義を追加
    - 依存性注入（DocumentService, FileHandlingService, ActivityLogService）を設定
    - _Requirements: 10.1, 10.2_

  - [x] 2.2 ルートフォルダ管理メソッドを実装
    - getOrCreateCategoryRootFolder()メソッドを実装
    - createDefaultSubfolders()メソッドを実装
    - ルートフォルダ作成時にcategory='contracts'を設定
    - _Requirements: 2.1, 2.2, 2.3, 9.1_

  - [x] 2.3 ドキュメント一覧取得メソッドを実装
    - getCategoryDocuments()メソッドを実装
    - カテゴリスコープを使用してフォルダとファイルを取得
    - ページネーション処理を実装
    - パンくずリスト生成を実装
    - _Requirements: 1.1, 1.2, 9.3_

  - [x] 2.4 ファイルアップロードメソッドを実装
    - uploadCategoryFile()メソッドを実装
    - ファイルアップロード時にcategory='contracts'を設定
    - ファイルサイズとタイプのバリデーション
    - アクティビティログ記録
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 9.2_

  - [x] 2.5 フォルダ作成メソッドを実装
    - createCategoryFolder()メソッドを実装
    - フォルダ作成時にcategory='contracts'を設定
    - 親フォルダのカテゴリを継承
    - アクティビティログ記録
    - _Requirements: 2.1, 2.2, 9.1_

  - [x] 2.6 統計情報取得メソッドを実装
    - getCategoryStats()メソッドを実装
    - カテゴリスコープを使用してファイル数とサイズを集計
    - 最近のファイル一覧を取得
    - _Requirements: 6.2, 6.3_

  - [x] 2.7 検索メソッドを実装
    - searchCategoryFiles()メソッドを実装
    - カテゴリスコープを使用してファイルとフォルダを検索
    - 部分一致検索を実装
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 3. ContractDocumentControllerの実装
  - [x] 3.1 コントローラーの基本構造を作成
    - ContractDocumentControllerクラスを作成
    - HandlesApiResponsesトレイトを使用
    - ContractDocumentServiceを依存性注入
    - _Requirements: 10.5_

  - [x] 3.2 ドキュメント一覧取得エンドポイントを実装
    - index()メソッドを実装
    - FacilityContractポリシーのviewメソッドで認可チェック
    - ContractDocumentService::getCategoryDocuments()を呼び出し
    - JSON形式でレスポンスを返す
    - _Requirements: 1.1, 1.4_

  - [x] 3.3 ファイルアップロードエンドポイントを実装
    - uploadFile()メソッドを実装
    - FacilityContractポリシーのupdateメソッドで認可チェック
    - バリデーション（file, folder_id）
    - ContractDocumentService::uploadCategoryFile()を呼び出し
    - _Requirements: 1.5, 3.1, 3.2, 3.3_

  - [x] 3.4 フォルダ作成エンドポイントを実装
    - createFolder()メソッドを実装
    - FacilityContractポリシーのupdateメソッドで認可チェック
    - バリデーション（name, parent_id）
    - ContractDocumentService::createCategoryFolder()を呼び出し
    - _Requirements: 1.5, 2.1, 2.2_

  - [x] 3.5 ファイルダウンロードエンドポイントを実装
    - downloadFile()メソッドを実装
    - FacilityContractポリシーのviewメソッドで認可チェック
    - DocumentService::downloadFile()を呼び出し
    - _Requirements: 1.4, 4.1, 4.2, 4.3_

  - [x] 3.6 ファイル削除エンドポイントを実装
    - deleteFile()メソッドを実装
    - FacilityContractポリシーのupdateメソッドで認可チェック
    - DocumentService::deleteFile()を呼び出し
    - _Requirements: 1.5, 7.1, 7.2, 7.4_

  - [x] 3.7 フォルダ削除エンドポイントを実装
    - deleteFolder()メソッドを実装
    - FacilityContractポリシーのupdateメソッドで認可チェック
    - DocumentService::deleteFolder()を呼び出し
    - _Requirements: 1.5, 7.1, 7.3, 7.4_

  - [x] 3.8 ファイル名変更エンドポイントを実装
    - renameFile()メソッドを実装
    - FacilityContractポリシーのupdateメソッドで認可チェック
    - バリデーション（name）
    - DocumentService::renameFile()を呼び出し
    - _Requirements: 1.5, 8.1, 8.2, 8.4_

  - [x] 3.9 フォルダ名変更エンドポイントを実装
    - renameFolder()メソッドを実装
    - FacilityContractポリシーのupdateメソッドで認可チェック
    - バリデーション（name）
    - DocumentService::renameFolder()を呼び出し
    - _Requirements: 1.5, 8.1, 8.2, 8.4_

- [x] 4. ルート定義の追加
  - routes/web.phpに契約書ドキュメント管理のルートを追加
  - RESTful設計に従ったルート定義
  - ミドルウェア（auth）を適用
  - _Requirements: 10.5_

- [x] 5. Bladeコンポーネントの実装
  - [x] 5.1 contract-document-manager.blade.phpを作成
    - maintenance-document-manager.blade.phpをベースに作成
    - category='contracts'に対応
    - ツールバー（フォルダ作成、ファイルアップロード、検索）を実装
    - パンくずナビゲーションを実装
    - _Requirements: 1.2, 1.3, 10.3_

  - [x] 5.2 ドキュメント一覧表示を実装
    - リスト表示とグリッド表示の切り替え
    - フォルダとファイルの表示
    - ローディング表示
    - 空の状態表示
    - エラー表示
    - _Requirements: 1.1, 1.2_

  - [x] 5.3 モーダルを実装
    - フォルダ作成モーダル
    - ファイルアップロードモーダル
    - 名前変更モーダル
    - プロパティモーダル
    - モーダルhoisting処理を実装（折りたたみ領域対応）
    - _Requirements: 2.1, 3.1, 6.1, 8.1_

  - [x] 5.4 コンテキストメニューを実装
    - 右クリックメニュー
    - フォルダ専用メニュー項目
    - ファイル専用メニュー項目
    - 権限に応じたメニュー表示
    - _Requirements: 2.4, 4.1, 6.1, 7.1, 8.1_

- [x] 6. JavaScriptモジュールの実装
  - [x] 6.1 ContractDocumentManager.jsを作成
    - MaintenanceDocumentManager.jsをベースに作成
    - クラス構造を定義
    - ApiClientを使用したAPI通信
    - _Requirements: 10.4_

  - [x] 6.2 初期化とイベントリスナーを実装
    - コンストラクタでfacilityIdとcategoryを設定
    - setupEventListeners()メソッドを実装
    - ボタンクリックイベント
    - モーダルイベント
    - コンテキストメニューイベント
    - _Requirements: 1.2_

  - [x] 6.3 ドキュメント一覧読み込みを実装
    - loadDocuments()メソッドを実装
    - API呼び出し（GET /facilities/{facility}/contract-documents）
    - UI更新（フォルダとファイルの表示）
    - パンくずリスト更新
    - _Requirements: 1.1_

  - [x] 6.4 ファイルアップロードを実装
    - uploadFile()メソッドを実装
    - FormData作成
    - API呼び出し（POST /facilities/{facility}/contract-documents/upload）
    - 進行状況表示
    - UI更新
    - _Requirements: 3.1, 3.2_

  - [x] 6.5 フォルダ作成を実装
    - createFolder()メソッドを実装
    - API呼び出し（POST /facilities/{facility}/contract-documents/folders）
    - UI更新
    - _Requirements: 2.1, 2.2_

  - [x] 6.6 削除機能を実装
    - deleteFile()メソッドを実装
    - deleteFolder()メソッドを実装
    - 確認ダイアログ表示
    - API呼び出し（DELETE）
    - UI更新
    - _Requirements: 7.1, 7.2, 7.3_

  - [x] 6.7 名前変更機能を実装
    - rename()メソッドを実装
    - API呼び出し（PATCH）
    - UI更新
    - _Requirements: 8.1, 8.2_

  - [x] 6.8 検索機能を実装
    - search()メソッドを実装
    - API呼び出し（GET with query parameter）
    - 検索結果表示
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 6.9 プロパティ表示を実装
    - showProperties()メソッドを実装
    - ファイルとフォルダのプロパティ表示
    - _Requirements: 6.1, 6.2, 6.3_

- [x] 7. 契約書タブへの統合
  - [x] 7.1 contracts/index.blade.phpを更新
    - ドキュメント管理セクションを追加
    - contract-document-managerコンポーネントを配置
    - 既存の契約書表示との統合
    - _Requirements: 1.1_

  - [x] 7.2 CSSスタイルを追加
    - ドキュメント管理セクションのスタイル
    - 既存のlifeline-document-management.cssを参考
    - _Requirements: 1.3_

  - [x] 7.3 JavaScriptの読み込みを設定
    - app-unified.jsにContractDocumentManagerをインポート
    - 初期化処理を追加
    - _Requirements: 10.4_

- [x] 8. テストの実装
  - [x] 8.1 ContractDocumentServiceのユニットテストを作成
    - getOrCreateCategoryRootFolder()のテスト
    - getCategoryDocuments()のテスト
    - uploadCategoryFile()のテスト
    - createCategoryFolder()のテスト
    - getCategoryStats()のテスト
    - searchCategoryFiles()のテスト
    - _Requirements: 10.1, 10.2_

  - [x] 8.2 ContractDocumentControllerの機能テストを作成
    - ドキュメント一覧取得のテスト
    - ファイルアップロードのテスト
    - フォルダ作成のテスト
    - ファイルダウンロードのテスト
    - ファイル削除のテスト
    - フォルダ削除のテスト
    - 名前変更のテスト
    - 検索のテスト
    - 認可テスト
    - _Requirements: 1.4, 1.5_

  - [x] 8.3 カテゴリ分離のテストを作成
    - DocumentFolderのcontracts()スコープテスト
    - DocumentFileのcontracts()スコープテスト
    - カテゴリ間のデータ分離テスト
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

  - [x] 8.4 統合テストを作成
    - 完全なドキュメント管理ワークフローのテスト
    - 契約書と修繕履歴のカテゴリ分離テスト
    - フォルダ階層管理のテスト
    - _Requirements: 9.4_

- [x] 9. ドキュメントとガイドの作成
  - 契約書ドキュメント管理の使用方法ドキュメントを作成
  - 開発者向けの実装ガイドを作成
  - APIドキュメントを作成
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [x] 10. 動作確認とデバッグ
  - ローカル環境での動作確認
  - ブラウザコンソールでのエラーチェック
  - ネットワークタブでのAPI通信確認
  - 各種ブラウザでの動作確認
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_
