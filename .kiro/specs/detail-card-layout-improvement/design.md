# Design Document

## Overview

詳細カードの表示改善により、行間の最適化、ラベルと項目の境界明確化、未設定項目の表示制御を実現します。既存のBootstrap 5とカスタムCSSを活用し、PC環境に最適化された統一レイアウトを提供します。

## Architecture

### 現在の実装分析

現在のシステムでは以下の詳細カード表示パターンが使用されています：

1. **施設基本情報詳細** (`resources/views/facilities/basic-info/show.blade.php`)
   - `.facility-info-card` クラスを使用
   - `.mb-3` による項目間マージン（24px）
   - `.detail-row` / `.detail-label` / `.detail-value` 構造

2. **土地情報詳細** (`resources/views/facilities/land-info/partials/display-card.blade.php`)
   - 同様の `.facility-detail-table` 構造
   - `.detail-row` に `padding: 0.75rem 0` (12px上下)
   - `border-bottom: 1px solid #f0f0f0` による区切り線

3. **既存CSSファイル**
   - `resources/css/pages/facilities.css` - 詳細表示スタイル
   - `resources/css/components.css` - 共通コンポーネント

### 設計方針

1. **既存ファイル拡張**: 新規ファイル作成を避け、既存CSSファイルに追加
2. **PC最適化**: 1024px以上の画面幅に特化
3. **統一性**: 全詳細カードで一貫したレイアウト
4. **後方互換性**: 既存機能の維持

## Components and Interfaces

### 1. CSS コンポーネント拡張

#### 1.1 詳細カード共通スタイル (`resources/css/pages/facilities.css`)

```css
/* 詳細カード改善スタイル */
.detail-card-improved {
  /* 行間最適化 */
  --detail-row-padding: 0.5rem 0; /* 8px上下（従来の33%削減） */
  --detail-row-gap: 0.75rem; /* 12px項目間隔 */
  
  /* ラベル・値境界明確化 */
  --label-width: 140px;
  --label-border-color: #e9ecef;
  --value-padding-left: 1rem;
}

.detail-card-improved .detail-row {
  padding: var(--detail-row-padding);
  border-bottom: 1px solid var(--label-border-color);
  display: flex;
  align-items: flex-start;
  min-height: 2.5rem; /* 最小高さ確保 */
}

.detail-card-improved .detail-label {
  min-width: var(--label-width);
  max-width: var(--label-width);
  font-weight: 600;
  color: #495057;
  padding-right: var(--value-padding-left);
  border-right: 2px solid var(--label-border-color);
  margin-right: var(--value-padding-left);
  flex-shrink: 0;
  line-height: 1.4;
}

.detail-card-improved .detail-value {
  flex: 1;
  color: #212529;
  word-break: break-word;
  line-height: 1.4;
}
```

#### 1.2 未設定項目制御スタイル

```css
/* 未設定項目表示制御 */
.detail-card-improved .empty-field {
  display: none; /* デフォルト非表示 */
}

.detail-card-improved.show-empty-fields .empty-field {
  display: flex; /* 表示時 */
}

.empty-field .detail-value {
  color: #6c757d;
  font-style: italic;
}

/* 表示切り替えボタン */
.empty-fields-toggle {
  font-size: 0.875rem;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  transition: all 0.3s ease;
}
```

### 2. JavaScript コンポーネント

#### 2.1 未設定項目制御モジュール (`resources/js/modules/detail-card-controller.js`)

```javascript
export class DetailCardController {
  constructor() {
    this.initializeToggleButtons();
    this.loadUserPreferences();
  }

  initializeToggleButtons() {
    // 各詳細カードにトグルボタンを追加
    document.querySelectorAll('.detail-card-improved').forEach(card => {
      this.addToggleButton(card);
    });
  }

  addToggleButton(card) {
    const header = card.querySelector('.card-header');
    if (!header) return;

    const button = this.createToggleButton();
    header.appendChild(button);
  }

  toggleEmptyFields(card) {
    card.classList.toggle('show-empty-fields');
    this.saveUserPreference(card.dataset.section);
  }
}
```

