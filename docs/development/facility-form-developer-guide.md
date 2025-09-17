# 施設フォーム開発者ガイド

## 概要

このガイドは、施設フォームレイアウト標準化システムを使用する開発者向けの包括的なリファレンスです。新しいフォームの作成から既存フォームの移行まで、すべての開発作業をサポートします。

## クイックスタート

### 新しい編集フォームの作成（5分で完了）

```bash
# 1. コントローラーの作成
php artisan make:controller ServiceInfoController

# 2. リクエストクラスの作成
php artisan make:request ServiceInfoRequest

# 3. ビューファイルの作成
mkdir -p resources/views/facilities/service-info
touch resources/views/facilities/service-info/edit.blade.php

# 4. ルートの追加
# routes/web.php に追加

# 5. テストの作成
php artisan make:test ServiceInfoControllerTest
```

### 最小限のフォーム実装

```blade
{{-- resources/views/facilities/service-info/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<x-facility.edit-layout 
    title="サービス情報編集"
    :facility="$facility"
    :breadcrumbs="$breadcrumbs"
    :back-route="route('facilities.show', $facility)"
    :form-action="route('facilities.service-info.update', $facility)"
    form-method="PUT">

    <x-form.section title="基本情報" icon="fas fa-cogs">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="service_type" class="form-label">サービス種別</label>
                    <input type="text" class="form-control" id="service_type" name="service_type">
                </div>
            </div>
        </div>
    </x-form.section>

</x-facility.edit-layout>
@endsection
```

## コンポーネントリファレンス

### FacilityEditLayout

**基本的な使用方法:**

```blade
<x-facility.edit-layout 
    title="ページタイトル"
    :facility="$facility"
    :breadcrumbs="$breadcrumbs"
    :back-route="$backRoute"
    :form-action="$formAction"
    form-method="PUT">
    
    <!-- フォームコンテンツ -->
    
</x-facility.edit-layout>
```

**プロパティ一覧:**

| プロパティ | 型 | 必須 | 説明 | 例 |
|-----------|---|------|------|---|
| `title` | string | ✓ | ページタイトル | `"土地情報編集"` |
| `facility` | Facility | ✓ | 施設オブジェクト | `$facility` |
| `breadcrumbs` | array | ✓ | パンくずリスト | `$breadcrumbs` |
| `backRoute` | string | ✓ | 戻るボタンのURL | `route('facilities.show', $facility)` |
| `formAction` | string | ✓ | フォームのアクションURL | `route('facilities.update', $facility)` |
| `formMethod` | string | - | HTTPメソッド | `"PUT"` (デフォルト: `"POST"`) |

### FormSection

**基本的な使用方法:**

```blade
<x-form.section 
    title="セクションタイトル"
    icon="fas fa-icon-name"
    icon-color="primary">
    
    <!-- セクションコンテンツ -->
    
</x-form.section>
```

**利用可能なアイコンと色:**

```php
// config/facility-form.php で定義
'icons' => [
    'basic_info' => 'fas fa-info-circle',
    'land_info' => 'fas fa-map',
    'contact_info' => 'fas fa-phone',
    'building_info' => 'fas fa-building',
    'service_info' => 'fas fa-cogs',
    'maintenance' => 'fas fa-tools',
    'financial' => 'fas fa-yen-sign',
    'documents' => 'fas fa-file-alt',
],

'colors' => [
    'primary' => 'primary',
    'success' => 'success',
    'info' => 'info',
    'warning' => 'warning',
    'danger' => 'danger',
]
```

## 開発パターン

### パターン1: 基本的な情報編集フォーム

```blade
<x-facility.edit-layout title="基本情報編集" :facility="$facility" :breadcrumbs="$breadcrumbs" 
                        :back-route="route('facilities.show', $facility)"
                        :form-action="route('facilities.basic-info.update', $facility)" form-method="PUT">

    <x-form.section title="基本情報" icon="fas fa-info-circle" icon-color="primary">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="name" class="form-label">施設名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           id="name" name="name" value="{{ old('name', $facility->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="type" class="form-label">施設タイプ</label>
                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type">
                        <option value="">選択してください</option>
                        <option value="medical" {{ old('type', $facility->type) === 'medical' ? 'selected' : '' }}>医療施設</option>
                        <option value="education" {{ old('type', $facility->type) === 'education' ? 'selected' : '' }}>教育施設</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </x-form.section>

</x-facility.edit-layout>
```

