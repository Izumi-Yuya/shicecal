@php
    // Ensure no output before DOCTYPE to prevent Quirks Mode
    if (ob_get_level()) {
        ob_clean();
    }
@endphp
@extends('layouts.app')

@section('title', 'CSV出力')

@push('head')
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
@endpush

@vite(['resources/css/pages/export.css'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-file-csv me-2 text-success"></i>
                    CSV出力
                </h1>

            </div>

            <!-- Progress Bar for Export -->
            <div id="exportProgress" class="mb-4 export-progress" style="display: none;">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-cog fa-spin text-primary me-2"></i>
                            <strong>CSV出力処理中...</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 id="exportProgressBar"
                                 aria-valuenow="0" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                0%
                            </div>
                        </div>
                        <small class="text-muted mt-1" id="exportProgressText" aria-live="polite">処理を開始しています...</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">出力設定</h5>
                </div>
                <div class="card-body">
                    <form id="csvExportForm" autocomplete="off">
                        @csrf
                        
                        <!-- 施設選択セクション -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3">施設選択</h6>
                                
                                <!-- 全選択/全解除ボタン -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="selectAllFacilities">
                                        全選択
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllFacilities">
                                        全解除
                                    </button>
                                    <span class="ms-3 text-muted">
                                        選択中: <span id="selectedFacilitiesCount">0</span> / {{ count($facilities) }} 件
                                    </span>
                                </div>

                                <!-- 施設一覧 -->
                                <div class="facility-list">
                                    @if(count($facilities) > 0)
                                        @foreach($facilities as $facility)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input facility-checkbox" 
                                                       type="checkbox" 
                                                       name="facility_ids[]" 
                                                       value="{{ $facility->id }}" 
                                                       id="facility_{{ $facility->id }}"
                                                       autocomplete="off">
                                                <label class="form-check-label" for="facility_{{ $facility->id }}">
                                                    <strong>{{ $facility->facility_name }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ $facility->company_name }} 
                                                        @if($facility->office_code)
                                                            ({{ $facility->office_code }})
                                                        @endif
                                                        - {{ $facility->address }}
                                                    </small>
                                                </label>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center text-muted py-4">
                                            <p>出力可能な施設がありません。</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 出力項目選択セクション -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3">出力項目選択</h6>
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>注意:</strong> CSVエクスポートはテキスト項目のみを出力します。ファイルやバイナリデータは含まれません。
                                </div>
                                
                                <!-- 全選択/全解除ボタン -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="selectAllFields">
                                        全選択
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllFields">
                                        全解除
                                    </button>
                                    <span class="ms-3 text-muted">
                                        選択中: <span id="selectedFieldsCount">0</span> / {{ app(\App\Services\ExportService::class)->getTotalFieldCount() }} 項目
                                    </span>
                                </div>

                                <!-- 項目一覧 -->
                                <div class="field-selection-container">
                                    <div class="row">
                                    <!-- 基本情報 -->
                                    <div class="col-12 mb-4">
                                        @php
                                            $facilityFields = [
                                                'company_name' => '会社名',
                                                'office_code' => '事業所コード',
                                                'facility_name' => '施設名',
                                                'designation_number' => '指定番号1',
                                                'designation_number_2' => '指定番号2',
                                                'postal_code' => '郵便番号',
                                                'opening_date' => '開設日',
                                                'address' => '住所',
                                                'opening_years' => '開設年数',
                                                'building_name' => '住所（建物名）',
                                                'building_structure' => '建物構造',
                                                'phone_number' => '電話番号',
                                                'building_floors' => '建物階数',
                                                'fax_number' => 'FAX番号',
                                                'paid_rooms_count' => '居室数',
                                                'toll_free_number' => 'フリーダイヤル',
                                                'ss_rooms_count' => '内SS数',
                                                'email' => 'メールアドレス',
                                                'capacity' => '定員数',
                                                'website_url' => 'URL',
                                                'service_types' => 'サービス種類',
                                                'service_validity_periods' => 'サービス有効期限',
                                            ];
                                        @endphp
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="form-check me-3">
                                                <input class="form-check-input category-checkbox" 
                                                       type="checkbox" 
                                                       id="category_facility"
                                                       data-category="facility"
                                                       autocomplete="off">
                                                <label class="form-check-label fw-bold text-primary" for="category_facility">
                                                    <i class="fas fa-building me-1"></i>基本情報
                                                </label>
                                            </div>
                                            <small class="text-muted">
                                                (<span class="category-count" data-category="facility">0</span>/{{ count($facilityFields) }} 項目選択中)
                                            </small>
                                        </div>
                                        <div class="row">
                                            @foreach($facilityFields as $field => $label)
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input field-checkbox" 
                                                               type="checkbox" 
                                                               name="export_fields[]" 
                                                               value="{{ $field }}" 
                                                               id="field_{{ $field }}"
                                                               data-category="facility"
                                                               autocomplete="off">
                                                        <label class="form-check-label" for="field_{{ $field }}">
                                                            {{ $label }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- 土地情報 -->
                                    <div class="col-12 mb-4">
                                        @php
                                            $landFields = [
                                                'land_ownership_type' => '土地所有区分',
                                                'land_site_area_sqm' => '敷地面積（㎡数）',
                                                'land_site_area_tsubo' => '敷地面積（坪数）',
                                                'land_parking_spaces' => '敷地内駐車場台数',
                                                'land_purchase_price' => '購入金額',
                                                'land_unit_price_per_tsubo' => '坪単価',
                                                'land_monthly_rent' => '家賃',
                                                'land_contract_period' => '契約期間',
                                                'land_auto_renewal' => '自動更新の有無',
                                                'land_contract_period_text' => '契約年数',
                                                'land_notes' => '土地備考',
                                                'land_management_company_name' => '土地管理会社名',
                                                'land_management_company_postal_code' => '土地管理会社郵便番号',
                                                'land_management_company_address' => '土地管理会社住所',
                                                'land_management_company_building' => '土地管理会社住所（建物名）',
                                                'land_management_company_phone' => '土地管理会社電話番号',
                                                'land_management_company_fax' => '土地管理会社FAX番号',
                                                'land_management_company_email' => '土地管理会社メールアドレス',
                                                'land_management_company_url' => '土地管理会社URL',
                                                'land_management_company_notes' => '土地管理会社備考',
                                                'land_owner_name' => '土地オーナー氏名',
                                                'land_owner_postal_code' => '土地オーナー郵便番号',
                                                'land_owner_address' => '土地オーナー住所',
                                                'land_owner_building' => '土地オーナー住所（建物名）',
                                                'land_owner_phone' => '土地オーナー電話番号',
                                                'land_owner_fax' => '土地オーナーFAX番号',
                                                'land_owner_email' => '土地オーナーメールアドレス',
                                                'land_owner_url' => '土地オーナーURL',
                                                'land_owner_notes' => '土地オーナー備考',
                                            ];
                                        @endphp
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="form-check me-3">
                                                <input class="form-check-input category-checkbox" 
                                                       type="checkbox" 
                                                       id="category_land"
                                                       data-category="land"
                                                       autocomplete="off">
                                                <label class="form-check-label fw-bold text-success" for="category_land">
                                                    <i class="fas fa-map-marked-alt me-1"></i>土地情報
                                                </label>
                                            </div>
                                            <small class="text-muted">
                                                (<span class="category-count" data-category="land">0</span>/{{ count($landFields) }} 項目選択中)
                                            </small>
                                        </div>
                                        <div class="row">
                                            @foreach($landFields as $field => $label)
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input field-checkbox" 
                                                               type="checkbox" 
                                                               name="export_fields[]" 
                                                               value="{{ $field }}" 
                                                               id="field_{{ $field }}"
                                                               data-category="land"
                                                               autocomplete="off">
                                                        <label class="form-check-label" for="field_{{ $field }}">
                                                            {{ $label }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- 建物情報 -->
                                    <div class="col-12 mb-4">
                                        @php
                                            $buildingFields = [
                                                'building_ownership_type' => '建物所有区分',
                                                'building_area_sqm' => '建築面積（㎡数）',
                                                'building_area_tsubo' => '建築面積（坪数）',
                                                'building_completion_date' => '竣工日',
                                                'building_total_floor_area_sqm' => '延床面積（㎡数）',
                                                'building_total_floor_area_tsubo' => '延べ床面積（坪数）',
                                                'building_age' => '築年数',
                                                'building_construction_cost' => '建築費用',
                                                'building_cost_per_tsubo' => '坪単価',
                                                'building_useful_life' => '耐用年数',
                                                'building_construction_cooperation_fee' => '建設協力金',
                                                'building_monthly_rent' => '建物家賃',
                                                'building_contract_years' => '建物契約年数',
                                                'building_contract_start_date' => '建物契約開始日',
                                                'building_contract_end_date' => '建物契約終了日',
                                                'building_auto_renewal' => '建物自動更新の有無',
                                                'building_construction_company_name' => '施工会社',
                                                'building_construction_company_phone' => '施工会社連絡先',
                                                'building_periodic_inspection_type' => '定期調査会社',
                                                'building_periodic_inspection_date' => '調査日',
                                                'building_notes' => '建物備考',
                                                'building_management_company_name' => '建物管理会社名',
                                                'building_management_company_phone' => '建物管理会社電話番号',
                                                'building_management_company_email' => '建物管理会社メールアドレス',
                                                'building_owner_name' => '建物オーナー氏名',
                                                'building_owner_phone' => '建物オーナー電話番号',
                                                'building_owner_email' => '建物オーナーメールアドレス',
                                            ];
                                        @endphp
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="form-check me-3">
                                                <input class="form-check-input category-checkbox" 
                                                       type="checkbox" 
                                                       id="category_building"
                                                       data-category="building"
                                                       autocomplete="off">
                                                <label class="form-check-label fw-bold text-info" for="category_building">
                                                    <i class="fas fa-building me-1"></i>建物情報
                                                </label>
                                            </div>
                                            <small class="text-muted">
                                                (<span class="category-count" data-category="building">0</span>/{{ count($buildingFields) }} 項目選択中)
                                            </small>
                                        </div>
                                        <div class="row">
                                            @foreach($buildingFields as $field => $label)
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input field-checkbox" 
                                                               type="checkbox" 
                                                               name="export_fields[]" 
                                                               value="{{ $field }}" 
                                                               id="field_{{ $field }}"
                                                               data-category="building"
                                                               autocomplete="off">
                                                        <label class="form-check-label" for="field_{{ $field }}">
                                                            {{ $label }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- ライフライン設備 -->
                                    <div class="col-12 mb-4">
                                        <h5 class="fw-bold text-primary mb-3"><i class="fas fa-plug me-1"></i>ライフライン設備</h5>

                                        <!-- 電気設備 -->
                                        @php
                                            $electricFields = [
                                                'power_company' => '電力会社',
                                                'power_capacity' => '受電容量',
                                                'power_backup' => '自家発電設備の有無',
                                                'power_backup_capacity' => '自家発電容量',
                                                'power_notes' => '電気備考',
                                            ];
                                        @endphp
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="form-check me-3">
                                                <input class="form-check-input category-checkbox" type="checkbox" id="category_electric" data-category="electric" autocomplete="off">
                                                <label class="form-check-label fw-bold text-warning" for="category_electric">電気設備</label>
                                            </div>
                                            <small class="text-muted">(<span class="category-count" data-category="electric">0</span>/{{ count($electricFields) }} 項目選択中)</small>
                                        </div>
                                        <div class="row mb-3">
                                            @foreach($electricFields as $field => $label)
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input field-checkbox" type="checkbox" name="export_fields[]" value="{{ $field }}" id="field_{{ $field }}" data-category="electric" autocomplete="off">
                                                        <label class="form-check-label" for="field_{{ $field }}">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- 水道設備 -->
                                        @php
                                            $waterFields = [
                                                'water_source' => '水源種別',
                                                'water_meter_count' => '水道メーター数',
                                                'water_tank_capacity' => '受水槽容量',
                                                'water_quality_check' => '水質検査有無',
                                                'water_notes' => '水道備考',
                                            ];
                                        @endphp
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="form-check me-3">
                                                <input class="form-check-input category-checkbox" type="checkbox" id="category_water" data-category="water" autocomplete="off">
                                                <label class="form-check-label fw-bold text-primary" for="category_water">水道設備</label>
                                            </div>
                                            <small class="text-muted">(<span class="category-count" data-category="water">0</span>/{{ count($waterFields) }} 項目選択中)</small>
                                        </div>
                                        <div class="row mb-3">
                                            @foreach($waterFields as $field => $label)
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input field-checkbox" type="checkbox" name="export_fields[]" value="{{ $field }}" id="field_{{ $field }}" data-category="water" autocomplete="off">
                                                        <label class="form-check-label" for="field_{{ $field }}">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- ガス設備 -->
                                        @php
                                            $gasFields = [
                                                'gas_company' => 'ガス会社',
                                                'gas_type' => 'ガス種別',
                                                'gas_meter_count' => 'ガスメーター数',
                                                'gas_tank_capacity' => 'ガスタンク容量',
                                                'gas_notes' => 'ガス備考',
                                            ];
                                        @endphp
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="form-check me-3">
                                                <input class="form-check-input category-checkbox" type="checkbox" id="category_gas" data-category="gas" autocomplete="off">
                                                <label class="form-check-label fw-bold text-danger" for="category_gas">ガス設備</label>
                                            </div>
                                            <small class="text-muted">(<span class="category-count" data-category="gas">0</span>/{{ count($gasFields) }} 項目選択中)</small>
                                        </div>
                                        <div class="row mb-3">
                                            @foreach($gasFields as $field => $label)
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input field-checkbox" type="checkbox" name="export_fields[]" value="{{ $field }}" id="field_{{ $field }}" data-category="gas" autocomplete="off">
                                                        <label class="form-check-label" for="field_{{ $field }}">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- EV設備 -->
                                        @php
                                            $evFields = [
                                                'ev_charger_count' => 'EV充電器台数',
                                                'ev_charger_type' => '充電器タイプ',
                                                'ev_output' => '出力(kW)',
                                                'ev_notes' => 'EV設備備考',
                                            ];
                                        @endphp
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="form-check me-3">
                                                <input class="form-check-input category-checkbox" type="checkbox" id="category_ev" data-category="ev" autocomplete="off">
                                                <label class="form-check-label fw-bold text-success" for="category_ev">EV設備</label>
                                            </div>
                                            <small class="text-muted">(<span class="category-count" data-category="ev">0</span>/{{ count($evFields) }} 項目選択中)</small>
                                        </div>
                                        <div class="row mb-3">
                                            @foreach($evFields as $field => $label)
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input field-checkbox" type="checkbox" name="export_fields[]" value="{{ $field }}" id="field_{{ $field }}" data-category="ev" autocomplete="off">
                                                        <label class="form-check-label" for="field_{{ $field }}">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- 空調・照明設備 -->
                                        @php
                                            $airFields = [
                                                'air_conditioner_type' => '空調方式',
                                                'air_conditioner_count' => '空調機器台数',
                                                'lighting_type' => '照明種別',
                                                'lighting_control' => '照明制御方式',
                                                'air_notes' => '空調・照明備考',
                                            ];
                                        @endphp
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="form-check me-3">
                                                <input class="form-check-input category-checkbox" type="checkbox" id="category_air" data-category="air" autocomplete="off">
                                                <label class="form-check-label fw-bold text-secondary" for="category_air">空調・照明設備</label>
                                            </div>
                                            <small class="text-muted">(<span class="category-count" data-category="air">0</span>/{{ count($airFields) }} 項目選択中)</small>
                                        </div>
                                        <div class="row">
                                            @foreach($airFields as $field => $label)
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input field-checkbox" type="checkbox" name="export_fields[]" value="{{ $field }}" id="field_{{ $field }}" data-category="air" autocomplete="off">
                                                        <label class="form-check-label" for="field_{{ $field }}">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- 防犯・防災設備 -->
                                    <div class="col-12 mb-4">
                                      @php
                                        $securityFields = [
                                          'security_camera_management_company' => '防犯カメラ管理業者',
                                          'security_camera_model_year' => '防犯カメラ年式',
                                          'security_camera_notes' => '防犯カメラ備考',
                                          'security_lock_management_company' => '電子錠管理業者',
                                          'security_lock_model_year' => '電子錠年式',
                                          'security_lock_notes' => '電子錠備考',
                                          'fire_manager' => '防火管理者',
                                          'fire_training_date' => '消防訓練実施日',
                                          'fire_inspection_company' => '消防設備点検業者',
                                          'fire_inspection_date' => '消防設備点検実施日',
                                          'disaster_practical_training_date' => '防災実地訓練実施日',
                                          'disaster_riding_training_date' => '防災起動訓練実施日',
                                        ];
                                      @endphp

                                      <div class="d-flex align-items-center mb-3">
                                        <div class="form-check me-3">
                                          <input class="form-check-input category-checkbox"
                                                 type="checkbox"
                                                 id="category_security"
                                                 data-category="security"
                                                 autocomplete="off">
                                          <label class="form-check-label fw-bold text-danger" for="category_security">
                                            <i class="fas fa-shield-alt me-1"></i>防犯・防災設備
                                          </label>
                                        </div>
                                        <small class="text-muted">
                                          (<span class="category-count" data-category="security">0</span>/{{ count($securityFields) }} 項目選択中)
                                        </small>
                                      </div>

                                      <div class="row">
                                        @foreach($securityFields as $field => $label)
                                          <div class="col-md-6 col-lg-4 mb-2">
                                            <div class="form-check">
                                              <input class="form-check-input field-checkbox"
                                                     type="checkbox"
                                                     name="export_fields[]"
                                                     value="{{ $field }}"
                                                     id="field_{{ $field }}"
                                                     data-category="security"
                                                     autocomplete="off">
                                              <label class="form-check-label" for="field_{{ $field }}">
                                                {{ $label }}
                                              </label>
                                            </div>
                                          </div>
                                        @endforeach
                                      </div>
                                    </div>

                                    <!-- 契約書 -->
                                    <div class="col-12 mb-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="form-check me-3">
                                                <input class="form-check-input category-checkbox" 
                                                       type="checkbox" 
                                                       id="category_contract"
                                                       data-category="contract"
                                                       autocomplete="off">
                                                <label class="form-check-label fw-bold text-purple" for="category_contract">
                                                    <i class="fas fa-file-contract me-1"></i>契約書
                                                </label>
                                            </div>
                                            <small class="text-muted">
                                                (<span class="category-count" data-category="contract">0</span> 項目選択中)
                                            </small>
                                        </div>
                                        
                                        <!-- その他契約書 -->
                                        <div class="mb-4">
                                          @php
                                            $othersContractFields = [
                                              'contract_others_company_name' => 'その他契約書_会社名',
                                              'contract_others_contract_type' => 'その他契約書_契約書の種類',
                                              'contract_others_contract_content' => 'その他契約書_契約内容',
                                              'contract_others_auto_renewal' => 'その他契約書_自動更新の有無',
                                              'contract_others_auto_renewal_details' => 'その他契約書_自動更新詳細',
                                              'contract_others_contract_start_date' => 'その他契約書_契約開始日',
                                              'contract_others_contract_end_date' => 'その他契約書_契約終了日',
                                              'contract_others_amount' => 'その他契約書_金額',
                                              'contract_others_notes' => 'その他契約書_備考',
                                            ];
                                          @endphp

                                          <div class="d-flex align-items-center mb-2">
                                            <div class="form-check me-3">
                                              <input class="form-check-input subcategory-checkbox" 
                                                     type="checkbox" 
                                                     id="subcategory_contract_others"
                                                     data-subcategory="contract_others"
                                                     data-parent-category="contract"
                                                     autocomplete="off">
                                              <label class="form-check-label fw-bold text-purple" for="subcategory_contract_others">
                                                <i class="fas fa-file-alt me-1"></i>その他契約書
                                              </label>
                                            </div>
                                            <small class="text-muted">
                                              (<span class="subcategory-count" data-subcategory="contract_others">0</span>/{{ count($othersContractFields) }} 項目)
                                            </small>
                                          </div>

                                          <div class="row">
                                            @foreach($othersContractFields as $field => $label)
                                              <div class="col-md-6 col-lg-4 mb-2">
                                                <div class="form-check">
                                                  <input class="form-check-input field-checkbox" 
                                                         type="checkbox" 
                                                         name="export_fields[]" 
                                                         value="{{ $field }}" 
                                                         id="field_{{ $field }}"
                                                         data-category="contract"
                                                         data-subcategory="contract_others"
                                                         autocomplete="off">
                                                  <label class="form-check-label" for="field_{{ $field }}">
                                                    {{ $label }}
                                                  </label>
                                                </div>
                                              </div>
                                            @endforeach
                                          </div>
                                        </div>

                                        <!-- 給食契約書 -->
                                        <div class="mb-4">
                                          @php
                                              $mealContractFields = [
                                                  'contract_meal_service_company_name' => '給食契約書_会社名',
                                                  'contract_meal_service_contract_type' => '給食契約書_契約書の種類',
                                                  'contract_meal_service_contract_content' => '給食契約書_契約内容',
                                                  'contract_meal_service_auto_renewal' => '給食契約書_自動更新の有無',
                                                  'contract_meal_service_contract_start_date' => '給食契約書_契約開始日',
                                                  'contract_meal_service_contract_end_date' => '給食契約書_契約終了日',
                                                  'contract_meal_service_amount' => '給食契約書_金額',
                                                  'contract_meal_service_notes' => '給食契約書_備考',
                                              ];
                                          @endphp
                                          <div class="d-flex align-items-center mb-2">
                                              <div class="form-check me-3">
                                                  <input class="form-check-input subcategory-checkbox" 
                                                         type="checkbox" 
                                                         id="subcategory_contract_meal"
                                                         data-subcategory="contract_meal"
                                                         data-parent-category="contract"
                                                         autocomplete="off">
                                                  <label class="form-check-label fw-bold text-success" for="subcategory_contract_meal">
                                                      <i class="fas fa-utensils me-1"></i>給食契約書
                                                  </label>
                                              </div>
                                              <small class="text-muted">
                                                  (<span class="subcategory-count" data-subcategory="contract_meal">0</span>/{{ count($mealContractFields) }} 項目)
                                              </small>
                                          </div>
                                          <div class="row">
                                              @foreach($mealContractFields as $field => $label)
                                                  <div class="col-md-6 col-lg-4 mb-2">
                                                      <div class="form-check">
                                                          <input class="form-check-input field-checkbox" 
                                                                 type="checkbox" 
                                                                 name="export_fields[]" 
                                                                 value="{{ $field }}" 
                                                                 id="field_{{ $field }}"
                                                                 data-category="contract"
                                                                 data-subcategory="contract_meal"
                                                                 autocomplete="off">
                                                          <label class="form-check-label" for="field_{{ $field }}">
                                                              {{ $label }}
                                                          </label>
                                                      </div>
                                                  </div>
                                              @endforeach
                                          </div>
                                        </div>

                                        <!-- 駐車場契約書 -->
                                        <div class="mb-4">
                                          @php
                                              $parkingContractFields = [
                                                  'contract_parking_company_name' => '駐車場契約書_会社名',
                                                  'contract_parking_contract_type' => '駐車場契約書_契約書の種類',
                                                  'contract_parking_contract_content' => '駐車場契約書_契約内容',
                                                  'contract_parking_auto_renewal' => '駐車場契約書_自動更新の有無',
                                                  'contract_parking_contract_start_date' => '駐車場契約書_契約開始日',
                                                  'contract_parking_contract_end_date' => '駐車場契約書_契約終了日',
                                                  'contract_parking_amount' => '駐車場契約書_金額',
                                                  'contract_parking_spaces' => '駐車場契約書_駐車場台数',
                                                  'contract_parking_notes' => '駐車場契約書_備考',
                                              ];
                                          @endphp
                                          <div class="d-flex align-items-center mb-2">
                                              <div class="form-check me-3">
                                                  <input class="form-check-input subcategory-checkbox" 
                                                         type="checkbox" 
                                                         id="subcategory_contract_parking"
                                                         data-subcategory="contract_parking"
                                                         data-parent-category="contract"
                                                         autocomplete="off">
                                                  <label class="form-check-label fw-bold text-info" for="subcategory_contract_parking">
                                                      <i class="fas fa-parking me-1"></i>駐車場契約書
                                                  </label>
                                              </div>
                                              <small class="text-muted">
                                                  (<span class="subcategory-count" data-subcategory="contract_parking">0</span>/{{ count($parkingContractFields) }} 項目)
                                              </small>
                                          </div>
                                          <div class="row">
                                              @foreach($parkingContractFields as $field => $label)
                                                  <div class="col-md-6 col-lg-4 mb-2">
                                                      <div class="form-check">
                                                          <input class="form-check-input field-checkbox" 
                                                                 type="checkbox" 
                                                                 name="export_fields[]" 
                                                                 value="{{ $field }}" 
                                                                 id="field_{{ $field }}"
                                                                 data-category="contract"
                                                                 data-subcategory="contract_parking"
                                                                 autocomplete="off">
                                                          <label class="form-check-label" for="field_{{ $field }}">
                                                              {{ $label }}
                                                          </label>
                                                      </div>
                                                  </div>
                                              @endforeach
                                          </div>
                                        </div>
                                    </div>

                                    <!-- 図面 -->
                                    <div class="col-12 mb-4">
                                        @php
                                            // 各サブセクションの配列を先に定義（件数の分母で利用）
                                            $handoverDrawingFields = [
                                                'drawing_handover_startup_drawing' => '引き渡し図面_就航図面',
                                                'drawing_handover_row_2' => '引き渡し図面_2行目',
                                                'drawing_handover_row_3' => '引き渡し図面_3行目',
                                                'drawing_handover_row_4' => '引き渡し図面_4行目',
                                                'drawing_handover_row_5' => '引き渡し図面_5行目',
                                            ];
                                            $completionDrawingFields = [
                                                'drawing_completion_row_1' => '完成図面_1行目',
                                                'drawing_completion_row_2' => '完成図面_2行目',
                                                'drawing_completion_row_3' => '完成図面_3行目',
                                                'drawing_completion_row_4' => '完成図面_4行目',
                                                'drawing_completion_row_5' => '完成図面_5行目',
                                            ];
                                            $otherDrawingFields = [
                                                'drawing_others_row_1' => 'その他図面_1行目',
                                                'drawing_others_row_2' => 'その他図面_2行目',
                                                'drawing_others_row_3' => 'その他図面_3行目',
                                                'drawing_others_row_4' => 'その他図面_4行目',
                                                'drawing_others_row_5' => 'その他図面_5行目',
                                                'drawing_notes' => '図面備考',
                                            ];
                                            $drawingTotalCount = count($handoverDrawingFields) + count($completionDrawingFields) + count($otherDrawingFields);
                                        @endphp

                                        <div class="d-flex align-items-center mb-3">
                                            <div class="form-check me-3">
                                                <input class="form-check-input category-checkbox"
                                                       type="checkbox"
                                                       id="category_drawing"
                                                       data-category="drawing"
                                                       autocomplete="off">
                                                <label class="form-check-label fw-bold text-primary" for="category_drawing">
                                                    <i class="fas fa-drafting-compass me-1"></i>図面
                                                </label>
                                            </div>
                                            <small class="text-muted">
                                                (<span class="category-count" data-category="drawing">0</span>/{{ $drawingTotalCount }} 項目選択中)
                                            </small>
                                        </div>

                                        <div class="alert alert-warning mb-3">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <small><strong>注意:</strong> 図面項目は<b>ファイル名のみ</b>を出力します。実際のファイルデータは含まれません。</small>
                                        </div>
                                        
                                        <!-- 引き渡し図面 -->
                                        <div class="mb-4">
                                            <h6 class="fw-bold text-primary mb-2">
                                                <i class="fas fa-file-pdf me-1"></i>引き渡し図面
                                            </h6>
                                            <div class="row">
                                                @foreach($handoverDrawingFields as $field => $label)
                                                    <div class="col-md-6 col-lg-4 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input field-checkbox"
                                                                   type="checkbox"
                                                                   name="export_fields[]"
                                                                   value="{{ $field }}"
                                                                   id="field_{{ $field }}"
                                                                   data-category="drawing"
                                                                   autocomplete="off">
                                                            <label class="form-check-label" for="field_{{ $field }}">{{ $label }}</label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- 完成図面 -->
                                        <div class="mb-4">
                                            <h6 class="fw-bold text-success mb-2">
                                                <i class="fas fa-file-image me-1"></i>完成図面
                                            </h6>
                                            <div class="row">
                                                @foreach($completionDrawingFields as $field => $label)
                                                    <div class="col-md-6 col-lg-4 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input field-checkbox"
                                                                   type="checkbox"
                                                                   name="export_fields[]"
                                                                   value="{{ $field }}"
                                                                   id="field_{{ $field }}"
                                                                   data-category="drawing"
                                                                   autocomplete="off">
                                                            <label class="form-check-label" for="field_{{ $field }}">{{ $label }}</label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- その他図面 -->
                                        <div class="mb-4">
                                            <h6 class="fw-bold text-info mb-2">
                                                <i class="fas fa-file-alt me-1"></i>その他図面
                                            </h6>
                                            <div class="row">
                                                @foreach($otherDrawingFields as $field => $label)
                                                    <div class="col-md-6 col-lg-4 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input field-checkbox"
                                                                   type="checkbox"
                                                                   name="export_fields[]"
                                                                   value="{{ $field }}"
                                                                   id="field_{{ $field }}"
                                                                   data-category="drawing"
                                                                   autocomplete="off">
                                                            <label class="form-check-label" for="field_{{ $field }}">{{ $label }}</label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 修繕履歴 -->
                                    <div class="col-12 mb-4">
                                        @php
                                            $maintenanceFields = [
                                                'maintenance_latest_date' => '修繕履歴_最新修繕日',
                                                'maintenance_latest_content' => '修繕履歴_最新修繕内容',
                                                'maintenance_latest_cost' => '修繕履歴_最新修繕費用',
                                                'maintenance_latest_contractor' => '修繕履歴_最新施工業者',
                                                'maintenance_latest_category' => '修繕履歴_最新カテゴリ',
                                                'maintenance_latest_subcategory' => '修繕履歴_最新サブカテゴリ',
                                                'maintenance_latest_contact_person' => '修繕履歴_最新担当者',
                                                'maintenance_latest_phone_number' => '修繕履歴_最新電話番号',
                                                'maintenance_latest_notes' => '修繕履歴_最新備考',
                                                'maintenance_latest_warranty_period' => '修繕履歴_最新保証期間',
                                                'maintenance_total_count' => '修繕履歴_総件数',
                                                'maintenance_total_cost' => '修繕履歴_総費用',
                                            ];
                                        @endphp

                                        <div class="d-flex align-items-center mb-3">
                                            <div class="form-check me-3">
                                                <input class="form-check-input category-checkbox"
                                                       type="checkbox"
                                                       id="category_maintenance"
                                                       data-category="maintenance"
                                                       autocomplete="off">
                                                <label class="form-check-label fw-bold text-warning" for="category_maintenance">
                                                    <i class="fas fa-tools me-1"></i>修繕履歴
                                                </label>
                                            </div>
                                            <small class="text-muted">
                                                (<span class="category-count" data-category="maintenance">0</span>/{{ count($maintenanceFields) }} 項目選択中)
                                            </small>
                                        </div>

                                        <div class="row">
                                            @foreach($maintenanceFields as $field => $label)
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input field-checkbox"
                                                               type="checkbox"
                                                               name="export_fields[]"
                                                               value="{{ $field }}"
                                                               id="field_{{ $field }}"
                                                               data-category="maintenance"
                                                               autocomplete="off">
                                                        <label class="form-check-label" for="field_{{ $field }}">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                                </div>

                        <!-- アクションボタン -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="exportButton" disabled>
                                        <i class="fas fa-download me-1"></i>
                                        CSV出力
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="saveFavoriteButton" disabled>
                                        <i class="fas fa-star me-1"></i>
                                        お気に入りに保存
                                    </button>
                                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#favoritesModal">
                                        <i class="fas fa-list me-1"></i>
                                        お気に入り一覧
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- お気に入り一覧モーダル -->
<div class="modal fade" id="favoritesModal" tabindex="-1" aria-labelledby="favoritesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="favoritesModalLabel">お気に入り一覧</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="favoritesList">
                    <!-- 静的テストコンテンツ -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>モーダル動作テスト</h6>
                        <p class="mb-2">このモーダルが正常に表示され、ボタンがクリック可能かテストしています。</p>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success btn-sm" onclick="alert('✅ ボタンクリックが正常に動作しています！')">
                                <i class="fas fa-check me-1"></i>テストボタン
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>モーダルを閉じる
                            </button>
                        </div>
                    </div>
                    
                    <div class="list-group">
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">テスト用お気に入り 1</h6>
                                    <small class="text-muted">施設: 3件 | 項目: 5項目 | 作成: 2024/01/01</small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" onclick="alert('読み込みボタンがクリックされました')">
                                        <i class="fas fa-download me-1"></i>読み込み
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="alert('編集ボタンがクリックされました')">
                                        <i class="fas fa-edit me-1"></i>編集
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="alert('削除ボタンがクリックされました')">
                                        <i class="fas fa-trash me-1"></i>削除
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">テスト用お気に入り 2</h6>
                                    <small class="text-muted">施設: 2件 | 項目: 8項目 | 作成: 2024/01/02</small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" onclick="alert('読み込みボタンがクリックされました')">
                                        <i class="fas fa-download me-1"></i>読み込み
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="alert('編集ボタンがクリックされました')">
                                        <i class="fas fa-edit me-1"></i>編集
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="alert('削除ボタンがクリックされました')">
                                        <i class="fas fa-trash me-1"></i>削除
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-lightbulb me-1"></i>
                            上記のボタンをクリックしてモーダルの動作をテストしてください
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- お気に入り保存モーダル -->
<div class="modal fade" id="saveFavoriteModal" tabindex="-1" aria-labelledby="saveFavoriteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveFavoriteModalLabel">お気に入りに保存</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="saveFavoriteForm">
                    @csrf
                    <div class="mb-3">
                        <label for="favoriteName" class="form-label">お気に入り名</label>
                        <input type="text" class="form-control" id="favoriteName" name="name" required autocomplete="off">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="saveFavoriteConfirm">保存</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.text-purple {
    color: #6f42c1 !important;
}

/* カテゴリーセクションのスタイル */
.export-category-section {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #fff;
}

/* プログレスバーの初期状態を非表示に */
.export-progress {
    display: none;
}


</style>
@endpush

@vite(['resources/js/modules/export.js'])

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('CSV Export: Setting up modal fixes');
    
    // Bootstrap に任せる軽量クリーンアップ
    function cleanupModalState() {
        // まだ開いているモーダルがあれば何もしない
        if (document.querySelector('.modal.show')) return;
        // 念のため残骸だけ掃除（Bootstrap 管理外の残りを除去）
        document.querySelectorAll('.modal-backdrop:not(.fade)').forEach(b => b.remove());
    }
    
    function setupModalFixes() {
        // Bootstrap に任せる。最小限の後処理のみ
        document.addEventListener('hidden.bs.modal', () => {
            setTimeout(() => cleanupModalState(), 50);
        });
    }
    
    // Setup modal fixes
    setupModalFixes();
    
    // 安全クローズ関数（緊急時のみ使用）
    window.safeCloseModalById = function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        const inst = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el, {});
        inst.hide();
        // 全て閉じたらだけ軽く掃除
        setTimeout(() => {
            if (!document.querySelector('.modal.show')) {
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            }
        }, 150);
    };
    
    // 緊急時のクリーンアップ（最小限）
    window.forceCleanupModals = function() {
        console.log('CSV Export: Emergency cleanup');
        cleanupModalState();
    };
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        console.log('CSV Export: Page unloading, cleaning up modals');
        cleanupModalState();
    });
    
    console.log('CSV Export: Modal fixes setup complete');
    
    // Force initialize ExportManager if not already done
    setTimeout(() => {
        console.log('CSV Export: Checking ExportManager initialization');
        console.log('CSV Export: window.exportManager exists:', !!window.exportManager);
        console.log('CSV Export: ExportManager class available:', typeof ExportManager !== 'undefined');
        
        if (typeof ExportManager !== 'undefined' && !window.exportManager) {
            console.log('CSV Export: Force initializing ExportManager');
            try {
                const manager = new ExportManager();
                window.exportManager = manager;
                console.log('CSV Export: ExportManager force initialized:', manager);
            } catch (error) {
                console.error('CSV Export: Failed to initialize ExportManager:', error);
            }
        }
        
        // Test modal functionality with simple content
        const favoritesModal = document.getElementById('favoritesModal');
        if (favoritesModal) {
            favoritesModal.addEventListener('show.bs.modal', function() {
                console.log('CSV Export: Favorites modal opening - adding test content');
                const container = document.getElementById('favoritesList');
                if (container) {
                    container.innerHTML = `
                        <div class="alert alert-success" style="pointer-events: auto; z-index: 1060;">
                            <h6>モーダルテスト</h6>
                            <p>このモーダルは正常に動作しています。</p>
                            <button type="button" class="btn btn-primary btn-sm" 
                                    style="pointer-events: auto; z-index: 1061; position: relative;"
                                    onclick="alert('ボタンが正常にクリックされました！'); console.log('Test button clicked!');">
                                テストボタン
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm ms-2" 
                                    style="pointer-events: auto; z-index: 1061; position: relative;"
                                    data-bs-dismiss="modal">
                                閉じる
                            </button>
                        </div>
                        <div class="mt-3" style="pointer-events: auto;">
                            <p><strong>デバッグ情報:</strong></p>
                            <ul>
                                <li>モーダル表示: 成功</li>
                                <li>コンテンツ読み込み: 成功</li>
                                <li>ボタンクリック: テスト中</li>
                            </ul>
                            <button type="button" class="btn btn-warning btn-sm" 
                                    style="pointer-events: auto; z-index: 1061; position: relative;"
                                    onclick="console.log('Debug button clicked'); window.forceCleanupModals(); alert('強制クリーンアップを実行しました');">
                                強制クリーンアップ
                            </button>
                        </div>
                    `;
                    
                    // Force all buttons to be clickable
                    const buttons = container.querySelectorAll('button');
                    buttons.forEach(btn => {
                        btn.style.pointerEvents = 'auto';
                        btn.style.zIndex = '1061';
                        btn.style.position = 'relative';
                        console.log('CSV Export: Button made clickable:', btn);
                    });
                }
            });
        }
    }, 1000);
});
</script>
@endpush