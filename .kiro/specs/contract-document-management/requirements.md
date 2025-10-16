# Requirements Document

## Introduction

契約書管理システムにドキュメント管理機能を追加し、契約書関連のファイル（契約書PDF、見積書、請求書など）を体系的に管理できるようにします。既存のライフライン設備や修繕履歴で実装されているドキュメント管理機能と同様の仕組みを、契約書カテゴリーに適用します。

## Glossary

- **System**: 施設管理システム（Shise-Cal）
- **Contract Document Management**: 契約書ドキュメント管理機能
- **Document Manager Component**: ドキュメント管理UIコンポーネント
- **Contract Service**: 契約書データ処理サービス
- **Document Service**: ドキュメント処理サービス
- **Folder**: ドキュメントを整理するためのフォルダ
- **File**: アップロードされたドキュメントファイル
- **Category**: ドキュメントのカテゴリ（contracts）
- **User**: システムを利用するユーザー
- **Facility**: 施設

## Requirements

### Requirement 1

**User Story:** 契約書管理者として、契約書関連のファイルを一元管理したいので、契約書タブにドキュメント管理機能が必要です

#### Acceptance Criteria

1. WHEN 契約書タブを表示する時、THE System SHALL 契約書ドキュメント管理セクションを表示する
2. WHEN ドキュメント管理セクションを表示する時、THE System SHALL フォルダ作成、ファイルアップロード、検索機能を提供する
3. WHEN ドキュメント管理セクションを表示する時、THE System SHALL 既存のライフライン設備や修繕履歴と同じUIパターンを使用する
4. WHEN ユーザーが編集権限を持たない時、THE System SHALL ドキュメントの閲覧のみを許可する
5. WHEN ユーザーが編集権限を持つ時、THE System SHALL ドキュメントの作成、編集、削除を許可する

### Requirement 2

**User Story:** 契約書管理者として、契約書関連ファイルをフォルダで整理したいので、フォルダ作成・管理機能が必要です

#### Acceptance Criteria

1. WHEN ユーザーが「新しいフォルダ」ボタンをクリックする時、THE System SHALL フォルダ作成モーダルを表示する
2. WHEN ユーザーがフォルダ名を入力して作成する時、THE System SHALL 契約書カテゴリ配下に新しいフォルダを作成する
3. WHEN フォルダを作成する時、THE System SHALL デフォルトサブフォルダ（契約書、見積書、請求書、その他）を自動作成する
4. WHEN ユーザーがフォルダを右クリックする時、THE System SHALL コンテキストメニュー（開く、名前変更、削除）を表示する
5. WHEN ユーザーがフォルダを削除する時、THE System SHALL フォルダ内のすべてのファイルとサブフォルダも削除する

### Requirement 3

**User Story:** 契約書管理者として、契約書関連ファイルをアップロードしたいので、ファイルアップロード機能が必要です

#### Acceptance Criteria

1. WHEN ユーザーが「ファイルアップロード」ボタンをクリックする時、THE System SHALL ファイルアップロードモーダルを表示する
2. WHEN ユーザーがファイルを選択してアップロードする時、THE System SHALL 現在のフォルダにファイルを保存する
3. WHEN ファイルをアップロードする時、THE System SHALL ファイルサイズを50MB以下に制限する
4. WHEN ファイルをアップロードする時、THE System SHALL ファイル名、サイズ、アップロード日時、アップロード者を記録する
5. WHEN ファイルアップロードが完了する時、THE System SHALL アクティビティログに記録する

### Requirement 4

**User Story:** 契約書管理者として、アップロードしたファイルをダウンロードしたいので、ファイルダウンロード機能が必要です

#### Acceptance Criteria

1. WHEN ユーザーがファイル名をクリックする時、THE System SHALL ファイルをダウンロードする
2. WHEN ファイルをダウンロードする時、THE System SHALL 元のファイル名でダウンロードする
3. WHEN ユーザーが閲覧権限のみを持つ時、THE System SHALL ファイルのダウンロードを許可する
4. WHEN ファイルが存在しない時、THE System SHALL 404エラーを表示する
5. WHEN ダウンロードエラーが発生する時、THE System SHALL 適切なエラーメッセージを表示する

### Requirement 5

**User Story:** 契約書管理者として、ファイルやフォルダを検索したいので、検索機能が必要です

#### Acceptance Criteria

