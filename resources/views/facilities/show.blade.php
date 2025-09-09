@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- 固定ヘッダーカード -->
            <div class="facility-header-card card mb-3 sticky-top">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="facility-icon me-3">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 facility-name">{{ $facility->facility_name }}</h5>
                                    <div class="facility-meta">
                                        <span class="badge bg-primary me-2">{{ $facility->office_code }}</span>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            更新日時: {{ $facility->updated_at->format('Y年m月d日 H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="facility-actions">
                                <a href="{{ route('facilities.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> 一覧に戻る
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- アラート表示エリア -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- タブナビゲーション -->
            <div class="facility-detail-container">
                <div class="tab-navigation mb-4">
                    <ul class="nav nav-tabs" id="facilityTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab" aria-controls="basic-info" aria-selected="true">
                                <i class="fas fa-info-circle me-2"></i>基本
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="land-tab" data-bs-toggle="tab" data-bs-target="#land-info" type="button" role="tab" aria-controls="land-info" aria-selected="false">
                                <i class="fas fa-map me-2"></i>土地
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="tab-content" id="facilityTabContent">
                    <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-tab">
                        <!-- 基本情報タブヘッダー -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                            </h4>
                            @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                                <a href="{{ route('facilities.edit-basic-info', $facility) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>編集
                                </a>
                            @endif
                        </div>
                        
                        <!-- View Toggle Component -->
                        @include('facilities.partials.view-toggle', ['viewMode' => $viewMode])
                        
                        <!-- Conditional rendering based on view mode -->
                        @if($viewMode === 'table')
                            @include('facilities.partials.basic-info-table', ['facility' => $facility])
                        @else
                            @include('facilities.partials.basic-info', ['facility' => $facility])
                        @endif
                    </div>
                    <div class="tab-pane fade" id="land-info" role="tabpanel" aria-labelledby="land-tab" data-lazy-load="true">
                        <!-- 土地情報タブヘッダー -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-map text-primary me-2"></i>土地情報
                            </h4>
                            @if(auth()->user()->canEditLandInfo())
                                <a href="{{ route('facilities.land-info.edit', $facility) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>
                                    @if(isset($landInfo) && $landInfo)
                                        編集
                                    @else
                                        登録
                                    @endif
                                </a>
                            @endif
                        </div>
                        
                        <div class="land-info-loading d-none">
                            <div class="d-flex justify-content-center p-4">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">読み込み中...</span>
                                </div>
                            </div>
                        </div>
                        <div class="land-info-content">
                            @if(isset($landInfo) && $landInfo)
                                <!-- 土地情報詳細表示 -->
                                <div class="row">
                                    <!-- 基本情報カード -->
                                    <div class="col-lg-6 mb-4">
                                        <div class="card facility-info-card h-100">
                                            <div class="card-header">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-map me-2"></i>基本情報
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="facility-detail-table">
                                                    <div class="detail-row">
                                                        <span class="detail-label">所有形態</span>
                                                        <span class="detail-value">
                                                            @switch($landInfo->ownership_type)
                                                                @case('owned')
                                                                    <span class="badge bg-success">自社</span>
                                                                    @break
                                                                @case('leased')
                                                                    <span class="badge bg-warning">賃借</span>
                                                                    @break
                                                                @case('owned_rental')
                                                                    <span class="badge bg-info">自社（賃貸）</span>
                                                                    @break
                                                                @default
                                                                    <span class="text-muted">未設定</span>
                                                            @endswitch
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">敷地内駐車場台数</span>
                                                        <span class="detail-value">
                                                            {{ $landInfo->parking_spaces !== null ? number_format($landInfo->parking_spaces) . '台' : '未設定' }}
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">敷地面積（㎡）</span>
                                                        <span class="detail-value">
                                                            {{ $landInfo->site_area_sqm !== null ? number_format($landInfo->site_area_sqm, 2) . '㎡' : '未設定' }}
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">敷地面積（坪数）</span>
                                                        <span class="detail-value">
                                                            {{ $landInfo->site_area_tsubo !== null ? number_format($landInfo->site_area_tsubo, 2) . '坪' : '未設定' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 金額・契約情報カード -->
                                    <div class="col-lg-6 mb-4">
                                        <div class="card facility-info-card h-100">
                                            <div class="card-header">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-yen-sign me-2"></i>金額・契約情報
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="facility-detail-table">
                                                    <div class="detail-row">
                                                        <span class="detail-label">購入金額</span>
                                                        <span class="detail-value">
                                                            {{ $landInfo->purchase_price !== null ? number_format($landInfo->purchase_price) . '円' : '未設定' }}
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">坪単価（自動計算）</span>
                                                        <span class="detail-value">
                                                            @php
                                                                $unitPrice = null;
                                                                if ($landInfo->unit_price_per_tsubo !== null) {
                                                                    $unitPrice = $landInfo->unit_price_per_tsubo;
                                                                } elseif ($landInfo->purchase_price && $landInfo->site_area_tsubo && $landInfo->site_area_tsubo > 0) {
                                                                    $unitPrice = round($landInfo->purchase_price / $landInfo->site_area_tsubo);
                                                                }
                                                            @endphp
                                                            {{ $unitPrice !== null ? number_format($unitPrice) . '円/坪' : '未設定' }}
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">家賃</span>
                                                        <span class="detail-value">
                                                            {{ $landInfo->monthly_rent !== null ? number_format($landInfo->monthly_rent) . '円' : '未設定' }}
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">契約期間（契約開始日）</span>
                                                        <span class="detail-value">
                                                            {{ $landInfo->contract_start_date ? $landInfo->contract_start_date->format('Y年m月d日') : '未設定' }}
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">契約期間（契約終了日）</span>
                                                        <span class="detail-value">
                                                            {{ $landInfo->contract_end_date ? $landInfo->contract_end_date->format('Y年m月d日') : '未設定' }}
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">自動更新の有無</span>
                                                        <span class="detail-value">
                                                            @if($landInfo->auto_renewal === 'yes')
                                                                <span class="badge bg-success">あり</span>
                                                            @elseif($landInfo->auto_renewal === 'no')
                                                                <span class="badge bg-secondary">なし</span>
                                                            @else
                                                                <span class="text-muted">未設定</span>
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">契約年数（自動計算）</span>
                                                        <span class="detail-value">
                                                            @php
                                                                $contractYears = null;
                                                                if ($landInfo->contract_start_date && $landInfo->contract_end_date) {
                                                                    $contractYears = $landInfo->contract_start_date->diffInYears($landInfo->contract_end_date);
                                                                }
                                                            @endphp
                                                            {{ $contractYears !== null ? $contractYears . '年' : '未設定' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 管理会社情報カード -->
                                    <div class="col-lg-6 mb-4">
                                        <div class="card facility-info-card h-100">
                                            <div class="card-header">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-building me-2"></i>管理会社情報
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="facility-detail-table">
                                                    <div class="detail-row">
                                                        <span class="detail-label">管理会社（会社名）</span>
                                                        <span class="detail-value">{{ $landInfo->management_company_name ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">管理会社（郵便番号）</span>
                                                        <span class="detail-value">{{ $landInfo->management_company_postal_code ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">管理会社（住所）</span>
                                                        <span class="detail-value">{{ $landInfo->management_company_address ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">管理会社（住所建物名）</span>
                                                        <span class="detail-value">{{ $landInfo->management_company_building ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">管理会社（電話番号）</span>
                                                        <span class="detail-value">{{ $landInfo->management_company_phone ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">管理会社（FAX番号）</span>
                                                        <span class="detail-value">{{ $landInfo->management_company_fax ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">管理会社（メールアドレス）</span>
                                                        <span class="detail-value">
                                                            @if($landInfo->management_company_email)
                                                                <a href="mailto:{{ $landInfo->management_company_email }}">{{ $landInfo->management_company_email }}</a>
                                                            @else
                                                                未設定
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">管理会社（URL）</span>
                                                        <span class="detail-value">
                                                            @if($landInfo->management_company_url)
                                                                <a href="{{ $landInfo->management_company_url }}" target="_blank">{{ $landInfo->management_company_url }}</a>
                                                            @else
                                                                未設定
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">管理会社（備考）</span>
                                                        <span class="detail-value">{{ $landInfo->management_company_notes ?? '未設定' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- オーナー情報カード -->
                                    <div class="col-lg-6 mb-4">
                                        <div class="card facility-info-card h-100">
                                            <div class="card-header">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-user-tie me-2"></i>オーナー情報
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="facility-detail-table">
                                                    <div class="detail-row">
                                                        <span class="detail-label">オーナー（氏名・会社名）</span>
                                                        <span class="detail-value">{{ $landInfo->owner_name ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">オーナー（郵便番号）</span>
                                                        <span class="detail-value">{{ $landInfo->owner_postal_code ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">オーナー（住所）</span>
                                                        <span class="detail-value">{{ $landInfo->owner_address ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">オーナー（住所建物名）</span>
                                                        <span class="detail-value">{{ $landInfo->owner_building ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">オーナー（電話番号）</span>
                                                        <span class="detail-value">{{ $landInfo->owner_phone ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">オーナー（FAX番号）</span>
                                                        <span class="detail-value">{{ $landInfo->owner_fax ?? '未設定' }}</span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">オーナー（メールアドレス）</span>
                                                        <span class="detail-value">
                                                            @if($landInfo->owner_email)
                                                                <a href="mailto:{{ $landInfo->owner_email }}">{{ $landInfo->owner_email }}</a>
                                                            @else
                                                                未設定
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">オーナー（URL）</span>
                                                        <span class="detail-value">
                                                            @if($landInfo->owner_url)
                                                                <a href="{{ $landInfo->owner_url }}" target="_blank">{{ $landInfo->owner_url }}</a>
                                                            @else
                                                                未設定
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">オーナー（備考欄）</span>
                                                        <span class="detail-value">{{ $landInfo->owner_notes ?? '未設定' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 関連書類・備考カード -->
                                    <div class="col-lg-12 mb-4">
                                        <div class="card facility-info-card">
                                            <div class="card-header">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-file-pdf me-2"></i>関連書類・備考
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="facility-detail-table">
                                                    <div class="detail-row">
                                                        <span class="detail-label">賃貸借契約書・覚書</span>
                                                        <span class="detail-value">
                                                            @if($landInfo->lease_contract_pdf_name)
                                                                <i class="fas fa-file-contract text-warning me-2"></i>{{ $landInfo->lease_contract_pdf_name }}
                                                            @else
                                                                未設定
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">謄本</span>
                                                        <span class="detail-value">
                                                            @if($landInfo->registry_pdf_name)
                                                                <i class="fas fa-file-alt text-info me-2"></i>{{ $landInfo->registry_pdf_name }}
                                                            @else
                                                                未設定
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">備考欄</span>
                                                        <span class="detail-value">
                                                            @if($landInfo->notes)
                                                                <div class="border rounded p-2 bg-light">{{ $landInfo->notes }}</div>
                                                            @else
                                                                未設定
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-4">
                                        <i class="fas fa-map-marked-alt fa-4x text-muted"></i>
                                    </div>
                                    <h4 class="text-muted mb-3">土地情報が登録されていません</h4>
                                    <p class="text-muted mb-4">
                                        この施設の土地情報はまだ登録されていません。<br>
                                        土地情報を登録するには、編集ボタンから登録してください。
                                    </p>
                                    @if(auth()->user()->canEditLandInfo())
                                        <a href="{{ route('facilities.land-info.edit', $facility) }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>土地情報を登録
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@vite(['resources/css/pages/facilities.css', 'resources/js/modules/facilities.js'])

<script>
// Pass facility ID to the JavaScript module
window.facilityId = {{ $facility->id }};
</script>