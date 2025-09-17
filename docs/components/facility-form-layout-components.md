# 施設フォームレイアウトコンポーネント使用ガイド

## 概要

このドキュメントでは、施設関連の編集フォームで使用する標準化されたレイアウトコンポーネントの使用方法について説明します。これらのコンポーネントは、一貫したユーザーエクスペリエンスと保守性を提供するために設計されています。

## コンポーネント一覧

### 1. FacilityEditLayout

メインレイアウトコンポーネント。すべての施設編集フォームの基盤となります。

**ファイル:** `resources/views/components/facility/edit-layout.blade.php`

#### 使用方法

```blade
<x-facility.edit-layout 
    :title="'土地情報編集'"
    :facility="$facility"
    :breadcrumbs="$breadcrumbs"
    :back-route="route('facilities.show', $facility)"
    :form-action="route('facilities.land-info.update', $facility)"
    form-method="PUT">
    
    <!-- フォームコンテンツをここに配置 -->
    <x-form.section title="基本情報" icon="fas fa-info-circle">
        <!-- フォームフィールド -->
    </x-form.section>
    
    <x-slot name="actions">
        <!-- カスタムアクションボタン（オプション） -->
        <button type="button" class="btn btn-warning">
            <i class="fas fa-download me-2"></i>PDFダウンロード
        </button>
    </x-slot>
</x-facility.edit-layout>
```

#### プロパティ

| プロパティ | 型 | 必須 | デフォルト | 説明 |
|-----------|---|------|-----------|------|
| `title` | string | ✓ | - | ページタイトル |
| `facility` | Facility | ✓ | - | 施設オブジェクト |
| `breadcrumbs` | array | ✓ | - | パンくずリスト配列 |
| `backRoute` | string | ✓ | - | 戻るボタンのルート |
| `formAction` | string | ✓ | - | フォームのアクションURL |
| `formMethod` | string | - | 'POST' | HTTPメソッド |

#### スロット

- `default`: メインフォームコンテンツ
- `actions`: カスタムアクションボタン（オプション）

### 2. FacilityInfoCard

施設情報を表示するカードコンポーネント。

**ファイル:** `resources/views/components/facility/info-card.blade.php`

#### 使用方法

```blade
<x-facility.info-card :facility="$facility" :show-type="true" />
```

#### プロパティ

| プロパティ | 型 | 必須 | デフォルト | 説明 |
|-----------|---|------|-----------|------|
| `facility` | Facility | ✓ | - | 施設オブジェクト |
| `showType` | boolean | - | true | 施設タイプの表示有無 |

### 3. FormSection

フォームセクションを整理するコンポーネント。

**ファイル:** `resources/views/components/form/section.blade.php`

#### 使用方法

```blade
<x-form.section 
    title="基本情報" 
    icon="fas fa-info-circle"
    icon-color="primary"
    :collapsible="true"
    :collapsed="false">
    
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="name" class="form-label">施設名</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $facility->name) }}">
            </div>
        </div>
    </div>
</x-form.section>
```

#### プロパティ

| プロパティ | 型 | 必須 | デフォルト | 説明 |
|-----------|---|------|-----------|------|
| `title` | string | ✓ | - | セクションタイトル |
| `icon` | string | ✓ | - | Font Awesomeアイコンクラス |
| `iconColor` | string | - | 'primary' | アイコンの色 |
| `collapsible` | boolean | - | false | 折りたたみ可能かどうか |
| `collapsed` | boolean | - | false | 初期状態で折りたたまれているか |

### 4. FormActions

フォームアクションボタンのコンポーネント。

**ファイル:** `resources/views/components/form/actions.blade.php`

#### 使用方法

```blade
<x-form.actions 
    :cancel-route="route('facilities.show', $facility)"
    cancel-text="キャンセル"
    submit-text="保存"
    submit-icon="fas fa-save">
    
    <x-slot name="additional">
        <button type="button" class="btn btn-info me-2">
            <i class="fas fa-preview me-2"></i>プレビュー
        </button>
    </x-slot>
</x-form.actions>
```

#### プロパティ

