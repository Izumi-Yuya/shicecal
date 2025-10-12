@php
    $electricalLifeline = $facility->getLifelineEquipmentByCategory('electrical');
    $electricalEquipment = $electricalLifeline?->electricalEquipment;
    $basicInfo = $electricalEquipment->basic_info ?? [];
    $pasInfo = $electricalEquipment->pas_info ?? [];
    $cubicleInfo = $electricalEquipment->cubicle_info ?? [];
    $generatorInfo = $electricalEquipment->generator_info ?? [];
    $cubicleEquipmentList = $cubicleInfo['equipment_list'] ?? [];
    $generatorEquipmentList = $generatorInfo['equipment_list'] ?? [];
    $canEdit = auth()->user()->canEditFacility($facility->id);
@endphp

<!-- 電気設備ヘッダー（ドキュメントアイコン付き） -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="fas fa-bolt text-warning me-2"></i>電気設備情報
    </h5>
    <div class="d-flex align-items-center gap-2">
        <!-- ドキュメント管理ボタン -->
        <button type="button" 
                class="btn btn-outline-primary btn-sm" 
                id="electrical-documents-toggle"
                title="電気設備ドキュメント管理">
            <i class="fas fa-folder-open me-1"></i>
            <span class="d-none d-md-inline">ドキュメント</span>
        </button>
    </div>
</div>

<!-- 基本情報テーブル -->
@php
    $basicInfoData = [
        [
            'type' => 'standard',
            'cells' => [
                [
                    'label' => '電力会社',
                    'value' => $basicInfo['electrical_contractor'] ?? null,
                    'type' => 'text',
                    'width' => '25%'
                ],
                [
                    'label' => '保安管理業者',
                    'value' => $basicInfo['safety_management_company'] ?? null,
                    'type' => 'text',
                    'width' => '25%'
                ]
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
                    'type' => 'text',
                    'width' => '25%'
                ],
                [
                    'label' => '点検報告書',
                    'value' => !empty($basicInfo['inspection']['inspection_report_pdf']) 
                        ? $basicInfo['inspection']['inspection_report_pdf'] 
                        : null,
                    'type' => 'file_display',
                    'width' => '25%',
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
    cardClass=""
    tableClass="table table-bordered facility-basic-info-table-clean"
    bodyClass=""
/>
<!-- PASテーブル -->
<div class="mb-3">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">PAS</h6>
    @php
        $pasData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '有無',
                        'value' => $pasInfo['availability'] ?? null,
                        'type' => 'text',
                        'width' => '25%'
                    ],
                    [
                        'label' => '更新年月日',
                        'value' => !empty($pasInfo['update_date']) 
                            ? \Carbon\Carbon::parse($pasInfo['update_date'])->format('Y年m月d日') 
                            : null,
                        'type' => 'text',
                        'width' => '25%'
                    ]
                ]
            ]
        ];
    @endphp
    
    <x-common-table 
        :data="$pasData"
        :showHeader="false"
        cardClass=""
        tableClass="table table-bordered facility-basic-info-table-clean"
        bodyClass=""
    />
</div>
<!-- キュービクルテーブル -->
<div class="mb-3" style="position: relative; overflow: visible;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">キュービクル</h6>
    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0; table-layout: fixed;">
            <colgroup>
                <col style="width: 16.67%;">
                <col style="width: 16.67%;">
                <col style="width: 16.67%;">
                <col style="width: 16.67%;">
                <col style="width: 16.67%;">
                <col style="width: 16.67%;">
            </colgroup>
            <tbody>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">有無</td>
                    <td class="detail-value {{ empty($cubicleInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="5">
                        {{ $cubicleInfo['availability'] ?? '未設定' }}
                    </td>
                </tr>
                @if(!empty($cubicleInfo['availability']) && $cubicleInfo['availability'] === '有')
                    @if(!empty($cubicleEquipmentList) && is_array($cubicleEquipmentList))
                        @foreach($cubicleEquipmentList as $index => $equipment)
                            <tr style="position: relative;">
                                <td class="detail-label" style="padding: 0.5rem; position: relative;">
                                    <div style="position: absolute; left: -30px; top: 50%; transform: translateY(-50%); z-index: 1000;">
                                        <span style="background: #007bff; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">{{ $index + 1 }}</span>
                                    </div>
                                    メーカー
                                </td>
                                <td class="detail-value {{ empty($equipment['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $equipment['manufacturer'] ?? '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">年式</td>
                                <td class="detail-value {{ empty($equipment['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['model_year']))
                                        {{ $equipment['model_year'] }}年式
                                    @else
                                        未設定
                                    @endif
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                                <td class="detail-value {{ empty($equipment['update_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['update_date']))
                                        {{ \Carbon\Carbon::parse($equipment['update_date'])->format('Y年m月d日') }}
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">メーカー</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                            <td class="detail-label" style="padding: 0.5rem;">年式</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                            <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                        </tr>
                    @endif
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- 非常用発電機テーブル -->
<div class="mb-3" style="position: relative; overflow: visible;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">非常用発電機</h6>
    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0; table-layout: fixed;">
            <colgroup>
                <col style="width: 16.67%;">
                <col style="width: 16.67%;">
                <col style="width: 16.67%;">
                <col style="width: 16.67%;">
                <col style="width: 16.67%;">
                <col style="width: 16.67%;">
            </colgroup>
            <tbody>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">有無</td>
                    <td class="detail-value {{ empty($generatorInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="5">
                        {{ $generatorInfo['availability'] ?? '未設定' }}
                    </td>
                </tr>
                @if(!empty($generatorInfo['availability']) && $generatorInfo['availability'] === '有')
                    @if(!empty($generatorEquipmentList) && is_array($generatorEquipmentList))
                        @foreach($generatorEquipmentList as $index => $equipment)
                            <tr style="position: relative;">
                                <td class="detail-label" style="padding: 0.5rem; position: relative;">
                                    <div style="position: absolute; left: -30px; top: 50%; transform: translateY(-50%); z-index: 1000;">
                                        <span style="background: #007bff; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">{{ $index + 1 }}</span>
                                    </div>
                                    メーカー
                                </td>
                                <td class="detail-value {{ empty($equipment['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $equipment['manufacturer'] ?? '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">年式</td>
                                <td class="detail-value {{ empty($equipment['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['model_year']))
                                        {{ $equipment['model_year'] }}年式
                                    @else
                                        未設定
                                    @endif
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                                <td class="detail-value {{ empty($equipment['update_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['update_date']))
                                        {{ \Carbon\Carbon::parse($equipment['update_date'])->format('Y年m月d日') }}
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">メーカー</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                            <td class="detail-label" style="padding: 0.5rem;">年式</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                            <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                        </tr>
                    @endif
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- 備考テーブル -->
<div class="mb-3">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">備考</h6>
    @php
        $notesData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '備考',
                        'value' => $electricalEquipment->notes ?? null,
                        'type' => 'text',
                        'width' => '100%'
                    ]
                ]
            ]
        ];
    @endphp
    
    <x-common-table 
        :data="$notesData"
        :showHeader="false"
        cardClass=""
        tableClass="table table-bordered facility-basic-info-table-clean"
        bodyClass=""
    />
</div>

<!-- 電気設備ドキュメント管理モーダル -->
<div class="modal fade" id="electrical-documents-modal" tabindex="-1" aria-labelledby="electrical-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="electrical-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>電気設備ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
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
