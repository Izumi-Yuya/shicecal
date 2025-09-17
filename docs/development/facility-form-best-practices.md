# 施設編集フォーム作成ベストプラクティス

## 概要

このドキュメントでは、新しい施設編集フォームを作成する際のベストプラクティスとガイドラインを説明します。標準化されたレイアウトコンポーネントを使用して、一貫性があり保守性の高いフォームを作成する方法を学びます。

## 基本原則

### 1. 一貫性の維持

すべての施設編集フォームは同じレイアウトパターンに従う必要があります：

- 統一されたヘッダー構造
- 一貫したセクション分割
- 標準化されたフォームアクション
- 共通のスタイリング

### 2. ユーザビリティの重視

- 直感的なナビゲーション
- 明確なフィードバック
- アクセシブルなインターフェース
- レスポンシブデザイン

### 3. 保守性の確保

- 再利用可能なコンポーネントの使用
- 設定ファイルによるカスタマイズ
- 適切なドキュメント化
- テストカバレッジの確保

## フォーム作成手順

### ステップ1: 要件の定義

新しいフォームを作成する前に、以下を明確にします：

```markdown
## フォーム要件チェックリスト

- [ ] 編集対象のデータモデルは何か？
- [ ] 必要なフィールドとバリデーションルールは？
- [ ] 権限管理の要件は？
- [ ] 特別な機能（ファイルアップロード、計算フィールドなど）は必要か？
- [ ] 関連するモデルとの関係は？
```

### ステップ2: ルートの定義

RESTfulな命名規則に従ってルートを定義します：

```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/facilities/{facility}/service-info/edit', [ServiceInfoController::class, 'edit'])
        ->name('facilities.service-info.edit');
    Route::put('/facilities/{facility}/service-info', [ServiceInfoController::class, 'update'])
        ->name('facilities.service-info.update');
});
```

### ステップ3: コントローラーの作成

標準的なコントローラー構造を使用します：

```php
<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Http\Requests\ServiceInfoRequest;
use App\Services\ServiceInfoService;
use App\Helpers\FacilityFormHelper;

class ServiceInfoController extends Controller
{
    public function __construct(
        private ServiceInfoService $serviceInfoService
    ) {}

    public function edit(Facility $facility)
    {
        $this->authorize('update', $facility);

        $breadcrumbs = FacilityFormHelper::generateBreadcrumbs($facility, 'サービス情報編集');

        return view('facilities.service-info.edit', compact('facility', 'breadcrumbs'));
    }

    public function update(ServiceInfoRequest $request, Facility $facility)
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

### ステップ4: リクエストクラスの作成

バリデーションルールを定義します：

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
            'service_type' => ['required', 'string', 'max:100'],
            'service_description' => ['nullable', 'string', 'max:1000'],
            'service_hours' => ['nullable', 'string', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'service_type' => 'サービス種別',
            'service_description' => 'サービス内容',
            'service_hours' => '営業時間',
            'contact_phone' => '連絡先電話番号',
            'contact_email' => '連絡先メールアドレス',
        ];
    }
}
```

### ステップ5: ビューの作成

標準化されたレイアウトコンポーネントを使用します：

