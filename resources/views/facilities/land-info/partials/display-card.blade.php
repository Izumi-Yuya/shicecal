@php
    // 所有タイプのバッジ表示用データ
    $ownershipBadge = null;
    $ownershipBadgeClass = 'badge bg-primary';
    
    switch($landInfo->ownership_type) {
        case 'owned':
            $ownershipBadge = '自社';
            $ownershipBadgeClass = 'badge bg-success';
            break;
        case 'leased':
            $ownershipBadge = '賃借';
            $ownershipBadgeClass = 'badge bg-warning';
            break;
        case 'owned_rental':
            $ownershipBadge = '自社（賃貸）';
            $ownershipBadgeClass = 'badge bg-info';
            break;
        default:
            $ownershipBadge = null;
    }
    
    // 所有テーブルデータ
    $ownershipData = [
        [
            'type' => 'standard',
            'cells' => [
                [
                    'label' => '所有', 
                    'value' => $ownershipBadge, 
                    'type' => 'text',
                    'colspan' => 3,
                    'options' => ['badge_class' => $ownershipBadgeClass]
                ],
            ]
        ],
    ];
@endphp

<!-- 所有テーブル（共通デザイン適用版） -->
<style>
    div.ownership-table-wrapper {
        width: 33.33% !important;
        max-width: 33.33% !important;
        margin-bottom: 1rem !important;
        display: block !important;
    }

    div.ownership-table-wrapper .facility-info-card {
        width: 100% !important;
        max-width: 100% !important;
    }

    div.ownership-table-wrapper .facility-basic-info-table-clean {
        table-layout: fixed !important;
        width: 100% !important;
    }

    div.ownership-table-wrapper .facility-basic-info-table-clean td {
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
    }

    /* 所有テーブルのスクロールバーも無効化 */
    .ownership-table-wrapper .table-responsive,
    .ownership-table-wrapper .table-responsive-md {
        overflow-x: visible !important;
        overflow-y: visible !important;
    }

    .ownership-table-wrapper .table-responsive::-webkit-scrollbar,
    .ownership-table-wrapper .table-responsive-md::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }

        /* 基本情報テーブルの6列均等幅設定 */
    div.basic-info-table table.facility-basic-info-table-clean {
        table-layout: fixed !important;
        width: 100% !important;
    }

    div.basic-info-table table.facility-basic-info-table-clean tbody tr td {
        width: 16.6667% !important;
        min-width: 16.6667% !important;
        max-width: 16.6667% !important;
        box-sizing: border-box !important;
    }

    /* 各列を個別に指定して確実に均等にする */
    div.basic-info-table table.facility-basic-info-table-clean tbody tr td:nth-child(1) {
        width: 16.6667% !important;
    }

    div.basic-info-table table.facility-basic-info-table-clean tbody tr td:nth-child(2) {
        width: 16.6667% !important;
    }

    div.basic-info-table table.facility-basic-info-table-clean tbody tr td:nth-child(3) {
        width: 16.6667% !important;
    }

    div.basic-info-table table.facility-basic-info-table-clean tbody tr td:nth-child(4) {
        width: 16.6667% !important;
    }

    div.basic-info-table table.facility-basic-info-table-clean tbody tr td:nth-child(5) {
        width: 16.6667% !important;
    }

    div.basic-info-table table.facility-basic-info-table-clean tbody tr td:nth-child(6) {
        width: 16.6667% !important;
    }

    /* この画面でのみスクロールバーを無効化 */
    div.basic-info-table .table-responsive,
    div.basic-info-table .table-responsive-md {
        overflow-x: visible !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: auto !important;
    }

    div.basic-info-table .table-responsive::-webkit-scrollbar,
    div.basic-info-table .table-responsive-md::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }

    div.basic-info-table {
        overflow: visible !important;
    }
</style>

<div class="ownership-table-wrapper">
    <!-- 所有テーブル（共通コンポーネント使用） -->
    <x-common-table
        :data="$ownershipData"
        :showHeader="false"
        cardClass="facility-info-card detail-card-improved"
        :tableAttributes="['style' => '--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0; table-layout: fixed; width: 100%;']"
        bodyClass="p-0" />
</div>

