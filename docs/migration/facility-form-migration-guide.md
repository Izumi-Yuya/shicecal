# 既存フォーム移行ガイド

## 概要

このドキュメントでは、既存の施設編集フォームを新しい標準化されたレイアウトコンポーネントシステムに移行する方法を説明します。段階的な移行プロセスにより、既存の機能を維持しながら一貫性のあるユーザーエクスペリエンスを実現します。

## 移行の目的

### 移行前の課題

- フォーム間でのレイアウトの不一致
- 重複したCSS・JavaScriptコード
- 保守性の低下
- 新機能追加時の一貫性の欠如

### 移行後の利点

- 統一されたユーザーエクスペリエンス
- 再利用可能なコンポーネント
- 保守性の向上
- 開発効率の向上
- アクセシビリティの改善

## 移行対象の特定

### 現在の編集フォーム一覧

```bash
# 既存の編集フォームを特定
find resources/views -name "*edit.blade.php" | grep -E "(facilities|land-info|basic-info)"
```

**主要な移行対象:**

1. `resources/views/facilities/basic-info/edit.blade.php` ✅ (参考実装)
2. `resources/views/facilities/land-info/edit.blade.php` ✅ (移行済み)
3. `resources/views/facilities/service-info/edit.blade.php` (移行予定)
4. `resources/views/facilities/maintenance/edit.blade.php` (移行予定)
5. `resources/views/facilities/contact-info/edit.blade.php` (移行予定)

## 移行プロセス

### フェーズ1: 準備作業

#### 1.1 現状分析

既存フォームの構造を分析します：

```bash
# 既存フォームの構造を確認
grep -r "form" resources/views/facilities/ --include="*.blade.php" | head -20
```

#### 1.2 依存関係の確認

```bash
# 使用されているCSSクラスを確認
grep -r "class=" resources/views/facilities/ --include="*.blade.php" | grep -E "(card|form|btn)"

# 使用されているJavaScript関数を確認
grep -r "onclick\|addEventListener" resources/views/facilities/ --include="*.blade.php"
```

#### 1.3 バックアップの作成

```bash
# 移行前のバックアップを作成
cp -r resources/views/facilities/ resources/views/facilities.backup.$(date +%Y%m%d)
```

### フェーズ2: 段階的移行

#### 2.1 基本構造の移行

**移行前の構造例:**

