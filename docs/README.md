# Shise-Cal ドキュメント

## 📚 ドキュメント構造

```
docs/
├── README.md                    ← このファイル
├── javascript/                  ← JavaScript関連
├── csv-export/                  ← CSVエクスポート関連
├── lifeline-equipment/          ← ライフライン設備関連
├── troubleshooting/             ← トラブルシューティング
└── ...
```

## 🚀 クイックスタート

### 初めての方
1. **[JavaScript クイックリファレンス](./javascript/js-quick-reference.md)** - 最初に読むべきドキュメント
2. **[フロントエンド構造](./javascript/frontend-structure.md)** - 開発ガイド

### 開発者向け
- **[JavaScript アーキテクチャ](./javascript/javascript-architecture.md)** - 詳細な構造説明
- **[プロジェクト構造](../.kiro/steering/structure.md)** - ディレクトリ構成
- **[技術スタック](../.kiro/steering/tech.md)** - 使用技術

## 📁 カテゴリ別ドキュメント

### 💻 JavaScript
**ディレクトリ**: [`javascript/`](./javascript/)

- [クイックリファレンス](./javascript/js-quick-reference.md) - 最初に読むべき
- [フロントエンド構造](./javascript/frontend-structure.md) - 開発ガイド
- [アーキテクチャ](./javascript/javascript-architecture.md) - 詳細な構造
- [トラブルシューティング](./javascript/troubleshooting-javascript.md) - 問題解決

### 📊 CSVエクスポート
**ディレクトリ**: [`csv-export/`](./csv-export/)

- [実装サマリー](./csv-export/csv-export-implementation-summary.md)
- [図面フィールド](./csv-export/csv-export-drawing-fields.md)
- [フィールド修正履歴](./csv-export/csv-export-field-fix-summary.md)
- [その他の修正履歴](./csv-export/) - ディレクトリ内を参照

### ⚡ ライフライン設備
**ディレクトリ**: [`lifeline-equipment/`](./lifeline-equipment/)

- [ドキュメント管理](./lifeline-equipment/lifeline-document-management.md)
- [表示問題修正](./lifeline-equipment/lifeline-document-display-fix.md)
- [フォルダ作成エラー修正](./lifeline-equipment/lifeline-folder-duplicate-submission-fix.md)
- [保守履歴データ構造](./lifeline-equipment/maintenance-history-data-structure.md)

### 🐛 トラブルシューティング
**ディレクトリ**: [`troubleshooting/`](./troubleshooting/)

- [モーダル問題](./troubleshooting/modal-troubleshooting-guide.md)
- [アクセシビリティ修正](./troubleshooting/accessibility-modal-fixes.md)
- [JavaScript問題](./javascript/troubleshooting-javascript.md)

### 📋 その他
- [ファイル処理](../.kiro/steering/file-handling.md) - アップロード・ダウンロード
- [契約書管理](../.kiro/steering/contracts-management.md) - 実装ガイドライン
- [モーダル実装](../.kiro/steering/modal-implementation-guide.md) - 実装方法

### 📋 実装ガイドライン

- **[モーダル実装ガイド](../.kiro/steering/modal-implementation-guide.md)** - モーダルの実装方法
- **[製品概要](../.kiro/steering/product.md)** - システム概要



## 🎯 目的別ガイド

| やりたいこと | 参照ドキュメント |
|------------|----------------|
| **新機能を追加** | [JavaScript アーキテクチャ](./javascript/javascript-architecture.md) → [フロントエンド構造](./javascript/frontend-structure.md) |
| **既存機能を修正** | [クイックリファレンス](./javascript/js-quick-reference.md) → 該当モジュール |
| **エラーが発生** | [トラブルシューティング](./javascript/troubleshooting-javascript.md) → 該当カテゴリ |
| **テストを書く** | `tests/Feature/` または `tests/Unit/` を参照 |

## 📝 ドキュメント管理

### 新しいドキュメントを追加
1. 適切なカテゴリディレクトリに`.md`ファイルを作成
2. カテゴリのREADME.mdに追加
3. 必要に応じてメインREADME.mdを更新

### ドキュメントを更新
1. 該当ファイルを直接編集
2. 大きな変更の場合は、カテゴリREADMEも更新

## 🔗 外部リソース

- [技術スタック](../.kiro/steering/tech.md) - 使用技術の詳細
- [プロジェクト構造](../.kiro/steering/structure.md) - ディレクトリ構成
- [製品概要](../.kiro/steering/product.md) - システム概要
