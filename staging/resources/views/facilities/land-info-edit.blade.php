@extends('layouts.app')

@section('title', '土地情報編集 - ' . $facility->facility_name)

@push('styles')
    @vite('resources/css/land-info.css')
@endpush

@section('content')
<div class="container-fluid">
    @php
        $canEditAny = auth()->user()->canEditLandInfo();
    @endphp

    @if(!$canEditAny)
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
    @else
        <!-- ヘッダー -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">土地情報編集</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}">施設一覧</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('facilities.show', $facility) }}">{{ $facility->facility_name }}</a></li>
                        <li class="breadcrumb-item active">土地情報編集</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>戻る
                </a>
            </div>
        </div>

        <!-- 施設情報カード -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="card-title mb-1">{{ $facility->facility_name }}</h5>
                        <p class="text-muted mb-0">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            {{ $facility->prefecture }}{{ $facility->city }}{{ $facility->address }}
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-primary">{{ $facility->facility_type }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 土地情報フォーム -->
        <form id="landInfoForm" action="{{ route('facilities.land-info.update', $facility) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <!-- 基本情報セクション -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-map text-primary me-2"></i>基本情報
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ownership_type" class="form-label required">所有形態</label>
                            <select name="ownership_type" id="ownership_type" class="form-select @error('ownership_type') is-invalid @enderror" required>
                                <option value="">選択してください</option>
                                <option value="owned" {{ old('ownership_type', $landInfo->ownership_type ?? '') === 'owned' ? 'selected' : '' }}>自社</option>
                                <option value="leased" {{ old('ownership_type', $landInfo->ownership_type ?? '') === 'leased' ? 'selected' : '' }}>賃借</option>
                                <option value="owned_rental" {{ old('ownership_type', $landInfo->ownership_type ?? '') === 'owned_rental' ? 'selected' : '' }}>自社（賃貸）</option>
                            </select>
                            @error('ownership_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="parking_spaces" class="form-label">敷地内駐車場台数</label>
                            <div class="input-group">
                                <input type="number" name="parking_spaces" id="parking_spaces" 
                                       class="form-control @error('parking_spaces') is-invalid @enderror" 
                                       value="{{ old('parking_spaces', $landInfo->parking_spaces ?? '') }}"
                                       min="0" placeholder="例: 50">
                                <span class="input-group-text">台</span>
                            </div>
                            @error('parking_spaces')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- 面積情報セクション -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-ruler-combined text-success me-2"></i>面積情報
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="site_area_sqm" class="form-label">敷地面積（㎡）</label>
                            <div class="input-group">
                                <input type="number" name="site_area_sqm" id="site_area_sqm" 
                                       class="form-control @error('site_area_sqm') is-invalid @enderror" 
                                       value="{{ old('site_area_sqm', $landInfo->site_area_sqm ?? '') }}"
                                       step="0.01" min="0" placeholder="例: 290.00">
                                <span class="input-group-text">㎡</span>
                            </div>
                            @error('site_area_sqm')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="site_area_tsubo" class="form-label">敷地面積（坪数）</label>
                            <div class="input-group">
                                <input type="number" name="site_area_tsubo" id="site_area_tsubo" 
                                       class="form-control @error('site_area_tsubo') is-invalid @enderror" 
                                       value="{{ old('site_area_tsubo', $landInfo->site_area_tsubo ?? '') }}"
                                       step="0.01" min="0" placeholder="例: 89.05">
                                <span class="input-group-text">坪</span>
                            </div>
                            @error('site_area_tsubo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- 保存ボタン -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>キャンセル
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>保存
                </button>
            </div>
        </form>
    @endif
</div>
@endsection

@push('scripts')
    @vite('resources/js/land-info.js')
@endpush 
           <!-- 自社物件情報セクション -->
            <div id="owned_section" class="card mb-4" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-building text-info me-2"></i>自社物件情報
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="purchase_price" class="form-label">購入金額</label>
                            <div class="input-group">
                                <input type="text" name="purchase_price" id="purchase_price" 
                                       class="form-control currency-input @error('purchase_price') is-invalid @enderror" 
                                       value="{{ old('purchase_price', $landInfo->purchase_price ? number_format($landInfo->purchase_price) : '') }}"
                                       placeholder="例: 10,000,000">
                                <span class="input-group-text">円</span>
                            </div>
                            @error('purchase_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="unit_price_display" class="form-label">坪単価（自動計算）</label>
                            <div class="input-group">
                                <input type="text" id="unit_price_display" class="form-control" readonly placeholder="自動計算されます">
                                <span class="input-group-text">円/坪</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 賃借物件情報セクション -->
            <div id="leased_section" class="card mb-4" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-contract text-warning me-2"></i>賃借物件情報
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="monthly_rent" class="form-label">家賃</label>
                            <div class="input-group">
                                <input type="text" name="monthly_rent" id="monthly_rent" 
                                       class="form-control currency-input @error('monthly_rent') is-invalid @enderror" 
                                       value="{{ old('monthly_rent', $landInfo->monthly_rent ? number_format($landInfo->monthly_rent) : '') }}"
                                       placeholder="例: 500,000">
                                <span class="input-group-text">円</span>
                            </div>
                            @error('monthly_rent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="contract_start_date" class="form-label">契約開始日</label>
                            <input type="date" name="contract_start_date" id="contract_start_date" 
                                   class="form-control @error('contract_start_date') is-invalid @enderror" 
                                   value="{{ old('contract_start_date', $landInfo->contract_start_date ? $landInfo->contract_start_date->format('Y-m-d') : '') }}">
                            @error('contract_start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="contract_end_date" class="form-label">契約終了日</label>
                            <input type="date" name="contract_end_date" id="contract_end_date" 
                                   class="form-control @error('contract_end_date') is-invalid @enderror" 
                                   value="{{ old('contract_end_date', $landInfo->contract_end_date ? $landInfo->contract_end_date->format('Y-m-d') : '') }}">
                            @error('contract_end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="auto_renewal" class="form-label">自動更新の有無</label>
                            <select name="auto_renewal" id="auto_renewal" class="form-select @error('auto_renewal') is-invalid @enderror">
                                <option value="">選択してください</option>
                                <option value="yes" {{ old('auto_renewal', $landInfo->auto_renewal ?? '') === 'yes' ? 'selected' : '' }}>あり</option>
                                <option value="no" {{ old('auto_renewal', $landInfo->auto_renewal ?? '') === 'no' ? 'selected' : '' }}>なし</option>
                            </select>
                            @error('auto_renewal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contract_period_display" class="form-label">契約年数（自動計算）</label>
                            <input type="text" id="contract_period_display" class="form-control" readonly placeholder="自動計算されます">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 管理会社情報セクション -->
            <div id="management_section" class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-building text-secondary me-2"></i>管理会社情報
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="management_company_name" class="form-label">会社名</label>
                            <input type="text" name="management_company_name" id="management_company_name" 
                                   class="form-control @error('management_company_name') is-invalid @enderror" 
                                   value="{{ old('management_company_name', $landInfo->management_company_name ?? '') }}"
                                   maxlength="30" placeholder="例: 株式会社○○管理">
                            @error('management_company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="management_company_postal_code" class="form-label">郵便番号</label>
                            <input type="text" name="management_company_postal_code" id="management_company_postal_code" 
                                   class="form-control @error('management_company_postal_code') is-invalid @enderror" 
                                   value="{{ old('management_company_postal_code', $landInfo->management_company_postal_code ?? '') }}"
                                   pattern="\d{3}-\d{4}" placeholder="例: 123-4567">
                            @error('management_company_postal_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="management_company_address" class="form-label">住所</label>
                            <input type="text" name="management_company_address" id="management_company_address" 
                                   class="form-control @error('management_company_address') is-invalid @enderror" 
                                   value="{{ old('management_company_address', $landInfo->management_company_address ?? '') }}"
                                   maxlength="30" placeholder="例: 東京都渋谷区○○1-2-3">
                            @error('management_company_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="management_company_building" class="form-label">住所建物名</label>
                            <input type="text" name="management_company_building" id="management_company_building" 
                                   class="form-control @error('management_company_building') is-invalid @enderror" 
                                   value="{{ old('management_company_building', $landInfo->management_company_building ?? '') }}"
                                   maxlength="20" placeholder="例: ○○ビル5F">
                            @error('management_company_building')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="management_company_phone" class="form-label">電話番号</label>
                            <input type="text" name="management_company_phone" id="management_company_phone" 
                                   class="form-control @error('management_company_phone') is-invalid @enderror" 
                                   value="{{ old('management_company_phone', $landInfo->management_company_phone ?? '') }}"
                                   pattern="\d{2,4}-\d{2,4}-\d{4}" placeholder="例: 03-1234-5678">
                            @error('management_company_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="management_company_fax" class="form-label">FAX番号</label>
                            <input type="text" name="management_company_fax" id="management_company_fax" 
                                   class="form-control @error('management_company_fax') is-invalid @enderror" 
                                   value="{{ old('management_company_fax', $landInfo->management_company_fax ?? '') }}"
                                   pattern="\d{2,4}-\d{2,4}-\d{4}" placeholder="例: 03-1234-5679">
                            @error('management_company_fax')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="management_company_email" class="form-label">メールアドレス</label>
                            <input type="email" name="management_company_email" id="management_company_email" 
                                   class="form-control @error('management_company_email') is-invalid @enderror" 
                                   value="{{ old('management_company_email', $landInfo->management_company_email ?? '') }}"
                                   maxlength="100" placeholder="例: info@example.com">
                            @error('management_company_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="management_company_url" class="form-label">URL</label>
                            <input type="url" name="management_company_url" id="management_company_url" 
                                   class="form-control @error('management_company_url') is-invalid @enderror" 
                                   value="{{ old('management_company_url', $landInfo->management_company_url ?? '') }}"
                                   maxlength="100" placeholder="例: https://example.com">
                            @error('management_company_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="management_company_notes" class="form-label">備考</label>
                            <textarea name="management_company_notes" id="management_company_notes" 
                                      class="form-control @error('management_company_notes') is-invalid @enderror" 
                                      rows="3" maxlength="1000" 
                                      placeholder="管理会社に関する備考があれば入力してください">{{ old('management_company_notes', $landInfo->management_company_notes ?? '') }}</textarea>
                            @error('management_company_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- オーナー情報セクション -->
            <div id="owner_section" class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-tie text-dark me-2"></i>オーナー情報
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="owner_name" class="form-label">氏名・会社名</label>
                            <input type="text" name="owner_name" id="owner_name" 
                                   class="form-control @error('owner_name') is-invalid @enderror" 
                                   value="{{ old('owner_name', $landInfo->owner_name ?? '') }}"
                                   maxlength="30" placeholder="例: 田中太郎 または 株式会社○○">
                            @error('owner_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="owner_postal_code" class="form-label">郵便番号</label>
                            <input type="text" name="owner_postal_code" id="owner_postal_code" 
                                   class="form-control @error('owner_postal_code') is-invalid @enderror" 
                                   value="{{ old('owner_postal_code', $landInfo->owner_postal_code ?? '') }}"
                                   pattern="\d{3}-\d{4}" placeholder="例: 123-4567">
                            @error('owner_postal_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="owner_address" class="form-label">住所</label>
                            <input type="text" name="owner_address" id="owner_address" 
                                   class="form-control @error('owner_address') is-invalid @enderror" 
                                   value="{{ old('owner_address', $landInfo->owner_address ?? '') }}"
                                   maxlength="30" placeholder="例: 東京都渋谷区○○1-2-3">
                            @error('owner_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="owner_building" class="form-label">住所建物名</label>
                            <input type="text" name="owner_building" id="owner_building" 
                                   class="form-control @error('owner_building') is-invalid @enderror" 
                                   value="{{ old('owner_building', $landInfo->owner_building ?? '') }}"
                                   maxlength="20" placeholder="例: ○○マンション101">
                            @error('owner_building')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="owner_phone" class="form-label">電話番号</label>
                            <input type="text" name="owner_phone" id="owner_phone" 
                                   class="form-control @error('owner_phone') is-invalid @enderror" 
                                   value="{{ old('owner_phone', $landInfo->owner_phone ?? '') }}"
                                   pattern="\d{2,4}-\d{2,4}-\d{4}" placeholder="例: 03-1234-5678">
                            @error('owner_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="owner_fax" class="form-label">FAX番号</label>
                            <input type="text" name="owner_fax" id="owner_fax" 
                                   class="form-control @error('owner_fax') is-invalid @enderror" 
                                   value="{{ old('owner_fax', $landInfo->owner_fax ?? '') }}"
                                   pattern="\d{2,4}-\d{2,4}-\d{4}" placeholder="例: 03-1234-5679">
                            @error('owner_fax')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="owner_email" class="form-label">メールアドレス</label>
                            <input type="email" name="owner_email" id="owner_email" 
                                   class="form-control @error('owner_email') is-invalid @enderror" 
                                   value="{{ old('owner_email', $landInfo->owner_email ?? '') }}"
                                   maxlength="100" placeholder="例: owner@example.com">
                            @error('owner_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="owner_url" class="form-label">URL</label>
                            <input type="url" name="owner_url" id="owner_url" 
                                   class="form-control @error('owner_url') is-invalid @enderror" 
                                   value="{{ old('owner_url', $landInfo->owner_url ?? '') }}"
                                   maxlength="100" placeholder="例: https://owner-site.com">
                            @error('owner_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="owner_notes" class="form-label">備考欄</label>
                            <textarea name="owner_notes" id="owner_notes" 
                                      class="form-control @error('owner_notes') is-invalid @enderror" 
                                      rows="3" maxlength="1000" 
                                      placeholder="オーナーに関する備考があれば入力してください">{{ old('owner_notes', $landInfo->owner_notes ?? '') }}</textarea>
                            @error('owner_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- 関連書類セクション -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-pdf text-danger me-2"></i>関連書類
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="lease_contract_pdf" class="form-label">賃貸借契約書・覚書</label>
                            <input type="file" name="lease_contract_pdf" id="lease_contract_pdf" 
                                   class="form-control @error('lease_contract_pdf') is-invalid @enderror" 
                                   accept=".pdf">
                            <small class="form-text text-muted">PDFファイルのみ（最大10MB）</small>
                            @error('lease_contract_pdf')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="registry_pdf" class="form-label">謄本</label>
                            <input type="file" name="registry_pdf" id="registry_pdf" 
                                   class="form-control @error('registry_pdf') is-invalid @enderror" 
                                   accept=".pdf">
                            <small class="form-text text-muted">PDFファイルのみ（最大10MB）</small>
                            @error('registry_pdf')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- 備考セクション -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sticky-note text-warning me-2"></i>備考欄
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="notes" class="form-label">土地情報備考</label>
                            <textarea name="notes" id="notes" 
                                      class="form-control @error('notes') is-invalid @enderror" 
                                      rows="5" maxlength="2000" 
                                      placeholder="上記項目に該当しない土地情報があれば入力してください">{{ old('notes', $landInfo->notes ?? '') }}</textarea>
                            <div class="d-flex justify-content-between">
                                <small class="form-text text-muted">テキスト（複数行）2,000文字まで</small>
                                <small class="form-text text-muted">
                                    <span id="notes_count">{{ strlen(old('notes', $landInfo->notes ?? '')) }}</span>/2000文字
                                </small>
                            </div>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- 保存ボタン -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>キャンセル
                </a>
                <div>
                    <button type="button" class="btn btn-outline-primary me-2" id="previewBtn">
                        <i class="fas fa-eye me-2"></i>プレビュー
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>保存
                    </button>
                </div>
            </div>