### パターン2: 複数セクションのフォーム

```blade
<x-facility.edit-layout title="詳細情報編集" :facility="$facility" :breadcrumbs="$breadcrumbs"
                        :back-route="route('facilities.show', $facility)"
                        :form-action="route('facilities.details.update', $facility)" form-method="PUT">

    {{-- 基本情報セクション --}}
    <x-form.section title="基本情報" icon="fas fa-info-circle" icon-color="primary">
        <!-- 基本情報フィールド -->
    </x-form.section>

    {{-- 連絡先情報セクション --}}
    <x-form.section title="連絡先情報" icon="fas fa-phone" icon-color="info">
        <!-- 連絡先フィールド -->
    </x-form.section>

    {{-- 営業情報セクション --}}
    <x-form.section title="営業情報" icon="fas fa-clock" icon-color="success">
        <!-- 営業情報フィールド -->
    </x-form.section>

</x-facility.edit-layout>
```

### パターン3: 折りたたみ可能なセクション

```blade
<x-form.section title="詳細設定" icon="fas fa-cog" icon-color="warning" 
                :collapsible="true" :collapsed="true">
    <!-- 通常は折りたたまれているセクション -->
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                このセクションは高度な設定項目です。必要な場合のみ変更してください。
            </div>
        </div>
    </div>
    <!-- 詳細設定フィールド -->
</x-form.section>
```

### パターン4: カスタムアクションボタン

```blade
<x-facility.edit-layout title="メンテナンス情報編集" :facility="$facility" :breadcrumbs="$breadcrumbs"
                        :back-route="route('facilities.show', $facility)"
                        :form-action="route('facilities.maintenance.update', $facility)" form-method="PUT">

    <!-- フォームセクション -->
    <x-form.section title="メンテナンス情報" icon="fas fa-tools" icon-color="warning">
        <!-- フィールド -->
    </x-form.section>

    {{-- カスタムアクションボタン --}}
    <x-slot name="actions">
        <button type="button" class="btn btn-info me-2" onclick="generateReport()">
            <i class="fas fa-file-pdf me-2"></i>レポート生成
        </button>
        <button type="button" class="btn btn-warning me-2" onclick="scheduleReminder()">
            <i class="fas fa-bell me-2"></i>リマインダー設定
        </button>
    </x-slot>

</x-facility.edit-layout>
```

## コントローラーパターン

### 基本的なコントローラー構造

```php
<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Http\Requests\ServiceInfoRequest;
use App\Services\ServiceInfoService;
use App\Helpers\FacilityFormHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceInfoController extends Controller
{
    public function __construct(
        private ServiceInfoService $serviceInfoService
    ) {}

    public function edit(Facility $facility): View
    {
        $this->authorize('update', $facility);

        $breadcrumbs = FacilityFormHelper::generateBreadcrumbs($facility, 'サービス情報編集');

        return view('facilities.service-info.edit', compact('facility', 'breadcrumbs'));
    }

    public function update(ServiceInfoRequest $request, Facility $facility): RedirectResponse
    {
        $this->authorize('update', $facility);

        try {
            $this->serviceInfoService->updateServiceInfo($facility, $request->validated());
            
            return redirect()
                ->route('facilities.show', $facility)
                ->with('success', 'サービス情報を更新しました。');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'サービス情報の更新に失敗しました。');
        }
    }
}
```

### 高度なコントローラーパターン

```php
public function edit(Facility $facility): View
{
    $this->authorize('update', $facility);

    // 関連データの取得
    $facility->load(['landInfo', 'services', 'maintenanceHistory']);

    // パンくずリストの生成
    $breadcrumbs = FacilityFormHelper::generateBreadcrumbs($facility, 'サービス情報編集');

    // 選択肢データの準備
    $serviceTypes = config('facility.service_types');
    $businessHours = config('facility.business_hours_options');

    // 権限チェック
    $canEditAdvanced = $this->user()->can('editAdvanced', $facility);

    return view('facilities.service-info.edit', compact(
        'facility', 
        'breadcrumbs', 
        'serviceTypes', 
        'businessHours',
        'canEditAdvanced'
    ));
}
```

