# JavaScript トラブルシューティングガイド

## よくある問題と解決方法

### 1. DOM要素が見つからない

**症状**:
```javascript
Cannot read property 'style' of null
Cannot read property 'classList' of undefined
```

**原因**:
- DOM要素がまだ存在しない（タイミング問題）
- IDやセレクタが間違っている
- 複数の要素が同じIDを持っている

**解決方法**:

```javascript
// ❌ 悪い例
const element = document.getElementById('my-element');
element.style.display = 'none'; // elementがnullの場合エラー

// ✅ 良い例
const element = document.getElementById('my-element');
if (element) {
  element.style.display = 'none';
} else {
  console.warn('Element not found: my-element');
}

// ✅ さらに良い例（スコープを限定）
const container = document.querySelector('[data-category="electrical"]');
if (container) {
  const element = container.querySelector('#my-element');
  if (element) {
    element.style.display = 'none';
  }
}
```

**デバッグ方法**:
```javascript
// 要素の存在確認
console.log('Element:', document.getElementById('my-element'));

// すべての該当要素を確認
console.log('All elements:', document.querySelectorAll('#my-element'));

// 親要素から検索
const parent = document.querySelector('[data-category="electrical"]');
console.log('Parent:', parent);
console.log('Child:', parent?.querySelector('#my-element'));
```

### 2. thisコンテキストの喪失

**症状**:
```javascript
Cannot read property 'method' of undefined
this.method is not a function
```

**原因**:
- イベントリスナー内で`this`が期待と異なるオブジェクトを指している
- コールバック関数で`this`が失われている

**解決方法**:

```javascript
// ❌ 悪い例
class MyClass {
  init() {
    document.getElementById('btn').addEventListener('click', function() {
      this.handleClick(); // thisがMyClassを指していない
    });
  }
}

// ✅ 良い例1: アロー関数を使用
class MyClass {
  init() {
    document.getElementById('btn').addEventListener('click', () => {
      this.handleClick(); // thisがMyClassを指す
    });
  }
}

// ✅ 良い例2: selfを使用
class MyClass {
  init() {
    const self = this;
    document.getElementById('btn').addEventListener('click', function() {
      self.handleClick(); // selfがMyClassを指す
    });
  }
}

// ✅ 良い例3: bindを使用
class MyClass {
  init() {
    document.getElementById('btn').addEventListener('click', 
      this.handleClick.bind(this)
    );
  }
}
```

### 3. イベントリスナーの重複登録

**症状**:
- イベントが複数回発火する
- 同じ処理が何度も実行される

**原因**:
- 初期化メソッドが複数回呼ばれている
- イベントリスナーを削除せずに再登録している

**解決方法**:

```javascript
// ❌ 悪い例
init() {
  document.getElementById('btn').addEventListener('click', () => {
    console.log('Clicked');
  });
  // 再度init()が呼ばれると、リスナーが重複登録される
}

// ✅ 良い例1: 既存リスナーを削除
class MyClass {
  constructor() {
    this.handleClick = this.handleClick.bind(this);
  }
  
  init() {
    const btn = document.getElementById('btn');
    btn.removeEventListener('click', this.handleClick);
    btn.addEventListener('click', this.handleClick);
  }
  
  handleClick() {
    console.log('Clicked');
  }
}

// ✅ 良い例2: 初期化フラグを使用
class MyClass {
  constructor() {
    this.initialized = false;
  }
  
  init() {
    if (this.initialized) return;
    
    document.getElementById('btn').addEventListener('click', () => {
      console.log('Clicked');
    });
    
    this.initialized = true;
  }
}

// ✅ 良い例3: イベントリスナーを管理
class MyClass {
  constructor() {
    this.eventListeners = [];
  }
  
  init() {
    this.removeEventListeners();
    
    const btn = document.getElementById('btn');
    const handler = () => console.log('Clicked');
    btn.addEventListener('click', handler);
    
    this.eventListeners.push({ element: btn, event: 'click', handler });
  }
  
  removeEventListeners() {
    this.eventListeners.forEach(({ element, event, handler }) => {
      element.removeEventListener(event, handler);
    });
    this.eventListeners = [];
  }
}
```

### 4. 非同期処理のタイミング問題

**症状**:
- データが取得される前に処理が実行される
- 画面が更新されない

**原因**:
- `async/await`を使用していない
- Promiseのチェーンが正しくない

**解決方法**:

```javascript
// ❌ 悪い例
loadData() {
  fetch('/api/data').then(response => response.json());
  this.renderData(); // データがまだ取得されていない
}

// ✅ 良い例1: async/awaitを使用
async loadData() {
  const response = await fetch('/api/data');
  const data = await response.json();
  this.renderData(data);
}

// ✅ 良い例2: Promiseチェーンを使用
loadData() {
  fetch('/api/data')
    .then(response => response.json())
    .then(data => this.renderData(data))
    .catch(error => console.error('Error:', error));
}

// ✅ 良い例3: ローディング状態を管理
async loadData() {
  this.setState({ loading: true });
  
  try {
    const response = await fetch('/api/data');
    const data = await response.json();
    this.renderData(data);
  } catch (error) {
    console.error('Error:', error);
    this.showError(error);
  } finally {
    this.setState({ loading: false });
  }
}
```