### 3. Blade テンプレート修正

#### 3.1 共通詳細カード構造

```blade
<div class="card facility-info-card detail-card-improved" data-section="{{ $section }}">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ $title }}</h5>
        <!-- 未設定項目トグルボタンはJSで自動追加 -->
    </div>
    <div class="card-body">
        <div class="facility-detail-table">
            @foreach($fields as $field)
                <div class="detail-row {{ empty($field['value']) ? 'empty-field' : '' }}">
                    <span class="detail-label">{{ $field['label'] }}</span>
                    <span class="detail-value">
                        {{ $field['value'] ?? '未設定' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>
```

## Data Models

### 1. ユーザー設定保存

```javascript
// LocalStorage構造
{
  "detailCardPreferences": {
    "facility_basic": { "showEmptyFields": false },
    "land_basic": { "showEmptyFields": false },
    "land_financial": { "showEmptyFields": true }
  }
}
```

### 2. 詳細カード設定

```php
// config/detail-cards.php
return [
    'default_show_empty' => false,
    'sections' => [
        'facility_basic' => [
            'title' => '基本情報',
            'icon' => 'fas fa-building',
            'fields' => [
                'company_name' => '会社名',
                'office_code' => '事業所コード',
                // ...
            ]
        ],
        // ...
    ]
];
```

## Error Handling

### 1. JavaScript エラー処理

```javascript
try {
    this.toggleEmptyFields(card);
} catch (error) {
    console.warn('詳細カード制御エラー:', error);
    // フォールバック: 基本表示を維持
}
```

### 2. CSS フォールバック

```css
/* 古いブラウザ対応 */
.detail-card-improved .detail-row {
    display: flex;
    display: -webkit-flex; /* Safari対応 */
}

/* CSS変数未対応ブラウザ */
.no-css-variables .detail-card-improved .detail-label {
    min-width: 140px;
    padding-right: 1rem;
    border-right: 2px solid #e9ecef;
}
```

## Testing Strategy

### 1. 視覚的回帰テスト

```javascript
// tests/js/detail-card-layout.test.js
describe('DetailCardController', () => {
  test('行間が適切に調整される', () => {
    const card = createTestCard();
    expect(getComputedStyle(card.querySelector('.detail-row')).padding)
      .toBe('8px 0px');
  });

  test('未設定項目が初期状態で非表示', () => {
    const card = createTestCard();
    const emptyFields = card.querySelectorAll('.empty-field');
    emptyFields.forEach(field => {
      expect(field.style.display).toBe('none');
    });
  });
});
```

### 2. ブラウザ互換性テスト

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### 3. レスポンシブテスト

PC環境での画面幅テスト：
- 1024px (最小対応幅)
- 1366px (標準ノートPC)
- 1920px (フルHD)
- 2560px (4K)

## Implementation Plan

### Phase 1: CSS基盤整備
1. `resources/css/pages/facilities.css` に改善スタイル追加
2. 既存詳細カードへのクラス適用

### Phase 2: JavaScript機能実装
1. `resources/js/modules/detail-card-controller.js` 作成
2. `resources/js/app.js` への統合

### Phase 3: テンプレート更新
1. 施設基本情報詳細の更新
2. 土地情報詳細の更新
3. その他詳細画面の統一

### Phase 4: 設定・テスト
1. 設定ファイル作成
2. ユーザー設定保存機能
3. テスト実装・実行

## Performance Considerations

### 1. CSS最適化
- CSS変数使用による再計算最小化
- 不要なセレクター削除
- レンダリング最適化

### 2. JavaScript最適化
- イベントリスナーの効率的な管理
- DOM操作の最小化
- LocalStorage使用による設定永続化

### 3. 読み込み最適化
- 既存ファイル拡張による追加リクエスト回避
- インライン処理の最小化