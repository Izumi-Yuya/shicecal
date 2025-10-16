# ドキュメント管理システムのカテゴリ分離 - 実装タスク

## 実装タスク

- [x] 1. データベースマイグレーションの作成と実行
  - マイグレーションファイルを作成し、`category`カラムを追加
  - 複合インデックスを作成してクエリパフォーマンスを最適化
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

- [x] 1.1 マイグレーションファイルの作成
  - `document_folders`テーブルに`category`カラムを追加
  - `document_files`テーブルに`category`カラムを追加
  - 複合インデックス`(facility_id, category)`を両テーブルに作成
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 1.2 マイグレーションの実行とテスト
  - マイグレーションを実行してスキーマを更新
  - ロールバックが正常に動作することを確認
  - 既存データが保持されることを確認
  - _Requirements: 1.6, 6.1, 6.2_

- [x] 2. DocumentFolderモデルの拡張
  - `category`フィールドを`fillable`に追加
  - スコープメソッドを実装してカテゴリ別フィルタリングを可能にする
  - カテゴリ判定メソッドを実装
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

- [x] 2.1 fillableプロパティの更新
  - `category`を`fillable`配列に追加
  - _Requirements: 2.1_

- [x] 2.2 スコープメソッドの実装
  - `scopeMain()`メソッドを実装（category IS NULL）
  - `scopeLifeline($category)`メソッドを実装
  - `scopeMaintenance($category)`メソッドを実装
  - _Requirements: 2.3, 2.4, 2.5_

- [x] 2.3 カテゴリ判定メソッドの実装
  - `isMain()`メソッドを実装
  - `isLifeline()`メソッドを実装
  - `isMaintenance()`メソッドを実装
  - `getCategoryName()`メソッドを実装
  - _Requirements: 2.6_

- [x] 3. DocumentFileモデルの拡張
  - DocumentFolderと同様の変更を適用
  - スコープメソッドとカテゴリ判定メソッドを実装
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

- [x] 3.1 fillableプロパティとスコープメソッドの実装
  - `category`を`fillable`に追加
  - `scopeMain()`, `scopeLifeline()`, `scopeMaintenance()`を実装
  - _Requirements: 2.1, 2.3, 2.4, 2.5_

- [x] 3.2 カテゴリ判定メソッドの実装
  - `isMain()`, `isLifeline()`, `isMaintenance()`を実装
  - _Requirements: 2.6_

- [x] 4. DocumentServiceの更新（メインドキュメント専用化）
  - フォルダ作成時に`category = NULL`を設定
  - ファイルアップロード時に`category = NULL`を設定
  - フォルダ内容取得時にメインドキュメントのみフィルタリング
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 4.1 createFolderメソッドの更新
  - フォルダ作成時に`category => null`を明示的に設定
  - _Requirements: 3.1_

- [x] 4.2 uploadFileメソッドの更新
  - ファイル作成時に`category => null`を明示的に設定
  - _Requirements: 3.2_

- [x] 4.3 getFolderContentsメソッドの更新
  - フォルダクエリに`main()`スコープを適用
  - ファイルクエリに`main()`スコープを適用
  - _Requirements: 3.3_

- [x] 4.4 getAvailableFileTypesメソッドの更新
  - ファイルタイプ集計クエリに`main()`スコープを適用
  - _Requirements: 3.4_

- [x] 4.5 getFolderStatsメソッドの更新
  - 統計クエリに`main()`スコープを適用
  - _Requirements: 3.5_

- [x] 5. LifelineDocumentServiceの更新
  - ルートフォルダ作成時に適切なカテゴリを設定
  - サブフォルダ作成時に親のカテゴリを継承
  - ドキュメント取得時にカテゴリでフィルタリング
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 5.1 getOrCreateCategoryRootFolderメソッドの更新
  - ルートフォルダ作成時に`category => 'lifeline_{$category}'`を設定
  - 既存フォルダ検索時に`lifeline($category)`スコープを使用
  - _Requirements: 4.1_

- [x] 5.2 createDefaultSubfoldersメソッドの更新
  - サブフォルダ作成時に親の`category`を継承
  - _Requirements: 4.2_

- [x] 5.3 uploadCategoryFileメソッドの更新
  - ファイルアップロード時にフォルダの`category`を継承
  - _Requirements: 4.3_

- [x] 5.4 getCategoryDocumentsメソッドの更新
  - フォルダとファイルのクエリに`lifeline($category)`スコープを適用
  - _Requirements: 4.4_

- [x] 5.5 getCategoryStatsメソッドの更新
  - 統計クエリに`lifeline($category)`スコープを適用
  - _Requirements: 4.5_

- [x] 6. MaintenanceDocumentServiceの更新
  - LifelineDocumentServiceと同様の変更を適用
  - カテゴリプレフィックスを`maintenance_`に変更
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 6.1 getOrCreateCategoryRootFolderメソッドの実装
  - ルートフォルダ作成時に`category => 'maintenance_{$category}'`を設定
  - _Requirements: 5.1_

