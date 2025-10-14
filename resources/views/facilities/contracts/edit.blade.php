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
                                    <i class="fas fa-file-contract"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 facility-name">{{ $facility->facility_name }} - 契約書編集</h5>
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
                                <a href="{{ route('facilities.show', $facility) }}#contracts" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> 契約書一覧に戻る
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

            @if ($errors->any())
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>入力エラーがあります</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- 契約書編集フォーム -->
            <div class="contracts-edit-container">
                <!-- サブタブナビゲーション -->
                <div class="contracts-subtabs">
                    <ul class="nav nav-tabs" id="contractsEditTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ ($activeSubTab ?? 'others') === 'meal-service' ? 'active' : '' }}" id="meal-service-edit-tab" data-bs-toggle="tab" data-bs-target="#meal-service-edit" type="button" role="tab" aria-controls="meal-service-edit" aria-selected="{{ ($activeSubTab ?? 'others') === 'meal-service' ? 'true' : 'false' }}">
                                <i class="fas fa-utensils me-2"></i>給食
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ ($activeSubTab ?? 'others') === 'parking' ? 'active' : '' }}" id="parking-edit-tab" data-bs-toggle="tab" data-bs-target="#parking-edit" type="button" role="tab" aria-controls="parking-edit" aria-selected="{{ ($activeSubTab ?? 'others') === 'parking' ? 'true' : 'false' }}">
                                <i class="fas fa-parking me-2"></i>駐車場
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ ($activeSubTab ?? 'others') === 'others' ? 'active' : '' }}" id="others-edit-tab" data-bs-toggle="tab" data-bs-target="#others-edit" type="button" role="tab" aria-controls="others-edit" aria-selected="{{ ($activeSubTab ?? 'others') === 'others' ? 'true' : 'false' }}">
                                <i class="fas fa-file-alt me-2"></i>その他
                            </button>
                        </li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('facilities.contracts.update', $facility) }}" enctype="multipart/form-data" id="contractsForm">
                    @csrf
                    @method('PUT')
                    
                    <!-- 現在のアクティブサブタブを追跡 -->
                    <input type="hidden" name="active_sub_tab" id="activeSubTabField" value="{{ $activeSubTab ?? 'others' }}">

                    <!-- サブタブコンテンツ -->
                    <div class="tab-content" id="contractsEditTabContent">
                        <!-- その他契約書編集 -->
                        <div class="tab-pane fade {{ ($activeSubTab ?? 'others') === 'others' ? 'show active' : '' }}" id="others-edit" role="tabpanel" aria-labelledby="others-edit-tab">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-file-alt text-secondary me-2"></i>その他契約書編集
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @php
                                        // データベースから既存データを取得
                                        $othersData = $contractsData['others'] ?? [];
                                        $othersContractData = [
                                            'company_name' => old('others.company_name', $othersData['company_name'] ?? ''),
                                            'contract_type' => old('others.contract_type', $othersData['contract_type'] ?? ''),
                                            'contract_content' => old('others.contract_content', $othersData['contract_content'] ?? ''),
                                            'auto_renewal' => old('others.auto_renewal', $othersData['auto_renewal'] ?? ''),
                                            'auto_renewal_details' => old('others.auto_renewal_details', $othersData['auto_renewal_details'] ?? ''),
                                            'contract_start_date' => old('others.contract_start_date', $othersData['contract_start_date'] ?? ''),
                                            'cancellation_conditions' => old('others.cancellation_conditions', $othersData['cancellation_conditions'] ?? ''),
                                            'renewal_notice_period' => old('others.renewal_notice_period', $othersData['renewal_notice_period'] ?? ''),
                                            'contract_end_date' => old('others.contract_end_date', $othersData['contract_end_date'] ?? ''),
                                            'other_matters' => old('others.other_matters', $othersData['other_matters'] ?? ''),
                                            'amount' => old('others.amount', $othersData['amount'] ?? ''),
                                            'contact_info' => old('others.contact_info', $othersData['contact_info'] ?? '')
                                        ];
                                    @endphp

                                    <div class="row">
                                        <!-- 1行目：会社名｜契約書の種類（テキスト） -->
                                        <div class="col-md-6 mb-3">
                                            <label for="others_company_name" class="form-label">会社名</label>
                                            <input type="text" 
                                                   class="form-control @error('others.company_name') is-invalid @enderror" 
                                                   id="others_company_name" 
                                                   name="others[company_name]"
                                                   value="{{ $othersContractData['company_name'] }}"
                                                   placeholder="会社名を入力してください">
                                            @error('others.company_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="others_contract_type" class="form-label">契約書の種類</label>
                                            <input type="text" 
                                                   class="form-control @error('others.contract_type') is-invalid @enderror" 
                                                   id="others_contract_type" 
                                                   name="others[contract_type]"
                                                   value="{{ $othersContractData['contract_type'] }}"
                                                   placeholder="例：保守契約書、清掃契約書など">
                                            @error('others.contract_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- 2行目：契約内容｜自動更新の有無（プルダウン＋テキスト入力） -->
                                        <div class="col-md-6 mb-3">
                                            <label for="others_contract_content" class="form-label">契約内容</label>
                                            <textarea class="form-control @error('others.contract_content') is-invalid @enderror" 
                                                      id="others_contract_content" 
                                                      name="others[contract_content]"
                                                      rows="3"
                                                      placeholder="契約の詳細内容を入力してください">{{ $othersContractData['contract_content'] }}</textarea>
                                            @error('others.contract_content')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="others_auto_renewal" class="form-label">自動更新の有無</label>
                                            <select class="form-select @error('others.auto_renewal') is-invalid @enderror" 
                                                    id="others_auto_renewal" 
                                                    name="others[auto_renewal]">
                                                <option value="">選択してください</option>
                                                <option value="あり" {{ $othersContractData['auto_renewal'] === 'あり' ? 'selected' : '' }}>あり</option>
                                                <option value="なし" {{ $othersContractData['auto_renewal'] === 'なし' ? 'selected' : '' }}>なし</option>
                                                <option value="条件付き" {{ $othersContractData['auto_renewal'] === '条件付き' ? 'selected' : '' }}>条件付き</option>
                                            </select>
                                            @error('others.auto_renewal')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            
                                            <div class="mt-2">
                                                <label for="others_auto_renewal_details" class="form-label">自動更新の詳細条件</label>
                                                <textarea class="form-control @error('others.auto_renewal_details') is-invalid @enderror" 
                                                          id="others_auto_renewal_details" 
                                                          name="others[auto_renewal_details]"
                                                          rows="2"
                                                          placeholder="自動更新の詳細や条件を入力してください">{{ $othersContractData['auto_renewal_details'] }}</textarea>
                                                @error('others.auto_renewal_details')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- 3行目：契約開始日｜解約条件・更新通知期限 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="others_contract_start_date" class="form-label">契約開始日</label>
                                            <input type="date" 
                                                   class="form-control @error('others.contract_start_date') is-invalid @enderror" 
                                                   id="others_contract_start_date" 
                                                   name="others[contract_start_date]"
                                                   value="{{ $othersContractData['contract_start_date'] }}">
                                            @error('others.contract_start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="others_cancellation_conditions" class="form-label">解約条件</label>
                                            <textarea class="form-control @error('others.cancellation_conditions') is-invalid @enderror" 
                                                      id="others_cancellation_conditions" 
                                                      name="others[cancellation_conditions]"
                                                      rows="2"
                                                      placeholder="解約条件を入力してください">{{ $othersContractData['cancellation_conditions'] }}</textarea>
                                            @error('others.cancellation_conditions')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            
                                            <div class="mt-2">
                                                <label for="others_renewal_notice_period" class="form-label">更新通知期限</label>
                                                <input type="text" 
                                                       class="form-control @error('others.renewal_notice_period') is-invalid @enderror" 
                                                       id="others_renewal_notice_period" 
                                                       name="others[renewal_notice_period]"
                                                       value="{{ $othersContractData['renewal_notice_period'] }}"
                                                       placeholder="例：契約満了日の3ヶ月前まで">
                                                @error('others.renewal_notice_period')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- 4行目：契約終了日｜その他事項 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="others_contract_end_date" class="form-label">契約終了日</label>
                                            <input type="date" 
                                                   class="form-control @error('others.contract_end_date') is-invalid @enderror" 
                                                   id="others_contract_end_date" 
                                                   name="others[contract_end_date]"
                                                   value="{{ $othersContractData['contract_end_date'] }}">
                                            @error('others.contract_end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="others_other_matters" class="form-label">その他事項</label>
                                            <textarea class="form-control @error('others.other_matters') is-invalid @enderror" 
                                                      id="others_other_matters" 
                                                      name="others[other_matters]"
                                                      rows="3"
                                                      placeholder="その他の事項を入力してください">{{ $othersContractData['other_matters'] }}</textarea>
                                            @error('others.other_matters')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- 5行目：金額｜空のラベル -->
                                        <div class="col-md-6 mb-3">
                                            <label for="others_amount" class="form-label">金額</label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       class="form-control @error('others.amount') is-invalid @enderror" 
                                                       id="others_amount" 
                                                       name="others[amount]"
                                                       value="{{ $othersContractData['amount'] }}"
                                                       placeholder="0"
                                                       min="0"
                                                       step="1">
                                                <span class="input-group-text">円</span>
                                                @error('others.amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <!-- 空のラベル -->
                                        </div>

                                        <!-- 6行目：連絡先｜空のラベル -->
                                        <div class="col-md-6 mb-3">
                                            <label for="others_contact_info" class="form-label">連絡先</label>
                                            <textarea class="form-control @error('others.contact_info') is-invalid @enderror" 
                                                      id="others_contact_info" 
                                                      name="others[contact_info]"
                                                      rows="3"
                                                      placeholder="担当者名、電話番号、メールアドレス等を入力してください">{{ $othersContractData['contact_info'] }}</textarea>
                                            @error('others.contact_info')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <!-- 空のラベル -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 給食契約書編集 -->
                        <div class="tab-pane fade {{ ($activeSubTab ?? 'others') === 'meal-service' ? 'show active' : '' }}" id="meal-service-edit" role="tabpanel" aria-labelledby="meal-service-edit-tab">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-utensils text-success me-2"></i>給食契約書編集
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @php
                                        // データベースから既存データを取得
                                        $mealServiceData = $contractsData['meal_service'] ?? [];
                                        $mealContractData = [
                                            'company_name' => old('meal_service.company_name', $mealServiceData['company_name'] ?? ''),
                                            'management_fee' => old('meal_service.management_fee', $mealServiceData['management_fee'] ?? ''),
                                            'contract_content' => old('meal_service.contract_content', $mealServiceData['contract_content'] ?? ''),
                                            'breakfast_price' => old('meal_service.breakfast_price', $mealServiceData['breakfast_price'] ?? ''),
                                            'contract_start_date' => old('meal_service.contract_start_date', $mealServiceData['contract_start_date'] ?? ''),
                                            'lunch_price' => old('meal_service.lunch_price', $mealServiceData['lunch_price'] ?? ''),
                                            'contract_type' => old('meal_service.contract_type', $mealServiceData['contract_type'] ?? ''),
                                            'dinner_price' => old('meal_service.dinner_price', $mealServiceData['dinner_price'] ?? ''),
                                            'auto_renewal' => old('meal_service.auto_renewal', $mealServiceData['auto_renewal'] ?? ''),
                                            'auto_renewal_details' => old('meal_service.auto_renewal_details', $mealServiceData['auto_renewal_details'] ?? ''),
                                            'snack_price' => old('meal_service.snack_price', $mealServiceData['snack_price'] ?? ''),
                                            'cancellation_conditions' => old('meal_service.cancellation_conditions', $mealServiceData['cancellation_conditions'] ?? ''),
                                            'event_meal_price' => old('meal_service.event_meal_price', $mealServiceData['event_meal_price'] ?? ''),
                                            'renewal_notice_period' => old('meal_service.renewal_notice_period', $mealServiceData['renewal_notice_period'] ?? ''),
                                            'staff_meal_price' => old('meal_service.staff_meal_price', $mealServiceData['staff_meal_price'] ?? ''),
                                            'other_matters' => old('meal_service.other_matters', $mealServiceData['other_matters'] ?? '')
                                        ];
                                        
                                    @endphp

                                    <div class="row">
                                        <!-- 1行目：会社名｜管理費 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_company_name" class="form-label">会社名</label>
                                            <input type="text" 
                                                   class="form-control @error('meal_service.company_name') is-invalid @enderror" 
                                                   id="meal_service_company_name" 
                                                   name="meal_service[company_name]"
                                                   value="{{ $mealContractData['company_name'] }}"
                                                   placeholder="例：○○給食サービス株式会社">
                                            @error('meal_service.company_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_management_fee" class="form-label">管理費</label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       class="form-control @error('meal_service.management_fee') is-invalid @enderror" 
                                                       id="meal_service_management_fee" 
                                                       name="meal_service[management_fee]"
                                                       value="{{ $mealContractData['management_fee'] }}"
                                                       placeholder="0"
                                                       min="0"
                                                       step="1">
                                                <span class="input-group-text">円</span>
                                                @error('meal_service.management_fee')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- 2行目：契約内容｜食単価 朝食 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_contract_content" class="form-label">契約内容</label>
                                            <textarea class="form-control @error('meal_service.contract_content') is-invalid @enderror" 
                                                      id="meal_service_contract_content" 
                                                      name="meal_service[contract_content]"
                                                      rows="3"
                                                      placeholder="例：給食業務委託、栄養管理、食材調達等">{{ $mealContractData['contract_content'] }}</textarea>
                                            @error('meal_service.contract_content')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_breakfast_price" class="form-label">朝食単価</label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       class="form-control @error('meal_service.breakfast_price') is-invalid @enderror" 
                                                       id="meal_service_breakfast_price" 
                                                       name="meal_service[breakfast_price]"
                                                       value="{{ $mealContractData['breakfast_price'] }}"
                                                       placeholder="0"
                                                       min="0"
                                                       step="1">
                                                <span class="input-group-text">円</span>
                                                @error('meal_service.breakfast_price')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- 3行目：契約開始日｜昼食 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_contract_start_date" class="form-label">契約開始日</label>
                                            <input type="date" 
                                                   class="form-control @error('meal_service.contract_start_date') is-invalid @enderror" 
                                                   id="meal_service_contract_start_date" 
                                                   name="meal_service[contract_start_date]"
                                                   value="{{ $mealContractData['contract_start_date'] }}">
                                            @error('meal_service.contract_start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_lunch_price" class="form-label">昼食単価</label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       class="form-control @error('meal_service.lunch_price') is-invalid @enderror" 
                                                       id="meal_service_lunch_price" 
                                                       name="meal_service[lunch_price]"
                                                       value="{{ $mealContractData['lunch_price'] }}"
                                                       placeholder="0"
                                                       min="0"
                                                       step="1">
                                                <span class="input-group-text">円</span>
                                                @error('meal_service.lunch_price')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- 4行目：契約書の種類（テキスト）｜夕食 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_contract_type" class="form-label">契約書の種類</label>
                                            <input type="text" 
                                                   class="form-control @error('meal_service.contract_type') is-invalid @enderror" 
                                                   id="meal_service_contract_type" 
                                                   name="meal_service[contract_type]"
                                                   value="{{ $mealContractData['contract_type'] }}"
                                                   placeholder="例：給食業務委託契約書">
                                            @error('meal_service.contract_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_dinner_price" class="form-label">夕食単価</label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       class="form-control @error('meal_service.dinner_price') is-invalid @enderror" 
                                                       id="meal_service_dinner_price" 
                                                       name="meal_service[dinner_price]"
                                                       value="{{ $mealContractData['dinner_price'] }}"
                                                       placeholder="0"
                                                       min="0"
                                                       step="1">
                                                <span class="input-group-text">円</span>
                                                @error('meal_service.dinner_price')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- 5行目：自動更新の有無（テキスト入力）｜おやつ -->
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_auto_renewal" class="form-label">自動更新の有無</label>
                                            <select class="form-select @error('meal_service.auto_renewal') is-invalid @enderror" 
                                                    id="meal_service_auto_renewal" 
                                                    name="meal_service[auto_renewal]"
                                                    onchange="toggleAutoRenewalDetails()">
                                                <option value="">選択してください</option>
                                                <option value="あり" {{ $mealContractData['auto_renewal'] === 'あり' ? 'selected' : '' }}>あり</option>
                                                <option value="なし" {{ $mealContractData['auto_renewal'] === 'なし' ? 'selected' : '' }}>なし</option>
                                            </select>
                                            @error('meal_service.auto_renewal')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            
                                            <!-- 自動更新の詳細条件（条件付き表示） -->
                                            <div class="mt-2" id="auto_renewal_details_container" style="display: {{ $mealContractData['auto_renewal'] === 'なし' || !empty($mealContractData['auto_renewal_details']) ? 'block' : 'none' }};">
                                                <label for="meal_service_auto_renewal_details" class="form-label">
                                                    <span id="auto_renewal_details_label">
                                                        {{ $mealContractData['auto_renewal'] === 'なし' ? '更新条件等' : '自動更新の詳細条件' }}
                                                    </span>
                                                </label>
                                                <textarea class="form-control @error('meal_service.auto_renewal_details') is-invalid @enderror" 
                                                          id="meal_service_auto_renewal_details" 
                                                          name="meal_service[auto_renewal_details]"
                                                          rows="2"
                                                          placeholder="{{ $mealContractData['auto_renewal'] === 'なし' ? '例：契約満了時に双方協議の上更新' : '例：1年毎自動更新、3ヶ月前通知で解約可能' }}">{{ $mealContractData['auto_renewal_details'] }}</textarea>
                                                @error('meal_service.auto_renewal_details')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_snack_price" class="form-label">おやつ単価</label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       class="form-control @error('meal_service.snack_price') is-invalid @enderror" 
                                                       id="meal_service_snack_price" 
                                                       name="meal_service[snack_price]"
                                                       value="{{ $mealContractData['snack_price'] }}"
                                                       placeholder="0"
                                                       min="0"
                                                       step="1">
                                                <span class="input-group-text">円</span>
                                                @error('meal_service.snack_price')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- 6行目：解約条件・更新通知期限｜行事食 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_cancellation_conditions" class="form-label">解約条件</label>
                                            <textarea class="form-control @error('meal_service.cancellation_conditions') is-invalid @enderror" 
                                                      id="meal_service_cancellation_conditions" 
                                                      name="meal_service[cancellation_conditions]"
                                                      rows="2"
                                                      placeholder="解約条件を入力してください">{{ $mealContractData['cancellation_conditions'] }}</textarea>
                                            @error('meal_service.cancellation_conditions')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            
                                            <div class="mt-2">
                                                <label for="meal_service_renewal_notice_period" class="form-label">更新通知期限</label>
                                                <input type="text" 
                                                       class="form-control @error('meal_service.renewal_notice_period') is-invalid @enderror" 
                                                       id="meal_service_renewal_notice_period" 
                                                       name="meal_service[renewal_notice_period]"
                                                       value="{{ $mealContractData['renewal_notice_period'] }}"
                                                       placeholder="例：契約満了日の3ヶ月前まで">
                                                @error('meal_service.renewal_notice_period')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_event_meal_price" class="form-label">行事食単価</label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       class="form-control @error('meal_service.event_meal_price') is-invalid @enderror" 
                                                       id="meal_service_event_meal_price" 
                                                       name="meal_service[event_meal_price]"
                                                       value="{{ $mealContractData['event_meal_price'] }}"
                                                       placeholder="0"
                                                       min="0"
                                                       step="1">
                                                <span class="input-group-text">円</span>
                                                @error('meal_service.event_meal_price')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- 7行目：その他事項｜職員食 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_other_matters" class="form-label">その他事項</label>
                                            <textarea class="form-control @error('meal_service.other_matters') is-invalid @enderror" 
                                                      id="meal_service_other_matters" 
                                                      name="meal_service[other_matters]"
                                                      rows="3"
                                                      placeholder="その他の事項を入力してください">{{ $mealContractData['other_matters'] }}</textarea>
                                            @error('meal_service.other_matters')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="meal_service_staff_meal_price" class="form-label">職員食単価</label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       class="form-control @error('meal_service.staff_meal_price') is-invalid @enderror" 
                                                       id="meal_service_staff_meal_price" 
                                                       name="meal_service[staff_meal_price]"
                                                       value="{{ $mealContractData['staff_meal_price'] }}"
                                                       placeholder="0"
                                                       min="0"
                                                       step="1">
                                                <span class="input-group-text">円</span>
                                                @error('meal_service.staff_meal_price')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 駐車場契約書編集 -->
                        <div class="tab-pane fade {{ ($activeSubTab ?? 'others') === 'parking' ? 'show active' : '' }}" id="parking-edit" role="tabpanel" aria-labelledby="parking-edit-tab">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-parking text-primary me-2"></i>駐車場契約書編集
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @php
                                        // データベースから既存データを取得
                                        $parkingData = $contractsData['parking'] ?? [];
                                        $parkingContractData = [
                                            'parking_name' => old('parking.parking_name', $parkingData['parking_name'] ?? ''),
                                            'contract_start_date' => old('parking.contract_start_date', $parkingData['contract_start_date'] ?? ''),
                                            'parking_location' => old('parking.parking_location', $parkingData['parking_location'] ?? ''),
                                            'contract_end_date' => old('parking.contract_end_date', $parkingData['contract_end_date'] ?? ''),
                                            'parking_spaces' => old('parking.parking_spaces', $parkingData['parking_spaces'] ?? ''),
                                            'auto_renewal' => old('parking.auto_renewal', $parkingData['auto_renewal'] ?? ''),
                                            'parking_position' => old('parking.parking_position', $parkingData['parking_position'] ?? ''),
                                            'cancellation_conditions' => old('parking.cancellation_conditions', $parkingData['cancellation_conditions'] ?? ''),
                                            'renewal_notice_period' => old('parking.renewal_notice_period', $parkingData['renewal_notice_period'] ?? ''),
                                            'price_per_space' => old('parking.price_per_space', $parkingData['price_per_space'] ?? ''),
                                            'usage_purpose' => old('parking.usage_purpose', $parkingData['usage_purpose'] ?? ''),
                                            'other_matters' => old('parking.other_matters', $parkingData['other_matters'] ?? '')
                                        ];
                                        
                                        // 管理会社情報
                                        $managementCompanyData = [
                                            'company_name' => old('parking.management_company_name', $parkingData['management_company_name'] ?? ''),
                                            'postal_code' => old('parking.management_postal_code', $parkingData['management_postal_code'] ?? ''),
                                            'address' => old('parking.management_address', $parkingData['management_address'] ?? ''),
                                            'building_name' => old('parking.management_building_name', $parkingData['management_building_name'] ?? ''),
                                            'phone' => old('parking.management_phone', $parkingData['management_phone'] ?? ''),
                                            'fax' => old('parking.management_fax', $parkingData['management_fax'] ?? ''),
                                            'email' => old('parking.management_email', $parkingData['management_email'] ?? ''),
                                            'url' => old('parking.management_url', $parkingData['management_url'] ?? ''),
                                            'notes' => old('parking.management_notes', $parkingData['management_notes'] ?? '')
                                        ];
                                        
                                        // オーナー情報
                                        $ownerData = [
                                            'name' => old('parking.owner_name', $parkingData['owner_name'] ?? ''),
                                            'postal_code' => old('parking.owner_postal_code', $parkingData['owner_postal_code'] ?? ''),
                                            'address' => old('parking.owner_address', $parkingData['owner_address'] ?? ''),
                                            'building_name' => old('parking.owner_building_name', $parkingData['owner_building_name'] ?? ''),
                                            'phone' => old('parking.owner_phone', $parkingData['owner_phone'] ?? ''),
                                            'fax' => old('parking.owner_fax', $parkingData['owner_fax'] ?? ''),
                                            'email' => old('parking.owner_email', $parkingData['owner_email'] ?? ''),
                                            'url' => old('parking.owner_url', $parkingData['owner_url'] ?? ''),
                                            'notes' => old('parking.owner_notes', $parkingData['owner_notes'] ?? '')
                                        ];
                                    @endphp

                                    <div class="row">
                                        <!-- 1行目：駐車場名｜契約開始日 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="parking_name" class="form-label">駐車場名</label>
                                            <input type="text" 
                                                   class="form-control @error('parking.parking_name') is-invalid @enderror" 
                                                   id="parking_name" 
                                                   name="parking[parking_name]"
                                                   value="{{ $parkingContractData['parking_name'] }}"
                                                   placeholder="例：○○駐車場、△△パーキング">
                                            @error('parking.parking_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="parking_contract_start_date" class="form-label">契約開始日</label>
                                            <input type="date" 
                                                   class="form-control @error('parking.contract_start_date') is-invalid @enderror" 
                                                   id="parking_contract_start_date" 
                                                   name="parking[contract_start_date]"
                                                   value="{{ $parkingContractData['contract_start_date'] }}">
                                            @error('parking.contract_start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- 2行目：駐車場所在地｜契約終了日 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="parking_location" class="form-label">駐車場所在地</label>
                                            <textarea class="form-control @error('parking.parking_location') is-invalid @enderror" 
                                                      id="parking_location" 
                                                      name="parking[parking_location]"
                                                      rows="2"
                                                      placeholder="駐車場の住所を入力してください">{{ $parkingContractData['parking_location'] }}</textarea>
                                            @error('parking.parking_location')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="parking_contract_end_date" class="form-label">契約終了日</label>
                                            <input type="date" 
                                                   class="form-control @error('parking.contract_end_date') is-invalid @enderror" 
                                                   id="parking_contract_end_date" 
                                                   name="parking[contract_end_date]"
                                                   value="{{ $parkingContractData['contract_end_date'] }}">
                                            @error('parking.contract_end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- 3行目：台数｜更新の有無（プルダウン） -->
                                        <div class="col-md-6 mb-3">
                                            <label for="parking_spaces" class="form-label">台数</label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       class="form-control @error('parking.parking_spaces') is-invalid @enderror" 
                                                       id="parking_spaces" 
                                                       name="parking[parking_spaces]"
                                                       value="{{ $parkingContractData['parking_spaces'] }}"
                                                       placeholder="0"
                                                       min="0"
                                                       step="1">
                                                <span class="input-group-text">台</span>
                                                @error('parking.parking_spaces')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="parking_auto_renewal" class="form-label">更新の有無</label>
                                            <select class="form-select @error('parking.auto_renewal') is-invalid @enderror" 
                                                    id="parking_auto_renewal" 
                                                    name="parking[auto_renewal]">
                                                <option value="">選択してください</option>
                                                <option value="あり" {{ $parkingContractData['auto_renewal'] === 'あり' ? 'selected' : '' }}>あり</option>
                                                <option value="なし" {{ $parkingContractData['auto_renewal'] === 'なし' ? 'selected' : '' }}>なし</option>
                                                <option value="条件付き" {{ $parkingContractData['auto_renewal'] === '条件付き' ? 'selected' : '' }}>条件付き</option>
                                            </select>
                                            @error('parking.auto_renewal')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- 4行目：停車位置｜解約条件・更新通知期限 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="parking_position" class="form-label">停車位置</label>
                                            <textarea class="form-control @error('parking.parking_position') is-invalid @enderror" 
                                                      id="parking_position" 
                                                      name="parking[parking_position]"
                                                      rows="2"
                                                      placeholder="例：A区画1-5番、B区画10-15番">{{ $parkingContractData['parking_position'] }}</textarea>
                                            @error('parking.parking_position')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="parking_cancellation_conditions" class="form-label">解約条件</label>
                                            <textarea class="form-control @error('parking.cancellation_conditions') is-invalid @enderror" 
                                                      id="parking_cancellation_conditions" 
                                                      name="parking[cancellation_conditions]"
                                                      rows="2"
                                                      placeholder="解約条件を入力してください">{{ $parkingContractData['cancellation_conditions'] }}</textarea>
                                            @error('parking.cancellation_conditions')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            
                                            <div class="mt-2">
                                                <label for="parking_renewal_notice_period" class="form-label">更新通知期限</label>
                                                <input type="text" 
                                                       class="form-control @error('parking.renewal_notice_period') is-invalid @enderror" 
                                                       id="parking_renewal_notice_period" 
                                                       name="parking[renewal_notice_period]"
                                                       value="{{ $parkingContractData['renewal_notice_period'] }}"
                                                       placeholder="例：契約満了日の1ヶ月前まで">
                                                @error('parking.renewal_notice_period')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- 5行目：１台あたりの金額｜使用用途 -->
                                        <div class="col-md-6 mb-3">
                                            <label for="parking_price_per_space" class="form-label">１台あたりの金額</label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       class="form-control @error('parking.price_per_space') is-invalid @enderror" 
                                                       id="parking_price_per_space" 
                                                       name="parking[price_per_space]"
                                                       value="{{ $parkingContractData['price_per_space'] }}"
                                                       placeholder="0"
                                                       min="0"
                                                       step="1">
                                                <span class="input-group-text">円</span>
                                                @error('parking.price_per_space')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="parking_usage_purpose" class="form-label">使用用途</label>
                                            <textarea class="form-control @error('parking.usage_purpose') is-invalid @enderror" 
                                                      id="parking_usage_purpose" 
                                                      name="parking[usage_purpose]"
                                                      rows="2"
                                                      placeholder="例：職員用、来客用、送迎車両用">{{ $parkingContractData['usage_purpose'] }}</textarea>
                                            @error('parking.usage_purpose')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- 6行目：空のラベル｜その他事項 -->
                                        <div class="col-md-6 mb-3">
                                            <!-- 空のラベル -->
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="parking_other_matters" class="form-label">その他事項</label>
                                            <textarea class="form-control @error('parking.other_matters') is-invalid @enderror" 
                                                      id="parking_other_matters" 
                                                      name="parking[other_matters]"
                                                      rows="3"
                                                      placeholder="その他の事項を入力してください">{{ $parkingContractData['other_matters'] }}</textarea>
                                            @error('parking.other_matters')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- 管理会社情報とオーナー情報 -->
                                    <div class="row mt-4">
                                        <!-- 管理会社情報 -->
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-building me-2"></i>管理会社情報
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label for="management_company_name" class="form-label">会社名</label>
                                                        <input type="text" 
                                                               class="form-control @error('parking.management_company_name') is-invalid @enderror" 
                                                               id="management_company_name" 
                                                               name="parking[management_company_name]"
                                                               value="{{ $managementCompanyData['company_name'] }}"
                                                               placeholder="管理会社名を入力してください">
                                                        @error('parking.management_company_name')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="management_postal_code" class="form-label">郵便番号</label>
                                                        <input type="text" 
                                                               class="form-control @error('parking.management_postal_code') is-invalid @enderror" 
                                                               id="management_postal_code" 
                                                               name="parking[management_postal_code]"
                                                               value="{{ $managementCompanyData['postal_code'] }}"
                                                               placeholder="例：123-4567">
                                                        @error('parking.management_postal_code')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="management_address" class="form-label">住所</label>
                                                        <textarea class="form-control @error('parking.management_address') is-invalid @enderror" 
                                                                  id="management_address" 
                                                                  name="parking[management_address]"
                                                                  rows="2"
                                                                  placeholder="住所を入力してください">{{ $managementCompanyData['address'] }}</textarea>
                                                        @error('parking.management_address')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="management_building_name" class="form-label">住所（建物名）</label>
                                                        <input type="text" 
                                                               class="form-control @error('parking.management_building_name') is-invalid @enderror" 
                                                               id="management_building_name" 
                                                               name="parking[management_building_name]"
                                                               value="{{ $managementCompanyData['building_name'] }}"
                                                               placeholder="建物名・部屋番号等">
                                                        @error('parking.management_building_name')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="management_phone" class="form-label">電話番号</label>
                                                        <input type="tel" 
                                                               class="form-control @error('parking.management_phone') is-invalid @enderror" 
                                                               id="management_phone" 
                                                               name="parking[management_phone]"
                                                               value="{{ $managementCompanyData['phone'] }}"
                                                               placeholder="例：03-1234-5678">
                                                        @error('parking.management_phone')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="management_fax" class="form-label">FAX番号</label>
                                                        <input type="tel" 
                                                               class="form-control @error('parking.management_fax') is-invalid @enderror" 
                                                               id="management_fax" 
                                                               name="parking[management_fax]"
                                                               value="{{ $managementCompanyData['fax'] }}"
                                                               placeholder="例：03-1234-5679">
                                                        @error('parking.management_fax')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="management_email" class="form-label">メールアドレス</label>
                                                        <input type="email" 
                                                               class="form-control @error('parking.management_email') is-invalid @enderror" 
                                                               id="management_email" 
                                                               name="parking[management_email]"
                                                               value="{{ $managementCompanyData['email'] }}"
                                                               placeholder="例：info@example.com">
                                                        @error('parking.management_email')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="management_url" class="form-label">URL</label>
                                                        <input type="url" 
                                                               class="form-control @error('parking.management_url') is-invalid @enderror" 
                                                               id="management_url" 
                                                               name="parking[management_url]"
                                                               value="{{ $managementCompanyData['url'] }}"
                                                               placeholder="例：https://www.example.com">
                                                        @error('parking.management_url')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="management_notes" class="form-label">備考</label>
                                                        <textarea class="form-control @error('parking.management_notes') is-invalid @enderror" 
                                                                  id="management_notes" 
                                                                  name="parking[management_notes]"
                                                                  rows="3"
                                                                  placeholder="備考を入力してください">{{ $managementCompanyData['notes'] }}</textarea>
                                                        @error('parking.management_notes')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- オーナー情報 -->
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-user me-2"></i>オーナー情報
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label for="owner_name" class="form-label">氏名</label>
                                                        <input type="text" 
                                                               class="form-control @error('parking.owner_name') is-invalid @enderror" 
                                                               id="owner_name" 
                                                               name="parking[owner_name]"
                                                               value="{{ $ownerData['name'] }}"
                                                               placeholder="オーナー氏名を入力してください">
                                                        @error('parking.owner_name')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="owner_postal_code" class="form-label">郵便番号</label>
                                                        <input type="text" 
                                                               class="form-control @error('parking.owner_postal_code') is-invalid @enderror" 
                                                               id="owner_postal_code" 
                                                               name="parking[owner_postal_code]"
                                                               value="{{ $ownerData['postal_code'] }}"
                                                               placeholder="例：123-4567">
                                                        @error('parking.owner_postal_code')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="owner_address" class="form-label">住所</label>
                                                        <textarea class="form-control @error('parking.owner_address') is-invalid @enderror" 
                                                                  id="owner_address" 
                                                                  name="parking[owner_address]"
                                                                  rows="2"
                                                                  placeholder="住所を入力してください">{{ $ownerData['address'] }}</textarea>
                                                        @error('parking.owner_address')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="owner_building_name" class="form-label">住所（建物名）</label>
                                                        <input type="text" 
                                                               class="form-control @error('parking.owner_building_name') is-invalid @enderror" 
                                                               id="owner_building_name" 
                                                               name="parking[owner_building_name]"
                                                               value="{{ $ownerData['building_name'] }}"
                                                               placeholder="建物名・部屋番号等">
                                                        @error('parking.owner_building_name')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="owner_phone" class="form-label">電話番号</label>
                                                        <input type="tel" 
                                                               class="form-control @error('parking.owner_phone') is-invalid @enderror" 
                                                               id="owner_phone" 
                                                               name="parking[owner_phone]"
                                                               value="{{ $ownerData['phone'] }}"
                                                               placeholder="例：03-1234-5678">
                                                        @error('parking.owner_phone')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="owner_fax" class="form-label">FAX番号</label>
                                                        <input type="tel" 
                                                               class="form-control @error('parking.owner_fax') is-invalid @enderror" 
                                                               id="owner_fax" 
                                                               name="parking[owner_fax]"
                                                               value="{{ $ownerData['fax'] }}"
                                                               placeholder="例：03-1234-5679">
                                                        @error('parking.owner_fax')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="owner_email" class="form-label">メールアドレス</label>
                                                        <input type="email" 
                                                               class="form-control @error('parking.owner_email') is-invalid @enderror" 
                                                               id="owner_email" 
                                                               name="parking[owner_email]"
                                                               value="{{ $ownerData['email'] }}"
                                                               placeholder="例：owner@example.com">
                                                        @error('parking.owner_email')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="owner_url" class="form-label">URL</label>
                                                        <input type="url" 
                                                               class="form-control @error('parking.owner_url') is-invalid @enderror" 
                                                               id="owner_url" 
                                                               name="parking[owner_url]"
                                                               value="{{ $ownerData['url'] }}"
                                                               placeholder="例：https://www.example.com">
                                                        @error('parking.owner_url')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="owner_notes" class="form-label">備考</label>
                                                        <textarea class="form-control @error('parking.owner_notes') is-invalid @enderror" 
                                                                  id="owner_notes" 
                                                                  name="parking[owner_notes]"
                                                                  rows="3"
                                                                  placeholder="備考を入力してください">{{ $ownerData['notes'] }}</textarea>
                                                        @error('parking.owner_notes')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 保存・キャンセルボタン -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('facilities.show', $facility) }}#contracts" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>キャンセル
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@vite(['resources/css/pages/facilities.css'])

<style>
/* Contracts Styles */
.contracts-container {
    margin-top: 1rem;
}

.contracts-subtabs {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 1.5rem;
}

.contracts-subtabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    background: none;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    margin-bottom: -2px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.contracts-subtabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.contracts-subtabs .nav-link.active {
    color: #6f42c1;
    border-bottom-color: #6f42c1;
    background-color: transparent;
    font-weight: 600;
}

.contracts-edit-container .contracts-subtabs .nav-link.active {
    color: #6f42c1;
    border-bottom-color: #6f42c1;
    background-color: transparent;
}

.contracts-container .card {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.4s ease, transform 0.4s ease;
}

.contracts-container .card.animate-in {
    opacity: 1;
    transform: translateY(0);
}

#others .card-header {
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
}

#meal-service .card-header {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
}

#parking .card-header {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle active sub-tab from session or controller parameter
    @if(session('activeSubTab'))
        const activeSubTab = '{{ session('activeSubTab') }}';
    @elseif(isset($activeSubTab))
        const activeSubTab = '{{ $activeSubTab }}';
    @else
        const activeSubTab = 'others';
    @endif
    
    if (activeSubTab && activeSubTab !== 'others') {
        const subTabButton = document.getElementById(activeSubTab + '-edit-tab');
        const subTabPane = document.getElementById(activeSubTab + '-edit');
        
        if (subTabButton && subTabPane) {
            // Remove active class from current active sub-tab
            document.querySelectorAll('#contractsEditTabs .nav-link.active').forEach(tab => {
                tab.classList.remove('active');
                tab.setAttribute('aria-selected', 'false');
            });
            document.querySelectorAll('#contractsEditTabContent .tab-pane.active').forEach(pane => {
                pane.classList.remove('active', 'show');
            });
            
            // Activate the target sub-tab
            subTabButton.classList.add('active');
            subTabButton.setAttribute('aria-selected', 'true');
            subTabPane.classList.add('active', 'show');
        }
    }

    // Animate cards when tabs are shown and update active sub tab
    const subTabs = document.querySelectorAll('#contractsEditTabs .nav-link');
    const activeSubTabField = document.getElementById('activeSubTabField');
    
    subTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            const targetId = event.target.getAttribute('data-bs-target');
            const targetPane = document.querySelector(targetId);
            
            // Update active sub tab field
            let subTabName = 'others';
            if (targetId === '#meal-service-edit') {
                subTabName = 'meal-service';
            } else if (targetId === '#parking-edit') {
                subTabName = 'parking';
            }
            
            if (activeSubTabField) {
                activeSubTabField.value = subTabName;
            }
            
            if (targetPane) {
                const cards = targetPane.querySelectorAll('.card');
                cards.forEach((card, index) => {
                    card.style.animationDelay = `${index * 0.1}s`;
                    card.classList.add('animate-in');
                });
            }
        });
    });

    // Initial animation for active tab
    setTimeout(() => {
        const activePane = document.querySelector('#contractsEditTabContent .tab-pane.active');
        if (activePane) {
            const cards = activePane.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate-in');
            });
        }
    }, 100);
});

