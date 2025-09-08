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
                土地情報の編集には以下のいずれかの部門権限が必要です：
            </p>
            <div class="alert alert-info d-inline-block text-start">
                <ul class="mb-0">
                    <li><strong>土地総務</strong>: 基本情報、面積情報、備考欄の編集</li>
                    <li><strong>土地総務・経理</strong>: 金額・契約情報、管理会社・オーナー情報の編集</li>
                    <li><strong>土地総務・工程表</strong>: PDFファイルの編集</li>
                </ul>
            </div>
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

    <!-- 編集権限情報カード -->
    @if($canEditAny)
    <div class="card mb-4 bg-light">
        <div class="card-body">
            <h6 class="card-title mb-2">
                <i class="fas fa-user-shield text-primary me-2"></i>編集権限
            </h6>
            <div class="row">
                <div class="col-12">
                    <span class="badge bg-success me-1">
                        <i class="fas fa-check me-1"></i>すべての土地情報を編集可能
                    </span>
                    <small class="text-muted d-block">
                        ロール: {{ auth()->user()->role === 'admin' ? '管理者' : '編集者' }}
                    </small>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 土地情報フォーム -->
    <form id="landInfoForm" action="{{ route('facilities.land-info.update', $facility) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <!-- バリデーションエラー表示エリア -->
        <div id="validation-errors" class="validation-errors"></div>
        
        <div class="land-info-edit-form">
            @if(!$canEditAny)
                <!-- 権限がない場合の説明 -->
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>注意:</strong> 現在の権限では編集できるセクションがありません。管理者にお問い合わせください。
                </div>
            @endif
            <!-- 基本情報セクション -->
            @if($canEditAny)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-map text-primary me-2"></i>基本情報
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
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
                        
                        <div class="col-md-4 mb-3">
                            <label for="parking_spaces" class="form-label">敷地内駐車場台数</label>
                            <div class="input-group">
                                <input type="number" name="parking_spaces" id="parking_spaces" 
                                       class="form-control @error('parking_spaces') is-invalid @enderror" 
                                       value="{{ old('parking_spaces', $landInfo->parking_spaces ?? '') }}"
                                       min="0" max="9999999999" placeholder="例: 50">
                                <span class="input-group-text">台</span>
                            </div>
                            <small class="form-text text-muted">半角数字で入力してください（最大10桁）</small>
                            @error('parking_spaces')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- 面積情報セクション -->
            @if($canEditAny)
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
                                       step="0.01" min="0" max="99999999.99" placeholder="例: 290.00">
                                <span class="input-group-text">㎡</span>
                            </div>
                            <small class="form-text text-muted">例: 290 → 表示: 290.00㎡</small>
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
                                       step="0.01" min="0" max="99999999.99" placeholder="例: 89.05">
                                <span class="input-group-text">坪</span>
                            </div>
                            <small class="form-text text-muted">例: 89.05 → 表示: 89.05坪</small>
                            @error('site_area_tsubo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- 自社物件情報セクション -->
            @if($canEditAny)
            <div id="owned_section" class="card mb-4 conditional-section" style="display: none;">
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
                            <small class="form-text text-muted">半角数字で入力してください（最大15桁、3桁区切りで表示）</small>
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
                            <small class="form-text text-muted">購入金額と敷地面積（坪数）から自動計算されます</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- 賃借物件情報セクション -->
            @if($canEditAny)
            <div id="leased_section" class="card mb-4 conditional-section" style="display: none;">
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
                            <small class="form-text text-muted">半角数字で入力してください（最大15桁）</small>
                            @error('monthly_rent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="contract_start_date" class="form-label">契約開始日</label>
                            <input type="date" name="contract_start_date" id="contract_start_date" 
                                   class="form-control @error('contract_start_date') is-invalid @enderror" 
                                   value="{{ old('contract_start_date', $landInfo->contract_start_date ? $landInfo->contract_start_date->format('Y-m-d') : '') }}">
                            <small class="form-text text-muted">YYYY/MM/DD形式で入力</small>
                            @error('contract_start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="contract_end_date" class="form-label">契約終了日</label>
                            <input type="date" name="contract_end_date" id="contract_end_date" 
                                   class="form-control @error('contract_end_date') is-invalid @enderror" 
                                   value="{{ old('contract_end_date', $landInfo->contract_end_date ? $landInfo->contract_end_date->format('Y-m-d') : '') }}">
                            <small class="form-text text-muted">YYYY/MM/DD形式で入力</small>
                            @error('contract_end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
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
                        
                        <div class="col-md-4 mb-3">
                            <label for="contract_period_display" class="form-label">契約年数（自動計算）</label>
                            <input type="text" id="contract_period_display" class="form-control" readonly placeholder="自動計算されます">
                            <small class="form-text text-muted">契約開始日と終了日から自動計算されます</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- 管理会社情報セクション -->
            @if($canEditAny)
            <div id="management_section" class="card mb-4" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important;">
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
                            <small class="form-text text-muted">全角・半角30文字まで</small>
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
                            <small class="form-text text-muted">ハイフンあり形式（例: 123-4567）</small>
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
                            <small class="form-text text-muted">全角・半角・記号（-）30文字まで</small>
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
                            <small class="form-text text-muted">全角・半角・記号（-）20文字まで</small>
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
                            <small class="form-text text-muted">ハイフンあり形式（例: 03-1234-5678）</small>
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
                            <small class="form-text text-muted">ハイフンあり形式（例: 03-1234-5679）</small>
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
                            <small class="form-text text-muted">メール形式（半角）100文字まで</small>
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
                            <small class="form-text text-muted">URL形式（半角）100文字まで</small>
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
                            <div class="d-flex justify-content-between">
                                <small class="form-text text-muted">テキスト（複数行）1,000文字まで</small>
                                <small class="form-text text-muted">
                                    <span id="management_company_notes_count">{{ strlen(old('management_company_notes', $landInfo->management_company_notes ?? '')) }}</span>/1000文字
                                </small>
                            </div>
                            @error('management_company_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- オーナー情報セクション -->
            @if($canEditAny)
            <div id="owner_section" class="card mb-4" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important;">
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
                            <small class="form-text text-muted">全角・半角30文字まで</small>
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
                            <small class="form-text text-muted">ハイフンあり形式（例: 123-4567）</small>
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
                            <small class="form-text text-muted">全角・半角30文字まで</small>
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
                            <small class="form-text text-muted">全角・半角20文字まで</small>
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
                            <small class="form-text text-muted">ハイフンあり形式（例: 03-1234-5678）</small>
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
                            <small class="form-text text-muted">ハイフンあり形式（例: 03-1234-5679）</small>
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
                            <small class="form-text text-muted">メール形式（半角）100文字まで</small>
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
                            <small class="form-text text-muted">URL形式（半角）100文字まで</small>
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
                            <div class="d-flex justify-content-between">
                                <small class="form-text text-muted">テキスト（複数行）1,000文字まで</small>
                                <small class="form-text text-muted">
                                    <span id="owner_notes_count">{{ strlen(old('owner_notes', $landInfo->owner_notes ?? '')) }}</span>/1000文字
                                </small>
                            </div>
                            @error('owner_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- ファイルアップロードセクション -->
            @if($canEditAny)
            <div id="file_section_1" class="card mb-4" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-pdf text-danger me-2"></i>関連書類
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="lease_contracts" class="form-label">賃貸借契約書・覚書</label>
                            <input type="file" name="lease_contracts[]" id="lease_contracts" 
                                   class="form-control @error('lease_contracts.*') is-invalid @enderror" 
                                   multiple accept=".pdf">
                            <small class="form-text text-muted">PDFファイルのみ、複数選択可能（最大10MB/ファイル）</small>
                            @error('lease_contracts.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            
                            <!-- 既存ファイル表示 -->
                            @if(isset($landInfo) && $landInfo->documents->where('land_document_type', 'lease_contract')->count() > 0)
                                <div class="mt-2">
                                    <small class="text-muted">現在のファイル:</small>
                                    <ul class="list-unstyled mt-1">
                                        @foreach($landInfo->documents->where('land_document_type', 'lease_contract') as $file)
                                            <li class="d-flex align-items-center justify-content-between border rounded p-2 mb-1">
                                                <span>
                                                    <i class="fas fa-file-pdf text-danger me-2"></i>
                                                    {{ $file->original_name }}
                                                </span>
                                                <div>
                                                    <a href="{{ route('files.download', $file) }}" class="btn btn-sm btn-outline-primary me-1">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteFile({{ $file->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="property_register" class="form-label">謄本</label>
                            <input type="file" name="property_register" id="property_register" 
                                   class="form-control @error('property_register') is-invalid @enderror" 
                                   accept=".pdf">
                            <small class="form-text text-muted">PDFファイルのみ（最大10MB）</small>
                            @error('property_register')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            
                            <!-- 既存ファイル表示 -->
                            @if(isset($landInfo) && $landInfo->documents->where('land_document_type', 'property_register')->first())
                                @php $file = $landInfo->documents->where('land_document_type', 'property_register')->first(); @endphp
                                <div class="mt-2">
                                    <small class="text-muted">現在のファイル:</small>
                                    <div class="d-flex align-items-center justify-content-between border rounded p-2 mt-1">
                                        <span>
                                            <i class="fas fa-file-pdf text-danger me-2"></i>
                                            {{ $file->original_name }}
                                        </span>
                                        <div>
                                            <a href="{{ route('files.download', $file) }}" class="btn btn-sm btn-outline-primary me-1">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteFile({{ $file->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- PDFファイルセクション -->
            @if($canEditAny)
            <div id="file_section_2" class="card mb-4" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-pdf text-danger me-2"></i>関連書類
                        <small class="text-muted ms-2">(土地総務・工程表により検討変更時)</small>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="lease_contract_pdf" class="form-label">賃貸借契約書・覚書PDF</label>
                            @if(isset($landInfo) && $landInfo->lease_contract_pdf_path)
                                <div class="mb-2">
                                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-file-pdf me-2"></i>
                                            {{ $landInfo->lease_contract_pdf_name ?? 'ファイル' }}
                                        </span>
                                        <div>
                                            <a href="{{ \Storage::url($landInfo->lease_contract_pdf_path) }}" 
                                               target="_blank" class="btn btn-outline-primary btn-sm me-2">
                                                <i class="fas fa-eye"></i> 表示
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="deleteExistingFile('lease_contract')">
                                                <i class="fas fa-trash"></i> 削除
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <input type="file" name="lease_contract_pdf" id="lease_contract_pdf" 
                                   class="form-control @error('lease_contract_pdf') is-invalid @enderror" 
                                   accept=".pdf">
                            <small class="form-text text-muted">PDFファイルのみ（最大10MB）</small>
                            @error('lease_contract_pdf')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="registry_pdf" class="form-label">謄本PDF</label>
                            @if(isset($landInfo) && $landInfo->registry_pdf_path)
                                <div class="mb-2">
                                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-file-pdf me-2"></i>
                                            {{ $landInfo->registry_pdf_name ?? 'ファイル' }}
                                        </span>
                                        <div>
                                            <a href="{{ \Storage::url($landInfo->registry_pdf_path) }}" 
                                               target="_blank" class="btn btn-outline-primary btn-sm me-2">
                                                <i class="fas fa-eye"></i> 表示
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="deleteExistingFile('registry')">
                                                <i class="fas fa-trash"></i> 削除
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
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
            @endif

            <!-- 備考セクション -->
            @if($canEditAny)
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
            @endif

            <!-- 保存ボタン -->
            @if($canEditAny)
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
            @endif
        </div>
    </form>
    @endif
</div>



@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Land Info Manager Class
    class LandInfoManager {
        constructor() {
            this.initializeEventListeners();
            this.updateConditionalSections();
            this.initializeCharacterCount();
            this.forceShowAllSections();
        }
        
        forceShowAllSections() {
            // 管理会社、オーナー、ファイルセクションを強制表示
            const managementSection = document.getElementById('management_section');
            const ownerSection = document.getElementById('owner_section');
            const fileSection1 = document.getElementById('file_section_1');
            const fileSection2 = document.getElementById('file_section_2');
            
            if (managementSection) {
                managementSection.style.display = 'block';
                managementSection.style.visibility = 'visible';
                console.log('Forced management section to show');
            }
            
            if (ownerSection) {
                ownerSection.style.display = 'block';
                ownerSection.style.visibility = 'visible';
                console.log('Forced owner section to show');
            }
            
            if (fileSection1) {
                fileSection1.style.display = 'block';
                fileSection1.style.visibility = 'visible';
                console.log('Forced file section 1 to show');
            }
            
            if (fileSection2) {
                fileSection2.style.display = 'block';
                fileSection2.style.visibility = 'visible';
                console.log('Forced file section 2 to show');
            }
        }
        
        initializeEventListeners() {
            // 所有形態変更時の表示制御
            const ownershipType = document.getElementById('ownership_type');
            if (ownershipType) {
                ownershipType.addEventListener('change', () => {
                    this.updateConditionalSections();
                });
            }
            
            // 自動計算機能
            const purchasePrice = document.getElementById('purchase_price');
            const siteAreaTsubo = document.getElementById('site_area_tsubo');
            
            if (purchasePrice) {
                purchasePrice.addEventListener('input', () => {
                    this.calculateUnitPrice();
                });
            }
            
            if (siteAreaTsubo) {
                siteAreaTsubo.addEventListener('input', () => {
                    this.calculateUnitPrice();
                });
            }
            
            // 契約期間計算
            const contractStartDate = document.getElementById('contract_start_date');
            const contractEndDate = document.getElementById('contract_end_date');
            
            if (contractStartDate) {
                contractStartDate.addEventListener('change', () => {
                    this.calculateContractPeriod();
                });
            }
            
            if (contractEndDate) {
                contractEndDate.addEventListener('change', () => {
                    this.calculateContractPeriod();
                });
            }
            
            // 通貨フォーマット
            document.querySelectorAll('.currency-input').forEach(input => {
                input.addEventListener('blur', (e) => {
                    this.formatCurrency(e.target);
                });
                
                input.addEventListener('focus', (e) => {
                    this.removeCurrencyFormat(e.target);
                });
            });
            
            // 全角数字を半角に変換
            document.querySelectorAll('input[type="number"], .currency-input').forEach(input => {
                input.addEventListener('input', (e) => {
                    this.convertToHalfWidth(e.target);
                });
            });
        }
        
        updateConditionalSections() {
            const ownershipTypeElement = document.getElementById('ownership_type');
            if (!ownershipTypeElement) return;
            
            const ownershipType = ownershipTypeElement.value;
            
            // セクションの表示/非表示制御
            const ownedSection = document.getElementById('owned_section');
            const leasedSection = document.getElementById('leased_section');
            const managementSection = document.getElementById('management_section');
            const ownerSection = document.getElementById('owner_section');
            const fileSection = document.getElementById('file_section');
            
            if (ownedSection) {
                ownedSection.style.display = ownershipType === 'owned' ? 'block' : 'none';
            }
            
            if (leasedSection) {
                leasedSection.style.display = 
                    ['leased', 'owned_rental'].includes(ownershipType) ? 'block' : 'none';
            }
            
            // 管理会社情報とオーナー情報は常に表示
            if (managementSection) {
                managementSection.style.display = 'block';
                console.log('Management section displayed');
            }
            
            if (ownerSection) {
                ownerSection.style.display = 'block';
                console.log('Owner section displayed');
            }
            
            // ファイルセクションも常に表示
            const fileSection1 = document.getElementById('file_section_1');
            const fileSection2 = document.getElementById('file_section_2');
            
            if (fileSection1) {
                fileSection1.style.display = 'block';
                console.log('File section 1 displayed');
            }
            
            if (fileSection2) {
                fileSection2.style.display = 'block';
                console.log('File section 2 displayed');
            }
            
            if (fileSection) {
                fileSection.style.display = 'block';
                console.log('Legacy file section displayed');
            }
        }
        
        calculateUnitPrice() {
            const purchasePriceInput = document.getElementById('purchase_price');
            const siteAreaTsuboInput = document.getElementById('site_area_tsubo');
            const unitPriceDisplay = document.getElementById('unit_price_display');
            
            if (!purchasePriceInput || !siteAreaTsuboInput || !unitPriceDisplay) return;
            
            const purchasePrice = parseFloat(purchasePriceInput.value.replace(/,/g, '')) || 0;
            const siteAreaTsubo = parseFloat(siteAreaTsuboInput.value) || 0;
            
            if (purchasePrice > 0 && siteAreaTsubo > 0) {
                const unitPrice = Math.round(purchasePrice / siteAreaTsubo);
                unitPriceDisplay.value = unitPrice.toLocaleString();
            } else {
                unitPriceDisplay.value = '';
            }
        }
        
        calculateContractPeriod() {
            const startDateInput = document.getElementById('contract_start_date');
            const endDateInput = document.getElementById('contract_end_date');
            const periodDisplay = document.getElementById('contract_period_display');
            
            if (!startDateInput || !endDateInput || !periodDisplay) return;
            
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            
            if (startDate && endDate && endDate > startDate) {
                const years = endDate.getFullYear() - startDate.getFullYear();
                const months = endDate.getMonth() - startDate.getMonth();
                
                let totalMonths = years * 12 + months;
                
                // 日付の調整
                if (endDate.getDate() < startDate.getDate()) {
                    totalMonths--;
                }
                
                const displayYears = Math.floor(totalMonths / 12);
                const displayMonths = totalMonths % 12;
                
                let periodText = '';
                if (displayYears > 0) periodText += `${displayYears}年`;
                if (displayMonths > 0) periodText += `${displayMonths}ヶ月`;
                
                periodDisplay.value = periodText || '0ヶ月';
            } else {
                periodDisplay.value = '';
            }
        }
        
        formatCurrency(input) {
            const value = parseInt(input.value.replace(/,/g, '')) || 0;
            if (value > 0) {
                input.value = value.toLocaleString();
            }
        }
        
        removeCurrencyFormat(input) {
            const value = input.value.replace(/,/g, '');
            input.value = value;
        }
        
        convertToHalfWidth(input) {
            // 全角数字を半角に変換
            input.value = input.value.replace(/[０-９]/g, function(s) {
                return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
            });
        }
        
        initializeCharacterCount() {
            const notesTextarea = document.getElementById('notes');
            const notesCount = document.getElementById('notes_count');
            
            if (notesTextarea && notesCount) {
                notesTextarea.addEventListener('input', function() {
                    notesCount.textContent = this.value.length;
                });
            }

            const managementNotesTextarea = document.getElementById('management_company_notes');
            const managementNotesCount = document.getElementById('management_company_notes_count');
            
            if (managementNotesTextarea && managementNotesCount) {
                managementNotesTextarea.addEventListener('input', function() {
                    managementNotesCount.textContent = this.value.length;
                });
            }
        }
    }
    
    // 初期化
    new LandInfoManager();
    
    // プレビュー機能
    document.getElementById('previewBtn')?.addEventListener('click', function() {
        // プレビュー機能の実装（必要に応じて）
        alert('プレビュー機能は今後実装予定です。');
    });
});

// 既存ファイル削除機能
function deleteExistingFile(fileType) {
    if (confirm('このファイルを削除しますか？')) {
        // 隠しフィールドを追加してファイル削除をマーク
        const form = document.getElementById('landInfoForm');
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = `delete_${fileType}_pdf`;
        deleteInput.value = '1';
        form.appendChild(deleteInput);
        
        // 表示を更新
        const alertElement = document.querySelector(`[onclick="deleteExistingFile('${fileType}')"]`).closest('.alert');
        if (alertElement) {
            alertElement.style.display = 'none';
        }
    }
}

// ファイル削除機能（一般的なファイル用）
function deleteFile(fileId) {
    if (confirm('このファイルを削除しますか？')) {
        // AJAX でファイル削除処理
        fetch(`/files/${fileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('ファイルの削除に失敗しました。');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました。');
        });
    }
}
</script>
@endpush


@endsection