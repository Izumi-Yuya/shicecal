<!-- 契約書管理 -->
<div class="contracts-container">
    <!-- ドキュメント管理ボタン -->
    <div class="unified-contract-documents-section mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <i class="fas fa-folder text-primary me-2"></i>契約書関連ドキュメント
            </h5>
            <button type="button" 
                    class="btn btn-primary btn-sm" 
                    id="open-contract-documents-modal-btn"
                    data-bs-toggle="modal" 
                    data-bs-target="#contract-documents-modal">
                <i class="fas fa-folder-open me-1"></i>
                <span>ドキュメント</span>
            </button>
        </div>
    </div>

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
            </div>

            <!-- 駐車場契約書テーブル -->
            <div class="table-responsive mb-3">
                <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                    <tbody>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">駐車場名</td>
                            <td class="detail-value {{ empty($parkingContractData['parking_name']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 37% !important;">
                                {{ $parkingContractData['parking_name'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">契約開始日</td>
                            <td class="detail-value {{ empty($parkingContractData['contract_start_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 35% !important;">
                                @if($parkingContractData['contract_start_date'])
                                    {{ \Carbon\Carbon::parse($parkingContractData['contract_start_date'])->format('Y年m月d日') }}
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">駐車場所在地</td>
                            <td class="detail-value {{ empty($parkingContractData['parking_location']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 37% !important;">
                                {{ $parkingContractData['parking_location'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">契約終了日</td>
                            <td class="detail-value {{ empty($parkingContractData['contract_end_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 35% !important;">
                                @if($parkingContractData['contract_end_date'])
                                    {{ \Carbon\Carbon::parse($parkingContractData['contract_end_date'])->format('Y年m月d日') }}
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">台数</td>
                            <td class="detail-value {{ empty($parkingContractData['parking_spaces']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 37% !important;">
                                @if($parkingContractData['parking_spaces'])
                                {{ $parkingContractData['parking_spaces'] }}台
                                @else
                                    未設定
                                @endif
                            </td>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">自動更新の有無</td>
                            <td class="detail-value {{ empty($parkingContractData['auto_renewal']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 35% !important;">
                                {{ $parkingContractData['auto_renewal'] ?: '未設定' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">停車位置</td>
                            <td class="detail-value {{ empty($parkingContractData['parking_position']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 37% !important;">
                                {{ $parkingContractData['parking_position'] ?: '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">解約条件・更新通知期限</td>
                            <td class="detail-value {{ empty($parkingContractData['cancellation_conditions']) && empty($parkingContractData['renewal_notice_period']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 35% !important;">
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
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">1台あたりの金額</td>
                            <td class="detail-value {{ empty($parkingContractData['price_per_space']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 37% !important;">
                                @if($parkingContractData['price_per_space'])
                                    {{ number_format($parkingContractData['price_per_space']) }}円
                                @else
                                    未設定
                                @endif
                            </td>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">使用用途</td>
                            <td class="detail-value {{ empty($parkingContractData['usage_purpose']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 35% !important;">
                                {{ $parkingContractData['usage_purpose'] ?: '未設定' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;"></td>
                            <td class="detail-value" style="padding: 0.5rem; width: 37% !important;"></td>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">その他事項</td>
                            <td class="detail-value {{ empty($parkingContractData['other_matters']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 35% !important;">
                                {{ $parkingContractData['other_matters'] ?: '未設定' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem; width: 14% !important;">備考</td>
                            <td class="detail-value {{ empty($parkingContractData['remarks']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 86% !important;" colspan="3">
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
                                    <td class="detail-label" style="padding: 0.5rem; width: 29% !important;">会社名</td>
                                    <td class="detail-value {{ empty($managementCompanyData['company_name']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 71% !important;">
                                        {{ $managementCompanyData['company_name'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29% !important;">郵便番号</td>
                                    <td class="detail-value {{ empty($managementCompanyData['postal_code']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 71% !important;">
                                        {{ $managementCompanyData['postal_code'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29% !important;">住所</td>
                                    <td class="detail-value {{ empty($managementCompanyData['address']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 71% !important;">
                                        {{ $managementCompanyData['address'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29% !important;">住所（建物名）</td>
                                    <td class="detail-value {{ empty($managementCompanyData['building_name']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 71% !important;">
                                        {{ $managementCompanyData['building_name'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29% !important;">電話番号</td>
                                    <td class="detail-value {{ empty($managementCompanyData['phone']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 71% !important;">
                                        {{ $managementCompanyData['phone'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29% !important;">FAX番号</td>
                                    <td class="detail-value {{ empty($managementCompanyData['fax']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 71% !important;">
                                        {{ $managementCompanyData['fax'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29% !important;">メールアドレス</td>
                                    <td class="detail-value {{ empty($managementCompanyData['email']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 71% !important;">
                                        @if($managementCompanyData['email'])
                                        <a href="mailto:{{ $managementCompanyData['email'] }}" class="text-decoration-none">{{ $managementCompanyData['email'] }}</a>
                                        @else
                                        未設定
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29% !important;">URL</td>
                                    <td class="detail-value {{ empty($managementCompanyData['url']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 71% !important;">
                                        @if($managementCompanyData['url'])
                                        <a href="{{ $managementCompanyData['url'] }}" target="_blank" class="text-decoration-none">{{ $managementCompanyData['url'] }}</a>
                                        @else
                                        未設定
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29% !important;">備考</td>
                                    <td class="detail-value {{ empty($managementCompanyData['notes']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 71% !important;">
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
                                    <td class="detail-label" style="padding: 0.5rem; width: 29.5% !important;">氏名</td>
                                    <td class="detail-value {{ empty($ownerData['name']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 70.5% !important;">
                                        {{ $ownerData['name'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29.5% !important;">郵便番号</td>
                                    <td class="detail-value {{ empty($ownerData['postal_code']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 70.5% !important;">
                                        {{ $ownerData['postal_code'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29.5% !important;">住所</td>
                                    <td class="detail-value {{ empty($ownerData['address']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 70.5% !important;">
                                        {{ $ownerData['address'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29.5% !important;">住所（建物名）</td>
                                    <td class="detail-value {{ empty($ownerData['building_name']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 70.5% !important;">
                                        {{ $ownerData['building_name'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29.5% !important;">電話番号</td>
                                    <td class="detail-value {{ empty($ownerData['phone']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 70.5% !important;">
                                        {{ $ownerData['phone'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29.5% !important;">FAX番号</td>
                                    <td class="detail-value {{ empty($ownerData['fax']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 70.5% !important;">
                                        {{ $ownerData['fax'] ?: '未設定' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29.5% !important;">メールアドレス</td>
                                    <td class="detail-value {{ empty($ownerData['email']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 70.5% !important;">
                                        @if($ownerData['email'])
                                            <a href="mailto:{{ $ownerData['email'] }}" class="text-decoration-none">{{ $ownerData['email'] }}</a>
                                        @else
                                            未設定
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29.5% !important;">URL</td>
                                    <td class="detail-value {{ empty($ownerData['url']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 70.5% !important;">
                                        @if($ownerData['url'])
                                            <a href="{{ $ownerData['url'] }}" target="_blank" class="text-decoration-none">{{ $ownerData['url'] }}</a>
                                        @else
                                            未設定
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding: 0.5rem; width: 29.5% !important;">備考</td>
                                    <td class="detail-value {{ empty($ownerData['notes']) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 70.5% !important;">
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

    // 統一ドキュメントセクションの折りたたみボタンテキスト変更
    const unifiedToggleBtn = document.getElementById('unified-documents-toggle');
    const unifiedSection = document.getElementById('unified-documents-section');
    
    if (unifiedToggleBtn && unifiedSection) {
        unifiedSection.addEventListener('show.bs.collapse', function() {
            const icon = unifiedToggleBtn.querySelector('i');
            const text = unifiedToggleBtn.querySelector('span');
            if (icon) icon.className = 'fas fa-folder me-1';
            if (text) text.textContent = 'ドキュメントを非表示';
        });
        
        unifiedSection.addEventListener('hide.bs.collapse', function() {
            const icon = unifiedToggleBtn.querySelector('i');
            const text = unifiedToggleBtn.querySelector('span');
            if (icon) icon.className = 'fas fa-folder-open me-1';
            if (text) text.textContent = 'ドキュメントを表示';
        });
    }

    // ===== Modal hoisting & z-index fix for unified contract document section =====
    function hoistModals(container) {
        if (!container) return;
        container.querySelectorAll('.modal').forEach(function(modal) {
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
        });
    }

    // Hoist modals for unified document section
    const unifiedDocumentSection = document.getElementById('unified-documents-section');
    
    if (unifiedDocumentSection) {
        // Initial hoisting
        hoistModals(unifiedDocumentSection);
        
        // Hoist on collapse shown
        unifiedDocumentSection.addEventListener('shown.bs.collapse', function() {
            hoistModals(unifiedDocumentSection);
        });
    }

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

{{--
 契約書ドキュメント管理モーダル --}}
<div class="modal fade" id="contract-documents-modal" tabindex="-1" aria-labelledby="contract-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="contract-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>契約書ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body p-0">
                <x-contract-document-manager 
                    :facility="$facility" 
                    categoryName="契約書"
                />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>閉じる
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* 契約書ドキュメント管理モーダルのスタイル */
#contract-documents-modal .modal-dialog {
    max-width: 90%;
    margin: 1.75rem auto;
}

#contract-documents-modal .modal-body {
    min-height: 500px;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

#contract-documents-modal .document-management {
    padding: 1.5rem;
}

/* モーダル内のドキュメント一覧の高さ調整 */
#contract-documents-modal .document-list-container {
    min-height: 400px;
}

/* ===== z-indexの調整 - 最優先 ===== */

/* メインモーダル */
#contract-documents-modal {
    z-index: 9999 !important;
}

/* メインモーダルのバックドロップ */
.modal-backdrop.show {
    z-index: 9998 !important;
}

/* ネストされたモーダル（フォルダ作成、ファイルアップロード等） */
#create-folder-modal-contracts,
#upload-file-modal-contracts,
#rename-modal-contracts,
#properties-modal-contracts {
    z-index: 10000 !important;
}

/* ネストされたモーダルのバックドロップ */
#create-folder-modal-contracts + .modal-backdrop,
#upload-file-modal-contracts + .modal-backdrop,
#rename-modal-contracts + .modal-backdrop,
#properties-modal-contracts + .modal-backdrop {
    z-index: 9999 !important;
}

/* モーダル内の要素が操作可能であることを保証 */
.modal .modal-content {
    position: relative;
    z-index: 1;
}

.modal .modal-header,
.modal .modal-body,
.modal .modal-footer {
    position: relative;
    z-index: 1;
}

/* モーダル内のボタンやフォーム要素が操作可能であることを保証 */
.modal button,
.modal input,
.modal select,
.modal textarea,
.modal a,
.modal label {
    pointer-events: auto !important;
    position: relative;
}

/* コンテキストメニュー */
.context-menu {
    z-index: 10001 !important;
}

/* ドキュメント一覧のテーブル行が操作可能であることを保証 */
#contract-documents-modal .document-item {
    pointer-events: auto !important;
}

#contract-documents-modal .document-item * {
    pointer-events: auto !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contractDocumentsModal = document.getElementById('contract-documents-modal');
    
    if (contractDocumentsModal) {
        // ===== Modal hoisting: Move modal to body to avoid z-index issues =====
        if (contractDocumentsModal.parentElement !== document.body) {
            console.log('[ContractDoc] Hoisting modal to body');
            document.body.appendChild(contractDocumentsModal);
        }
        
        // モーダルが開かれたときにドキュメントを読み込む
        contractDocumentsModal.addEventListener('shown.bs.modal', function() {
            console.log('[ContractDoc] Modal opened, loading documents');
            
            // ContractDocumentManagerのインスタンスを取得または作成
            if (window.contractDocManager) {
                // 既にインスタンスが存在する場合は、ドキュメントを再読み込み
                if (typeof window.contractDocManager.loadDocuments === 'function') {
                    window.contractDocManager.loadDocuments();
                }
            } else {
                // インスタンスが存在しない場合は、少し待ってから再試行
                console.log('[ContractDoc] Manager not found, waiting for initialization');
                setTimeout(function() {
                    if (window.contractDocManager && typeof window.contractDocManager.loadDocuments === 'function') {
                        window.contractDocManager.loadDocuments();
                    }
                }, 500);
            }
        });
        
        // モーダルが閉じられたときの処理
        contractDocumentsModal.addEventListener('hidden.bs.modal', function() {
            console.log('[ContractDoc] Modal closed');
        });
    }
    
    // ===== Modal z-index enforcement =====
    document.addEventListener('show.bs.modal', function(ev) {
        var modalEl = ev.target;
        if (modalEl) {
            // メインモーダル
            if (modalEl.id === 'contract-documents-modal') {
                modalEl.style.zIndex = '9999';
                setTimeout(function() {
                    var backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(function(bd) {
                        bd.style.zIndex = '9998';
                    });
                }, 0);
            }
            // ネストされたモーダル
            else if (modalEl.id && modalEl.id.includes('contracts')) {
                modalEl.style.zIndex = '10000';
                setTimeout(function() {
                    var backdrops = document.querySelectorAll('.modal-backdrop');
                    var lastBackdrop = backdrops[backdrops.length - 1];
                    if (lastBackdrop) {
                        lastBackdrop.style.zIndex = '9999';
                    }
                }, 0);
            }
        }
    });
    
    // ===== Cleanup extra backdrops =====
    document.addEventListener('hidden.bs.modal', function(ev) {
        if (ev.target && ev.target.id === 'contract-documents-modal') {
            setTimeout(function() {
                var backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 1) {
                    for (var i = 0; i < backdrops.length - 1; i++) {
                        backdrops[i].parentNode.removeChild(backdrops[i]);
                    }
                }
            }, 100);
        }
    });
});
</script>
