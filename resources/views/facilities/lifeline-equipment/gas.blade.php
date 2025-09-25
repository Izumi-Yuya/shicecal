@php
    $gasEquipment = $facility->getGasEquipment();
    $basicInfo = $gasEquipment?->basic_info ?? [];
    $waterHeaterInfo = $basicInfo['water_heater_info'] ?? [];
    $waterHeaters = $waterHeaterInfo['water_heaters'] ?? [];

    // 基本情報テーブルデータの構築
    $basicInfoData = [
        // 第1行：2カラム（ガス契約会社、種類）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'ガス契約会社', 'value' => $basicInfo['gas_supplier'] ?? null, 'type' => 'text'],
                ['label' => 'ガス種類', 'value' => $basicInfo['gas_type'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'bg-info']],
            ]
        ],
    ];

    // 給湯器情報の処理
    $waterHeaterInfo = $basicInfo['water_heater_info'] ?? [];
    $waterHeaters = [];
    
    if (isset($waterHeaterInfo['water_heaters']) && is_array($waterHeaterInfo['water_heaters'])) {
        $waterHeaters = $waterHeaterInfo['water_heaters'];
    }

    // 給湯器テーブルデータの構築（複数台対応）
    $waterHeaterDataSets = [];
    
    if (!empty($waterHeaters)) {
        foreach ($waterHeaters as $index => $heater) {
            $waterHeaterDataSets[] = [
                'number' => $index + 1,
                'data' => [
                    [
                        'type' => 'standard',
                        'cells' => [
                            ['label' => 'メーカー', 'value' => $heater['manufacturer'] ?? null, 'type' => 'text'],
                            ['label' => '年式', 'value' => !empty($heater['model_year']) ? $heater['model_year'] . '年式' : null, 'type' => 'text'],
                            ['label' => '更新年月日', 'value' => $heater['update_date'] ?? null, 'type' => 'date'],
                        ]
                    ],
                ]
            ];
        }
    }

    // 備考テーブルデータの構築
    $notesData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '備考', 'value' => $gasEquipment?->notes ?? null, 'type' => 'text'],
            ]
        ],
    ];

    // 給湯器テーブルデータの構築
    $waterHeaterData = [
        // 第1行：1カラム（設置の有無）
        [
            'type' => 'standard',
            'cells' => [
                [
                    'label' => '設置の有無', 
                    'value' => $waterHeaterInfo['availability'] ?? null, 
                    'type' => 'badge', 
                    'options' => [
                        'badge_class' => 'availability',
                        'data_attributes' => ['data-value' => $waterHeaterInfo['availability'] ?? '']
                    ]
                ],
            ]
        ],
    ];

    // 給湯器設備一覧データの構築（設置の有無が「有」の場合のみ）
    $waterHeaterEquipmentData = [];
    if (($waterHeaterInfo['availability'] ?? '') === '有' && !empty($waterHeaters)) {
        foreach ($waterHeaters as $index => $heater) {
            $waterHeaterEquipmentData[] = [
                'number' => $index + 1,
                'data' => [
                    // 第1行：メーカー、年式、更新年月日
                    [
                        'type' => 'standard',
                        'cells' => [
                            ['label' => 'メーカー', 'value' => $heater['manufacturer'] ?? null, 'type' => 'text'],
                            ['label' => '年式', 'value' => !empty($heater['model_year']) ? $heater['model_year'] . '年式' : null, 'type' => 'text'],
                            ['label' => '更新年月日', 'value' => $heater['update_date'] ?? null, 'type' => 'date'],
                        ]
                    ],
                ]
            ];
        }
    }

@endphp

<div class="gas-equipment-sections">
    <!-- 基本情報セクション -->
    <div class="equipment-section mb-4">
        <x-common-table 
            :data="$basicInfoData"
            :showHeader="false"
            :tableAttributes="['class' => 'table table-bordered gas-info-table']"
            bodyClass=""
            cardClass=""
            tableClass="table table-bordered facility-basic-info-table-clean"
        />
    </div>

    <!-- 給湯器セクション -->
    <div class="equipment-section mb-4">
        <div class="section-header d-flex justify-content-between align-items-center mb-3">
            <h6 class="section-title mb-0">
                <i class="fas fa-fire-flame-curved me-2 text-warning"></i>給湯器
            </h6>
            @can('update', $facility)
                <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'gas']) }}" 
                   class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-edit me-1"></i>編集
                </a>
            @endcan
        </div>

        <!-- 給湯器基本情報 -->
        <x-common-table 
            :data="$waterHeaterData"
            :showHeader="false"
            :tableAttributes="['class' => 'table table-bordered water-heater-info-table']"
            bodyClass=""
            cardClass=""
            tableClass="table table-bordered facility-basic-info-table-clean"
        />

        <!-- 給湯器設備一覧（設置の有無が「有」の場合のみ表示） -->
        @if(($waterHeaterInfo['availability'] ?? '') === '有')
            @if(!empty($waterHeaterEquipmentData))
                <div class="equipment-list mt-3">
                    <h6 class="equipment-list-title mb-3">
                        <i class="fas fa-list me-2"></i>給湯器設備一覧
                    </h6>
                    
                    @foreach($waterHeaterEquipmentData as $equipmentItem)
                        <div class="equipment-item mb-3">
                            <div class="equipment-header d-flex align-items-center mb-2">
                                <span class="equipment-number badge bg-warning text-dark me-2">{{ $equipmentItem['number'] }}</span>
                                <span class="equipment-title">給湯器 {{ $equipmentItem['number'] }}</span>
                            </div>
                            
                            <x-common-table 
                                :data="$equipmentItem['data']"
                                :showHeader="false"
                                :tableAttributes="['class' => 'table table-bordered water-heater-equipment-table']"
                                bodyClass=""
                                cardClass=""
                                tableClass="table table-bordered facility-basic-info-table-clean"
                            />
                        </div>
                    @endforeach
                </div>
            @else
                <div class="no-equipment-message mt-3">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        給湯器設備の詳細情報が登録されていません。
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- 備考セクション -->
    <div class="equipment-section mb-4">
        <h6 class="section-title">備考</h6>
        <x-common-table 
            :data="$notesData"
            :showHeader="false"
            :tableAttributes="['class' => 'table table-bordered gas-info-table']"
            bodyClass=""
            cardClass=""
            tableClass="table table-bordered facility-basic-info-table-clean"
        />
    </div>

</div>