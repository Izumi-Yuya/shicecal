@php
    // Standardized data access pattern with error handling
    try {
        $electricalEquipment = $facility->getElectricalEquipment();
        $basicInfo = $electricalEquipment?->basic_info ?? [];
        $pasInfo = $electricalEquipment?->pas_info ?? [];
        $cubicleInfo = $electricalEquipment?->cubicle_info ?? [];
        $generatorInfo = $electricalEquipment?->generator_info ?? [];
        $cubicleEquipmentList = $cubicleInfo['equipment_list'] ?? [];
        $generatorEquipmentList = $generatorInfo['equipment_list'] ?? [];
        $canEdit = auth()->user()?->canEditFacility($facility->id) ?? false;
        $hasError = false;
    } catch (\Exception $e) {
        $hasError = true;
        $errorMessage = 'データの取得中にエラーが発生しました。';
        \Log::error('Electrical equipment data access error', [
            'facility_id' => $facility->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Set default values to prevent further errors
        $electricalEquipment = null;
        $basicInfo = [];
        $pasInfo = [];
        $cubicleInfo = [];
        $generatorInfo = [];
        $cubicleEquipmentList = [];
        $generatorEquipmentList = [];
        $canEdit = false;
    }
@endphp

<style>
    /* =====================================================
       Electrical Equipment Specific Styles
       ===================================================== */
    
    .electrical-equipment-sections {
        --label-width: 16.6667%;
        --value-width-2col: 50.2%;
        --value-width-3col: 16%;
        --value-width-4col: 27.8%;
        --notes-value-width: 83.3333%;
    }

    .electrical-equipment-sections .fbasic-basicinfo {
        --label-width: 16.6%;
        --value-width-2col: 50.2%;
        --value-width-3col: 16.6%;
        --value-width-4col: 16.6%;
    }
    .electrical-equipment-sections .fbasic-pas {
        --label-width: 16.6%;
        --value-width-2col: 50.2%;
        --value-width-3col: 16.6%;
        --value-width-4col: 16.6%;
    }

    /* 6列構成テーブル（キュービクル／発電機） */
    .electrical-equipment-sections .table-col-6 col:first-child,
    .electrical-equipment-sections .table-col-6 td:first-child,
    .electrical-equipment-sections .table-col-6 th:first-child {
        width: var(--label-width) !important;
    }

    /* 4列構成テーブル（基本情報・PAS）を調整 */
    .electrical-equipment-sections .fbasic-4col tbody > tr > td:nth-child(1),
    .electrical-equipment-sections .fbasic-4col tbody > tr > th:nth-child(1) {
        width: var(--label-width) !important;
    }

    .electrical-equipment-sections .fbasic-4col tbody > tr > td:nth-child(2),
    .electrical-equipment-sections .fbasic-4col tbody > tr > th:nth-child(2) {
        width: var(--value-width-2col) !important;
    }

    .electrical-equipment-sections .fbasic-4col tbody > tr > td:nth-child(3),
    .electrical-equipment-sections .fbasic-4col tbody > tr > th:nth-child(3) {
        width: var(--value-width-3col) !important;
    }

    .electrical-equipment-sections .fbasic-4col tbody > tr > td:nth-child(4),
    .electrical-equipment-sections .fbasic-4col tbody > tr > th:nth-child(4) {
        width: var(--value-width-4col) !important;
    }

    /* 2列構成テーブル（備考） */
    .electrical-equipment-sections .fbasic-2col tbody > tr > td:first-child,
    .electrical-equipment-sections .fbasic-2col tbody > tr > th:first-child {
        width: var(--label-width) !important;
        white-space: nowrap;
    }

    .electrical-equipment-sections .fbasic-2col tbody > tr > td:nth-child(2),
    .electrical-equipment-sections .fbasic-2col tbody > tr > th:nth-child(2) {
        width: var(--notes-value-width) !important;
    }

    /* デザイン共通 */
    .electrical-equipment-sections .facility-basic-info-table-clean td.detail-label {
        font-weight: 500;
        background-color: var(--bs-gray-100, #f8f9fa);
        white-space: nowrap;
        color: var(--bs-gray-700, #495057);
    }

    .electrical-equipment-sections .facility-basic-info-table-clean td.detail-value {
        background-color: var(--bs-white, #fff);
    }

    .electrical-equipment-sections .empty-field {
        color: var(--bs-gray-500, #6c757d);
        font-style: italic;
    }
</style>

<!-- 電気設備ヘッダー -->
<div class="mb-3">
    <h5 class="mb-0">
        <i class="fas fa-bolt text-warning me-2"></i>電気設備情報
    </h5>
</div>

<div class="electrical-equipment-sections equipment-sections">
@if($hasError ?? false)
<div class="alert alert-danger" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    {{ $errorMessage ?? 'データの読み込み中にエラーが発生しました。' }}
</div>
@else

<!-- 基本情報 -->
@php
    $basicInfoData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '電力会社', 'value' => $basicInfo['electrical_contractor'] ?? null, 'type' => 'text'],
                ['label' => '保安管理業者', 'value' => $basicInfo['safety_management_company'] ?? null, 'type' => 'text']
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                [
                    'label' => '保守点検実施日',
                    'value' => !empty($basicInfo['maintenance_inspection_date'])
                        ? \Carbon\Carbon::parse($basicInfo['maintenance_inspection_date'])->format('Y年m月d日')
                        : null,
                    'type' => 'text'
                ],
                [
                    'label' => '点検報告書',
                    'value' => !empty($basicInfo['inspection']['inspection_report_pdf'])
                        ? $basicInfo['inspection']['inspection_report_pdf']
                        : null,
                    'type' => 'file_display',
                    'options' => [
                        'route' => 'facilities.lifeline-equipment.download-file',
                        'params' => [$facility, 'electrical', 'inspection_report'],
                        'display_name' => $basicInfo['inspection']['inspection_report_pdf'] ?? null
                    ]
                ]
            ]
        ]
    ];
@endphp

<x-common-table
    :data="$basicInfoData"
    :showHeader="false"
    tableClass="table table-bordered facility-basic-info-table-clean fbasic-4col fbasic-basicinfo" />

<!-- PAS -->
<div class="mb-3">
    <h6 class="fw-bold text-dark mb-2">PAS</h6>
    @php
        $pasData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '有無', 'value' => $pasInfo['availability'] ?? null, 'type' => 'text'],
                    [
                        'label' => '更新年月日',
                        'value' => !empty($pasInfo['update_date'])
                            ? \Carbon\Carbon::parse($pasInfo['update_date'])->format('Y年m月d日')
                            : null,
                        'type' => 'text'
                    ]
                ]
            ]
        ];
    @endphp

    <x-common-table
        :data="$pasData"
        :showHeader="false"
        tableClass="table table-bordered facility-basic-info-table-clean fbasic-4col fbasic-basicinfo" />
