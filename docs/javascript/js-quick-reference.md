# JavaScript クイックリファレンス

## ファイル一覧（重要度順）

| ファイル | 役割 | 編集頻度 |
|---------|------|---------|
| `app-unified.js` | コアアプリケーション | 🔴 高 |
| `app.js` | メインエントリー | 🟡 中 |
| `modules/*.js` | 機能別モジュール | 🔴 高 |
| `shared/*.js` | 共有ユーティリティ | 🟢 低 |

## 🚫 削除されたファイル

- ~~`app-unified-backup.js`~~ - バックアップファイル（不要）

## 📁 ファイル構造（簡易版）

```
resources/js/
├── app.js                    ← START HERE (メインエントリー)
├── app-unified.js            ← CORE (コアロジック)
├── modules/                  ← FEATURES (機能)
│   ├── DocumentManager.js
│   ├── LifelineDocumentManager.js
│   └── ...
└── shared/                   ← UTILS (ユーティリティ)
    ├── ApiClient.js
    └── AppUtils.js
```

## 🎯 よく使う操作

### ドキュメント管理を再初期化
```javascript
window.shiseCalApp.initializeLifelineDocumentManagers();
```

### 施設データを取得
```javascript
const facility = window.shiseCalApp.modules.facilityManager;
```

### APIリクエスト
```javascript
const api = new ApiClient();
const data = await api.get('/api/facilities/1');
```

### トースト通知
```javascript
AppUtils.showToast('成功しました', 'success');
```

### 確認ダイアログ
```javascript
const confirmed = await AppUtils.confirmDialog('削除しますか？');
if (confirmed) {
  // 削除処理
}
```

## 🔧 開発コマンド

```bash
# 開発サーバー
npm run dev

# 本番ビルド
npm run build

# テスト
npm run test
```

## 🐛 デバッグ

### コンソールで確認
```javascript
// アプリケーション全体
window.shiseCalApp

// モジュール一覧
window.shiseCalApp.modules

// 特定のモジュール
window.shiseCalApp.modules.lifelineDocumentManager_electrical
```

### よくあるエラー

**エラー**: `Cannot read property 'init' of undefined`
**原因**: モジュールが初期化されていない
**解決**: 初期化メソッドを呼び出す

**エラー**: `this is undefined`
**原因**: コンテキストの喪失
**解決**: アロー関数を使用するか、`bind(this)`

## 📚 詳細ドキュメント

- [JavaScript アーキテクチャ](./javascript-architecture.md) - 詳細な構造説明
- [フロントエンド構造](./frontend-structure.md) - 開発ガイド
- [技術スタック](../.kiro/steering/tech.md) - 使用技術
- [ライフライン設備ドキュメント表示修正](./lifeline-document-display-fix.md) - トラブルシューティング事例

## 💡 ヒント

1. **新機能を追加する場合**
   - `modules/`に新しいファイルを作成
   - `app-unified.js`で初期化

2. **既存機能を修正する場合**
   - 該当するモジュールファイルを直接編集

3. **スタイルを変更する場合**
   - `resources/css/`の該当ファイルを編集

4. **わからない場合**
   - ブラウザコンソールで`window.shiseCalApp`を確認
   - 詳細ドキュメントを参照
