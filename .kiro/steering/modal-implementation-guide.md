---
inclusion: always
---

# モーダル実装ガイドライン

## 重要な原則

### 🚨 最優先ルール
**既存の動作しているモーダル実装がある場合は、その構造を完全にコピーして使用する**

新しいカスタムモーダル実装を作成せず、動作が保証されている既存実装を再利用することで、問題を回避する。

## 実装パターン

### ✅ 推奨：既存実装の完全コピー

```blade
{{-- 動作している実装をベースにする --}}
<div class="modal" id="existing-modal-pattern" tabindex="-1" aria-labelledby="title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="existing-form-pattern" method="POST">
                @csrf
                {{-- 既存と同じ構造を維持 --}}
            </form>
        </div>
    </div>
</div>
```

### ❌ 避けるべき：カスタム実装

```blade
{{-- 新しいカスタム実装は避ける --}}
<div class="modal fade" id="custom-modal-{{ $uniqueId }}" onclick="customHandler()">
    {{-- 複雑なカスタムJavaScript処理 --}}
</div>
```

## 具体的な実装手順

### 1. 動作している実装を特定
- `resources/views/facilities/documents/index.blade.php` のモーダル実装
- `app-unified.js` の `DocumentManager` クラス処理
- 標準的なBootstrapモーダル属性

### 2. 必要最小限の変更のみ実施
```blade
{{-- 変更するのは表示テキストとIDのみ --}}
<h5 class="modal-title">{{ $categoryName }} - ファイルアップロード</h5>
```

### 3. JavaScript処理の統一
- 既存の `DocumentManager` クラスを活用
- `document-management-container` IDを使用
- カスタムJavaScriptクラスは作成しない

## 技術的な詳細

### Bootstrap モーダル属性（必須）
```html
data-bs-backdrop="static"    <!-- 背景クリックで閉じない -->
data-bs-keyboard="true"      <!-- ESCキーで閉じる -->
aria-labelledby="modal-title" <!-- アクセシビリティ -->
aria-hidden="true"           <!-- スクリーンリーダー対応 -->
```

### フォーム構造（必須）
```html
<form id="standard-form-id" method="POST">
    @csrf
    <input type="hidden" name="required_field" value="">
    <!-- 標準的なフォーム要素 -->
</form>
```

### JavaScript初期化（推奨）
```javascript
// app-unified.js の既存クラスを活用
// カスタムクラスは作成しない
// 既存のイベントリスナーパターンを使用
```

## トラブルシューティング

### モーダルが開かない場合
1. **Bootstrap属性を確認**：`data-bs-toggle="modal"` と `data-bs-target="#modal-id"`
2. **既存実装と比較**：動作しているモーダルとの差分を確認
3. **JavaScript エラーチェック**：ブラウザコンソールでエラー確認

### モーダルが操作できない場合
1. **HTML構造を統一**：動作している実装と完全に同じ構造にする
2. **JavaScript処理を統一**：既存の `DocumentManager` クラスを使用
3. **フォームIDを統一**：既存パターンと同じIDを使用

### フォーム送信が動作しない場合
1. **フォーム構造を確認**：`method="POST"` と `@csrf` の存在
2. **イベントリスナーを確認**：既存の処理が適用されているか
3. **ルート設定を確認**：適切なAPIエンドポイントが存在するか

## 実装チェックリスト

### モーダル作成時
- [ ] 既存の動作しているモーダル実装を特定
- [ ] HTML構造を完全にコピー
- [ ] 必要最小限の変更のみ実施（テキスト、ID等）
- [ ] Bootstrap標準属性をすべて含める
- [ ] カスタムJavaScript処理は作成しない

### 動作確認時
- [ ] モーダルが正常に開く
- [ ] フォーム要素が操作可能
- [ ] フォーム送信が動作する
- [ ] モーダルが正常に閉じる
- [ ] エラーハンドリングが動作する

## 参考実装

### 成功例：ライフライン設備ドキュメント管理
```blade
{{-- 動作しているドキュメントタブと同じ構造を使用 --}}
<div class="document-management" data-facility-id="{{ $facility->id }}" id="document-management-container">
    {{-- 既存と同じツールバー構造 --}}
    <div class="document-toolbar mb-3">
        <button type="button" id="create-folder-btn" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#create-folder-modal">
            <i class="fas fa-folder-plus me-1"></i>新しいフォルダ
        </button>
    </div>
</div>

{{-- 既存と同じモーダル構造 --}}
<div class="modal" id="create-folder-modal" tabindex="-1" aria-labelledby="create-folder-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="create-folder-form" method="POST">
                @csrf
                {{-- 既存と同じフォーム構造 --}}
            </form>
        </div>
    </div>
</div>
```

## 重要な教訓

1. **「車輪の再発明」を避ける**：動作している実装があれば、それを再利用する
2. **最小変更の原則**：必要最小限の変更のみ行う
3. **標準化の重要性**：Bootstrap標準の実装パターンに従う
4. **既存コードの活用**：`app-unified.js` の既存クラスを最大限活用する
5. **段階的な実装**：まず動作させてから、必要に応じて機能を追加する
6. **折りたたみ領域内のモーダル問題**：collapse内でレンダリングされるモーダルは、親要素のスタッキングコンテキストに負けて背面に回る可能性がある

