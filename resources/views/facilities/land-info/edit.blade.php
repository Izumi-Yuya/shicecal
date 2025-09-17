@php
    $canEditAny = auth()->user()->canEditLandInfo();
    $breadcrumbs = [
        [
            'title' => '施設一覧',
            'route' => 'facilities.index',
            'active' => false
        ],
        [
            'title' => $facility->facility_name,
            'route' => 'facilities.show',
            'params' => [$facility],
            'active' => false
        ],
        [
            'title' => '土地情報編集',
            'active' => true
        ]
    ];
    
    // Cache error fields to avoid repeated helper calls
    $errorFieldsCache = [
        'basic_info' => App\Helpers\FacilityFormHelper::getErrorFieldsForSection('basic_info'),
        'area_info' => App\Helpers\FacilityFormHelper::getErrorFieldsForSection('area_info'),
        'owned_property' => App\Helpers\FacilityFormHelper::getErrorFieldsForSection('owned_property'),
        'leased_property' => App\Helpers\FacilityFormHelper::getErrorFieldsForSection('leased_property'),
        'management_company' => App\Helpers\FacilityFormHelper::getErrorFieldsForSection('management_company'),
        'owner_info' => App\Helpers\FacilityFormHelper::getErrorFieldsForSection('owner_info'),
    ];
@endphp

@if(!$canEditAny)
    @extends('layouts.app')
    
    @section('title', '土地情報編集 - ' . $facility->facility_name)
    
    @section('content')
    <div class="container-fluid">
        <!-- 権限なしの場合の表示 -->
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-lock fa-4x text-muted"></i>
            </div>
            <h4 class="text-muted mb-3">編集権限がありません</h4>
            <p class="text-muted mb-4">
                この施設の土地情報を編集する権限がありません。<br>
                土地情報の編集には管理者または編集者権限が必要です。
            </p>
            <div class="mt-4">
                <a href="{{ route('facilities.show', $facility) }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>施設詳細に戻る
                </a>
            </div>
        </div>
    </div>
    @endsection
