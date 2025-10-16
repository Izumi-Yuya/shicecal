# サイドバー実装ガイド

## 概要

このドキュメントは、Shise-Calアプリケーションのサイドバーコンポーネントの実装、アーキテクチャ、およびトラブルシューティングに関する包括的なガイドです。

## アーキテクチャ

### ファイル構成

```
resources/
├── js/
│   └── shared/
│       └── sidebar.js          # サイドバーコンポーネント
├── css/
│   └── layout.css              # サイドバースタイル
└── views/
    └── layouts/
        └── app.blade.php       # サイドバーHTML
```

### コンポーネント構造

サイドバーシステムは3つの主要なクラスで構成されています：

1. **SidebarComponent** - メインのサイドバー機能（トグル、レスポンシブ動作）
2. **ActiveMenuComponent** - アクティブメニュー項目のハイライト
3. **SmoothScrollComponent** - スムーズスクロール機能

## SidebarComponent

### シングルトンパターン

`SidebarComponent`はシングルトンパターンを使用して、アプリケーション全体で1つのインスタンスのみが存在することを保証します。

```javascript
export class SidebarComponent {
  constructor() {
    this.sidebar = null;
    this.mainContent = null;
    this.sidebarToggle = null;
    this.boundToggleHandler = null;
    this.init();
  }

  static getInstance() {
    if (!SidebarComponent.instance) {
      SidebarComponent.instance = new SidebarComponent();
    }
    return SidebarComponent.instance;
  }
}
```

### 主要機能

#### 1. トグル機能

サイドバーの展開/折りたたみを管理します。

```javascript
toggleSidebar() {
  const isCollapsed = this.sidebar.classList.contains('collapsed');
  
  if (isCollapsed) {
    this.expandSidebar();
    localStorage.setItem('sidebarCollapsed', 'false');
  } else {
    this.collapseSidebar();
    localStorage.setItem('sidebarCollapsed', 'true');
  }
}
```

**状態管理:**
- CSSクラス `collapsed` でサイドバーの状態を管理
- `localStorage` で状態を永続化
- アイコンの自動切り替え（bars ↔ times）

#### 2. レスポンシブ動作

画面サイズに応じて自動的にサイドバーを調整します。

```javascript
handleResponsive() {
  const mediaQuery = window.matchMedia('(max-width: 768px)');
  
  const handleMediaChange = (e) => {
    if (e.matches) {
      // モバイル: サイドバーを折りたたむ
      this.collapseSidebar();
    } else {
      // デスクトップ: 保存された状態を復元
      const sidebarState = localStorage.getItem('sidebarCollapsed');
      if (sidebarState !== 'true') {
        this.expandSidebar();
      }
    }
  };
  
  mediaQuery.addEventListener('change', handleMediaChange);
}
```

**ブレークポイント:**
- モバイル: ≤768px
- デスクトップ: >768px

#### 3. 外部クリック検出

モバイルデバイスで、サイドバー外をクリックすると自動的に閉じます。

```javascript
handleOutsideClick() {
  document.addEventListener('click', (e) => {
    const isSmallScreen = window.innerWidth <= 768;
    const isClickInsideSidebar = this.sidebar.contains(e.target);
    const isClickOnToggle = this.sidebarToggle.contains(e.target);
    const isSidebarVisible = !this.sidebar.classList.contains('collapsed');

    if (isSmallScreen && !isClickInsideSidebar && !isClickOnToggle && isSidebarVisible) {
      this.collapseSidebar();
    }
  });
}
```

## 初期化

### 自動初期化

`sidebar.js`は`DOMContentLoaded`イベントで自動的に初期化されます。

```javascript
document.addEventListener('DOMContentLoaded', () => {
  if (!window.sidebarInitialized) {
    console.log('Sidebar: Auto-initializing as fallback');
    initializeSidebar();
  }
});
```

### 手動初期化

必要に応じて手動で初期化することもできます。

```javascript
import { initializeSidebar } from './shared/sidebar.js';

// アプリケーション起動時
const components = initializeSidebar();
```

### 初期化の重複防止

グローバルフラグ `window.sidebarInitialized` を使用して、重複初期化を防ぎます。

```javascript
export function initializeSidebar() {
  if (window.sidebarInitialized) {
    console.log('Sidebar: Already initialized, skipping');
    return window.sidebarComponents;
  }
  
  const sidebarComponent = SidebarComponent.getInstance();
  // ... 初期化処理
  
  window.sidebarInitialized = true;
  return components;
}
```

## キーボードショートカット

サイドバーは以下のキーボードショートカットをサポートしています：

- **Ctrl/Cmd + B**: サイドバーのトグル