@php
    // 基本情報テーブルデータの構築
    $basicInfoData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '敷地面積（㎡数）', 'value' => $landInfo->site_area_sqm !== null ? number_format($landInfo->site_area_sqm, 2) . '㎡' : null, 'type' => 'text'],
                ['label' => '敷地面積（坪数）', 'value' => $landInfo->site_area_tsubo !== null ? number_format($landInfo->site_area_tsubo, 2) . '坪' : null, 'type' => 'text'],
                ['label' => '敷地内駐車場台数', 'value' => $landInfo->parking_spaces !== null ? number_format($landInfo->parking_spaces) . '台' : null, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '購入金額', 'value' => $landInfo->purchase_price, 'type' => 'currency'],
                ['label' => '坪単価', 'value' => $landInfo->unit_price_per_tsubo !== null ? number_format($landInfo->unit_price_per_tsubo) . '円/坪' : null, 'type' => 'text'],
                [
                    'label' => '謄本', 
                    'value' => $landInfoFileData['registry'] ?? null, 
                    'type' => 'file_display',
                    'options' => []
                ],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '家賃', 'value' => $landInfo->monthly_rent, 'type' => 'currency'],
                ['label' => '-', 'value' => '-', 'type' => 'text'],
                [
                    'label' => '契約書・覚書', 
                    'value' => $landInfoFileData['lease_contract'] ?? null, 
                    'type' => 'file_display',
                    'options' => []
                ],
            ]
        ],
    ];
    
    // 契約期間の文字列構築
    $contractPeriod = null;
    if ($landInfo->contract_start_date && $landInfo->contract_end_date) {
        $contractPeriod = $landInfo->contract_start_date->format('Y年n月j日') . ' ～ ' . $landInfo->contract_end_date->format('Y年n月j日');
    }
    
    // 自動更新バッジ
    $autoRenewalBadge = null;
    $autoRenewalBadgeClass = 'badge bg-primary';
    if ($landInfo->auto_renewal === 'yes') {
        $autoRenewalBadge = '有り';
        $autoRenewalBadgeClass = 'badge bg-success';
    } elseif ($landInfo->auto_renewal === 'no') {
        $autoRenewalBadge = '無し';
        $autoRenewalBadgeClass = 'badge bg-secondary';
    }
    
    $basicInfoData[] = [
        'type' => 'standard',
        'cells' => [
            ['label' => '契約期間', 'value' => $contractPeriod, 'type' => 'text'],
            [
                'label' => '自動更新の有無', 
                'value' => $autoRenewalBadge, 
                'type' => 'text',
                'options' => ['badge_class' => $autoRenewalBadgeClass]
            ],
            ['label' => '契約年数', 'value' => $landInfo->contract_period_text, 'type' => 'text'],
        ]
    ];
    
    $basicInfoData[] = [
        'type' => 'standard',
        'cells' => [
            ['label' => '備考', 'value' => $landInfo->notes, 'type' => 'text', 'colspan' => 5],
        ]
    ];
@endphp

<!-- 基本情報テーブル（共通コンポーネント使用） -->
<div class="basic-info-table">
    <x-common-table
        :data="$basicInfoData"
        :showHeader="false"
        :tableAttributes="['style' => '--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0; table-layout: fixed; width: 100%;']"
        bodyClass="p-0" />
</div>

@php
    // 管理会社情報データ
    $managementCompanyData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '会社名', 'value' => $landInfo->management_company_name, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '郵便番号', 'value' => $landInfo->management_company_postal_code, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '住所', 'value' => $landInfo->management_company_address, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '住所（建物名）', 'value' => $landInfo->management_company_building, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '電話番号', 'value' => $landInfo->management_company_phone, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'FAX番号', 'value' => $landInfo->management_company_fax, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'メールアドレス', 'value' => $landInfo->management_company_email, 'type' => 'email'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'URL', 'value' => $landInfo->management_company_url, 'type' => 'url'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '備考', 'value' => $landInfo->management_company_notes, 'type' => 'text'],
            ]
        ],
    ];
    
    // オーナー情報データ
    $ownerData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '氏名', 'value' => $landInfo->owner_name, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '郵便番号', 'value' => $landInfo->owner_postal_code, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '住所', 'value' => $landInfo->owner_address, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '住所（建物名）', 'value' => $landInfo->owner_building, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '電話番号', 'value' => $landInfo->owner_phone, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'FAX番号', 'value' => $landInfo->owner_fax, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'メールアドレス', 'value' => $landInfo->owner_email, 'type' => 'email'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'URL', 'value' => $landInfo->owner_url, 'type' => 'url'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '備考', 'value' => $landInfo->owner_notes, 'type' => 'text'],
            ]
        ],
    ];
@endphp

<!-- 管理会社情報・オーナー情報テーブル -->
<style>
    .row .col-md-6 .facility-info-card.detail-card-improved .card-header {
        background: #f8f9fa !important;
        background-color: #f8f9fa !important;
        color: #212529 !important;
    }

    .row .col-md-6 .facility-info-card .card-header h5 {
        color: #212529 !important;
    }

    .facility-basic-info-table-clean tbody tr:nth-child(3) td:nth-child(3) {
        background-color: #f8f9fa !important;
    }
</style>
<div class="row mb-3">
    <!-- 管理会社情報テーブル -->
    <div class="col-md-6">
        <x-common-table 
            :data="$managementCompanyData"
            title="管理会社情報"
            :showHeader="true"
            cardClass="facility-info-card detail-card-improved h-100"
            :tableAttributes="['style' => '--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;']"
            bodyClass="p-0"
        />
    </div>

    <!-- オーナー情報テーブル -->
    <div class="col-md-6">
        <x-common-table 
            :data="$ownerData"
            title="オーナー情報"
            :showHeader="true"
            cardClass="facility-info-card detail-card-improved h-100"
            :tableAttributes="['style' => '--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;']"
            bodyClass="p-0"
        />
    </div>
</div>