```blade
{{-- resources/views/facilities/service-info/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'サービス情報編集')

@section('content')
<x-facility.edit-layout 
    :title="'サービス情報編集'"
    :facility="$facility"
    :breadcrumbs="$breadcrumbs"
    :back-route="route('facilities.show', $facility)"
    :form-action="route('facilities.service-info.update', $facility)"
    form-method="PUT">

    {{-- 基本サービス情報セクション --}}
    <x-form.section title="基本サービス情報" icon="fas fa-cogs" icon-color="primary">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="service_type" class="form-label">サービス種別 <span class="text-danger">*</span></label>
                    <select class="form-select @error('service_type') is-invalid @enderror" 
                            id="service_type" 
                            name="service_type" 
                            required>
                        <option value="">選択してください</option>
                        <option value="medical" {{ old('service_type', $facility->service_type) === 'medical' ? 'selected' : '' }}>医療</option>
                        <option value="education" {{ old('service_type', $facility->service_type) === 'education' ? 'selected' : '' }}>教育</option>
                        <option value="welfare" {{ old('service_type', $facility->service_type) === 'welfare' ? 'selected' : '' }}>福祉</option>
                    </select>
                    @error('service_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="service_hours" class="form-label">営業時間</label>
                    <input type="text" 
                           class="form-control @error('service_hours') is-invalid @enderror" 
                           id="service_hours" 
                           name="service_hours" 
                           value="{{ old('service_hours', $facility->service_hours) }}"
                           placeholder="例: 9:00-17:00">
                    @error('service_hours')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="mb-3">
                    <label for="service_description" class="form-label">サービス内容</label>
                    <textarea class="form-control @error('service_description') is-invalid @enderror" 
                              id="service_description" 
                              name="service_description" 
                              rows="4"
                              placeholder="提供するサービスの詳細を入力してください">{{ old('service_description', $facility->service_description) }}</textarea>
                    @error('service_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </x-form.section>

    {{-- 連絡先情報セクション --}}
    <x-form.section title="連絡先情報" icon="fas fa-phone" icon-color="info">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="contact_phone" class="form-label">連絡先電話番号</label>
                    <input type="tel" 
                           class="form-control @error('contact_phone') is-invalid @enderror" 
                           id="contact_phone" 
                           name="contact_phone" 
                           value="{{ old('contact_phone', $facility->contact_phone) }}"
                           placeholder="例: 03-1234-5678">
                    @error('contact_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="contact_email" class="form-label">連絡先メールアドレス</label>
                    <input type="email" 
                           class="form-control @error('contact_email') is-invalid @enderror" 
                           id="contact_email" 
                           name="contact_email" 
                           value="{{ old('contact_email', $facility->contact_email) }}"
                           placeholder="例: contact@example.com">
                    @error('contact_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </x-form.section>

</x-facility.edit-layout>
@endsection
```

### ステップ6: サービスクラスの作成

ビジネスロジックを分離します：

```php
<?php

namespace App\Services;

use App\Models\Facility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceInfoService
{
    public function updateServiceInfo(Facility $facility, array $data): Facility
    {
        return DB::transaction(function () use ($facility, $data) {
            $facility->update($data);
            
            // アクティビティログの記録
            activity()
                ->performedOn($facility)
                ->withProperties($data)
                ->log('サービス情報を更新しました');
            
            Log::info('Service info updated', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'changes' => $data
            ]);
            
            return $facility->fresh();
        });
    }
}
```

### ステップ7: テストの作成

包括的なテストを作成します：

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

    public function test_edit_page_displays_correctly()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.service-info.edit', $this->facility));

        $response->assertOk()
            ->assertViewIs('facilities.service-info.edit')
            ->assertViewHas('facility', $this->facility)
            ->assertSee('サービス情報編集');
    }

    public function test_update_service_info_successfully()
    {
        $data = [
            'service_type' => 'medical',
            'service_description' => 'テスト医療サービス',
            'service_hours' => '9:00-17:00',
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

    public function test_validation_errors_are_displayed()
    {
        $response = $this->actingAs($this->user)
            ->put(route('facilities.service-info.update', $this->facility), [
                'service_type' => '', // 必須フィールドを空にする
                'contact_email' => 'invalid-email', // 無効なメール形式
            ]);

        $response->assertSessionHasErrors(['service_type', 'contact_email']);
    }
}
```

## コーディング規約

### 命名規則

```php
// コントローラー
class ServiceInfoController extends Controller

// リクエストクラス
class ServiceInfoRequest extends FormRequest

// サービスクラス
class ServiceInfoService

// ビューファイル
// resources/views/facilities/service-info/edit.blade.php

// ルート名
Route::name('facilities.service-info.edit')
```

### フィールド命名

```php
// データベースカラム: snake_case
'service_type', 'contact_phone', 'created_at'

// HTMLフィールド名: snake_case
<input name="service_type">

// CSS クラス: kebab-case
.service-info-section, .contact-form
```

### バリデーション

```php
public function rules(): array
{
    return [
        // 必須フィールド
        'service_type' => ['required', 'string', 'max:100'],
        
        // オプションフィールド
        'service_description' => ['nullable', 'string', 'max:1000'],
        
        // 特殊バリデーション
        'contact_email' => ['nullable', 'email', 'max:255'],
        'contact_phone' => ['nullable', 'regex:/^[0-9\-\(\)\+\s]+$/'],
    ];
}
```

## セキュリティ考慮事項

### 認可の実装

```php
// コントローラーで認可チェック
public function edit(Facility $facility)
{
    $this->authorize('update', $facility);
    // ...
}

// リクエストクラスで認可チェック
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('facility'));
}
```

### CSRFトークン

```blade
{{-- フォームに自動的に含まれる --}}
<x-facility.edit-layout form-action="..." form-method="PUT">
    {{-- CSRFトークンは自動的に追加される --}}
