# 施設フォーム クイックリファレンス

## 🚀 新しいフォームを5分で作成

### 1. ファイル作成
```bash
# コントローラー
php artisan make:controller ServiceInfoController

# リクエスト
php artisan make:request ServiceInfoRequest

# ビュー
mkdir -p resources/views/facilities/service-info
touch resources/views/facilities/service-info/edit.blade.php
```

### 2. 最小限のビュー
```blade
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

### 3. 基本コントローラー
```php
public function edit(Facility $facility)
{
    $this->authorize('update', $facility);
    $breadcrumbs = FacilityFormHelper::generateBreadcrumbs($facility, 'サービス情報編集');
    return view('facilities.service-info.edit', compact('facility', 'breadcrumbs'));
}

public function update(ServiceInfoRequest $request, Facility $facility)
{
    $this->authorize('update', $facility);
    $facility->update($request->validated());
    return redirect()->route('facilities.show', $facility)->with('success', '更新しました。');
}
```

## 📋 コンポーネント早見表

### FacilityEditLayout
```blade
<x-facility.edit-layout 
    title="ページタイトル"           <!-- 必須 -->
    :facility="$facility"           <!-- 必須 -->
    :breadcrumbs="$breadcrumbs"     <!-- 必須 -->
    :back-route="$backRoute"        <!-- 必須 -->
    :form-action="$formAction"      <!-- 必須 -->
    form-method="PUT">              <!-- オプション: POST -->
    <!-- コンテンツ -->
</x-facility.edit-layout>
```

### FormSection
```blade
<x-form.section 
    title="セクション名"            <!-- 必須 -->
    icon="fas fa-icon"             <!-- 必須 -->
    icon-color="primary"           <!-- オプション: primary -->
    :collapsible="false"           <!-- オプション: false -->
    :collapsed="false">            <!-- オプション: false -->
    <!-- フィールド -->
</x-form.section>
```

## 🎨 利用可能なアイコンと色

### アイコン
```php
'basic_info'    => 'fas fa-info-circle'
'land_info'     => 'fas fa-map'
'contact_info'  => 'fas fa-phone'
'building_info' => 'fas fa-building'
'service_info'  => 'fas fa-cogs'
'maintenance'   => 'fas fa-tools'
'financial'     => 'fas fa-yen-sign'
'documents'     => 'fas fa-file-alt'
```

### 色
```php
'primary'   => 'primary'    // 青
'success'   => 'success'    // 緑
'info'      => 'info'       // 水色
'warning'   => 'warning'    // 黄
'danger'    => 'danger'     // 赤
```

## 🔧 よく使うフィールドパターン

### テキスト入力
```blade
<div class="mb-3">
    <label for="field_name" class="form-label">ラベル <span class="text-danger">*</span></label>
    <input type="text" 
           class="form-control @error('field_name') is-invalid @enderror" 
           id="field_name" 
           name="field_name" 
           value="{{ old('field_name', $facility->field_name) }}"
           required>
    @error('field_name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

### セレクトボックス
```blade
<div class="mb-3">
    <label for="select_field" class="form-label">選択項目</label>
    <select class="form-select @error('select_field') is-invalid @enderror" 
            id="select_field" 
            name="select_field">
        <option value="">選択してください</option>
        <option value="option1" {{ old('select_field', $facility->select_field) === 'option1' ? 'selected' : '' }}>オプション1</option>
    </select>
    @error('select_field')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

### テキストエリア
```blade
<div class="mb-3">
    <label for="description" class="form-label">説明</label>
    <textarea class="form-control @error('description') is-invalid @enderror" 
              id="description" 
              name="description" 
              rows="4"
              placeholder="詳細を入力してください">{{ old('description', $facility->description) }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

## ✅ バリデーション早見表

### 基本ルール
```php
'field_name' => ['required', 'string', 'max:255'],
'email'      => ['nullable', 'email', 'max:255'],
'phone'      => ['nullable', 'regex:/^[0-9\-\(\)\+\s]+$/', 'max:20'],
'number'     => ['nullable', 'integer', 'min:0'],
'date'       => ['nullable', 'date'],
'select'     => ['required', 'in:option1,option2,option3'],
```

### 日本語属性名
```php
public function attributes(): array
{
    return [
        'service_type' => 'サービス種別',
        'contact_phone' => '連絡先電話番号',
        'contact_email' => '連絡先メールアドレス',
    ];
}
```

## 🧪 基本テストパターン

```php
public function test_edit_page_displays_correctly()
{
    $response = $this->actingAs($this->user)
        ->get(route('facilities.service-info.edit', $this->facility));

    $response->assertOk()
        ->assertViewIs('facilities.service-info.edit')
        ->assertSee('サービス情報編集');
}

public function test_update_successfully()
{
    $data = ['service_type' => 'medical'];
    
    $response = $this->actingAs($this->user)
        ->put(route('facilities.service-info.update', $this->facility), $data);

    $response->assertRedirect()
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('facilities', $data);
}
```

## 🔍 デバッグコマンド

```bash
# ビューキャッシュクリア
php artisan view:clear

# 設定キャッシュクリア
php artisan config:clear

# アセットビルド
npm run build

# テスト実行
php artisan test --filter=ServiceInfo

# ルート確認
php artisan route:list | grep service-info
```

## 📁 ファイル配置

```
app/Http/Controllers/ServiceInfoController.php
app/Http/Requests/ServiceInfoRequest.php
resources/views/facilities/service-info/edit.blade.php
tests/Feature/ServiceInfoControllerTest.php
```

## 🔗 関連ドキュメント

- [詳細ガイド](facility-form-developer-guide.md)
- [ベストプラクティス](facility-form-best-practices.md)
- [コンポーネント仕様](../components/facility-form-layout-components.md)
- [移行ガイド](../migration/facility-form-migration-guide.md)