// 自動更新詳細フィールドの表示制御
function toggleAutoRenewalDetails() {
    const autoRenewalSelect = document.getElementById('meal_service_auto_renewal');
    const detailsContainer = document.getElementById('auto_renewal_details_container');
    const detailsLabel = document.getElementById('auto_renewal_details_label');
    const detailsTextarea = document.getElementById('meal_service_auto_renewal_details');
    
    if (!autoRenewalSelect || !detailsContainer || !detailsLabel || !detailsTextarea) {
        return;
    }
    
    const selectedValue = autoRenewalSelect.value;
    
    if (selectedValue === 'あり' || selectedValue === 'なし') {
        detailsContainer.style.display = 'block';
        
        if (selectedValue === 'なし') {
            detailsLabel.textContent = '更新条件等';
            detailsTextarea.placeholder = '例：契約満了時に双方協議の上更新、1年毎に契約見直し';
        } else if (selectedValue === 'あり') {
            detailsLabel.textContent = '自動更新の詳細条件';
            detailsTextarea.placeholder = '例：1年毎自動更新、3ヶ月前通知で解約可能';
        }
    } else {
        detailsContainer.style.display = 'none';
        detailsTextarea.value = '';
    }
}

// ページ読み込み時に初期状態を設定
document.addEventListener('DOMContentLoaded', function() {
    toggleAutoRenewalDetails();
    
    // サブタブの切り替えを追跡
    const subTabs = document.querySelectorAll('#contractsEditTabs .nav-link');
    const activeSubTabField = document.getElementById('activeSubTabField');
    const backButton = document.querySelector('.facility-actions a');
    
    // 初期のアクティブサブタブに基づいて戻るボタンのURLを設定
    updateBackButtonUrl();
    
    subTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            const targetId = event.target.getAttribute('data-bs-target');
            let subTabName = 'others';
            
            if (targetId === '#meal-service-edit') {
                subTabName = 'meal-service';
            } else if (targetId === '#parking-edit') {
                subTabName = 'parking';
            }
            
            // 隠しフィールドを更新
            if (activeSubTabField) {
                activeSubTabField.value = subTabName;
            }
            
            // 戻るボタンのURLを更新
            updateBackButtonUrl(subTabName);
        });
    });
    
    function updateBackButtonUrl(subTab = null) {
        if (!backButton) return;
        
        const currentSubTab = subTab || (activeSubTabField ? activeSubTabField.value : 'others');
        const baseUrl = '{{ route("facilities.show", $facility) }}#contracts';
        
        // サブタブ情報をURLフラグメントに追加
        const newUrl = baseUrl + (currentSubTab !== 'others' ? '-' + currentSubTab : '');
        backButton.href = newUrl;
    }
});
</script>
