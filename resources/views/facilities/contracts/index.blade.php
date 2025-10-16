<!-- 契約書管理 -->
<div class="contracts-container">
    <!-- サブタブナビゲーション -->
    <div class="contracts-subtabs">
        <ul class="nav nav-tabs" id="contractsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="meal-service-tab" data-bs-toggle="tab" data-bs-target="#meal-service" type="button" role="tab" aria-controls="meal-service" aria-selected="true">
                    <i class="fas fa-utensils me-2"></i>給食
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="parking-tab" data-bs-toggle="tab" data-bs-target="#parking" type="button" role="tab" aria-controls="parking" aria-selected="false">
                    <i class="fas fa-parking me-2"></i>駐車場
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="others-tab" data-bs-toggle="tab" data-bs-target="#others" type="button" role="tab" aria-controls="others" aria-selected="false">
                    <i class="fas fa-file-alt me-2"></i>その他
                </button>
            </li>
        </ul>
    </div>

    <!-- サブタブコンテンツ -->
    <div class="tab-content" id="contractsTabContent">
        <!-- その他契約書 -->
        <div class="tab-pane fade" id="others" role="tabpanel" aria-labelledby="others-tab">
            @php
                // データベースから保存されたデータを取得
                $othersData = $contractsData['others'] ?? [];
                $othersContractData = [
                    'company_name' => $othersData['company_name'] ?? '',
                    'contract_type' => $othersData['contract_type'] ?? '',
                    'contract_content' => $othersData['contract_content'] ?? '',
                    'auto_renewal' => $othersData['auto_renewal'] ?? '',
                    'auto_renewal_details' => $othersData['auto_renewal_details'] ?? '',
                    'contract_start_date' => $othersData['contract_start_date'] ?? '',
                    'cancellation_conditions' => $othersData['cancellation_conditions'] ?? '',
                    'renewal_notice_period' => $othersData['renewal_notice_period'] ?? '',
                    'contract_end_date' => $othersData['contract_end_date'] ?? '',
                    'other_matters' => $othersData['other_matters'] ?? '',
                    'amount' => $othersData['amount'] ?? '',
                    'contact_info' => $othersData['contact_info'] ?? '',
                    'remarks' => $othersData['remarks'] ?? ''
                ];
            @endphp

            <!-- サブタブヘッダー -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt text-secondary me-2"></i>その他契約書
                </h5>
                @if(auth()->user()->canEditFacility($facility->id))
                    <a href="{{ route('facilities.contracts.edit', ['facility' => $facility, 'sub_tab' => 'others']) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-2"></i>編集
                    </a>
                @endif
            </div>

            <!-- ドキュメント管理セクション -->
            <div class="contract-documents-section mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="fas fa-folder text-primary me-2"></i>関連ドキュメント
                    </h6>
                    <button type="button" 
                            class="btn btn-outline-primary btn-sm contract-documents-toggle" 
                            id="others-documents-toggle"
                            data-bs-toggle="collapse" 
                            data-bs-target="#others-documents-section" 
                            aria-expanded="false" 
                            aria-controls="others-documents-section">
                        <i class="fas fa-folder-open me-1"></i>
                        <span>ドキュメントを表示</span>
                    </button>
                </div>
                
                <div class="collapse" id="others-documents-section">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-folder-open me-2"></i>その他契約書関連ドキュメント
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <x-contract-document-manager 
                                :facility="$facility" 
                                category="others"
                                categoryName="その他契約書"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- その他契約書テーブル -->
            <div class="table-responsive mb-3">
                <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                    <tbody>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">会社名</td>
                            <td class="detail-value {{ empty($othersContractData['company_name']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $othersContractData['company_name'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">契約書の種類</td>
                            <td class="detail-value {{ empty($othersContractData['contract_type']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $othersContractData['contract_type'] ?: '未設定' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">契約内容</td>
                            <td class="detail-value {{ empty($othersContractData['contract_content']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $othersContractData['contract_content'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">自動更新の有無</td>
                            <td class="detail-value {{ empty($othersContractData['auto_renewal']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($othersContractData['auto_renewal'])
                                    <div>{{ $othersContractData['auto_renewal'] }}</div>
                                    @if($othersContractData['auto_renewal_details'])
                                        <small class="text-muted">（詳細: {{ $othersContractData['auto_renewal_details'] }}）</small>
                                    @endif
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">契約開始日</td>
                            <td class="detail-value {{ empty($othersContractData['contract_start_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($othersContractData['contract_start_date'])
                                    {{ \Carbon\Carbon::parse($othersContractData['contract_start_date'])->format('Y年m月d日') }}
                                @else
                                    未設定
                                @endif
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">解約・更新条件</td>
                            <td class="detail-value {{ empty($othersContractData['cancellation_conditions']) && empty($othersContractData['renewal_notice_period']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($othersContractData['cancellation_conditions'] || $othersContractData['renewal_notice_period'])
                                    @if($othersContractData['cancellation_conditions'])
                                        <div><strong>解約条件:</strong> {{ $othersContractData['cancellation_conditions'] }}</div>
                                    @endif
                                    @if($othersContractData['renewal_notice_period'])
                                        <div><strong>更新通知期限:</strong> {{ $othersContractData['renewal_notice_period'] }}</div>
                                    @endif
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">契約終了日</td>
                            <td class="detail-value {{ empty($othersContractData['contract_end_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($othersContractData['contract_end_date'])
                                    {{ \Carbon\Carbon::parse($othersContractData['contract_end_date'])->format('Y年m月d日') }}
                                @else
                                    未設定
                                @endif
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">その他事項</td>
                            <td class="detail-value {{ empty($othersContractData['other_matters']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $othersContractData['other_matters'] ?: '未設定' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">金額</td>
                            <td class="detail-value {{ empty($othersContractData['amount']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($othersContractData['amount'])
                                    {{ number_format($othersContractData['amount']) }}円
                                @else
                                    未設定
                                @endif
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;"></td>
                            <td class="detail-value" style="padding: 0.5rem;"></td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">連絡先</td>
                            <td class="detail-value {{ empty($othersContractData['contact_info']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $othersContractData['contact_info'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;"></td>
                            <td class="detail-value" style="padding: 0.5rem;"></td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">備考</td>
                            <td class="detail-value {{ empty($othersContractData['remarks']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="3">
                                {{ $othersContractData['remarks'] ?: '未設定' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 給食契約書 -->
        <div class="tab-pane fade show active" id="meal-service" role="tabpanel" aria-labelledby="meal-service-tab">
            @php
                // データベースから保存されたデータを取得
                $mealServiceData = $contractsData['meal_service'] ?? [];
                $mealServiceContractData = [
                    'company_name' => $mealServiceData['company_name'] ?? '',
                    'management_fee' => $mealServiceData['management_fee'] ?? '',
                    'contract_content' => $mealServiceData['contract_content'] ?? '',
                    'breakfast_price' => $mealServiceData['breakfast_price'] ?? '',
                    'contract_start_date' => $mealServiceData['contract_start_date'] ?? '',
                    'lunch_price' => $mealServiceData['lunch_price'] ?? '',
                    'contract_type' => $mealServiceData['contract_type'] ?? '',
                    'dinner_price' => $mealServiceData['dinner_price'] ?? '',
                    'auto_renewal' => $mealServiceData['auto_renewal'] ?? '',
                    'auto_renewal_details' => $mealServiceData['auto_renewal_details'] ?? '',
                    'snack_price' => $mealServiceData['snack_price'] ?? '',
                    'cancellation_conditions' => $mealServiceData['cancellation_conditions'] ?? '',
                    'event_meal_price' => $mealServiceData['event_meal_price'] ?? '',
                    'renewal_notice_period' => $mealServiceData['renewal_notice_period'] ?? '',
                    'staff_meal_price' => $mealServiceData['staff_meal_price'] ?? '',
                    'other_matters' => $mealServiceData['other_matters'] ?? '',
                    'remarks' => $mealServiceData['remarks'] ?? ''
                ];
            @endphp

            <!-- サブタブヘッダー -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-utensils text-success me-2"></i>給食契約書
                </h5>
                @if(auth()->user()->canEditFacility($facility->id))
                    <a href="{{ route('facilities.contracts.edit', ['facility' => $facility, 'sub_tab' => 'meal-service']) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-2"></i>編集
                    </a>
                @endif
            </div>

            <!-- ドキュメント管理セクション -->
            <div class="contract-documents-section mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="fas fa-folder text-success me-2"></i>関連ドキュメント
                    </h6>
                    <button type="button" 
                            class="btn btn-outline-success btn-sm contract-documents-toggle" 
                            id="meal-service-documents-toggle"
                            data-bs-toggle="collapse" 
                            data-bs-target="#meal-service-documents-section" 
                            aria-expanded="false" 
                            aria-controls="meal-service-documents-section">
                        <i class="fas fa-folder-open me-1"></i>
                        <span>ドキュメントを表示</span>
                    </button>
                </div>
                
                <div class="collapse" id="meal-service-documents-section">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-folder-open me-2"></i>給食契約書関連ドキュメント
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <x-contract-document-manager 
                                :facility="$facility" 
                                category="meal_service"
                                categoryName="給食契約書"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- 給食契約書テーブル -->
            <div class="table-responsive mb-3">
                <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                    <tbody>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">会社名</td>
                            <td class="detail-value {{ empty($mealServiceContractData['company_name']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $mealServiceContractData['company_name'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">管理費</td>
                            <td class="detail-value {{ empty($mealServiceContractData['management_fee']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($mealServiceContractData['management_fee'])
                                    {{ number_format($mealServiceContractData['management_fee']) }}円
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">契約内容</td>
                            <td class="detail-value {{ empty($mealServiceContractData['contract_content']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $mealServiceContractData['contract_content'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">食単価　朝食</td>
                            <td class="detail-value {{ empty($mealServiceContractData['breakfast_price']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($mealServiceContractData['breakfast_price'])
                                    {{ number_format($mealServiceContractData['breakfast_price']) }}円
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">契約開始日</td>
                            <td class="detail-value {{ empty($mealServiceContractData['contract_start_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($mealServiceContractData['contract_start_date'])
                                    {{ \Carbon\Carbon::parse($mealServiceContractData['contract_start_date'])->format('Y年m月d日') }}
                                @else
                                    未設定
                                @endif
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">　　　　昼食</td>
                            <td class="detail-value {{ empty($mealServiceContractData['lunch_price']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($mealServiceContractData['lunch_price'])
                                    {{ number_format($mealServiceContractData['lunch_price']) }}円
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">契約書の種類</td>
                            <td class="detail-value {{ empty($mealServiceContractData['contract_type']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $mealServiceContractData['contract_type'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">　　　　夕食</td>
                            <td class="detail-value {{ empty($mealServiceContractData['dinner_price']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($mealServiceContractData['dinner_price'])
                                    {{ number_format($mealServiceContractData['dinner_price']) }}円
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">自動更新の有無</td>
                            <td class="detail-value {{ empty($mealServiceContractData['auto_renewal']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($mealServiceContractData['auto_renewal'])
                                    <div>{{ $mealServiceContractData['auto_renewal'] }}</div>
                                    @if($mealServiceContractData['auto_renewal_details'] ?? false)
                                        <small class="text-muted">（詳細: {{ $mealServiceContractData['auto_renewal_details'] }}）</small>
                                    @endif
                                @else
                                    未設定
                                @endif
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">　　　　おやつ</td>
                            <td class="detail-value {{ empty($mealServiceContractData['snack_price']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($mealServiceContractData['snack_price'])
                                    {{ number_format($mealServiceContractData['snack_price']) }}円
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">解約・更新条件</td>
                            <td class="detail-value {{ empty($mealServiceContractData['cancellation_conditions']) && empty($mealServiceContractData['renewal_notice_period']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($mealServiceContractData['cancellation_conditions'] || $mealServiceContractData['renewal_notice_period'])
                                    @if($mealServiceContractData['cancellation_conditions'])
                                        <div><strong>解約条件:</strong> {{ $mealServiceContractData['cancellation_conditions'] }}</div>
                                    @endif
                                    @if($mealServiceContractData['renewal_notice_period'])
                                        <div><strong>更新通知期限:</strong> {{ $mealServiceContractData['renewal_notice_period'] }}</div>
                                    @endif
                                @else
                                    未設定
                                @endif
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">　　　　行事食</td>
                            <td class="detail-value {{ empty($mealServiceContractData['event_meal_price']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($mealServiceContractData['event_meal_price'])
                                    {{ number_format($mealServiceContractData['event_meal_price']) }}円
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">その他事項</td>
                            <td class="detail-value {{ empty($mealServiceContractData['other_matters']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $mealServiceContractData['other_matters'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">　　　　職員食</td>
                            <td class="detail-value {{ empty($mealServiceContractData['staff_meal_price']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($mealServiceContractData['staff_meal_price'])
                                    {{ number_format($mealServiceContractData['staff_meal_price']) }}円
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">備考</td>
                            <td class="detail-value {{ empty($mealServiceContractData['remarks']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="3">
                                {{ $mealServiceContractData['remarks'] ?: '未設定' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 駐車場契約書 -->
        <div class="tab-pane fade" id="parking" role="tabpanel" aria-labelledby="parking-tab">
            @php
                // データベースから保存されたデータを取得
                $parkingData = $contractsData['parking'] ?? [];
                $parkingContractData = [
                    'parking_name' => $parkingData['parking_name'] ?? '',
                    'contract_start_date' => $parkingData['contract_start_date'] ?? '',
                    'parking_location' => $parkingData['parking_location'] ?? '',
                    'contract_end_date' => $parkingData['contract_end_date'] ?? '',
                    'parking_spaces' => $parkingData['parking_spaces'] ?? '',
                    'auto_renewal' => $parkingData['auto_renewal'] ?? '',
                    'parking_position' => $parkingData['parking_position'] ?? '',
                    'cancellation_conditions' => $parkingData['cancellation_conditions'] ?? '',
                    'renewal_notice_period' => $parkingData['renewal_notice_period'] ?? '',
                    'price_per_space' => $parkingData['price_per_space'] ?? '',
                    'usage_purpose' => $parkingData['usage_purpose'] ?? '',
                    'other_matters' => $parkingData['other_matters'] ?? '',
                    'remarks' => $parkingData['remarks'] ?? ''
                ];
                
                // 管理会社情報
                $managementCompanyData = [
                    'company_name' => $parkingData['management_company_name'] ?? '',
                    'postal_code' => $parkingData['management_postal_code'] ?? '',
                    'address' => $parkingData['management_address'] ?? '',
                    'building_name' => $parkingData['management_building_name'] ?? '',
                    'phone' => $parkingData['management_phone'] ?? '',
                    'fax' => $parkingData['management_fax'] ?? '',
                    'email' => $parkingData['management_email'] ?? '',
                    'url' => $parkingData['management_url'] ?? '',
                    'notes' => $parkingData['management_notes'] ?? ''
                ];
                
                // オーナー情報
                $ownerData = [
                    'name' => $parkingData['owner_name'] ?? '',
                    'postal_code' => $parkingData['owner_postal_code'] ?? '',
                    'address' => $parkingData['owner_address'] ?? '',
                    'building_name' => $parkingData['owner_building_name'] ?? '',
                    'phone' => $parkingData['owner_phone'] ?? '',
                    'fax' => $parkingData['owner_fax'] ?? '',
                    'email' => $parkingData['owner_email'] ?? '',
                    'url' => $parkingData['owner_url'] ?? '',
                    'notes' => $parkingData['owner_notes'] ?? ''
                ];
            @endphp

            <!-- サブタブヘッダー -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-parking text-primary me-2"></i>駐車場契約書
                </h5>
                @if(auth()->user()->canEditFacility($facility->id))
                    <a href="{{ route('facilities.contracts.edit', ['facility' => $facility, 'sub_tab' => 'parking']) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-2"></i>編集
                    </a>
                @endif
            </div>

            <!-- ドキュメント管理セクション -->
            <div class="contract-documents-section mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="fas fa-folder text-info me-2"></i>関連ドキュメント
                    </h6>
                    <button type="button" 
                            class="btn btn-outline-info btn-sm contract-documents-toggle" 
                            id="parking-documents-toggle"
                            data-bs-toggle="collapse" 
                            data-bs-target="#parking-documents-section" 
                            aria-expanded="false" 
                            aria-controls="parking-documents-section">
                        <i class="fas fa-folder-open me-1"></i>
                        <span>ドキュメントを表示</span>
                    </button>
                </div>
                
                <div class="collapse" id="parking-documents-section">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-folder-open me-2"></i>駐車場契約書関連ドキュメント
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <x-contract-document-manager 
                                :facility="$facility" 
                                category="parking"
                                categoryName="駐車場契約書"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- 駐車場契約書テーブル -->
            <div class="table-responsive mb-3">
                <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                    <tbody>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">駐車場名</td>
                            <td class="detail-value {{ empty($parkingContractData['parking_name']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $parkingContractData['parking_name'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">契約開始日</td>
                            <td class="detail-value {{ empty($parkingContractData['contract_start_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($parkingContractData['contract_start_date'])
                                    {{ \Carbon\Carbon::parse($parkingContractData['contract_start_date'])->format('Y年m月d日') }}
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">駐車場所在地</td>
                            <td class="detail-value {{ empty($parkingContractData['parking_location']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $parkingContractData['parking_location'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">契約終了日</td>
                            <td class="detail-value {{ empty($parkingContractData['contract_end_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($parkingContractData['contract_end_date'])
                                    {{ \Carbon\Carbon::parse($parkingContractData['contract_end_date'])->format('Y年m月d日') }}
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">台数</td>
                            <td class="detail-value {{ empty($parkingContractData['parking_spaces']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($parkingContractData['parking_spaces'])
                                    {{ $parkingContractData['parking_spaces'] }}台
                                @else
                                    未設定
                                @endif
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">自動更新の有無</td>
                            <td class="detail-value {{ empty($parkingContractData['auto_renewal']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $parkingContractData['auto_renewal'] ?: '未設定' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">停車位置</td>
                            <td class="detail-value {{ empty($parkingContractData['parking_position']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $parkingContractData['parking_position'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">解約条件・更新通知期限</td>
                            <td class="detail-value {{ empty($parkingContractData['cancellation_conditions']) && empty($parkingContractData['renewal_notice_period']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($parkingContractData['cancellation_conditions'] || $parkingContractData['renewal_notice_period'])
                                    @if($parkingContractData['cancellation_conditions'])
                                        <div><strong>解約条件:</strong> {{ $parkingContractData['cancellation_conditions'] }}</div>
                                    @endif
                                    @if($parkingContractData['renewal_notice_period'])
                                        <div><strong>更新通知期限:</strong> {{ $parkingContractData['renewal_notice_period'] }}</div>
                                    @endif
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">1台あたりの金額</td>
                            <td class="detail-value {{ empty($parkingContractData['price_per_space']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if($parkingContractData['price_per_space'])
                                    {{ number_format($parkingContractData['price_per_space']) }}円
                                @else
                                    未設定
                                @endif
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">使用用途</td>
                            <td class="detail-value {{ empty($parkingContractData['usage_purpose']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $parkingContractData['usage_purpose'] ?: '未設定' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;"></td>
                            <td class="detail-value" style="padding: 0.5rem;"></td>
                            <td class="detail-label" style="padding: 0.5rem;">その他事項</td>
                            <td class="detail-value {{ empty($parkingContractData['other_matters']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $parkingContractData['other_matters'] ?: '未設定' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">備考</td>
                            <td class="detail-value {{ empty($parkingContractData['remarks']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="3">
                                {{ $parkingContractData['remarks'] ?: '未設定' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 管理会社情報とオーナー情報 -->
            <div class="row">
                <!-- 管理会社情報 -->
                <div class="col-md-6">
                    <h6 class="mb-2">
                        <i class="fas fa-building text-secondary me-2"></i>管理会社情報
                    </h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                            <tbody>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 30%;">会社名</td>
                                    <td class="detail-value {{ empty($managementCompanyData['company_name']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $managementCompanyData['company_name'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">郵便番号</td>
                                    <td class="detail-value {{ empty($managementCompanyData['postal_code']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $managementCompanyData['postal_code'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">住所</td>
                                    <td class="detail-value {{ empty($managementCompanyData['address']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $managementCompanyData['address'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">住所（建物名）</td>
                                    <td class="detail-value {{ empty($managementCompanyData['building_name']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $managementCompanyData['building_name'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">電話番号</td>
                                    <td class="detail-value {{ empty($managementCompanyData['phone']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $managementCompanyData['phone'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">FAX番号</td>
                                    <td class="detail-value {{ empty($managementCompanyData['fax']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $managementCompanyData['fax'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">メールアドレス</td>
                                    <td class="detail-value {{ empty($managementCompanyData['email']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        @if($managementCompanyData['email'])
                                            <a href="mailto:{{ $managementCompanyData['email'] }}" class="text-decoration-none">{{ $managementCompanyData['email'] }}</a>
                                        @else
                                            未設定
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">URL</td>
                                    <td class="detail-value {{ empty($managementCompanyData['url']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        @if($managementCompanyData['url'])
                                            <a href="{{ $managementCompanyData['url'] }}" target="_blank" class="text-decoration-none">{{ $managementCompanyData['url'] }}</a>
                                        @else
                                            未設定
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">備考</td>
                                    <td class="detail-value {{ empty($managementCompanyData['notes']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $managementCompanyData['notes'] ?: '未設定' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- オーナー情報 -->
                <div class="col-md-6">
                    <h6 class="mb-2">
                        <i class="fas fa-user text-secondary me-2"></i>オーナー情報
                    </h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                            <tbody>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 30%;">氏名</td>
                                    <td class="detail-value {{ empty($ownerData['name']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $ownerData['name'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">郵便番号</td>
                                    <td class="detail-value {{ empty($ownerData['postal_code']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $ownerData['postal_code'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">住所</td>
                                    <td class="detail-value {{ empty($ownerData['address']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $ownerData['address'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">住所（建物名）</td>
                                    <td class="detail-value {{ empty($ownerData['building_name']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $ownerData['building_name'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">電話番号</td>
                                    <td class="detail-value {{ empty($ownerData['phone']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $ownerData['phone'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">FAX番号</td>
                                    <td class="detail-value {{ empty($ownerData['fax']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $ownerData['fax'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">メールアドレス</td>
                                    <td class="detail-value {{ empty($ownerData['email']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        @if($ownerData['email'])
                                            <a href="mailto:{{ $ownerData['email'] }}" class="text-decoration-none">{{ $ownerData['email'] }}</a>
                                        @else
                                            未設定
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">URL</td>
                                    <td class="detail-value {{ empty($ownerData['url']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        @if($ownerData['url'])
                                            <a href="{{ $ownerData['url'] }}" target="_blank" class="text-decoration-none">{{ $ownerData['url'] }}</a>
                                        @else
                                            未設定
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem;">備考</td>
                                    <td class="detail-value {{ empty($ownerData['notes']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                        {{ $ownerData['notes'] ?: '未設定' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 契約書サブタブのクリックイベントを監視
    const contractsSubTabs = document.querySelectorAll('#contractsTabs .nav-link');
    
    contractsSubTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            const targetId = event.target.getAttribute('data-bs-target');
            let fragmentSuffix = '';
            
            if (targetId === '#meal-service') {
                fragmentSuffix = '-meal-service';
            } else if (targetId === '#parking') {
                fragmentSuffix = '-parking';
            }
            
            // URLフラグメントを更新（履歴に追加しない）
            const newHash = '#contracts' + fragmentSuffix;
            if (window.location.hash !== newHash) {
                history.replaceState(null, null, newHash);
            }
        });
    });

    // ===== Modal hoisting & z-index fix for contract document managers =====
    function hoistModals(container) {
        if (!container) return;
        container.querySelectorAll('.modal').forEach(function(modal) {
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
        });
    }

    // Hoist modals for all contract document sections
    const documentSections = [
        document.getElementById('others-documents-section'),
        document.getElementById('meal-service-documents-section'),
        document.getElementById('parking-documents-section')
    ];

    documentSections.forEach(function(section) {
        if (section) {
            // Initial hoisting
            hoistModals(section);
            
            // Hoist on collapse shown
            section.addEventListener('shown.bs.collapse', function() {
                hoistModals(section);
            });
        }
    });

    // Modal z-index enforcement
    document.addEventListener('show.bs.modal', function(ev) {
        var modalEl = ev.target;
        if (modalEl) {
            modalEl.style.zIndex = '2010';
        }
        setTimeout(function() {
            var backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(function(bd) {
                bd.style.zIndex = '2000';
            });
        }, 0);
    });

    // Cleanup extra backdrops
    document.addEventListener('hidden.bs.modal', function() {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 1) {
            for (var i = 0; i < backdrops.length - 1; i++) {
                backdrops[i].parentNode.removeChild(backdrops[i]);
            }
        }
    });
});
</script>