| プロパティ | 型 | 必須 | デフォルト | 説明 |
|-----------|---|------|-----------|------|
| `cancelRoute` | string | ✓ | - | キャンセルボタンのルート |
| `cancelText` | string | - | 'キャンセル' | キャンセルボタンのテキスト |
| `submitText` | string | - | '保存' | 送信ボタンのテキスト |
| `submitIcon` | string | - | 'fas fa-save' | 送信ボタンのアイコン |

#### スロット

- `additional`: 追加のアクションボタン（オプション）

## 設定とカスタマイズ

### 設定ファイル

`config/facility-form.php` でコンポーネントの動作をカスタマイズできます。

```php
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

### パンくずリストの作成

パンくずリストは以下の形式で作成します：

```php
$breadcrumbs = [
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
];
```

### ヘルパー関数の使用

`FacilityFormHelper` クラスを使用して、共通的な処理を簡素化できます：

```php
use App\Helpers\FacilityFormHelper;

// パンくずリストの生成
$breadcrumbs = FacilityFormHelper::generateBreadcrumbs($facility, '土地情報編集');

// アイコンの取得
$icon = FacilityFormHelper::getIconForSection('land_info');

// セクションの色の取得
$color = FacilityFormHelper::getColorForSection('primary');
```

## JavaScript機能

### 自動初期化

コンポーネントのJavaScript機能は自動的に初期化されます：

```javascript
// resources/js/modules/facility-form-layout.js が自動読み込み
document.addEventListener('DOMContentLoaded', () => {
    new FacilityFormLayout();
});
```

### カスタム機能の追加

独自の機能を追加する場合：

```javascript
import { FacilityFormLayout } from './modules/facility-form-layout.js';

class CustomFacilityForm extends FacilityFormLayout {
    constructor() {
        super();
        this.initializeCustomFeatures();
    }
    
    initializeCustomFeatures() {
        // カスタム機能の実装
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new CustomFacilityForm();
});
```

## エラーハンドリング

### バリデーションエラーの表示

コンポーネントは自動的にLaravelのバリデーションエラーを表示します：

```blade
<x-form.section title="基本情報" icon="fas fa-info-circle">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="name" class="form-label">施設名 <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control @error('name') is-invalid @enderror" 
                       id="name" 
                       name="name" 
                       value="{{ old('name', $facility->name) }}"
                       required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</x-form.section>
```

### セクションレベルのエラー表示

セクションにエラーがある場合、ヘッダーに警告アイコンが表示されます：

```blade
<x-form.section 
    title="基本情報" 
    icon="fas fa-info-circle"
    :has-errors="$errors->hasAny(['name', 'address', 'type'])">
    <!-- フォームフィールド -->
</x-form.section>
```

## アクセシビリティ

コンポーネントは以下のアクセシビリティ機能を提供します：

- **キーボードナビゲーション**: すべてのインタラクティブ要素がキーボードでアクセス可能
- **スクリーンリーダー対応**: 適切なARIAラベルとロール
- **色のコントラスト**: WCAG 2.1 AA基準に準拠
- **フォーカス管理**: 明確なフォーカスインジケーター

## レスポンシブデザイン

コンポーネントは自動的にレスポンシブ対応されています：

- **デスクトップ**: 2カラムレイアウト
- **タブレット**: 1カラムレイアウト
- **モバイル**: タッチフレンドリーなインターフェース

## トラブルシューティング

### よくある問題

1. **コンポーネントが表示されない**
   - `php artisan view:clear` でビューキャッシュをクリア
   - コンポーネントファイルのパスを確認

2. **スタイルが適用されない**
   - `npm run build` でアセットをビルド
   - CSSファイルが正しく読み込まれているか確認

3. **JavaScript機能が動作しない**
   - ブラウザのコンソールでエラーを確認
   - モジュールが正しく読み込まれているか確認

### デバッグ方法

```blade
<!-- コンポーネントのデバッグ -->
@if(config('app.debug'))
    <div class="alert alert-info">
        <strong>Debug Info:</strong>
        <pre>{{ json_encode($facility, JSON_PRETTY_PRINT) }}</pre>
    </div>
@endif
```

## サポート

問題が発生した場合は、以下のドキュメントも参照してください：

- [施設フォームレイアウト設計書](./facility-form-layout.md)
- [アクセシビリティ実装ガイド](./accessibility-implementation.md)
- [エラーハンドリングシステム](./error-handling-system.md)