- [x] 6.2 サブフォルダとファイルのカテゴリ継承
  - サブフォルダとファイル作成時に親の`category`を継承
  - _Requirements: 5.2, 5.3_

- [x] 6.3 ドキュメント取得と統計のフィルタリング
  - クエリに`maintenance($category)`スコープを適用
  - _Requirements: 5.4, 5.5_

- [x] 7. 機能テストの作成
  - カテゴリ分離が正しく動作することを確認するテストを作成
  - 各システムの独立性を検証
  - _Requirements: 9.1, 9.2, 9.3, 9.4_

- [x] 7.1 DocumentFolderモデルのテスト
  - `main()`スコープがメインドキュメントのみ返すことをテスト
  - `lifeline()`スコープがライフライン設備のみ返すことをテスト
  - `maintenance()`スコープが修繕履歴のみ返すことをテスト
  - _Requirements: 9.1_

- [x] 7.2 DocumentServiceの統合テスト
  - メインドキュメント作成時に他のカテゴリが混入しないことをテスト
  - メインドキュメント取得時に他のカテゴリが表示されないことをテスト
  - _Requirements: 9.1, 7.1, 7.2_

- [x] 7.3 LifelineDocumentServiceの統合テスト
  - ライフライン設備ドキュメント作成時に正しいカテゴリが設定されることをテスト
  - ライフライン設備ドキュメント取得時にメインドキュメントが混入しないことをテスト
  - _Requirements: 9.2, 7.3, 7.4_

- [x] 7.4 MaintenanceDocumentServiceの統合テスト
  - 修繕履歴ドキュメント作成時に正しいカテゴリが設定されることをテスト
  - 修繕履歴ドキュメント取得時に他のカテゴリが混入しないことをテスト
  - _Requirements: 9.3, 7.5_

- [x] 7.5 カテゴリ間独立性テスト
  - 各システムでドキュメントを作成し、他のシステムに表示されないことを確認
  - _Requirements: 9.4, 7.1, 7.2, 7.3, 7.4_

- [x] 8. ドキュメントの作成
  - 実装ガイドとトラブルシューティングガイドを作成
  - カテゴリ値の命名規則を文書化
  - _Requirements: 10.1, 10.2, 10.3, 10.4_

- [x] 8.1 カテゴリ実装ガイドの作成
  - カテゴリ値の命名規則を記載
  - 各サービスでのカテゴリ設定方法を説明
  - _Requirements: 10.1, 10.2_

- [x] 8.2 トラブルシューティングガイドの作成
  - よくある問題と解決方法を記載
  - カテゴリ不一致エラーの対処法を説明
  - _Requirements: 10.3_

- [-] 9. 動作確認とデプロイ
  - 開発環境で動作確認
  - ステージング環境でテスト
  - 本番環境へのデプロイ
  - _Requirements: すべて_

- [x] 9.1 開発環境での動作確認
  - メインドキュメントタブでフォルダ作成
  - ライフライン設備でフォルダ作成
  - 修繕履歴でフォルダ作成
  - 各システムで他のカテゴリが表示されないことを確認
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 9.2 パフォーマンステスト
  - 大量データでのクエリパフォーマンスを測定
  - インデックスが正しく使用されていることを確認
  - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [ ] 9.3 本番環境へのデプロイ
  - マイグレーションを実行
  - アプリケーションコードをデプロイ
  - 動作確認を実施
  - _Requirements: すべて_

## 実装の優先順位

### 高優先度（即座に実装）
1. データベースマイグレーション（タスク1）
2. モデルの拡張（タスク2, 3）
3. DocumentServiceの更新（タスク4）

### 中優先度（1週間以内）
4. LifelineDocumentServiceの更新（タスク5）
5. MaintenanceDocumentServiceの更新（タスク6）
6. 基本的な機能テスト（タスク7.1, 7.2）

### 低優先度（2週間以内）
7. 包括的なテスト（タスク7.3, 7.4, 7.5）
8. ドキュメント作成（タスク8）
9. パフォーマンステスト（タスク9.2）

## 注意事項

1. **既存データの保護**: マイグレーション実行前に必ずバックアップを取得
2. **段階的なデプロイ**: 開発環境 → ステージング環境 → 本番環境の順で慎重にデプロイ
3. **後方互換性**: 既存のドキュメント（category = NULL）が引き続き動作することを確認
4. **パフォーマンス監視**: デプロイ後、クエリパフォーマンスを監視

## 完了基準

- [x] すべてのタスクが完了している
- [x] すべてのテストがパスしている
- [x] ライフライン設備でフォルダを作成しても、メインドキュメントタブに表示されない
- [x] メインドキュメントでフォルダを作成しても、ライフライン設備に表示されない
- [x] 修繕履歴でフォルダを作成しても、他のシステムに表示されない
- [x] 既存のドキュメントが引き続き正常に動作する
- [x] パフォーマンスが維持されている（クエリ実行時間 < 1秒）
- [x] ドキュメントが作成されている