```javascript
document.addEventListener('keydown', (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
    e.preventDefault();
    sidebarComponent.toggleSidebar();
  }
});
```

## ActiveMenuComponent

現在のページに基づいてアクティブなメニュー項目をハイライトします。

### 機能

1. **自動ハイライト**: URLパスに基づいてメニュー項目を自動的にハイライト
2. **スクロール**: アクティブな項目を表示領域にスクロール
3. **クリックハンドリング**: メニュー項目クリック時の状態更新

```javascript
highlightActiveMenu() {
  const currentPath = window.location.pathname;
  const menuLinks = document.querySelectorAll('.sidebar .nav-link');

  menuLinks.forEach(link => {
    const linkPath = new URL(link.href).pathname;

    if (linkPath === currentPath || (currentPath.startsWith(linkPath) && linkPath !== '/')) {
      link.classList.add('active');
      
      // アクティブ項目を表示領域にスクロール
      setTimeout(() => {
        link.scrollIntoView({
          behavior: 'smooth',
          block: 'nearest'
        });
      }, 100);
    }
  });
}
```

## CSS統合

### 必要なクラス

```css
/* サイドバーの基本状態 */
.sidebar {
  transition: transform 0.3s ease;
}

/* 折りたたみ状態 */
.sidebar.collapsed {
  transform: translateX(-100%);
}

/* メインコンテンツの拡張 */
.main-content.expanded {
  margin-left: 0;
}

/* トグルボタンのアニメーション */
.sidebar-toggle.collapsed {
  /* カスタムスタイル */
}
```

## HTML構造

### 必須要素

```html
<!-- サイドバー -->
<nav id="sidebar" class="sidebar">
  <div class="sidebar-content">
    <!-- メニュー項目 -->
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link" href="/dashboard">
          <i class="fas fa-home"></i>
          ダッシュボード
        </a>
      </li>
    </ul>
  </div>
</nav>

<!-- トグルボタン -->
<button id="sidebarToggle" class="btn btn-outline-light">
  <i class="fas fa-times"></i>
</button>

<!-- メインコンテンツ -->
<main class="main-content">
  <!-- コンテンツ -->
</main>
```

## トラブルシューティング

### 問題: サイドバーが複数回トグルされる

**症状**: トグルボタンを1回クリックすると、サイドバーが複数回開閉する

**原因**: 複数のイベントリスナーが登録されている

**解決方法**:
1. イベントリスナーの数を確認:
```javascript
const toggle = document.getElementById('sidebarToggle');
console.log('Event listeners:', getEventListeners(toggle));
```

2. 他のスクリプトでサイドバートグルを処理していないか確認
3. `app-unified.js`などでサイドバー関連のコードを削除

**修正例**:
```javascript
// ❌ 悪い例 - app-unified.jsで重複処理
sidebarToggle.addEventListener('click', () => {
  sidebar.classList.toggle('collapsed');
});

// ✅ 良い例 - sidebar.jsに任せる
// サイドバートグルはsidebar.jsモジュールで処理
// 重複を避けるためここにイベントリスナーを追加しない
```

### 問題: 初期状態が正しくない

**症状**: ページ読み込み時にサイドバーの状態が期待と異なる

**原因**: `localStorage`の状態とDOMの状態が一致していない

**解決方法**:
```javascript
// localStorageをクリア
localStorage.removeItem('sidebarCollapsed');

// またはデフォルト状態を設定
localStorage.setItem('sidebarCollapsed', 'false');
```

### 問題: モバイルでサイドバーが閉じない

**症状**: モバイルデバイスで外部クリック時にサイドバーが閉じない

**原因**: `handleOutsideClick`が正しく動作していない

**解決方法**:
1. ブレークポイントを確認（768px）
2. イベントリスナーが正しく登録されているか確認
3. `e.stopPropagation()`が過度に使用されていないか確認

### 問題: シングルトンが機能しない

**症状**: 複数のSidebarComponentインスタンスが作成される

**原因**: `getInstance()`メソッドを使用していない

**解決方法**:
```javascript
// ❌ 悪い例
const sidebar = new SidebarComponent();

// ✅ 良い例
const sidebar = SidebarComponent.getInstance();
```

## デバッグ

### デバッグ関数

`window.testSidebar()`関数を使用してサイドバーの状態を確認できます。

```javascript
window.testSidebar();
// 出力:
// {
//   sidebar: true,
//   toggle: true,
//   mainContent: true,
//   sidebarClasses: "sidebar bg-light border-end",
//   mainContentClasses: "main-content",
//   sidebarInitialized: true
// }
```

### 状態確認

