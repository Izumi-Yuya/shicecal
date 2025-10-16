# ドキュメント管理システム - カテゴリ分離ドキュメント索引

## 概要

このドキュメントは、ドキュメント管理システムのカテゴリ分離機能に関する全ドキュメントの索引です。

## ドキュメント構成

### 🎯 実装ガイド

#### [category-implementation-guide.md](./category-implementation-guide.md)
**対象**: 開発者
**内容**:
- カテゴリ値の命名規則
- モデルでのカテゴリ設定方法
- サービス層での実装パターン
- コントローラーでの使用例
- ベストプラクティス

**使用場面**:
- 新しいカテゴリを追加する時
- カテゴリ分離機能を実装する時
- コードレビュー時の参照

---

#### [category-troubleshooting-guide.md](./category-troubleshooting-guide.md)
**対象**: 開発者、運用担当者
**内容**:
- カテゴリ不一致エラーの対処法
- ドキュメントが表示されない問題の解決
- フォルダ作成時のエラー対応
- ファイルアップロード時のエラー対応
- パフォーマンス問題の解決
- マイグレーション関連の問題
- データ整合性の問題

**使用場面**:
- エラーが発生した時
- ドキュメントが正しく表示されない時
- パフォーマンス問題が発生した時
- データ整合性チェック時

---

### 📊 実装サマリー

#### [category-separation-summary.md](./category-separation-summary.md)
**対象**: プロジェクトマネージャー、開発者
**内容**:
- カテゴリ分離機能の概要
- 実装された機能の一覧
- テスト結果のサマリー
- 今後の改善点

**使用場面**:
- プロジェクトの進捗確認
- 機能の全体像を把握する時
- ステークホルダーへの報告

---

### 🔧 詳細実装ドキュメント

#### [lifeline-service-category-implementation.md](./lifeline-service-category-implementation.md)
**対象**: 開発者
**内容**:
- LifelineDocumentServiceの実装詳細
- カテゴリ別のフォルダ管理
- ファイルアップロード処理
- テストケース

**使用場面**:
- ライフライン設備ドキュメント機能の開発
- バグ修正時の参照
- 機能拡張時の参照

---

#### [maintenance-service-category-implementation.md](./maintenance-service-category-implementation.md)
**対象**: 開発者
**内容**:
- MaintenanceDocumentServiceの実装詳細
- カテゴリ別のフォルダ管理
- ファイルアップロード処理
- テストケース

**使用場面**:
- 修繕履歴ドキュメント機能の開発
- バグ修正時の参照
- 機能拡張時の参照

---

## ドキュメントの使い方

### 新規開発者向け

1. **まず読むべきドキュメント**:
   - [category-separation-summary.md](./category-separation-summary.md) - 全体像を把握
   - [category-implementation-guide.md](./category-implementation-guide.md) - 実装パターンを学習

2. **実装時に参照するドキュメント**:
   - [category-implementation-guide.md](./category-implementation-guide.md) - 実装パターン
   - [lifeline-service-category-implementation.md](./lifeline-service-category-implementation.md) - ライフライン設備の詳細
   - [maintenance-service-category-implementation.md](./maintenance-service-category-implementation.md) - 修繕履歴の詳細

3. **問題発生時に参照するドキュメント**:
   - [category-troubleshooting-guide.md](./category-troubleshooting-guide.md) - トラブルシューティング

### 既存開発者向け

1. **機能追加時**:
   - [category-implementation-guide.md](./category-implementation-guide.md) - ベストプラクティスを確認
   - 該当するサービスの詳細ドキュメント

2. **バグ修正時**:
   - [category-troubleshooting-guide.md](./category-troubleshooting-guide.md) - よくある問題を確認
   - 該当するサービスの詳細ドキュメント

3. **コードレビュー時**:
   - [category-implementation-guide.md](./category-implementation-guide.md) - 命名規則とパターンを確認

### 運用担当者向け

1. **問題発生時**:
   - [category-troubleshooting-guide.md](./category-troubleshooting-guide.md) - トラブルシューティング
   - デバッグツールの使用方法

2. **定期メンテナンス時**:
   - [category-troubleshooting-guide.md](./category-troubleshooting-guide.md) - データ整合性チェック

## クイックリファレンス

### カテゴリ値一覧

```php
// メインドキュメント
NULL

// ライフライン設備
'lifeline_electrical'      // 電気設備
'lifeline_gas'             // ガス設備
'lifeline_water'           // 水道設備
'lifeline_elevator'        // エレベーター設備
'lifeline_hvac_lighting'   // 空調・照明設備

// 修繕履歴
'maintenance_exterior'            // 外装
'maintenance_interior'            // 内装
'maintenance_summer_condensation' // 夏季結露
'maintenance_other'               // その他
```

### よく使うスコープメソッド

```php
// メインドキュメント
DocumentFolder::main()->where('facility_id', $id)->get();

// ライフライン設備（特定カテゴリ）
DocumentFolder::lifeline('electrical')->where('facility_id', $id)->get();

// ライフライン設備（全カテゴリ）
DocumentFolder::lifeline()->where('facility_id', $id)->get();

// 修繕履歴（特定カテゴリ）
DocumentFolder::maintenance('exterior')->where('facility_id', $id)->get();

// 修繕履歴（全カテゴリ）
DocumentFolder::maintenance()->where('facility_id', $id)->get();
```

### よくあるエラーと対処法

| エラー | 対処法 | 参照ドキュメント |
|--------|--------|------------------|
| カテゴリ不一致エラー | フォルダのカテゴリを継承 | [category-troubleshooting-guide.md](./category-troubleshooting-guide.md#カテゴリ不一致エラー) |
| ドキュメントが表示されない | スコープメソッドを確認 | [category-troubleshooting-guide.md](./category-troubleshooting-guide.md#ドキュメントが表示されない) |
| フォルダ作成エラー | カテゴリ設定を確認 | [category-troubleshooting-guide.md](./category-troubleshooting-guide.md#フォルダ作成時のエラー) |
| パフォーマンス問題 | インデックスを確認 | [category-troubleshooting-guide.md](./category-troubleshooting-guide.md#パフォーマンス問題) |

## 関連ドキュメント

### プロジェクト全体のドキュメント
- [README.md](./README.md) - ドキュメント管理システム総合ガイド
- [separation-strategy.md](./separation-strategy.md) - 分離戦略
- [current-implementation-analysis.md](./current-implementation-analysis.md) - 実装状況分析

### 技術ドキュメント
- [tech.md](../../.kiro/steering/tech.md) - 技術スタック
- [structure.md](../../.kiro/steering/structure.md) - プロジェクト構造
- [file-handling.md](../../.kiro/steering/file-handling.md) - ファイル処理ガイドライン

## 更新履歴

| 日付 | 更新内容 | 担当者 |
|------|----------|--------|
| 2025-10-16 | カテゴリ実装ガイド作成 | 開発チーム |
| 2025-10-16 | トラブルシューティングガイド作成 | 開発チーム |
| 2025-10-16 | ドキュメント索引作成 | 開発チーム |

## フィードバック

ドキュメントに関するフィードバックや改善提案は、開発チームまでお願いします。

---

**最終更新**: 2025年10月16日
**バージョン**: 1.0.0
