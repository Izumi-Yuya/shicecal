@php
    use Illuminate\Support\Facades\Storage;
    
    $hvacLightingEquipment = $facility->getHvacLightingEquipment();
    $basicInfo = $hvacLightingEquipment?->basic_info ?? [];
    
    // 空調設備情報
    $hvacInfo = $basicInfo['hvac'] ?? [];
    
    // 照明設備情報
    $lightingInfo = $basicInfo['lighting'] ?? [];
@endphp

{{-- 空調・照明設備表示カード --}}
<div class="hvac-lighting-equipment-sections">
    @if($hvacLightingEquipment)
        <div class="row">
            {{-- 左側：空調設備テーブルと備考 --}}
            <div class="col-md-6">
                {{-- 空調設備テーブル --}}
                <div class="equipment-section mb-4">
                    <div class="section-header d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">空調</h5>
                        @can('update', $facility)
                            <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'hvac_lighting']) }}" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-edit me-1"></i>編集
                            </a>
                        @endcan
                    </div>

                    @php
                        $hvacData = [
                            [
                                'type' => 'standard',
                                'cells' => [
                                    [
                                        'label' => 'フロンガス点検業者',
                                        'value' => $hvacInfo['freon_inspector'] ?? null,
                                        'type' => 'text',
                                        'colspan' => 5
                                    ]
                                ]
                            ],
                            [
                                'type' => 'standard',
                                'cells' => [
                                    [
                                        'label' => '点検実施日',
                                        'value' => isset($hvacInfo['inspection_date']) && $hvacInfo['inspection_date'] ? \Carbon\Carbon::parse($hvacInfo['inspection_date'])->format('Y年m月d日') : null,
                                        'type' => 'text',
                                        'colspan' => 5
                                    ]
                                ]
                            ],
                            [
                                'type' => 'standard',
                                'cells' => [
                                    [
                                        'label' => '点検報告書',
                                        'value' => !empty($hvacInfo['inspection']['inspection_report_filename']) ? $hvacInfo['inspection']['inspection_report_filename'] : null,
                                        'type' => 'file_display',
                                        'colspan' => 5,
                                        'options' => [
                                            'route' => 'facilities.lifeline-equipment.download-file',
                                            'params' => [$facility, 'hvac-lighting', 'hvac_inspection_report'],
                                            'display_name' => $hvacInfo['inspection']['inspection_report_filename'] ?? null
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'type' => 'standard',
                                'cells' => [
                                    [
                                        'label' => '点検対象機器',
                                        'value' => $hvacInfo['target_equipment'] ?? null,
                                        'type' => 'text',
                                        'colspan' => 5
                                    ]
                                ]
                            ]
                        ];
                    @endphp
                    
                    <x-common-table 
                        :data="$hvacData"
                        :showHeader="false"
                        :tableAttributes="['class' => 'table table-bordered hvac-equipment-table']"
                        bodyClass=""
                        cardClass=""
                        tableClass="table table-bordered facility-basic-info-table-clean"
                    />
                </div>

                {{-- 左側備考テーブル --}}
                <div class="equipment-section mb-4">
                    @php
                        $hvacNotesData = [
                            [
                                'type' => 'standard',
                                'cells' => [
                                    [
                                        'label' => '備考',
                                        'value' => $hvacInfo['notes'] ?? null,
                                        'type' => 'text',
                                        'colspan' => 5
                                    ]
                                ]
                            ]
                        ];
                    @endphp
                    
                    <x-common-table 
                        :data="$hvacNotesData"
                        :showHeader="false"
                        :tableAttributes="['class' => 'table table-bordered hvac-notes-table']"
                        bodyClass=""
                        cardClass=""
                        tableClass="table table-bordered facility-basic-info-table-clean"
                    />
                </div>
            </div>

            {{-- 右側：照明設備テーブルと備考 --}}
            <div class="col-md-6">
                {{-- 照明設備テーブル --}}
                <div class="equipment-section mb-4">
                    <div class="section-header d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">照明</h5>
                        @can('update', $facility)
                            <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'hvac_lighting']) }}" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-edit me-1"></i>編集
                            </a>
                        @endcan
                    </div>

                    @php
                        $lightingData = [
                            [
                                'type' => 'standard',
                                'cells' => [
                                    [
                                        'label' => 'メーカー',
                                        'value' => $lightingInfo['manufacturer'] ?? null,
                                        'type' => 'text',
                                        'colspan' => 5
                                    ]
                                ]
                            ],
                            [
                                'type' => 'standard',
                                'cells' => [
                                    [
                                        'label' => '更新日',
                                        'value' => isset($lightingInfo['update_date']) && $lightingInfo['update_date'] ? \Carbon\Carbon::parse($lightingInfo['update_date'])->format('Y年m月d日') : null,
                                        'type' => 'text',
                                        'colspan' => 5
                                    ]
                                ]
                            ],
                            [
                                'type' => 'standard',
                                'cells' => [
                                    [
                                        'label' => '保証期間',
                                        'value' => $lightingInfo['warranty_period'] ?? null,
                                        'type' => 'text',
                                        'colspan' => 5
                                    ]
                                ]
                            ]
                        ];
                    @endphp
                    
                    <x-common-table 
                        :data="$lightingData"
                        :showHeader="false"
                        :tableAttributes="['class' => 'table table-bordered lighting-equipment-table']"
                        bodyClass=""
                        cardClass=""
                        tableClass="table table-bordered facility-basic-info-table-clean"
                    />
                </div>

                {{-- 右側備考テーブル --}}
                <div class="equipment-section mb-4">
                    @php
                        $lightingNotesData = [
                            [
                                'type' => 'standard',
                                'cells' => [
                                    [
                                        'label' => '備考',
                                        'value' => $lightingInfo['notes'] ?? null,
                                        'type' => 'text',
                                        'colspan' => 5
                                    ]
                                ]
                            ]
                        ];
                    @endphp
                    
                    <x-common-table 
                        :data="$lightingNotesData"
                        :showHeader="false"
                        :tableAttributes="['class' => 'table table-bordered lighting-notes-table']"
                        bodyClass=""
                        cardClass=""
                        tableClass="table table-bordered facility-basic-info-table-clean"
                    />
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            詳細仕様は開発中です。基本的なカード構造が準備されています。
        </div>
    @endif
</div>