## バリデーションパターン

### 基本的なリクエストクラス

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('facility'));
    }

    public function rules(): array
    {
        return [
            'service_type' => ['required', 'string', 'in:medical,education,welfare'],
            'service_description' => ['nullable', 'string', 'max:1000'],
            'business_hours' => ['nullable', 'string', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'regex:/^[0-9\-\(\)\+\s]+$/', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'service_type' => 'サービス種別',
            'service_description' => 'サービス内容',
            'business_hours' => '営業時間',
            'contact_phone' => '連絡先電話番号',
            'contact_email' => '連絡先メールアドレス',
        ];
    }

    public function messages(): array
    {
        return [
            'service_type.required' => 'サービス種別を選択してください。',
            'service_type.in' => '有効なサービス種別を選択してください。',
            'contact_phone.regex' => '電話番号の形式が正しくありません。',
            'contact_email.email' => '正しいメールアドレス形式で入力してください。',
        ];
    }
}
```

### 条件付きバリデーション

```php
public function rules(): array
{
    $rules = [
        'service_type' => ['required', 'string', 'in:medical,education,welfare'],
        'service_description' => ['nullable', 'string', 'max:1000'],
    ];

    // サービス種別が医療の場合、追加の必須項目
    if ($this->input('service_type') === 'medical') {
        $rules['medical_license_number'] = ['required', 'string', 'max:50'];
        $rules['doctor_name'] = ['required', 'string', 'max:100'];
    }

    // 営業時間が設定されている場合、営業日も必須
    if ($this->filled('business_hours')) {
        $rules['business_days'] = ['required', 'array'];
        $rules['business_days.*'] = ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'];
    }

    return $rules;
}
```

## テストパターン

### 基本的なテスト構造

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Facility;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServiceInfoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create();
    }

    public function test_edit_page_displays_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.service-info.edit', $this->facility));

        $response->assertOk()
            ->assertViewIs('facilities.service-info.edit')
            ->assertViewHas('facility', $this->facility)
            ->assertViewHas('breadcrumbs')
            ->assertSee('サービス情報編集')
            ->assertSee($this->facility->name);
    }

    public function test_update_service_info_successfully(): void
    {
        $data = [
            'service_type' => 'medical',
            'service_description' => 'テスト医療サービス',
            'business_hours' => '9:00-17:00',
            'contact_phone' => '03-1234-5678',
            'contact_email' => 'test@example.com',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('facilities.service-info.update', $this->facility), $data);

        $response->assertRedirect(route('facilities.show', $this->facility))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('facilities', [
            'id' => $this->facility->id,
            'service_type' => 'medical',
            'service_description' => 'テスト医療サービス',
        ]);
    }

    public function test_validation_errors_are_displayed(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('facilities.service-info.update', $this->facility), [
                'service_type' => '',
                'contact_email' => 'invalid-email',
            ]);

        $response->assertSessionHasErrors(['service_type', 'contact_email']);
    }

    public function test_unauthorized_user_cannot_edit(): void
    {
        $unauthorizedUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)
            ->get(route('facilities.service-info.edit', $this->facility));

        $response->assertForbidden();
    }
}
```

### コンポーネントテスト

```php
<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use App\Models\Facility;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FacilityEditLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_facility_edit_layout_renders_correctly(): void
    {
        $facility = Facility::factory()->create([
            'name' => 'テスト施設',
            'address' => 'テスト住所',
            'type' => 'medical'
        ]);

        $breadcrumbs = [
            ['title' => 'ホーム', 'route' => 'facilities.index', 'active' => false],
            ['title' => 'テスト', 'active' => true]
        ];

        $view = $this->blade(
            '<x-facility.edit-layout 
                title="テストタイトル"
                :facility="$facility"
                :breadcrumbs="$breadcrumbs"
                back-route="http://example.com/back"
                form-action="http://example.com/action">
                <p>テストコンテンツ</p>
            </x-facility.edit-layout>',
            compact('facility', 'breadcrumbs')
        );

        $view->assertSee('テストタイトル')
            ->assertSee('テスト施設')
            ->assertSee('テスト住所')
            ->assertSee('テストコンテンツ')
            ->assertSee('ホーム');
    }
}
```

## デバッグとトラブルシューティング

