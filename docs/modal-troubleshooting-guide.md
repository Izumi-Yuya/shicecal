# モーダル問題解決ガイド

## 概要

複数のBladeコンポーネントが同じページに存在する際に発生するモーダルID重複問題と、関連するJavaScript・CSS構文エラーの解決方法をまとめたガイドです。

## 問題パターンと解決方法

### 1. モーダルID重複問題

#### 問題
同じコンポーネントが複数回使用される際にモーダルIDが重複し、正しいモーダルが開かない。

#### 解決方法

**Bladeコンポーネント側**:
```php
@php
    // ユニークIDを生成
    $uniqueId = $facility->id . '_' . $category . '_' . uniqid();
@endphp

<!-- モーダルIDにユニークIDを使用 -->
<div class="modal fade" id="uploadModal-{{ $uniqueId }}" tabindex="-1">
    <!-- モーダル内容 -->
</div>

<!-- フォームIDも同様に -->
<form id="uploadForm-{{ $uniqueId }}" enctype="multipart/form-data">
    <!-- フォーム内容 -->
</form>
```

**JavaScript初期化時にユニークIDを渡す**:
```javascript
window.LifelineDocumentManager.init('{{ $category }}', {
    facilityId: {{ $facility->id }},
    uniqueId: '{{ $uniqueId }}',
    // その他のオプション
});
```

### 2. JavaScript要素存在チェック

#### 問題
存在しないモーダル要素への参照でエラーが発生する。

#### 解決方法

```javascript
showUploadModal(category) {
    const config = this.categories.get(category);
    const uniqueId = config?.uniqueId || category;
    const modalElement = document.getElementById(`uploadModal-${uniqueId}`);
    
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        console.error(`Upload modal not found for category: ${category}, uniqueId: ${uniqueId}`);
    }
}
```

### 3. イベントリスナーの適切な設定

#### 問題
ユニークIDに対応していないイベントリスナーで要素が見つからない。

#### 解決方法

```javascript
setupEventListeners(category, uniqueId) {
    // ユニークIDまたはカテゴリをフォールバックとして使用
    const uploadForm = document.getElementById(`uploadForm-${uniqueId || category}`);
    if (uploadForm) {
        uploadForm.addEventListener('submit', (e) => this.handleFileUpload(e, category));
    }

    const createFolderForm = document.getElementById(`createFolderForm-${uniqueId || category}`);
    if (createFolderForm) {
        createFolderForm.addEventListener('submit', (e) => this.handleFolderCreate(e, category));
    }
}
```

### 4. グローバルオブジェクトアクセスの統一

#### 問題
`LifelineDocumentManager`への不適切なアクセスでエラーが発生する。

#### 解決方法

**Blade内のonclick属性**:
```html
<button onclick="window.LifelineDocumentManager.showUploadModal('{{ $category }}')">
    アップロード
</button>
```

**JavaScript内の動的HTML生成**:
```javascript
const html = `
    <a href="#" onclick="window.LifelineDocumentManager.navigateToFolder('${category}', ${folder.id})">
        ${folder.name}
    </a>
`;
```

### 5. モーダルを閉じる処理の修正

#### 問題
モーダルを閉じる際にも正しいユニークIDを使用する必要がある。

#### 解決方法

```javascript
// ファイルアップロード成功時
if (result.success) {
    const config = this.categories.get(category);
    const uniqueId = config?.uniqueId || category;
    const modal = bootstrap.Modal.getInstance(document.getElementById(`uploadModal-${uniqueId}`));
    modal?.hide();
    
    // その他の処理...
}
```

## JavaScript構文エラーの解決

### 1. クラス構造の維持

#### 問題
クラスの外にメソッドが出てしまい構文エラーが発生する。

#### 解決方法

```javascript
class LifelineDocumentManager {
    constructor() {
        // コンストラクタ
    }
    
    // 既存メソッド...
    
    formatDate(dateString) {
        // 最後のメソッド
        return date.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }
    
    // 新しいメソッドはクラス内に追加（インデントに注意）
    newMethod() {
        console.log('新しいメソッド');
    }
} // ← クラス終了の括弧

// グローバルインスタンス作成
window.LifelineDocumentManager = new LifelineDocumentManager();
export default LifelineDocumentManager;
```

### 2. メソッドの適切な追加

新しいメソッドを追加する際は：

1. クラスの最後の括弧 `}` の**前**に追加
2. 適切なインデント（2スペース）を使用
3. メソッド間に空行を入れる

```javascript
// 正しい例
class MyClass {
    existingMethod() {
        // 既存メソッド
    }
    
    // 新しいメソッドを追加
    newMethod() {
        // 新しいメソッド
    }
} // クラス終了
```

## CSS構文エラーの解決

### 1. コメント構文の修正

#### 問題
不正なコメント構文でビルドエラーが発生する。

#### 解決方法

```css
/* 正しいコメント構文 */
.lifeline-document-manager {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

/* 間違った構文（スペースが入っている）- これはエラーになる */
/ * 間違ったコメント */
```

## 実装チェックリスト

### コンポーネント作成時
- [ ] ユニークIDの生成
- [ ] モーダルIDにユニークIDを使用
- [ ] フォームIDにユニークIDを使用
- [ ] JavaScript初期化時にユニークIDを渡す

### JavaScript実装時
- [ ] 要素存在チェックの実装
- [ ] グローバルオブジェクトへの適切なアクセス
- [ ] イベントリスナーでのユニークID対応
- [ ] モーダル操作でのユニークID使用

### 構文チェック
- [ ] クラス構造の維持
- [ ] 適切なインデント
- [ ] CSSコメント構文の確認
- [ ] ビルドエラーの確認

## トラブルシューティング

### よくあるエラーと対処法

1. **"Modal not found" エラー**
   - 要素存在チェックを追加
   - ユニークIDが正しく渡されているか確認

2. **"Cannot read property of undefined" エラー**
   - グローバルオブジェクトアクセスに `window.` を追加
   - オブジェクトの初期化タイミングを確認

3. **"Parse error" ビルドエラー**
   - クラス構造を確認
   - CSSコメント構文を確認

4. **モーダルが開かない**
   - モーダルIDの重複を確認
   - Bootstrap初期化を確認

### デバッグ方法

```javascript
// 要素存在確認
const modalElement = document.getElementById(`uploadModal-${uniqueId}`);
console.log('Modal element:', modalElement);

// 設定確認
const config = this.categories.get(category);
console.log('Config:', config);

// ユニークID確認
console.log('Unique ID:', uniqueId);
```

## 予防策

1. **標準化されたコンポーネント作成**
   - テンプレートの使用
   - ユニークID生成の自動化

2. **コードレビュー**
   - モーダル実装の確認
   - 構文チェック

3. **自動テスト**
   - モーダル機能のテスト
   - 複数インスタンスのテスト

4. **開発ツール**
   - ESLint設定
   - Prettier設定
   - ビルド時エラーチェック

## 関連ファイル

- `resources/js/modules/LifelineDocumentManager.js` - メインJavaScriptクラス
- `resources/views/components/lifeline-document-manager.blade.php` - Bladeコンポーネント
- `resources/css/app-unified.css` - スタイル定義
- `tests/Feature/LifelineDocumentTest.php` - 機能テスト

## 更新履歴

- 2024-12-XX: 初版作成（ライフライン設備ドキュメント管理機能実装時）