@php
    use Illuminate\Support\Facades\Storage;
    
    $elevatorEquipment = $facility->getElevatorEquipment();
    $basicInfo = $elevatorEquipment?->basic_info ?? [];
    $elevators = $basicInfo['elevators'] ?? [];
    $inspectionInfo = $basicInfo['inspection'] ?? [];
    
    // 基本情報テーブルデータの準備
    $availability = $basicInfo['availability'] ?? null;
    $basicInfoData = [
        [
            'cells' => [
                [
                    'label' => '設置の有無',
                    'value' => $availability,
                    'type' => 'text',
                    'width' => '100%'
                ]
            ]
        ]
    ];
@endphp

{{-- エレベーター設備表示カード --}}
<div class="elevator-equipment-sections">
    @if($elevatorEquipment)
        {{-- 基本情報セクション --}}
        <div class="equipment-section mb-4">
            <div class="section-header d-flex justify-content-between align-items-center mb-3">
                @can('update', $facility)
                    <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'elevator']) }}" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>編集
                    </a>
                @endcan
            </div>

            <x-common-table 
                :data="$basicInfoData"
                :showHeader="false"
                :tableAttributes="['class' => 'table table-bordered elevator-basic-info-table']"
                bodyClass=""
                cardClass=""
                tableClass="table table-bordered facility-basic-info-table-clean"
            />
        </div>

        {{-- エレベーター設備一覧セクション（設置の有無が「有」の場合のみ表示） --}}
        @if($availability === '有')
            <div class="equipment-section mb-4">
                <div class="section-header d-flex justify-content-between align-items-center mb-3">
                </div>

                @if(!empty($elevators))
                    @foreach($elevators as $index => $elevator)
                        @php
                            // 各エレベーターのテーブルデータを準備
                            $elevatorData = [
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => 'メーカー',
                                            'value' => $elevator['manufacturer'] ?? null,
                                            'type' => 'text',
                                            'width' => '25%'
                                        ],
                                        [
                                            'label' => '種類',
                                            'value' => $elevator['type'] ?? null,
                                            'type' => 'text',
                                            'width' => '25%'
                                        ],
                                        [
                                            'label' => '年式',
                                            'value' => $elevator['model_year'] ? $elevator['model_year'] . '年式' : null,
                                            'type' => 'text',
                                            'width' => '25%'
                                        ],
                                        [
                                            'label' => '更新年月日',
                                            'value' => $elevator['update_date'] ? \Carbon\Carbon::parse($elevator['update_date'])->format('Y年m月d日') : null,
                                            'type' => 'date',
                                            'width' => '25%'
                                        ]
                                    ]
                                ]
                            ];
                        @endphp
                        
                        <div class="equipment-item mb-3">
                            <div class="equipment-header d-flex align-items-center mb-2">
                                <span class="equipment-number badge bg-warning text-dark me-2">{{ $index + 1 }}</span>
                            </div>
                            
                            <div class="elevator-eight-column-equal">
                                <x-common-table 
                                    :data="$elevatorData"
                                    :showHeader="false"
                                    :tableAttributes="['class' => 'table table-bordered elevator-equipment-table']"
                                    bodyClass=""
                                    cardClass=""
                                    tableClass="table table-bordered facility-basic-info-table-clean"
                                />
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        エレベーター設備の詳細情報が登録されていません。
                    </div>
                @endif
            </div>
        @endif

        {{-- 点検情報セクション --}}
        <div class="equipment-section mb-4">
            <div class="section-header d-flex justify-content-between align-items-center mb-3">
                @can('update', $facility)
                    <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'elevator']) }}" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>編集
                    </a>
                @endcan
            </div>

            @php
                $inspectionData = [
                    [
                        'type' => 'standard',
                        'cells' => [
                            [
                                'label' => '保守業者',
                                'value' => $inspectionInfo['maintenance_contractor'] ?? null,
                                'type' => 'text',
                                'width' => '33.33%'
                            ],
                            [
                                'label' => '保守点検実施日',
                                'value' => $inspectionInfo['inspection_date'] ? \Carbon\Carbon::parse($inspectionInfo['inspection_date'])->format('Y年m月d日') : null,
                                'type' => 'text',
                                'width' => '33.33%'
                            ],
                            [
                                'label' => '保守点検報告書',
                                'value' => !empty($inspectionInfo['inspection_report_filename']) ? $inspectionInfo['inspection_report_filename'] : null,
                                'type' => 'file_display',
                                'width' => '33.33%',
                                'options' => [
                                    'route' => 'facilities.lifeline-equipment.download-file',
                                    'params' => [$facility, 'elevator', 'inspection_report'],
                                    'display_name' => $inspectionInfo['inspection_report_filename'] ?? null
                                ]
                            ]
                        ]
                    ]
                ];
            @endphp
            
            <div class="elevator-six-column-equal">
                <x-common-table 
                    :data="$inspectionData"
                    :showHeader="false"
                    :tableAttributes="['class' => 'table table-bordered elevator-inspection-table']"
                    bodyClass=""
                    cardClass=""
                    tableClass="table table-bordered facility-basic-info-table-clean"
                />
            </div>
        </div>

        {{-- 備考セクション --}}
        <div class="equipment-section mb-4">
            <div class="section-header d-flex justify-content-between align-items-center mb-3">
                @can('update', $facility)
                    <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'elevator']) }}" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>編集
                    </a>
                @endcan
            </div>

            @php
                $notesData = [
                    [
                        'type' => 'standard',
                        'cells' => [
                            [
                                'label' => '備考',
                                'value' => $elevatorEquipment->notes ?? null,
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
                :tableAttributes="['class' => 'table table-bordered elevator-notes-table']"
                bodyClass=""
                cardClass=""
                tableClass="table table-bordered facility-basic-info-table-clean"
            />
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            詳細仕様は開発中です。基本的なカード構造が準備されています。
        </div>
    @endif
</div>