### 5. モジュールが初期化されない

**症状**:
- `window.shiseCalApp.modules.xxx is undefined`
- 機能が動作しない

**原因**:
- 初期化メソッドが呼ばれていない
- DOM要素が存在しない
- エラーが発生して初期化が中断されている

**解決方法**:

```javascript
// デバッグ: 初期化状態を確認
console.log('App:', window.shiseCalApp);
console.log('Modules:', window.shiseCalApp.modules);
console.log('Specific module:', window.shiseCalApp.modules.lifelineDocumentManager_electrical);

// 手動で初期化
window.shiseCalApp.initializeLifelineDocumentManagers();

// DOM要素の存在確認
console.log('Containers:', document.querySelectorAll('[data-lifeline-category]'));

// エラーログを確認
// ブラウザコンソールで赤いエラーメッセージを探す
```

## デバッグツール

### 1. ブラウザコンソール

```javascript
// アプリケーション全体の状態
window.shiseCalApp

// 特定のモジュール
window.shiseCalApp.modules.lifelineDocumentManager_electrical

// DOM要素の確認
document.querySelectorAll('[data-lifeline-category]')

// イベントリスナーの確認（Chrome DevTools）
getEventListeners(document.getElementById('my-element'))
```

### 2. ネットワークタブ

1. デベロッパーツールを開く（F12）
2. Networkタブを選択
3. 操作を実行
4. リクエスト/レスポンスを確認
   - Status: 200 OK か？
   - Response: エラーメッセージがないか？
   - Headers: CSRFトークンが正しいか？

### 3. Sourcesタブ（ブレークポイント）

1. Sourcesタブを開く
2. 該当するJSファイルを開く
3. 行番号をクリックしてブレークポイントを設定
4. 操作を実行
5. 変数の値を確認

## パフォーマンス問題

### 1. メモリリーク

**症状**:
- ページが徐々に重くなる
- ブラウザがクラッシュする

**原因**:
- イベントリスナーが削除されていない
- DOM要素への参照が残っている

**解決方法**:

```javascript
class MyClass {
  constructor() {
    this.eventListeners = [];
  }
  
  init() {
    // イベントリスナーを登録
    const handler = () => console.log('Clicked');
    document.getElementById('btn').addEventListener('click', handler);
    this.eventListeners.push({ element: btn, event: 'click', handler });
  }
  
  cleanup() {
    // イベントリスナーを削除
    this.eventListeners.forEach(({ element, event, handler }) => {
      element.removeEventListener(event, handler);
    });
    this.eventListeners = [];
  }
}

// ページ離脱時にクリーンアップ
window.addEventListener('beforeunload', () => {
  myInstance.cleanup();
});
```

### 2. 過度なDOM操作

**症状**:
- 画面の更新が遅い
- スクロールがカクカクする

**原因**:
- ループ内でDOM操作を繰り返している
- リフローを何度も発生させている

**解決方法**:

```javascript
// ❌ 悪い例
items.forEach(item => {
  const div = document.createElement('div');
  div.textContent = item.name;
  container.appendChild(div); // 毎回リフローが発生
});

// ✅ 良い例1: DocumentFragmentを使用
const fragment = document.createDocumentFragment();
items.forEach(item => {
  const div = document.createElement('div');
  div.textContent = item.name;
  fragment.appendChild(div);
});
container.appendChild(fragment); // 1回だけリフロー

// ✅ 良い例2: innerHTMLを使用
const html = items.map(item => `<div>${item.name}</div>`).join('');
container.innerHTML = html; // 1回だけリフロー
```

## エラーメッセージ一覧

| エラーメッセージ | 原因 | 解決方法 |
|----------------|------|---------|
| `Cannot read property 'xxx' of null` | DOM要素が見つからない | 要素の存在確認、セレクタの確認 |
| `this.method is not a function` | thisコンテキストの喪失 | アロー関数、bind、selfを使用 |
| `Uncaught ReferenceError: xxx is not defined` | 変数が定義されていない | 変数の宣言、スコープの確認 |
| `Failed to fetch` | ネットワークエラー | URL、CORS、CSRFトークンの確認 |
| `Unexpected token` | JSON解析エラー | レスポンスの内容確認 |

## 関連ドキュメント

- [ライフライン設備ドキュメント表示修正](./lifeline-document-display-fix.md)
- [JavaScript アーキテクチャ](./javascript-architecture.md)
- [フロントエンド構造](./frontend-structure.md)