</div>

<!-- キュービクル -->
<div class="mb-3">
    <h6 class="fw-bold text-dark mb-2">キュービクル</h6>
    <div class="table-responsive">
        <table class="table table-bordered facility-basic-info-table-clean table-col-6" style="table-layout: fixed;">
            <colgroup>
                <col style="width:16.67%;">
                <col style="width:16.67%;">
                <col style="width:16.67%;">
                <col style="width:16.67%;">
                <col style="width:16.67%;">
                <col style="width:16.67%;">
            </colgroup>
            <tbody>
                <tr>
                    <td class="detail-label">有無</td>
                    <td class="detail-value {{ empty($cubicleInfo['availability']) ? 'empty-field' : '' }}" colspan="5">
                        {{ $cubicleInfo['availability'] ?? '未設定' }}
                    </td>
                </tr>
                @if(!empty($cubicleInfo['availability']) && $cubicleInfo['availability'] === '有')
                @foreach($cubicleEquipmentList as $index => $equipment)
                <tr>
                    <td class="detail-label">メーカー{{ $index + 1 }}</td>
                    <td class="detail-value">{{ $equipment['manufacturer'] ?? '未設定' }}</td>
                    <td class="detail-label">年式</td>
                    <td class="detail-value">{{ !empty($equipment['model_year']) ? $equipment['model_year'].'年式' : '未設定' }}</td>
                    <td class="detail-label">更新年月日</td>
                    <td class="detail-value">{{ !empty($equipment['update_date']) ? \Carbon\Carbon::parse($equipment['update_date'])->format('Y年m月d日') : '未設定' }}</td>
                </tr>
                @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- 非常用発電機 -->
<div class="mb-3">
    <h6 class="fw-bold text-dark mb-2">非常用発電機</h6>
    <div class="table-responsive">
        <table class="table table-bordered facility-basic-info-table-clean table-col-6" style="table-layout: fixed;">
            <colgroup>
                <col style="width:16.67%;">
                <col style="width:16.67%;">
                <col style="width:16.67%;">
                <col style="width:16.67%;">
                <col style="width:16.67%;">
                <col style="width:16.67%;">
            </colgroup>
            <tbody>
                <tr>
                    <td class="detail-label">有無</td>
                    <td class="detail-value {{ empty($generatorInfo['availability']) ? 'empty-field' : '' }}" colspan="5">
                        {{ $generatorInfo['availability'] ?? '未設定' }}
                    </td>
                </tr>
                @if(!empty($generatorInfo['availability']) && $generatorInfo['availability'] === '有')
                @foreach($generatorEquipmentList as $index => $equipment)
                <tr>
                    <td class="detail-label">メーカー{{ $index + 1 }}</td>
                    <td class="detail-value">{{ $equipment['manufacturer'] ?? '未設定' }}</td>
                    <td class="detail-label">年式</td>
                    <td class="detail-value">{{ !empty($equipment['model_year']) ? $equipment['model_year'].'年式' : '未設定' }}</td>
                    <td class="detail-label">更新年月日</td>
                    <td class="detail-value">{{ !empty($equipment['update_date']) ? \Carbon\Carbon::parse($equipment['update_date'])->format('Y年m月d日') : '未設定' }}</td>
                </tr>
                @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- 備考 -->
<div class="mb-3">
    <h6 class="fw-bold text-dark mb-2">備考</h6>
    @php
        $notesData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '備考', 'value' => $electricalEquipment->notes ?? null, 'type' => 'text']
                ]
            ]
        ];
    @endphp
    <x-common-table
        :data="$notesData"
        :showHeader="false"
        tableClass="table table-bordered facility-basic-info-table-clean fbasic-2col" />
</div>

@vite(['resources/js/modules/lifeline-modal-manager.js'])
@vite(['resources/css/pages/lifeline-equipment.css'])

<!-- 隠されたドキュメントボタン（統一ボタンからクリックされる対象） -->
<button type="button" 
        class="d-none" 
        id="electrical-documents-toggle"
        data-bs-toggle="modal" 
        data-bs-target="#electrical-documents-modal">
</button>

<!-- ドキュメント管理モーダル -->
<div class="modal fade" id="electrical-documents-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-folder-open me-2"></i>電気設備ドキュメント管理</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <x-lifeline-document-manager
                    :facility="$facility"
                    category="electrical"
                    categoryName="電気設備"
                />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

</div> {{-- End electrical-equipment-sections --}}

@endif {{-- End error handling --}}