1. WHEN ユーザーが検索ボックスにキーワードを入力する時、THE System SHALL 契約書カテゴリ内のファイルとフォルダを検索する
2. WHEN 検索を実行する時、THE System SHALL ファイル名とフォルダ名を部分一致で検索する
3. WHEN 検索結果を表示する時、THE System SHALL ファイルとフォルダを区別して表示する
4. WHEN 検索結果が0件の時、THE System SHALL 「検索結果がありません」メッセージを表示する
5. WHEN 検索をクリアする時、THE System SHALL 元のフォルダ表示に戻る

### Requirement 6

**User Story:** 契約書管理者として、ファイルやフォルダの詳細情報を確認したいので、プロパティ表示機能が必要です

#### Acceptance Criteria

1. WHEN ユーザーがファイルまたはフォルダを右クリックして「プロパティ」を選択する時、THE System SHALL プロパティモーダルを表示する
2. WHEN ファイルのプロパティを表示する時、THE System SHALL ファイル名、サイズ、種類、作成日時、作成者を表示する
3. WHEN フォルダのプロパティを表示する時、THE System SHALL フォルダ名、ファイル数、合計サイズ、作成日時、作成者を表示する
4. WHEN プロパティモーダルを表示する時、THE System SHALL 読み取り専用で情報を表示する
5. WHEN ユーザーが「閉じる」ボタンをクリックする時、THE System SHALL プロパティモーダルを閉じる

### Requirement 7

**User Story:** 契約書管理者として、ファイルやフォルダを削除したいので、削除機能が必要です

#### Acceptance Criteria

1. WHEN ユーザーがファイルまたはフォルダを右クリックして「削除」を選択する時、THE System SHALL 削除確認ダイアログを表示する
2. WHEN ユーザーが削除を確認する時、THE System SHALL ファイルまたはフォルダを削除する
3. WHEN フォルダを削除する時、THE System SHALL フォルダ内のすべてのファイルとサブフォルダも削除する
4. WHEN 削除が完了する時、THE System SHALL アクティビティログに記録する
5. WHEN 削除エラーが発生する時、THE System SHALL 適切なエラーメッセージを表示する

### Requirement 8

**User Story:** 契約書管理者として、ファイルやフォルダの名前を変更したいので、名前変更機能が必要です

#### Acceptance Criteria

1. WHEN ユーザーがファイルまたはフォルダを右クリックして「名前変更」を選択する時、THE System SHALL 名前変更モーダルを表示する
2. WHEN ユーザーが新しい名前を入力して変更する時、THE System SHALL ファイルまたはフォルダの名前を更新する
3. WHEN 名前を変更する時、THE System SHALL 同じフォルダ内に同名のファイルまたはフォルダが存在しないことを確認する
4. WHEN 名前変更が完了する時、THE System SHALL アクティビティログに記録する
5. WHEN 名前変更エラーが発生する時、THE System SHALL 適切なエラーメッセージを表示する

### Requirement 9

**User Story:** システム管理者として、契約書ドキュメントのカテゴリ分離を保証したいので、データベースレベルでのカテゴリ管理が必要です

#### Acceptance Criteria

1. WHEN ドキュメントフォルダを作成する時、THE System SHALL categoryフィールドに'contracts'を設定する
2. WHEN ドキュメントファイルをアップロードする時、THE System SHALL categoryフィールドに'contracts'を設定する
3. WHEN ドキュメント一覧を取得する時、THE System SHALL category='contracts'でフィルタリングする
4. WHEN 他のカテゴリのドキュメントを取得する時、THE System SHALL 契約書カテゴリのドキュメントを含めない
5. WHEN データベースクエリを実行する時、THE System SHALL カテゴリスコープを使用してデータを分離する

### Requirement 10

**User Story:** 開発者として、既存のドキュメント管理実装を再利用したいので、統一されたサービス層とコンポーネントが必要です

#### Acceptance Criteria

1. WHEN 契約書ドキュメント管理を実装する時、THE System SHALL ContractDocumentServiceクラスを作成する
2. WHEN ContractDocumentServiceを実装する時、THE System SHALL MaintenanceDocumentServiceと同じパターンを使用する
3. WHEN UIコンポーネントを実装する時、THE System SHALL maintenance-document-managerコンポーネントと同じ構造を使用する
4. WHEN JavaScriptを実装する時、THE System SHALL MaintenanceDocumentManagerクラスと同じパターンを使用する
5. WHEN APIエンドポイントを実装する時、THE System SHALL RESTful設計原則に従う
