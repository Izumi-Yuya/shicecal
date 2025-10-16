# サイドバードキュメント

このディレクトリには、Shise-Calアプリケーションのサイドバーコンポーネントに関する包括的なドキュメントが含まれています。

## ドキュメント一覧

### 📘 実装ガイド
- **[sidebar-implementation-guide.md](./sidebar-implementation-guide.md)**
  - サイドバーの完全な実装ガイド
  - アーキテクチャとコンポーネント構造
  - ベストプラクティスとパフォーマンス最適化
  - トラブルシューティングガイド

### 🔧 修正履歴
- **[sidebar-fix-summary.md](./sidebar-fix-summary.md)**
  - 2025年10月17日の修正サマリー
  - トグル問題の根本原因と解決方法
  - 修正前後の比較
  - 学んだ教訓と今後の予防策

### 📝 過去の分析・修正記録
- **[toggle-button-analysis.md](./toggle-button-analysis.md)**
  - 初期の問題分析
  - トグルボタンの動作調査

- **[toggle-button-fix.md](./toggle-button-fix.md)**
  - 最初の修正試行
  - 部分的な解決策

- **[toggle-button-final-fix.md](./toggle-button-final-fix.md)**
  - 最終的な修正アプローチ
  - 完全な解決策

## クイックスタート

### サイドバーの使用方法

```javascript
// シングルトンインスタンスの取得
import { SidebarComponent } from './shared/sidebar.js';
const sidebar = SidebarComponent.getInstance();

// サイドバーをトグル
sidebar.toggleSidebar();

// サイドバーを展開
sidebar.expandSidebar();

// サイドバーを折りたたむ
sidebar.collapseSidebar();
```

### 初期化

```javascript
// 自動初期化（推奨）
// sidebar.jsがDOMContentLoadedで自動的に初期化されます

// 手動初期化（必要な場合のみ）
import { initializeSidebar } from './shared/sidebar.js';
const components = initializeSidebar();
```

## 主要な概念

### シングルトンパターン

サイドバーコンポーネントはシングルトンパターンを使用しており、アプリケーション全体で1つのインスタンスのみが存在します。

```javascript
// ✅ 正しい使用方法
const sidebar = SidebarComponent.getInstance();

// ❌ 避けるべき使用方法
const sidebar = new SidebarComponent();
```

### 状態管理

サイドバーの状態は以下の2つの方法で管理されます：

1. **CSSクラス**: `collapsed`クラスで視覚的な状態を管理
2. **localStorage**: `sidebarCollapsed`キーで状態を永続化

```javascript
// 状態の確認
const isCollapsed = sidebar.classList.contains('collapsed');
const storedState = localStorage.getItem('sidebarCollapsed');
```

### レスポンシブ動作

- **モバイル（≤768px）**: デフォルトで折りたたまれる
- **デスクトップ（>768px）**: localStorage状態を復元

## トラブルシューティング

### よくある問題

#### 1. サイドバーが複数回トグルされる

**症状**: 1回のクリックで複数回開閉する

**解決方法**:
```javascript
// イベントリスナー数を確認
const toggle = document.getElementById('sidebarToggle');
console.log('Event listeners:', getEventListeners(toggle));
// 期待値: {click: Array(1)}
```

詳細は[sidebar-fix-summary.md](./sidebar-fix-summary.md)を参照してください。

#### 2. 初期状態が正しくない

**症状**: ページ読み込み時の状態が期待と異なる

**解決方法**:
```javascript
// localStorageをリセット
localStorage.removeItem('sidebarCollapsed');
// またはデフォルト状態を設定
localStorage.setItem('sidebarCollapsed', 'false');
```

#### 3. モバイルで外部クリックが機能しない

**症状**: サイドバー外をクリックしても閉じない

**解決方法**: `handleOutsideClick()`が正しく初期化されているか確認

