# CSVエクスポート - カテゴリ折りたたみ機能実装

## 概要
CSVエクスポート画面の出力項目選択セクションに、カテゴリごとの表示/非表示（折りたたみ）機能を追加しました。

## 実装日
2025年10月10日

## 実装内容

### 1. 機能概要
- 各カテゴリ（基本情報、土地情報、建物情報、ライフライン設備など）のヘッダー部分に折りたたみボタンを追加
- ボタンをクリックすることで、そのカテゴリのフィールド一覧を表示/非表示できる
- 折りたたみ状態に応じてアイコンが回転（下向き矢印 ⇔ 右向き矢印）
- デフォルトではすべてのカテゴリが展開された状態

### 2. 対象カテゴリ
以下のすべてのカテゴリに折りたたみ機能を実装：

#### メインカテゴリ
- **基本情報** (`category-facility-fields`)
- **土地情報** (`category-land-fields`)
- **建物情報** (`category-building-fields`)

#### ライフライン設備サブカテゴリ
- **電気設備** (`category-electric-fields`)
- **水道設備** (`category-water-fields`)
- **ガス設備** (`category-gas-fields`)
- **エレベーター設備** (`category-elevator-fields`)
- **空調・照明設備** (`category-hvac-fields`)

#### その他カテゴリ
- **防犯・防災設備** (`category-security-fields`)

### 3. UI/UX設計

#### 折りたたみボタン
```html
<button class="btn btn-link text-decoration-none p-0 me-2 category-toggle-btn" 
        type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#category-facility-fields" 
        aria-expanded="true" 
        aria-controls="category-facility-fields">
    <i class="fas fa-chevron-down category-toggle-icon"></i>
</button>
```

#### 折りたたみ可能なコンテナ
```html
<div class="collapse show" id="category-facility-fields">
    <div class="row">
        <!-- フィールドチェックボックス -->
    </div>
</div>
```

### 4. JavaScript実装

#### 初期化処理
```javascript
setupCategoryToggleHandlers() {
  // Handle category collapse toggle icon rotation
  document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
    const targetId = button.getAttribute('data-bs-target');
    if (!targetId) return;

    const targetElement = document.querySelector(targetId);
    if (!targetElement) return;

    // Set initial icon state based on collapse state
    const icon = button.querySelector('.category-toggle-icon');
    if (icon) {
      if (targetElement.classList.contains('show')) {
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-down');
      } else {
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-right');
      }
    }

    // Listen for collapse events
    targetElement.addEventListener('show.bs.collapse', () => {
      const icon = button.querySelector('.category-toggle-icon');
      if (icon) {
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-down');
      }
    });

    targetElement.addEventListener('hide.bs.collapse', () => {
      const icon = button.querySelector('.category-toggle-icon');
      if (icon) {
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-right');
      }
    });
  });
}
```

### 5. CSS実装

#### スタイル定義
```css
/* Category Toggle Button */
.category-toggle-btn {
  color: #6c757d;
  font-size: 0.875rem;
  transition: color 0.2s ease;
}

.category-toggle-btn:hover {
  color: #495057;
}

.category-toggle-btn:focus {
  box-shadow: none;
}

.category-toggle-icon {
  transition: transform 0.2s ease;
  display: inline-block;
}

.category-toggle-btn[aria-expanded="true"] .category-toggle-icon {
  transform: rotate(0deg);
}

.category-toggle-btn[aria-expanded="false"] .category-toggle-icon {
  transform: rotate(-90deg);
}
```

## 技術仕様

### Bootstrap Collapse
- Bootstrap 5の標準Collapse機能を使用
- `data-bs-toggle="collapse"` 属性でトグル動作を実現
- `data-bs-target` 属性で対象要素を指定
- `aria-expanded` 属性で展開状態を管理（アクセシビリティ対応）

### アイコンアニメーション
- Font Awesome 6のシェブロンアイコンを使用
- CSS transitionで滑らかな回転アニメーション
- 展開時: `fa-chevron-down`（下向き矢印）
- 折りたたみ時: `fa-chevron-right`（右向き矢印）

### イベント処理
- `show.bs.collapse`: 展開開始時にアイコンを下向きに変更
- `hide.bs.collapse`: 折りたたみ開始時にアイコンを右向きに変更
- ページ読み込み時に初期状態を設定

## ユーザーメリット

### 1. 視認性の向上
- 必要なカテゴリのみを表示することで、画面がすっきりする
- スクロール量が減り、目的のフィールドを見つけやすくなる

### 2. 操作性の向上
- 大量のフィールドから選択する際の負担が軽減
- カテゴリ単位での管理が容易になる

### 3. パフォーマンス
- DOMレンダリングの負荷は変わらないが、視覚的な情報量が減る
- ユーザーの認知負荷が軽減される

## 既存機能との互換性

### 影響なし
- カテゴリチェックボックスによる一括選択機能
- フィールドカウント表示機能
- 全選択/全解除ボタン
- お気に入り機能
- CSVエクスポート機能

### 動作確認項目
- [x] カテゴリチェックボックスで一括選択/解除
- [x] 折りたたみ状態でもカウントが正しく表示される
- [x] 全選択/全解除ボタンが正常に動作
- [x] 折りたたまれたカテゴリのフィールドも正しくエクスポートされる

## 今後の拡張可能性

### 1. 状態の保存
- ユーザーの折りたたみ状態をlocalStorageに保存
- 次回アクセス時に前回の状態を復元

### 2. 一括操作
- 「すべて展開」「すべて折りたたみ」ボタンの追加
- キーボードショートカット対応

### 3. カテゴリグループ化
- ライフライン設備全体を折りたたみ可能にする
- 階層的な折りたたみ構造の実装

## ファイル変更履歴

### 変更ファイル
1. `resources/views/export/csv/index.blade.php`
   - 各カテゴリヘッダーに折りたたみボタンを追加
   - フィールドリストをcollapse要素でラップ

2. `resources/js/modules/export.js`
   - `setupCategoryToggleHandlers()` メソッドを追加
   - `init()` メソッドに初期化処理を追加

3. `resources/css/pages/export.css`
   - 折りたたみボタンのスタイルを追加
   - アイコンアニメーションのスタイルを追加

### 新規ファイル
- `docs/csv-export-category-toggle-implementation.md`（本ドキュメント）

## テスト項目

### 機能テスト
- [ ] 各カテゴリの折りたたみボタンが正常に動作する
- [ ] アイコンが正しく回転する
- [ ] 折りたたみ状態でもチェックボックスの選択が保持される
- [ ] 折りたたまれたフィールドもCSVに正しく出力される

### ブラウザ互換性
- [ ] Chrome（最新版）
- [ ] Firefox（最新版）
- [ ] Safari（最新版）
- [ ] Edge（最新版）

### レスポンシブ対応
- [ ] デスクトップ表示
- [ ] タブレット表示
- [ ] モバイル表示

### アクセシビリティ
- [ ] キーボード操作で折りたたみ可能
- [ ] スクリーンリーダーで状態が読み上げられる
- [ ] aria属性が適切に設定されている

## 参考資料
- [Bootstrap 5 Collapse Documentation](https://getbootstrap.com/docs/5.1/components/collapse/)
- [Font Awesome Icons](https://fontawesome.com/icons)
- [ARIA: button role](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Roles/button_role)
