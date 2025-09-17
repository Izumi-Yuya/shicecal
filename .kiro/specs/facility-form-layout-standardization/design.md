# 設計書

## 概要

この設計書では、施設関連の編集フォームのレイアウトを標準化するための共通レイアウトシステムを定義します。現在の基本情報編集フォームの優れたレイアウトパターンを基に、再利用可能なBladeコンポーネントを作成し、土地情報編集フォームを含む将来のすべての編集フォームで一貫したユーザーエクスペリエンスを提供します。

## アーキテクチャ

### 全体アーキテクチャ

```
施設編集フォーム標準化システム
├── 共通レイアウトコンポーネント
│   ├── FacilityEditLayout (メインレイアウト)
│   ├── FacilityInfoCard (施設情報表示)
│   ├── FormSection (フォームセクション)
│   └── FormActions (アクション部分)
├── 既存フォームの更新
│   ├── 土地情報編集フォーム
│   └── 基本情報編集フォーム（参考実装）
└── スタイリング
    ├── 共通CSS変数
    ├── レスポンシブデザイン
    └── アクセシビリティ対応
```

### コンポーネント階層

```
FacilityEditLayout
├── ヘッダーセクション
│   ├── ページタイトル
│   ├── パンくずリスト
│   └── アクションボタン（戻る）
├── FacilityInfoCard
│   ├── 施設名
│   ├── 住所
│   └── 施設タイプ
├── フォームコンテンツ（スロット）
│   └── FormSection（複数）
│       ├── セクションヘッダー（アイコン + タイトル）
│       └── フォームフィールド
└── FormActions
    ├── キャンセルボタン
    └── 保存ボタン
```

## コンポーネントと インターフェース

### 1. FacilityEditLayout コンポーネント

**ファイル:** `resources/views/components/facility/edit-layout.blade.php`

**プロパティ:**
- `title` (string): ページタイトル
- `facility` (Facility): 施設オブジェクト
- `breadcrumbs` (array): パンくずリストの配列
- `backRoute` (string): 戻るボタンのルート
- `formAction` (string): フォームのアクション URL
- `formMethod` (string, default: 'POST'): フォームのHTTPメソッド

**スロット:**
- `default`: メインフォームコンテンツ
- `actions`: カスタムアクションボタン（オプション）

### 2. FacilityInfoCard コンポーネント

**ファイル:** `resources/views/components/facility/info-card.blade.php`

**プロパティ:**
- `facility` (Facility): 施設オブジェクト
- `showType` (boolean, default: true): 施設タイプの表示有無

### 3. FormSection コンポーネント

**ファイル:** `resources/views/components/form/section.blade.php`

**プロパティ:**
- `title` (string): セクションタイトル
- `icon` (string): Font Awesomeアイコンクラス
- `iconColor` (string, default: 'primary'): アイコンの色
- `collapsible` (boolean, default: false): 折りたたみ可能かどうか
- `collapsed` (boolean, default: false): 初期状態で折りたたまれているか

**スロット:**
- `default`: セクションコンテンツ

### 4. FormActions コンポーネント

**ファイル:** `resources/views/components/form/actions.blade.php`

**プロパティ:**
- `cancelRoute` (string): キャンセルボタンのルート
- `cancelText` (string, default: 'キャンセル'): キャンセルボタンのテキスト
- `submitText` (string, default: '保存'): 送信ボタンのテキスト
- `submitIcon` (string, default: 'fas fa-save'): 送信ボタンのアイコン

**スロット:**
- `additional`: 追加のアクションボタン（オプション）

## データモデル

### Breadcrumb データ構造

```php
[
    [
        'title' => 'ホーム',
        'route' => 'facilities.index',
        'active' => false
    ],
    [
        'title' => '施設詳細',
        'route' => 'facilities.show',
        'params' => [$facility],
        'active' => false
    ],
    [
        'title' => '土地情報編集',
        'active' => true
    ]
]
```

### コンポーネント設定

```php
// config/facility-form.php
return [
    'layout' => [
        'container_class' => 'container-fluid',
        'card_spacing' => 'mb-4',
        'section_spacing' => 'mb-4',
    ],
    'icons' => [
        'basic_info' => 'fas fa-info-circle',
        'land_info' => 'fas fa-map',
        'contact_info' => 'fas fa-phone',
        'building_info' => 'fas fa-building',
        'service_info' => 'fas fa-cogs',
    ],
    'colors' => [
        'primary' => 'primary',
        'success' => 'success',
        'info' => 'info',
        'warning' => 'warning',
        'danger' => 'danger',
    ]
];
```

## エラーハンドリング

### バリデーションエラー表示

1. **フォームレベルエラー**: ページ上部にアラートとして表示
2. **フィールドレベルエラー**: 各入力フィールドの下に表示
3. **セクションレベルエラー**: エラーがあるセクションのヘッダーに警告アイコン表示

### エラー表示パターン