詳細は[sidebar-implementation-guide.md](./sidebar-implementation-guide.md#トラブルシューティング)を参照してください。

## ベストプラクティス

### ✅ 推奨される実装

```javascript
// 1. シングルトンの使用
const sidebar = SidebarComponent.getInstance();

// 2. メソッドを使用した状態変更
sidebar.toggleSidebar();

// 3. 初期化の重複防止
if (!window.sidebarInitialized) {
  initializeSidebar();
}
```

### ❌ 避けるべき実装

```javascript
// 1. 直接インスタンス化
const sidebar = new SidebarComponent();

// 2. 直接DOM操作
sidebar.classList.toggle('collapsed');

// 3. 重複したイベントリスナー
sidebarToggle.addEventListener('click', () => {
  // app-unified.jsなど他のファイルで追加しない
});
```

## デバッグ

### デバッグコマンド

```javascript
// サイドバー状態の確認
window.testSidebar();

// イベントリスナーの確認
const toggle = document.getElementById('sidebarToggle');
console.log('Listeners:', getEventListeners(toggle));

// インスタンスの確認
console.log('Instance:', SidebarComponent.instance);
console.log('Initialized:', window.sidebarInitialized);
```

### ログレベル

開発中は、コンソールログで以下の情報を確認できます：

- 初期化状態
- イベントリスナーの登録
- 状態変更
- エラーメッセージ

## アーキテクチャ

### ファイル構成

```
resources/
├── js/
│   └── shared/
│       └── sidebar.js          # メインコンポーネント
├── css/
│   └── layout.css              # スタイル定義
└── views/
    └── layouts/
        └── app.blade.php       # HTML構造
```

### コンポーネント

1. **SidebarComponent** - メイン機能
   - トグル機能
   - レスポンシブ動作
   - 状態管理

2. **ActiveMenuComponent** - メニュー管理
   - アクティブ項目のハイライト
   - スクロール機能

3. **SmoothScrollComponent** - スクロール
   - スムーズスクロール動作

## キーボードショートカット

- **Ctrl/Cmd + B**: サイドバーをトグル

## アクセシビリティ

サイドバーは以下のアクセシビリティ機能をサポートしています：

- ARIA属性（`aria-label`, `aria-expanded`, `aria-controls`）
- キーボードナビゲーション
- スクリーンリーダー対応

## パフォーマンス

### 最適化手法

1. **CSS遷移**: スムーズなアニメーション
2. **イベント委譲**: 効率的なイベント処理
3. **デバウンス**: リサイズイベントの最適化
4. **シングルトン**: メモリ使用量の削減

## 今後の拡張

計画中の機能：

- [ ] アニメーション設定のカスタマイズ
- [ ] テーマ切り替え（ライト/ダーク）
- [ ] カスタマイズ可能な幅
- [ ] ピン留め機能

## 貢献

サイドバーコンポーネントの改善に貢献する場合は、以下のガイドラインに従ってください：

1. [sidebar-implementation-guide.md](./sidebar-implementation-guide.md)を読む
2. ベストプラクティスに従う
3. 変更内容をドキュメント化する
4. テストを追加する

## 関連リソース

### 内部ドキュメント
- [実装ガイド](./sidebar-implementation-guide.md)
- [修正サマリー](./sidebar-fix-summary.md)
- [トグルボタン分析](./toggle-button-analysis.md)

### 外部リソース
- [Bootstrap 5.1 Documentation](https://getbootstrap.com/docs/5.1/)
- [MDN Web Docs - Event Listeners](https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener)
- [Singleton Pattern](https://refactoring.guru/design-patterns/singleton)

## サポート

問題が発生した場合：

1. [トラブルシューティングガイド](./sidebar-implementation-guide.md#トラブルシューティング)を確認
2. [修正サマリー](./sidebar-fix-summary.md)で類似の問題を検索
3. デバッグコマンドで状態を確認
4. 必要に応じて開発チームに連絡

## 変更履歴

### 2025年10月17日
- トグル問題の修正
- シングルトンパターンの改善
- イベントリスナー管理の改善
- ドキュメントの全面的な更新

### 過去の変更
- 初期実装
- レスポンシブ対応
- アクセシビリティ改善

---

**最終更新**: 2025年10月17日  
**メンテナー**: Shise-Cal開発チーム