## 今後の開発指針

- 新しいモーダルが必要な場合は、まず既存の動作している実装を探す
- カスタム実装は最後の手段とし、可能な限り既存パターンを再利用する
- 複雑なJavaScriptクラスよりも、シンプルな標準実装を優先する
- 問題が発生した場合は、既存の動作している実装との差分を確認する
- **折りたたみ領域内でモーダルを使用する場合は、必ずモーダルhoisting処理を実装する**

## 折りたたみ領域内モーダル修正パターン（2024年12月適用）

### 適用済みファイル
- `resources/views/facilities/lifeline-equipment/electrical.blade.php`
- `resources/views/facilities/lifeline-equipment/gas.blade.php`
- `resources/views/facilities/lifeline-equipment/water.blade.php`
- `resources/views/facilities/lifeline-equipment/elevator.blade.php`

### 修正内容
1. **モーダルhoisting処理**：折りたたみ内のモーダルを`<body>`直下に移動
2. **z-index強制設定**：`.modal-backdrop`を2000、`.modal`を2010に固定
3. **余計なBackdrop掃除**：複数残ったBackdropの最新以外を削除
4. **overflow設定**：折りたたみ領域に`overflow: visible`を適用

### JavaScript修正パターン
```javascript
// ===== Modal hoisting & z-index fix for document manager =====
function hoistModals(container) {
    if (!container) return;
    container.querySelectorAll('.modal').forEach(function(modal) {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });
}

// 初期化時とcollapse展開時に実行
hoistModals(documentSection);
documentSection.addEventListener('shown.bs.collapse', function () {
    hoistModals(documentSection);
});

// モーダル表示時のz-index強制設定
document.addEventListener('show.bs.modal', function (ev) {
    var modalEl = ev.target;
    if (modalEl) {
        modalEl.style.zIndex = '2010';
    }
    setTimeout(function () {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(function (bd) {
            bd.style.zIndex = '2000';
        });
    }, 0);
});

// 余計なBackdrop掃除
document.addEventListener('hidden.bs.modal', function () {
    var backdrops = document.querySelectorAll('.modal-backdrop');
    if (backdrops.length > 1) {
        for (var i = 0; i < backdrops.length - 1; i++) {
            backdrops[i].parentNode.removeChild(backdrops[i]);
        }
    }
});
```

### CSS修正パターン
```css
/* ==== Modal stacking fixes for [category] documents section ==== */
#[category]-documents-section { 
    overflow: visible; /* avoid creating a clipping context for absolute/fixed elements */
}
/* Ensure Bootstrap modal/backdrop are above collapsed/card content */
.modal-backdrop {
    z-index: 2000 !important;
}
.modal {
    z-index: 2010 !important;
}
```
- **折りたたみ領域内でモーダルを使用する場合は、必ずモーダルhoisting処理を実装する**

## 折りたたみ領域内モーダル修正パターン（2024年12月適用）

### 適用済みファイル
- `resources/views/facilities/lifeline-equipment/electrical.blade.php`
- `resources/views/facilities/lifeline-equipment/gas.blade.php`
- `resources/views/facilities/lifeline-equipment/water.blade.php`
- `resources/views/facilities/lifeline-equipment/elevator.blade.php`

### 修正内容
1. **モーダルhoisting処理**：折りたたみ内のモーダルを`<body>`直下に移動
2. **z-index強制設定**：`.modal-backdrop`を2000、`.modal`を2010に固定
3. **余計なBackdrop掃除**：複数残ったBackdropの最新以外を削除
4. **overflow設定**：折りたたみ領域に`overflow: visible`を適用

### JavaScript修正パターン
```javascript
// ===== Modal hoisting & z-index fix for document manager =====
function hoistModals(container) {
    if (!container) return;
    container.querySelectorAll('.modal').forEach(function(modal) {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });
}

// 初期化時とcollapse展開時に実行
hoistModals(documentSection);
documentSection.addEventListener('shown.bs.collapse', function () {
    hoistModals(documentSection);
});

// モーダル表示時のz-index強制設定
document.addEventListener('show.bs.modal', function (ev) {
    var modalEl = ev.target;
    if (modalEl) {
        modalEl.style.zIndex = '2010';
    }
    setTimeout(function () {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(function (bd) {
            bd.style.zIndex = '2000';
        });
    }, 0);
});

// 余計なBackdrop掃除
document.addEventListener('hidden.bs.modal', function () {
    var backdrops = document.querySelectorAll('.modal-backdrop');
    if (backdrops.length > 1) {
        for (var i = 0; i < backdrops.length - 1; i++) {
            backdrops[i].parentNode.removeChild(backdrops[i]);
        }
    }
});
```

### CSS修正パターン
```css
/* ==== Modal stacking fixes for [category] documents section ==== */
#[category]-documents-section { 
    overflow: visible; /* avoid creating a clipping context for absolute/fixed elements */
}
/* Ensure Bootstrap modal/backdrop are above collapsed/card content */
.modal-backdrop {
    z-index: 2000 !important;
}
.modal {
    z-index: 2010 !important;
}
```