```blade
@if ($errors->any())
    <div class="alert alert-danger mb-4">
        <h6><i class="fas fa-exclamation-triangle me-2"></i>入力エラーがあります</h6>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

## テスト戦略

### 1. コンポーネントテスト

**ファイル:** `tests/Feature/Components/FacilityEditLayoutTest.php`

- レイアウトコンポーネントの正常なレンダリング
- プロパティの正しい受け渡し
- スロットコンテンツの表示
- 条件付きレンダリングの動作

### 2. 統合テスト

**ファイル:** `tests/Feature/FacilityFormLayoutTest.php`

- 土地情報編集フォームでの新レイアウト使用
- フォーム送信とバリデーション
- レスポンシブデザインの動作
- アクセシビリティ要件の確認

### 3. ビジュアル回帰テスト

- 既存フォームとの視覚的一貫性
- 異なる画面サイズでの表示確認
- ブラウザ間の互換性確認

## 実装詳細

### CSS変数とスタイリング

```css
/* resources/css/components/facility-form.css */
:root {
  --facility-form-card-shadow: 0 2px 4px rgba(0,0,0,0.1);
  --facility-form-section-border: #e9ecef;
  --facility-form-header-bg: #f8f9fa;
  --facility-form-spacing: 1.5rem;
  --facility-form-border-radius: 0.375rem;
}

.facility-edit-layout {
  .facility-info-card {
    background: var(--facility-form-header-bg);
    border: 1px solid var(--facility-form-section-border);
    border-radius: var(--facility-form-border-radius);
    box-shadow: var(--facility-form-card-shadow);
  }
  
  .form-section {
    margin-bottom: var(--facility-form-spacing);
    
    .section-header {
      background: var(--facility-form-header-bg);
      border-bottom: 1px solid var(--facility-form-section-border);
      padding: 1rem 1.25rem;
      
      h5 {
        margin: 0;
        display: flex;
        align-items: center;
        
        i {
          margin-right: 0.5rem;
        }
      }
    }
  }
}

@media (max-width: 768px) {
  .facility-edit-layout {
    .form-section .row {
      margin: 0;
      
      .col-md-6 {
        padding: 0 0.75rem;
        margin-bottom: 1rem;
      }
    }
  }
}
```

### JavaScript機能

```javascript
// resources/js/modules/facility-form-layout.js
export class FacilityFormLayout {
    constructor() {
        this.initializeCollapsibleSections();
        this.initializeFormValidation();
        this.initializeResponsiveFeatures();
    }
    
    initializeCollapsibleSections() {
        document.querySelectorAll('[data-collapsible="true"]').forEach(section => {
            const header = section.querySelector('.section-header');
            const content = section.querySelector('.card-body');
            
            header.addEventListener('click', () => {
                content.classList.toggle('collapse');
                const icon = header.querySelector('.collapse-icon');
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-up');
            });
        });
    }
    
    initializeFormValidation() {
        // リアルタイムバリデーション
        const form = document.querySelector('.facility-edit-form');
        if (form) {
            form.addEventListener('input', this.validateField.bind(this));
        }
    }
    
    validateField(event) {
        const field = event.target;
        const errorElement = field.parentNode.querySelector('.invalid-feedback');
        
        // カスタムバリデーションロジック
        if (field.hasAttribute('required') && !field.value.trim()) {
            field.classList.add('is-invalid');
            if (errorElement) {
                errorElement.textContent = 'この項目は必須です';
            }
        } else {
            field.classList.remove('is-invalid');
        }
    }
    
    initializeResponsiveFeatures() {
        // モバイルでのフォーム最適化
        if (window.innerWidth <= 768) {
            this.optimizeForMobile();
        }
        
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 768) {
                this.optimizeForMobile();
            } else {
                this.optimizeForDesktop();
            }
        });
    }
}

// 自動初期化
document.addEventListener('DOMContentLoaded', () => {
    new FacilityFormLayout();
});
```

### アクセシビリティ対応

1. **キーボードナビゲーション**: すべてのインタラクティブ要素がキーボードでアクセス可能
2. **スクリーンリーダー対応**: 適切なARIAラベルとロール
3. **色のコントラスト**: WCAG 2.1 AA基準に準拠
4. **フォーカス管理**: 明確なフォーカスインジケーター

```blade
<!-- アクセシビリティ属性の例 -->
<div class="form-section" role="region" aria-labelledby="section-{{ $sectionId }}">
    <div class="section-header" id="section-{{ $sectionId }}">
        <h5>
            <i class="{{ $icon }}" aria-hidden="true"></i>
            {{ $title }}
        </h5>
    </div>
    <div class="card-body" role="group" aria-labelledby="section-{{ $sectionId }}">
        {{ $slot }}
    </div>
</div>
```

## パフォーマンス考慮事項

1. **コンポーネントキャッシュ**: Bladeコンポーネントの適切なキャッシュ戦略
2. **CSS最適化**: 未使用CSSの削除とミニファイ
3. **JavaScript遅延読み込み**: 必要な時のみスクリプト読み込み
4. **画像最適化**: 施設画像の適切なサイズとフォーマット

## セキュリティ考慮事項

1. **CSRF保護**: すべてのフォームでCSRFトークン使用
2. **入力サニタイゼーション**: XSS攻撃の防止
3. **認可チェック**: 編集権限の適切な確認
4. **データバリデーション**: サーバーサイドでの厳密な検証