@else
    @push('styles')
        @vite('resources/css/land-info-final.css')
    @endpush
    <x-facility.edit-layout
        title="土地情報編集 - {{ $facility->facility_name }}"
        :facility="$facility"
        :breadcrumbs="$breadcrumbs"
        :back-route="route('facilities.show', $facility)"
        :form-action="route('facilities.land-info.update', $facility)"
        form-method="PUT"
        form-id="landInfoForm"
    >
        <!-- 基本情報セクション -->
        <x-form.section title="基本情報" icon="fas fa-map" icon-color="primary" 
                        :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('basic_info')">
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-lg-4">
                    <label for="ownership_type" class="form-label">所有形態</label>
                    <select name="ownership_type" id="ownership_type" 
                            class="form-select @error('ownership_type') is-invalid @enderror" 
                            data-initial-value="{{ old('ownership_type', $landInfo->ownership_type ?? '') }}"
                            aria-describedby="ownership_type_help">
                        <option value="">選択してください</option>
                        <option value="owned" {{ old('ownership_type', $landInfo->ownership_type ?? '') === 'owned' ? 'selected' : '' }}>自社</option>
                        <option value="leased" {{ old('ownership_type', $landInfo->ownership_type ?? '') === 'leased' ? 'selected' : '' }}>賃借</option>
                        <option value="owned_rental" {{ old('ownership_type', $landInfo->ownership_type ?? '') === 'owned_rental' ? 'selected' : '' }}>自社（賃貸）</option>
                    </select>
                    <small id="ownership_type_help" class="form-text text-muted">
                        選択した所有形態により表示される項目が変わります
                    </small>
                    <x-form.field-error field="ownership_type" />
                </div>
                
                <div class="col-12 col-sm-6 col-lg-4">
                    <label for="parking_spaces" class="form-label">敷地内駐車場台数</label>
                    <div class="input-group">
                        <input type="number" name="parking_spaces" id="parking_spaces" 
                               class="form-control @error('parking_spaces') is-invalid @enderror" 
                               value="{{ old('parking_spaces', $landInfo->parking_spaces ?? '') }}"
                               min="0" max="9999999999" step="1" 
                               pattern="[0-9]*" inputmode="numeric"
                               placeholder="例: 50"
                               aria-describedby="parking_spaces_help">
                        <span class="input-group-text">台</span>
                    </div>
                    <small id="parking_spaces_help" class="form-text text-muted visually-hidden">
                        敷地内の駐車場台数を入力してください
                    </small>
                    <x-form.field-error field="parking_spaces" />
                </div>
            </div>
        </x-form.section>

        <!-- 面積情報セクション -->
        <x-form.section title="面積情報" icon="fas fa-ruler-combined" icon-color="success"
                        :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('area_info')">
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-lg-4">
                    <label for="site_area_sqm" class="form-label">敷地面積（㎡）</label>
                    <div class="input-group">
                        <input type="number" name="site_area_sqm" id="site_area_sqm" 
                               class="form-control @error('site_area_sqm') is-invalid @enderror" 
                               value="{{ old('site_area_sqm', $landInfo->site_area_sqm ?? '') }}"
                               step="0.01" min="0" max="99999999.99"
                               inputmode="decimal" placeholder="例: 290.00"
                               aria-describedby="site_area_sqm_help">
                        <span class="input-group-text">㎡</span>
                    </div>
                    <small id="site_area_sqm_help" class="form-text text-muted visually-hidden">
                        敷地面積を平方メートル単位で入力してください
                    </small>
                    <x-form.field-error field="site_area_sqm" />
                </div>
                
                <div class="col-12 col-sm-6 col-lg-4">
                    <label for="site_area_tsubo" class="form-label">敷地面積（坪数）</label>
                    <div class="input-group">
                        <input type="number" name="site_area_tsubo" id="site_area_tsubo" 
                               class="form-control @error('site_area_tsubo') is-invalid @enderror" 
                               value="{{ old('site_area_tsubo', $landInfo->site_area_tsubo ?? '') }}"
                               step="0.01" min="0" max="99999999.99"
                               inputmode="decimal" placeholder="例: 89.05"
                               aria-describedby="site_area_tsubo_help">
                        <span class="input-group-text">坪</span>
                    </div>
                    <small id="site_area_tsubo_help" class="form-text text-muted visually-hidden">
                        敷地面積を坪単位で入力してください
                    </small>
                    <x-form.field-error field="site_area_tsubo" />
                </div>
            </div>
        </x-form.section>

        <!-- 自社物件情報セクション -->
        <div id="owned_section" class="conditional-section mb-4" style="display: block;" aria-hidden="false" aria-expanded="true" role="region" aria-labelledby="owned_section_title">
            <x-form.section title="自社物件情報" icon="fas fa-building" icon-color="info"
                            :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('owned_property')">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="purchase_price" class="form-label">購入金額</label>
                        <div class="input-group">
                            <input type="text" name="purchase_price" id="purchase_price" 
                                   class="form-control currency-input @error('purchase_price') is-invalid @enderror" 
                                   value="{{ old('purchase_price', $landInfo && $landInfo->purchase_price ? number_format($landInfo->purchase_price) : '') }}"
                                   placeholder="例: 10,000,000"
                                   aria-describedby="purchase_price_help">
                            <span class="input-group-text">円</span>
                        </div>
                        <small id="purchase_price_help" class="form-text text-muted visually-hidden">
                            土地の購入金額を入力してください
                        </small>
                        <x-form.field-error field="purchase_price" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="unit_price_display" class="form-label">
                            坪単価
                            <span class="auto-calc-indicator" title="購入金額と坪数から自動計算されます">
                                <i class="fas fa-calculator text-info"></i>
                                <small class="text-muted">自動計算</small>
                            </span>
                        </label>
                        <div class="input-group">
                            <input type="text" id="unit_price_display" class="form-control auto-calc-field" readonly 
                                   placeholder="自動計算されます"
                                   aria-describedby="unit_price_help"
                                   title="このフィールドは購入金額と坪数から自動計算されます">
                            <span class="input-group-text">円/坪</span>
                        </div>
                        <small id="unit_price_help" class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> 購入金額と坪数を入力すると自動で計算されます
                        </small>
                    </div>
                </div>
            </x-form.section>
        </div>

        <!-- 賃借物件情報セクション -->
        <div id="leased_section" class="conditional-section mb-4" style="display: block;" aria-hidden="false" aria-expanded="true" role="region" aria-labelledby="leased_section_title">
            <x-form.section title="賃借物件情報" icon="fas fa-file-contract" icon-color="warning"
                            :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('leased_property')">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="monthly_rent" class="form-label">家賃</label>
                        <div class="input-group">
                            <input type="text" name="monthly_rent" id="monthly_rent" 
                                   class="form-control currency-input @error('monthly_rent') is-invalid @enderror" 
                                   value="{{ old('monthly_rent', $landInfo && $landInfo->monthly_rent ? number_format($landInfo->monthly_rent) : '') }}"
                                   placeholder="例: 500,000"
                                   aria-describedby="monthly_rent_help">
                            <span class="input-group-text">円</span>
                        </div>
                        <small id="monthly_rent_help" class="form-text text-muted visually-hidden">
                            月額賃料を入力してください
                        </small>
                        <x-form.field-error field="monthly_rent" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="contract_start_date" class="form-label">契約開始日</label>
                        <input type="date" name="contract_start_date" id="contract_start_date" 
                               class="form-control @error('contract_start_date') is-invalid @enderror" 
                               value="{{ old('contract_start_date', $landInfo && $landInfo->contract_start_date ? $landInfo->contract_start_date->format('Y-m-d') : '') }}"
                               aria-describedby="contract_start_date_help">
                        <small id="contract_start_date_help" class="form-text text-muted visually-hidden">
                            賃貸借契約の開始日を選択してください
                        </small>
                        <x-form.field-error field="contract_start_date" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="contract_end_date" class="form-label">契約終了日</label>
                        <input type="date" name="contract_end_date" id="contract_end_date" 
                               class="form-control @error('contract_end_date') is-invalid @enderror" 
                               value="{{ old('contract_end_date', $landInfo && $landInfo->contract_end_date ? $landInfo->contract_end_date->format('Y-m-d') : '') }}"
                               aria-describedby="contract_end_date_help">
                        <small id="contract_end_date_help" class="form-text text-muted visually-hidden">
                            賃貸借契約の終了日を選択してください
                        </small>
                        <x-form.field-error field="contract_end_date" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="auto_renewal" class="form-label">自動更新の有無</label>
                        <select name="auto_renewal" id="auto_renewal" 
                                class="form-select @error('auto_renewal') is-invalid @enderror"
                                aria-describedby="auto_renewal_help">
                            <option value="">選択してください</option>
                            <option value="yes" {{ old('auto_renewal', $landInfo->auto_renewal ?? '') === 'yes' ? 'selected' : '' }}>あり</option>
                            <option value="no" {{ old('auto_renewal', $landInfo->auto_renewal ?? '') === 'no' ? 'selected' : '' }}>なし</option>
                        </select>
                        <small id="auto_renewal_help" class="form-text text-muted visually-hidden">
                            契約の自動更新の有無を選択してください
                        </small>
                        <x-form.field-error field="auto_renewal" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="contract_period_display" class="form-label">
                            契約年数
                            <span class="auto-calc-indicator" title="契約開始日と終了日から自動計算されます">
                                <i class="fas fa-calculator text-info"></i>
                                <small class="text-muted">自動計算</small>
                            </span>
                        </label>
                        <input type="text" id="contract_period_display" class="form-control auto-calc-field" readonly 
                               placeholder="自動計算されます"
                               aria-describedby="contract_period_help"
                               title="このフィールドは契約開始日と終了日から自動計算されます">
                        <small id="contract_period_help" class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> 契約開始日と終了日を入力すると自動で計算されます
                        </small>
                    </div>
                </div>
            </x-form.section>
        </div>

        <!-- 管理会社情報セクション -->
        <div id="management_section" class="conditional-section mb-4" style="display: block;" aria-hidden="false" aria-expanded="true" role="region" aria-labelledby="management_section_title">
            <x-form.section title="管理会社情報" icon="fas fa-building" icon-color="secondary"
                            :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('management_company')">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="management_company_name" class="form-label">会社名</label>
                        <input type="text" name="management_company_name" id="management_company_name" 
                               class="form-control @error('management_company_name') is-invalid @enderror" 
                               value="{{ old('management_company_name', $landInfo->management_company_name ?? '') }}"
                               maxlength="30" placeholder="例: 株式会社○○管理"
                               aria-describedby="management_company_name_help">
                        <small id="management_company_name_help" class="form-text text-muted visually-hidden">
                            管理会社の会社名を入力してください
                        </small>
                        <x-form.field-error field="management_company_name" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="management_company_postal_code" class="form-label">郵便番号</label>
                        <input type="text" name="management_company_postal_code" id="management_company_postal_code" 
                               class="form-control @error('management_company_postal_code') is-invalid @enderror" 
                               value="{{ old('management_company_postal_code', $landInfo->management_company_postal_code ?? '') }}"
                               pattern="\d{3}-\d{4}" placeholder="例: 123-4567"
                               aria-describedby="management_company_postal_code_help">
                        <small id="management_company_postal_code_help" class="form-text text-muted visually-hidden">
                            管理会社の郵便番号を入力してください
                        </small>
                        <x-form.field-error field="management_company_postal_code" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="management_company_address" class="form-label">住所</label>
                        <input type="text" name="management_company_address" id="management_company_address" 
                               class="form-control @error('management_company_address') is-invalid @enderror" 
                               value="{{ old('management_company_address', $landInfo->management_company_address ?? '') }}"
                               maxlength="30" placeholder="例: 東京都渋谷区○○1-2-3"
                               aria-describedby="management_company_address_help">
                        <small id="management_company_address_help" class="form-text text-muted visually-hidden">
                            管理会社の住所を入力してください
                        </small>
                        <x-form.field-error field="management_company_address" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="management_company_building" class="form-label">住所建物名</label>
                        <input type="text" name="management_company_building" id="management_company_building" 
                               class="form-control @error('management_company_building') is-invalid @enderror" 
                               value="{{ old('management_company_building', $landInfo->management_company_building ?? '') }}"
                               maxlength="20" placeholder="例: ○○ビル5F"
                               aria-describedby="management_company_building_help">
                        <small id="management_company_building_help" class="form-text text-muted visually-hidden">
                            管理会社の建物名を入力してください
                        </small>
                        <x-form.field-error field="management_company_building" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="management_company_phone" class="form-label">電話番号</label>
                        <input type="text" name="management_company_phone" id="management_company_phone" 
                               class="form-control @error('management_company_phone') is-invalid @enderror" 
                               value="{{ old('management_company_phone', $landInfo->management_company_phone ?? '') }}"
                               pattern="\d{2,4}-\d{2,4}-\d{4}" placeholder="例: 03-1234-5678"
                               aria-describedby="management_company_phone_help">
                        <small id="management_company_phone_help" class="form-text text-muted visually-hidden">
                            管理会社の電話番号を入力してください
                        </small>
                        <x-form.field-error field="management_company_phone" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="management_company_fax" class="form-label">FAX番号</label>
                        <input type="text" name="management_company_fax" id="management_company_fax" 
                               class="form-control @error('management_company_fax') is-invalid @enderror" 
                               value="{{ old('management_company_fax', $landInfo->management_company_fax ?? '') }}"
                               pattern="\d{2,4}-\d{2,4}-\d{4}" placeholder="例: 03-1234-5679"
                               aria-describedby="management_company_fax_help">
                        <small id="management_company_fax_help" class="form-text text-muted visually-hidden">
                            管理会社のFAX番号を入力してください
                        </small>
                        <x-form.field-error field="management_company_fax" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="management_company_email" class="form-label">メールアドレス</label>
                        <input type="email" name="management_company_email" id="management_company_email" 
                               class="form-control @error('management_company_email') is-invalid @enderror" 
                               value="{{ old('management_company_email', $landInfo->management_company_email ?? '') }}"
                               maxlength="100" placeholder="例: info@example.com"
                               aria-describedby="management_company_email_help">
                        <small id="management_company_email_help" class="form-text text-muted visually-hidden">
                            管理会社のメールアドレスを入力してください
                        </small>
                        <x-form.field-error field="management_company_email" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="management_company_url" class="form-label">URL</label>
                        <input type="url" name="management_company_url" id="management_company_url" 
                               class="form-control @error('management_company_url') is-invalid @enderror" 
                               value="{{ old('management_company_url', $landInfo->management_company_url ?? '') }}"
                               maxlength="100" placeholder="例: https://example.com"
                               aria-describedby="management_company_url_help">
                        <small id="management_company_url_help" class="form-text text-muted visually-hidden">
                            管理会社のWebサイトURLを入力してください
                        </small>
                        <x-form.field-error field="management_company_url" />
                    </div>
                    
                    <div class="col-12">
                        <label for="management_company_notes" class="form-label">備考</label>
                        <textarea name="management_company_notes" id="management_company_notes" 
                                  class="form-control @error('management_company_notes') is-invalid @enderror" 
                                  rows="3" maxlength="1000" 
                                  placeholder="管理会社に関する備考があれば入力してください"
                                  aria-describedby="management_company_notes_help">{{ old('management_company_notes', $landInfo->management_company_notes ?? '') }}</textarea>
                        <small id="management_company_notes_help" class="form-text text-muted visually-hidden">
                            管理会社に関する備考を入力してください（最大1000文字）
                        </small>
                        <x-form.field-error field="management_company_notes" />
                    </div>
                </div>
            </x-form.section>
        </div>

        <!-- オーナー情報セクション -->
        <div id="owner_section" class="conditional-section mb-4" style="display: block;" aria-hidden="false" aria-expanded="true" role="region" aria-labelledby="owner_section_title">
            <x-form.section title="オーナー情報" icon="fas fa-user-tie" icon-color="dark"
                            :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('owner_info')">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="owner_name" class="form-label">氏名・会社名</label>
                        <input type="text" name="owner_name" id="owner_name" 
                               class="form-control @error('owner_name') is-invalid @enderror" 
                               value="{{ old('owner_name', $landInfo->owner_name ?? '') }}"
                               maxlength="30" placeholder="例: 田中太郎 または 株式会社○○"
                               aria-describedby="owner_name_help">
                        <small id="owner_name_help" class="form-text text-muted visually-hidden">
                            オーナーの氏名または会社名を入力してください
                        </small>
                        <x-form.field-error field="owner_name" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="owner_postal_code" class="form-label">郵便番号</label>
                        <input type="text" name="owner_postal_code" id="owner_postal_code" 
                               class="form-control @error('owner_postal_code') is-invalid @enderror" 
                               value="{{ old('owner_postal_code', $landInfo->owner_postal_code ?? '') }}"
                               pattern="\d{3}-\d{4}" placeholder="例: 123-4567"
                               aria-describedby="owner_postal_code_help">
                        <small id="owner_postal_code_help" class="form-text text-muted visually-hidden">
                            オーナーの郵便番号を入力してください
                        </small>
                        <x-form.field-error field="owner_postal_code" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="owner_address" class="form-label">住所</label>
                        <input type="text" name="owner_address" id="owner_address" 
                               class="form-control @error('owner_address') is-invalid @enderror" 
                               value="{{ old('owner_address', $landInfo->owner_address ?? '') }}"
                               maxlength="30" placeholder="例: 東京都渋谷区○○1-2-3"
                               aria-describedby="owner_address_help">
                        <small id="owner_address_help" class="form-text text-muted visually-hidden">
                            オーナーの住所を入力してください
                        </small>
                        <x-form.field-error field="owner_address" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="owner_building" class="form-label">住所建物名</label>
                        <input type="text" name="owner_building" id="owner_building" 
                               class="form-control @error('owner_building') is-invalid @enderror" 
                               value="{{ old('owner_building', $landInfo->owner_building ?? '') }}"
                               maxlength="20" placeholder="例: ○○マンション101"
                               aria-describedby="owner_building_help">
                        <small id="owner_building_help" class="form-text text-muted visually-hidden">
                            オーナーの建物名を入力してください
                        </small>
                        <x-form.field-error field="owner_building" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="owner_phone" class="form-label">電話番号</label>
                        <input type="text" name="owner_phone" id="owner_phone" 
                               class="form-control @error('owner_phone') is-invalid @enderror" 
                               value="{{ old('owner_phone', $landInfo->owner_phone ?? '') }}"
                               pattern="\d{2,4}-\d{2,4}-\d{4}" placeholder="例: 03-1234-5678"
                               aria-describedby="owner_phone_help">
                        <small id="owner_phone_help" class="form-text text-muted visually-hidden">
                            オーナーの電話番号を入力してください
                        </small>
                        <x-form.field-error field="owner_phone" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="owner_fax" class="form-label">FAX番号</label>
                        <input type="text" name="owner_fax" id="owner_fax" 
                               class="form-control @error('owner_fax') is-invalid @enderror" 
                               value="{{ old('owner_fax', $landInfo->owner_fax ?? '') }}"
                               pattern="\d{2,4}-\d{2,4}-\d{4}" placeholder="例: 03-1234-5679"
                               aria-describedby="owner_fax_help">
                        <small id="owner_fax_help" class="form-text text-muted visually-hidden">
                            オーナーのFAX番号を入力してください
                        </small>
                        <x-form.field-error field="owner_fax" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="owner_email" class="form-label">メールアドレス</label>
                        <input type="email" name="owner_email" id="owner_email" 
                               class="form-control @error('owner_email') is-invalid @enderror" 
                               value="{{ old('owner_email', $landInfo->owner_email ?? '') }}"
                               maxlength="100" placeholder="例: owner@example.com"
                               aria-describedby="owner_email_help">
                        <small id="owner_email_help" class="form-text text-muted visually-hidden">
                            オーナーのメールアドレスを入力してください
                        </small>
                        <x-form.field-error field="owner_email" />
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label for="owner_url" class="form-label">URL</label>
                        <input type="url" name="owner_url" id="owner_url" 
                               class="form-control @error('owner_url') is-invalid @enderror" 
                               value="{{ old('owner_url', $landInfo->owner_url ?? '') }}"
                               maxlength="100" placeholder="例: https://owner-site.com"
                               aria-describedby="owner_url_help">
                        <small id="owner_url_help" class="form-text text-muted visually-hidden">
                            オーナーのWebサイトURLを入力してください
                        </small>
                        <x-form.field-error field="owner_url" />
                    </div>
                    
                    <div class="col-12">
                        <label for="owner_notes" class="form-label">備考欄</label>
                        <textarea name="owner_notes" id="owner_notes" 
                                  class="form-control @error('owner_notes') is-invalid @enderror" 
                                  rows="3" maxlength="1000" 
                                  placeholder="オーナーに関する備考があれば入力してください"
                                  aria-describedby="owner_notes_help">{{ old('owner_notes', $landInfo->owner_notes ?? '') }}</textarea>
                        <small id="owner_notes_help" class="form-text text-muted visually-hidden">
                            オーナーに関する備考を入力してください（最大1000文字）
                        </small>
                        <x-form.field-error field="owner_notes" />
                    </div>
                </div>
            </x-form.section>
        </div>

        <!-- 関連書類セクション -->
        <div id="file_section" class="conditional-section mb-4" style="display: block;" aria-hidden="false" aria-expanded="true" role="region" aria-labelledby="file_section_title">
            <x-form.section title="関連書類" icon="fas fa-file-pdf" icon-color="danger"
                            :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('documents')">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label for="lease_contract_pdf" class="form-label">賃貸借契約書・覚書</label>
                        @if($landInfo && $landInfo->lease_contract_pdf_name)
                            <div class="mb-2 p-2 bg-light border rounded">
                                <small class="text-muted">現在のファイル:</small><br>
                                <a href="{{ route('facilities.land-info.download', ['facility' => $facility, 'type' => 'lease_contract']) }}" 
                                   class="text-decoration-none" target="_blank">
                                    <i class="fas fa-file-contract text-warning me-1"></i>{{ $landInfo->lease_contract_pdf_name }}
                                    <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                </a>
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="delete_lease_contract_pdf" id="delete_lease_contract_pdf">
                                    <label class="form-check-label text-danger" for="delete_lease_contract_pdf">
                                        <small>このファイルを削除する</small>
                                    </label>
                                </div>
                            </div>
                        @endif
                        <input type="file" name="lease_contract_pdf" id="lease_contract_pdf" 
                               class="form-control @error('lease_contract_pdf') is-invalid @enderror" 
                               accept=".pdf" 
                               data-max-size="2097152"
                               aria-describedby="lease_contract_help">
                        <small id="lease_contract_help" class="form-text text-muted">PDFファイルのみ（最大2MB）</small>
                        <x-form.field-error field="lease_contract_pdf" />
                    </div>
                    
                    <div class="col-12 col-sm-6">
                        <label for="registry_pdf" class="form-label">謄本</label>
                        @if($landInfo && $landInfo->registry_pdf_name)
                            <div class="mb-2 p-2 bg-light border rounded">
                                <small class="text-muted">現在のファイル:</small><br>
                                <a href="{{ route('facilities.land-info.download', ['facility' => $facility, 'type' => 'registry']) }}" 
                                   class="text-decoration-none" target="_blank">
                                    <i class="fas fa-file-alt text-info me-1"></i>{{ $landInfo->registry_pdf_name }}
                                    <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                </a>
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="delete_registry_pdf" id="delete_registry_pdf">
                                    <label class="form-check-label text-danger" for="delete_registry_pdf">
                                        <small>このファイルを削除する</small>
                                    </label>
                                </div>
                            </div>
                        @endif
                        <input type="file" name="registry_pdf" id="registry_pdf" 
                               class="form-control @error('registry_pdf') is-invalid @enderror" 
                               accept=".pdf"
                               data-max-size="2097152"
                               aria-describedby="registry_help">
                        <small id="registry_help" class="form-text text-muted">PDFファイルのみ（最大2MB）</small>
                        <x-form.field-error field="registry_pdf" />
                    </div>
                </div>
            </x-form.section>
        </div>

        <!-- 備考セクション -->
        <x-form.section title="備考欄" icon="fas fa-sticky-note" icon-color="warning"
                        :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('notes')">
            <div class="row g-3">
                <div class="col-12">
                    <label for="notes" class="form-label">土地情報備考</label>
                    <textarea name="notes" id="notes" 
                              class="form-control @error('notes') is-invalid @enderror" 
                              rows="5" maxlength="2000" 
                              placeholder="上記項目に該当しない土地情報があれば入力してください"
                              aria-describedby="notes_help notes_count">{{ old('notes', $landInfo->notes ?? '') }}</textarea>
                    <div class="d-flex justify-content-between flex-wrap">
                        <small id="notes_help" class="form-text text-muted">テキスト（複数行）2,000文字まで</small>
                        <small class="form-text text-muted">
                            <span id="notes_count" aria-live="polite">{{ strlen(old('notes', $landInfo->notes ?? '')) }}</span>/2000文字
                        </small>
                    </div>
                    <x-form.field-error field="notes" />
                </div>
            </div>
        </x-form.section>

        <!-- カスタムアクションボタン -->
        <x-slot name="actions">
            <x-form.actions 
                :cancel-route="route('facilities.show', $facility)" 
                cancel-text="キャンセル"
                submit-text="保存"
                submit-icon="fas fa-save"
            >
                <x-slot name="additional">
                    <button type="button" class="btn btn-outline-primary me-2" id="previewBtn">
                        <i class="fas fa-eye me-2"></i>プレビュー
                    </button>
                </x-slot>
            </x-form.actions>
        </x-slot>

    </x-facility.edit-layout>

    @push('scripts')
        @vite('resources/js/land-info-final.js')
    @endpush
@endif