</x-facility.edit-layout>
```

### 入力サニタイゼーション

```php
// リクエストクラスでの前処理
protected function prepareForValidation()
{
    $this->merge([
        'contact_phone' => preg_replace('/[^\d\-\(\)\+\s]/', '', $this->contact_phone),
        'service_description' => strip_tags($this->service_description),
    ]);
}
```

## パフォーマンス最適化

### データベースクエリ

```php
// N+1問題の回避
$facility = Facility::with(['landInfo', 'services'])->findOrFail($id);

// 必要なカラムのみ選択
$facilities = Facility::select(['id', 'name', 'address'])->get();
```

### ビューの最適化

```blade
{{-- 条件付きレンダリング --}}
@if($facility->hasServiceInfo())
    <x-form.section title="既存サービス情報" icon="fas fa-info">
        {{-- 既存情報の表示 --}}
    </x-form.section>
@endif

{{-- 遅延読み込み --}}
@push('scripts')
    <script src="{{ asset('js/service-info-form.js') }}" defer></script>
@endpush
```

## エラーハンドリング

### 例外処理

```php
public function update(ServiceInfoRequest $request, Facility $facility)
{
    try {
        $this->serviceInfoService->updateServiceInfo($facility, $request->validated());
        
        return redirect()
            ->route('facilities.show', $facility)
            ->with('success', 'サービス情報を更新しました。');
            
    } catch (ValidationException $e) {
        return back()
            ->withErrors($e->errors())
            ->withInput();
            
    } catch (\Exception $e) {
        Log::error('Service info update failed', [
            'facility_id' => $facility->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()
            ->withInput()
            ->with('error', 'サービス情報の更新に失敗しました。しばらく時間をおいて再度お試しください。');
    }
}
```

### ユーザーフレンドリーなエラーメッセージ

```php
public function messages(): array
{
    return [
        'service_type.required' => 'サービス種別を選択してください。',
        'contact_email.email' => '正しいメールアドレス形式で入力してください。',
        'contact_phone.regex' => '電話番号は数字、ハイフン、括弧のみ使用できます。',
    ];
}
```

## アクセシビリティ

### フォームラベル

```blade
<label for="service_type" class="form-label">
    サービス種別 <span class="text-danger" aria-label="必須">*</span>
</label>
<select class="form-select" 
        id="service_type" 
        name="service_type" 
        aria-describedby="service_type_help"
        required>
    <option value="">選択してください</option>
    {{-- オプション --}}
</select>
<div id="service_type_help" class="form-text">
    提供するサービスの種別を選択してください。
</div>
```

### エラーメッセージ

```blade
@error('service_type')
    <div class="invalid-feedback" role="alert" aria-live="polite">
        {{ $message }}
    </div>
@enderror
```

## 国際化対応

### 言語ファイル

```php
// lang/ja/service_info.php
return [
    'title' => 'サービス情報',
    'edit_title' => 'サービス情報編集',
    'service_type' => 'サービス種別',
    'service_description' => 'サービス内容',
    'contact_info' => '連絡先情報',
    'success_message' => 'サービス情報を更新しました。',
];
```

### ビューでの使用

```blade
<x-form.section title="{{ __('service_info.contact_info') }}" icon="fas fa-phone">
    {{-- フォームフィールド --}}
</x-form.section>
```

## デプロイメント

### 本番環境での注意点

```bash
# アセットのビルド
npm run build

# キャッシュのクリア
php artisan config:cache
php artisan route:cache
php artisan view:cache

# マイグレーションの実行
php artisan migrate --force
```

### 環境設定

```env
# .env.production
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
```

## まとめ

このベストプラクティスに従うことで：

- 一貫性のあるユーザーエクスペリエンス
- 保守性の高いコード
- セキュアなアプリケーション
- アクセシブルなインターフェース
- 高いパフォーマンス

を実現できます。新しいフォームを作成する際は、このガイドラインを参考にして、品質の高い機能を開発してください。