### よくある問題と解決方法

#### 1. コンポーネントが表示されない

```bash
# ビューキャッシュをクリア
php artisan view:clear

# 設定キャッシュをクリア
php artisan config:clear

# コンポーネントファイルの存在確認
ls -la resources/views/components/facility/
ls -la resources/views/components/form/
```

#### 2. スタイルが適用されない

```bash
# アセットをビルド
npm run build

# 開発モードでアセットを監視
npm run dev

# CSSファイルの確認
ls -la resources/css/components/
```

#### 3. JavaScript機能が動作しない

```javascript
// ブラウザのコンソールでエラーを確認
console.log('FacilityFormLayout loaded:', typeof FacilityFormLayout);

// モジュールの読み込み確認
import { FacilityFormLayout } from './modules/facility-form-layout.js';
console.log('Module imported successfully');
```

#### 4. バリデーションエラーが表示されない

```blade
{{-- エラー表示の確認 --}}
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- 個別フィールドのエラー確認 --}}
@error('field_name')
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
```

### デバッグ用のヘルパー

```blade
{{-- デバッグ情報の表示 --}}
@if(config('app.debug'))
    <div class="alert alert-info mt-3">
        <h6>Debug Information:</h6>
        <p><strong>Facility ID:</strong> {{ $facility->id }}</p>
        <p><strong>User ID:</strong> {{ auth()->id() }}</p>
        <p><strong>Breadcrumbs:</strong> {{ json_encode($breadcrumbs) }}</p>
        <p><strong>Errors:</strong> {{ $errors->count() }} errors</p>
        @if($errors->any())
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
```

## パフォーマンス最適化

### データベースクエリの最適化

```php
// N+1問題の回避
$facility = Facility::with(['landInfo', 'services', 'maintenanceHistory'])
    ->findOrFail($id);

// 必要なカラムのみ選択
$facilities = Facility::select(['id', 'name', 'address', 'type'])
    ->where('active', true)
    ->get();
```

### ビューの最適化

```blade
{{-- 条件付きレンダリング --}}
@if($facility->hasLandInfo())
    <x-form.section title="土地情報" icon="fas fa-map">
        {{-- 土地情報フィールド --}}
    </x-form.section>
@endif

{{-- 遅延読み込み --}}
@push('scripts')
    <script src="{{ asset('js/facility-form-advanced.js') }}" defer></script>
@endpush
```

### アセットの最適化

```javascript
// 動的インポート
document.addEventListener('DOMContentLoaded', async () => {
    if (document.querySelector('.facility-edit-layout')) {
        const { FacilityFormLayout } = await import('./modules/facility-form-layout.js');
        new FacilityFormLayout();
    }
});
```

## セキュリティ考慮事項

### 認可の実装

```php
// ポリシーの使用
Gate::define('update-facility', function (User $user, Facility $facility) {
    return $user->canEdit($facility);
});

// コントローラーでの認可チェック
$this->authorize('update', $facility);

// ビューでの条件表示
@can('update', $facility)
    <x-form.section title="管理者専用設定" icon="fas fa-cog">
        {{-- 管理者のみ表示 --}}
    </x-form.section>
@endcan
```

### 入力サニタイゼーション

```php
// リクエストクラスでの前処理
protected function prepareForValidation()
{
    $this->merge([
        'phone' => preg_replace('/[^\d\-\(\)\+\s]/', '', $this->phone),
        'description' => strip_tags($this->description),
        'email' => strtolower(trim($this->email)),
    ]);
}
```

## 関連ドキュメント

- [コンポーネント使用ガイド](../components/facility-form-layout-components.md)
- [ベストプラクティス](./facility-form-best-practices.md)
- [移行ガイド](../migration/facility-form-migration-guide.md)
- [アクセシビリティ実装](../components/accessibility-implementation.md)
- [エラーハンドリングシステム](../components/error-handling-system.md)

## サポート

問題が発生した場合は、以下の手順で対応してください：

1. このドキュメントのトラブルシューティングセクションを確認
2. 関連するテストを実行して問題を特定
3. ログファイルでエラーの詳細を確認
4. 必要に応じて開発チームに相談

開発効率を向上させるため、このガイドを定期的に参照し、最新のベストプラクティスに従って開発を進めてください。