```javascript
// サイドバーの状態
const sidebar = document.getElementById('sidebar');
console.log('Collapsed:', sidebar.classList.contains('collapsed'));

// localStorage状態
console.log('Stored state:', localStorage.getItem('sidebarCollapsed'));

// イベントリスナー数
const toggle = document.getElementById('sidebarToggle');
console.log('Listeners:', getEventListeners(toggle));

// インスタンス確認
console.log('Instance:', SidebarComponent.instance);
console.log('Initialized:', window.sidebarInitialized);
```

## ベストプラクティス

### 1. シングルトンパターンの使用

常に`getInstance()`を使用してSidebarComponentにアクセスします。

```javascript
const sidebar = SidebarComponent.getInstance();
sidebar.toggleSidebar();
```

### 2. イベントリスナーの重複を避ける

サイドバー関連のイベントリスナーは`sidebar.js`でのみ管理します。

```javascript
// ❌ 他のファイルでサイドバートグルを処理しない
// app-unified.js, layout.js などで重複処理を避ける

// ✅ sidebar.jsに任せる
import { SidebarComponent } from './shared/sidebar.js';
const sidebar = SidebarComponent.getInstance();
```

### 3. 状態の一貫性

サイドバーの状態を変更する場合は、必ず`collapseSidebar()`または`expandSidebar()`メソッドを使用します。

```javascript
// ❌ 直接クラスを操作しない
sidebar.classList.toggle('collapsed');

// ✅ メソッドを使用
const sidebarComponent = SidebarComponent.getInstance();
sidebarComponent.toggleSidebar();
```

### 4. レスポンシブ対応

モバイルとデスクトップで異なる動作を実装する場合は、`handleResponsive()`を拡張します。

```javascript
handleResponsive() {
  const mediaQuery = window.matchMedia('(max-width: 768px)');
  
  const handleMediaChange = (e) => {
    if (e.matches) {
      // モバイル固有の処理
      this.collapseSidebar();
    } else {
      // デスクトップ固有の処理
      this.restoreSavedState();
    }
  };
  
  mediaQuery.addEventListener('change', handleMediaChange);
}
```

## パフォーマンス最適化

### 1. イベントリスナーの管理

不要になったイベントリスナーは必ず削除します。

```javascript
destroy() {
  if (this.boundToggleHandler && this.sidebarToggle) {
    this.sidebarToggle.removeEventListener('click', this.boundToggleHandler);
  }
  SidebarComponent.instance = null;
}
```

### 2. デバウンス処理

リサイズイベントなど、頻繁に発生するイベントにはデバウンスを適用します。

```javascript
// 将来の拡張用
const debouncedResize = debounce(() => {
  this.handleResponsive();
}, 250);

window.addEventListener('resize', debouncedResize);
```

### 3. CSS遷移の最適化

CSS遷移を使用してスムーズなアニメーションを実現します。

```css
.sidebar {
  transition: transform 0.3s ease;
  will-change: transform;
}
```

## アクセシビリティ

### ARIA属性

サイドバーには適切なARIA属性を設定します。

```html
<nav id="sidebar" 
     class="sidebar" 
     role="navigation" 
     aria-label="メインナビゲーション">
  <!-- コンテンツ -->
</nav>

<button id="sidebarToggle" 
        aria-label="サイドバーを開閉" 
        aria-expanded="true"
        aria-controls="sidebar">
  <i class="fas fa-times"></i>
</button>
```

### キーボードナビゲーション

- **Tab**: メニュー項目間を移動
- **Enter/Space**: メニュー項目を選択
- **Ctrl/Cmd + B**: サイドバーをトグル

## 今後の拡張

### 計画中の機能

1. **アニメーション設定**: ユーザーがアニメーションを無効化できる
2. **テーマ切り替え**: ライト/ダークモード対応
3. **カスタマイズ可能な幅**: サイドバーの幅を調整可能に
4. **ピン留め機能**: サイドバーを常に表示

### 拡張例

```javascript
// 将来の拡張用インターフェース
class SidebarComponent {
  // 既存のメソッド...
  
  setWidth(width) {
    this.sidebar.style.width = `${width}px`;
  }
  
  setTheme(theme) {
    this.sidebar.setAttribute('data-theme', theme);
  }
  
  setPinned(pinned) {
    this.sidebar.classList.toggle('pinned', pinned);
  }
}
```

## まとめ

サイドバーコンポーネントは、以下の原則に基づいて設計されています：

1. **シングルトンパターン**: 1つのインスタンスのみ
2. **イベントリスナーの一元管理**: 重複を防止
3. **状態の永続化**: localStorageを使用
4. **レスポンシブ対応**: モバイルとデスクトップで最適化
5. **アクセシビリティ**: ARIA属性とキーボードナビゲーション

これらの原則に従うことで、保守性が高く、パフォーマンスの良いサイドバーを実装できます。