```blade
{{-- 移行前: resources/views/facilities/service-info/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>サービス情報編集</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}">ホーム</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('facilities.show', $facility) }}">施設詳細</a></li>
                            <li class="breadcrumb-item active">サービス情報編集</li>
                        </ol>
                    </nav>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('facilities.service-info.update', $facility) }}">
                        @csrf
                        @method('PUT')
                        
                        <!-- フォームフィールド -->
                        <div class="form-group">
                            <label for="service_type">サービス種別</label>
                            <select class="form-control" id="service_type" name="service_type">
                                <!-- オプション -->
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">保存</button>
                            <a href="{{ route('facilities.show', $facility) }}" class="btn btn-secondary">キャンセル</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

**移行後の構造:**

```blade
{{-- 移行後: resources/views/facilities/service-info/edit.blade.php --}}
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
                        <!-- オプション -->
                    </select>
                    @error('service_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </x-form.section>

</x-facility.edit-layout>
@endsection
```

#### 2.2 コントローラーの更新

**移行前:**

```php
public function edit(Facility $facility)
{
    return view('facilities.service-info.edit', compact('facility'));
}
```

**移行後:**

```php
use App\Helpers\FacilityFormHelper;

public function edit(Facility $facility)
{
    $this->authorize('update', $facility);
    
    $breadcrumbs = FacilityFormHelper::generateBreadcrumbs($facility, 'サービス情報編集');
    
    return view('facilities.service-info.edit', compact('facility', 'breadcrumbs'));
}
```

#### 2.3 CSS・JavaScriptの移行

**移行前のカスタムCSS:**

```css
/* 移行前: resources/css/service-info.css */
.service-info-form .card {
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.service-info-form .form-group {
    margin-bottom: 15px;
}

.service-info-form .btn-group {
    margin-top: 20px;
}
```

**移行後:**

```css
/* 移行後: 標準コンポーネントのスタイルを使用 */
/* カスタムスタイルが必要な場合のみ追加 */
.service-info-specific {
    /* サービス情報固有のスタイル */
}
```

### フェーズ3: 機能の検証

#### 3.1 移行チェックリスト

```markdown
## 移行完了チェックリスト

### 基本機能
- [ ] フォームが正常に表示される
- [ ] フィールドの値が正しく表示される
- [ ] バリデーションが正常に動作する
- [ ] 保存処理が正常に動作する
- [ ] キャンセル機能が正常に動作する

### レイアウト
- [ ] ヘッダーが統一されている
- [ ] パンくずリストが正しく表示される
- [ ] セクション分割が適切である
- [ ] アクションボタンが統一されている

### レスポンシブデザイン
- [ ] デスクトップで正常に表示される
- [ ] タブレットで正常に表示される
- [ ] モバイルで正常に表示される

### アクセシビリティ
- [ ] キーボードナビゲーションが動作する
- [ ] スクリーンリーダーで読み上げ可能
- [ ] 色のコントラストが適切
- [ ] フォーカス表示が明確

### パフォーマンス
- [ ] ページ読み込み速度が適切
- [ ] JavaScriptエラーがない
- [ ] CSSが正しく適用されている
```

#### 3.2 自動テストの更新

**移行前のテスト:**

```php
public function test_service_info_edit_page_displays()
{
    $response = $this->actingAs($this->user)
        ->get(route('facilities.service-info.edit', $this->facility));

    $response->assertOk()
        ->assertSee('サービス情報編集')
        ->assertSee($this->facility->name);
}
```

**移行後のテスト:**

```php
public function test_service_info_edit_page_displays_with_new_layout()
{
    $response = $this->actingAs($this->user)
        ->get(route('facilities.service-info.edit', $this->facility));

    $response->assertOk()
        ->assertViewIs('facilities.service-info.edit')
        ->assertViewHas('facility', $this->facility)
        ->assertViewHas('breadcrumbs')
        ->assertSee('サービス情報編集')
        ->assertSee($this->facility->name)
        ->assertSee('基本サービス情報'); // 新しいセクションタイトル
}
```

## 具体的な移行例

### 例1: メンテナンス情報編集フォーム

#### 移行前の分析

```blade
{{-- 現在の構造 --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>メンテナンス情報編集</h2>
    
    <form method="POST" action="{{ route('facilities.maintenance.update', $facility) }}">
        @csrf
        @method('PUT')
        
        <div class="card">
            <div class="card-header">基本情報</div>
            <div class="card-body">
                <div class="form-group">
                    <label>メンテナンス種別</label>
                    <select name="maintenance_type" class="form-control">
                        <!-- オプション -->
                    </select>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">保存</button>
    </form>
</div>
@endsection
```

#### 移行手順

**ステップ1: コントローラーの更新**

```php
// app/Http/Controllers/MaintenanceController.php
public function edit(Facility $facility)
{
    $this->authorize('update', $facility);
    
    $breadcrumbs = FacilityFormHelper::generateBreadcrumbs($facility, 'メンテナンス情報編集');
    
    return view('facilities.maintenance.edit', compact('facility', 'breadcrumbs'));
}
```

**ステップ2: ビューの移行**

```blade
{{-- resources/views/facilities/maintenance/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'メンテナンス情報編集')

@section('content')
<x-facility.edit-layout 
    :title="'メンテナンス情報編集'"
    :facility="$facility"
    :breadcrumbs="$breadcrumbs"
    :back-route="route('facilities.show', $facility)"
    :form-action="route('facilities.maintenance.update', $facility)"
    form-method="PUT">

    <x-form.section title="基本メンテナンス情報" icon="fas fa-tools" icon-color="warning">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="maintenance_type" class="form-label">メンテナンス種別 <span class="text-danger">*</span></label>
                    <select class="form-select @error('maintenance_type') is-invalid @enderror" 
                            id="maintenance_type" 
                            name="maintenance_type" 
                            required>
                        <option value="">選択してください</option>
                        <option value="regular" {{ old('maintenance_type', $facility->maintenance_type) === 'regular' ? 'selected' : '' }}>定期メンテナンス</option>
                        <option value="repair" {{ old('maintenance_type', $facility->maintenance_type) === 'repair' ? 'selected' : '' }}>修理</option>
                        <option value="inspection" {{ old('maintenance_type', $facility->maintenance_type) === 'inspection' ? 'selected' : '' }}>点検</option>
                    </select>
                    @error('maintenance_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="maintenance_date" class="form-label">実施予定日</label>
                    <input type="date" 
                           class="form-control @error('maintenance_date') is-invalid @enderror" 
                           id="maintenance_date" 
                           name="maintenance_date" 
                           value="{{ old('maintenance_date', $facility->maintenance_date?->format('Y-m-d')) }}">
                    @error('maintenance_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </x-form.section>

    <x-form.section title="詳細情報" icon="fas fa-clipboard-list" icon-color="info">
        <div class="row">
            <div class="col-12">
                <div class="mb-3">
                    <label for="maintenance_description" class="form-label">メンテナンス内容</label>
                    <textarea class="form-control @error('maintenance_description') is-invalid @enderror" 
                              id="maintenance_description" 
                              name="maintenance_description" 
                              rows="4"
                              placeholder="実施するメンテナンスの詳細を入力してください">{{ old('maintenance_description', $facility->maintenance_description) }}</textarea>
                    @error('maintenance_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </x-form.section>

</x-facility.edit-layout>
@endsection
```

**ステップ3: テストの更新**

```php
// tests/Feature/MaintenanceControllerTest.php
public function test_maintenance_edit_uses_new_layout_components()
{
    $response = $this->actingAs($this->user)
        ->get(route('facilities.maintenance.edit', $this->facility));

    $response->assertOk()
        ->assertSee('基本メンテナンス情報')
        ->assertSee('詳細情報')
        ->assertSee('fas fa-tools') // アイコンの確認
        ->assertViewHas('breadcrumbs');
}
```

### 例2: 連絡先情報編集フォーム

#### 移行計画

```markdown
## 連絡先情報編集フォーム移行計画

### 現状分析
- 単一のカードレイアウト
- 基本的なフォームフィールド
- 簡単なバリデーション

### 移行後の構造
- 基本連絡先情報セクション
- 緊急連絡先セクション
- 営業時間セクション

### 必要な作業
1. コントローラーの更新
2. ビューの移行
3. バリデーションルールの確認
4. テストの更新
```

## 移行時の注意点

### 1. 既存機能の保持

```php
// 移行前の特殊な処理を保持
if ($facility->hasSpecialRequirements()) {
    // 特殊処理のロジック
}
```

### 2. カスタムスタイルの処理

```css
/* 必要に応じてカスタムスタイルを追加 */
.contact-info-form .emergency-contact {
    border-left: 4px solid #dc3545;
    padding-left: 1rem;
}
```

### 3. JavaScript機能の移行

```javascript
// 既存のカスタムJavaScript機能
document.addEventListener('DOMContentLoaded', function() {
    // 郵便番号自動入力機能
    const postalCodeInput = document.getElementById('postal_code');
    if (postalCodeInput) {
        postalCodeInput.addEventListener('blur', function() {
            // 住所自動入力処理
        });
    }
});
```

### 4. データベース変更の確認

```php
// マイグレーションが必要な場合
Schema::table('facilities', function (Blueprint $table) {
    $table->string('emergency_contact_phone')->nullable();
    $table->string('business_hours')->nullable();
});
```

## 移行後の検証

### 1. 機能テスト

```bash
# 全体的なテストの実行
php artisan test --filter=Facility

# 特定のフォームのテスト
php artisan test tests/Feature/MaintenanceControllerTest.php
```

### 2. ビジュアル回帰テスト

```bash
# スクリーンショット比較（手動）
# 移行前後のスクリーンショットを比較
```

### 3. パフォーマンステスト

```bash
# ページ読み込み速度の確認
curl -w "@curl-format.txt" -o /dev/null -s "http://localhost:8000/facilities/1/maintenance/edit"
```

### 4. アクセシビリティテスト

```bash
# axe-core を使用したアクセシビリティテスト
npm run test:accessibility
```

## ロールバック計画

### 緊急時のロールバック

```bash
# バックアップからの復元
cp -r resources/views/facilities.backup.20240101/ resources/views/facilities/

# キャッシュのクリア
php artisan view:clear
php artisan config:clear
```

### 段階的なロールバック

```php
// 設定による切り替え
if (config('facility-form.use_legacy_layout')) {
    return view('facilities.maintenance.edit-legacy', compact('facility'));
}

return view('facilities.maintenance.edit', compact('facility', 'breadcrumbs'));
```

## 移行スケジュール例

### 週1: 準備フェーズ
- 現状分析
- バックアップ作成
- 移行計画の詳細化

### 週2-3: 移行実装
- コンポーネントの適用
- テストの更新
- 機能検証

### 週4: 検証・調整
- 包括的なテスト
- パフォーマンス確認
- ドキュメント更新

### 週5: デプロイ・監視
- 本番環境への適用
- 監視・問題対応
- フィードバック収集

## まとめ

この移行ガイドに従うことで：

- 既存機能を維持しながら段階的に移行
- 一貫性のあるユーザーエクスペリエンスを実現
- 保守性とスケーラビリティを向上
- 将来の機能追加を効率化

移行作業は慎重に進め、各段階で十分なテストを実施することが重要です。問題が発生した場合は、すぐにロールバックできる体制を整えておきましょう。