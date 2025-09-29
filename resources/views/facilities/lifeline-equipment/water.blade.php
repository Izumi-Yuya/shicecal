@php
    $waterEquipment = $facility->getWaterEquipment();
    $basicInfo = $waterEquipment?->basic_info ?? [];
    $filterInfo = $basicInfo['filter_info'] ?? [];
    $tankInfo = $basicInfo['tank_info'] ?? [];
    $pumpInfo = $basicInfo['pump_info'] ?? [];
    $septicTankInfo = $basicInfo['septic_tank_info'] ?? [];
    $legionellaInfo = $basicInfo['legionella_info'] ?? [];

    // 基本情報テーブルデータの構築
    $basicInfoData = [
        // 第1行：1カラム（水道契約会社）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '水道契約会社', 'value' => $basicInfo['water_contractor'] ?? null, 'type' => 'text'],
            ]
        ],
        // 第2行：3カラム（受水槽清掃関連）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '受水槽清掃業者', 'value' => $basicInfo['tank_cleaning_company'] ?? null, 'type' => 'text'],
                ['label' => '受水槽清掃実施日', 'value' => $basicInfo['tank_cleaning_date'] ?? null, 'type' => 'date'],
                ['label' => '受水槽清掃報告書', 'value' => $basicInfo['tank_cleaning']['tank_cleaning_report_pdf'] ?? null, 'type' => 'file_display', 'options' => ['route' => 'facilities.lifeline-equipment.download-file', 'params' => [$facility, 'water', 'tank_cleaning_report'], 'display_name' => 'ダウンロード']],
            ]
        ],
    ];

    // ろ過器テーブルデータの構築
    $filterData = [
        // 第1行：1カラム（浴槽方式）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '浴槽循環方式', 'value' => $filterInfo['bath_system'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'bg-info']],
            ]
        ],
        // 第2行：3カラム（有無、メーカー、年式）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '設置の有無', 'value' => $filterInfo['availability'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'availability']],
                ['label' => 'メーカー', 'value' => $filterInfo['manufacturer'] ?? null, 'type' => 'text'],
                ['label' => '年式', 'value' => !empty($filterInfo['model_year']) ? $filterInfo['model_year'] . '年式' : null, 'type' => 'text'],
            ]
        ],
    ];

    // 受水槽テーブルデータの構築
    $tankData = [
        // 第1行：3カラム（有無、メーカー、年式）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '設置の有無', 'value' => $tankInfo['availability'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'availability']],
                ['label' => 'メーカー', 'value' => $tankInfo['manufacturer'] ?? null, 'type' => 'text'],
                ['label' => '年式', 'value' => !empty($tankInfo['model_year']) ? $tankInfo['model_year'] . '年式' : null, 'type' => 'text'],
            ]
        ],
    ];

    // 浄化槽テーブルデータの構築
    $septicTankData = [
        // 第1行：3カラム（有無、メーカー、年式）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '設置の有無', 'value' => $septicTankInfo['availability'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'availability']],
                ['label' => 'メーカー', 'value' => $septicTankInfo['manufacturer'] ?? null, 'type' => 'text'],
                ['label' => '年式', 'value' => !empty($septicTankInfo['model_year']) ? $septicTankInfo['model_year'] . '年式' : null, 'type' => 'text'],
            ]
        ],
        // 第2行：3カラム（点検・清掃業者、実施日、実施報告書）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '点検・清掃業者', 'value' => $septicTankInfo['inspection_company'] ?? null, 'type' => 'text'],
                ['label' => '点検・清掃実施日', 'value' => $septicTankInfo['inspection_date'] ?? null, 'type' => 'date'],
                ['label' => '点検・清掃実施報告書', 'value' => $septicTankInfo['inspection']['inspection_report_pdf'] ?? null, 'type' => 'file_display', 'options' => ['route' => 'facilities.lifeline-equipment.download-file', 'params' => [$facility, 'water', 'septic_tank_inspection_report'], 'display_name' => 'ダウンロード']],
            ]
        ],
    ];

    // 加圧ポンプテーブルデータの構築（複数台対応）
    $pumpDataSets = [];
    
    // 加圧ポンプが配列形式（複数台）の場合
    if (isset($pumpInfo['pumps']) && is_array($pumpInfo['pumps'])) {
        foreach ($pumpInfo['pumps'] as $index => $pump) {
            $pumpDataSets[] = [
                'number' => $index + 1,
                'data' => [
                    [
                        'type' => 'standard',
                        'cells' => [
                            ['label' => 'メーカー', 'value' => $pump['manufacturer'] ?? null, 'type' => 'text'],
                            ['label' => '年式', 'value' => !empty($pump['model_year']) ? $pump['model_year'] . '年式' : null, 'type' => 'text'],
                            ['label' => '更新年月日', 'value' => $pump['update_date'] ?? null, 'type' => 'date'],
                        ]
                    ],
                ]
            ];
        }
    } else {
        // 単一の加圧ポンプの場合
        $pumpDataSets[] = [
            'number' => null, // 単一の場合は番号なし
            'data' => [
                [
                    'type' => 'standard',
                    'cells' => [
                        ['label' => 'メーカー', 'value' => $pumpInfo['manufacturer'] ?? null, 'type' => 'text'],
                        ['label' => '年式', 'value' => !empty($pumpInfo['model_year']) ? $pumpInfo['model_year'] . '年式' : null, 'type' => 'text'],
                        ['label' => '更新年月日', 'value' => $pumpInfo['update_date'] ?? null, 'type' => 'date'],
                    ]
                ],
            ]
        ];
    }

    // レジオネラ検査テーブルデータの構築（複数設備対応）
    $legionellaDataSets = [];
    
    // レジオネラ検査が配列形式（複数設備）の場合
    if (isset($legionellaInfo['inspections']) && is_array($legionellaInfo['inspections'])) {
        foreach ($legionellaInfo['inspections'] as $index => $inspection) {
            $legionellaDataSets[] = [
                'number' => $index + 1,
                'data' => [
                    // 第1行：実施日、検査結果報告書
                    [
                        'type' => 'standard',
                        'cells' => [
                            ['label' => '実施日', 'value' => $inspection['inspection_date'] ?? null, 'type' => 'date'],
                            ['label' => '検査結果報告書', 'value' => $inspection['report']['report_pdf'] ?? null, 'type' => 'file_display', 'options' => ['route' => 'facilities.lifeline-equipment.download-file', 'params' => [$facility, 'water', 'legionella_report_' . $index], 'display_name' => 'ダウンロード']],
                        ]
                    ],
                    // 第2行：検査結果（初回）、数値（陽性の場合）
                    [
                        'type' => 'standard',
                        'cells' => [
                            ['label' => '検査結果（初回）', 'value' => $inspection['first_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($inspection['first_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success']],
                            ['label' => '数値（陽性の場合）', 'value' => $inspection['first_value'] ?? null, 'type' => 'text'],
                        ]
                    ],
                    // 第3行：検査結果（2回目）、数値（陽性の場合）
                    [
                        'type' => 'standard',
                        'cells' => [
                            ['label' => '検査結果（2回目）', 'value' => $inspection['second_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($inspection['second_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success']],
                            ['label' => '数値（陽性の場合）', 'value' => $inspection['second_value'] ?? null, 'type' => 'text'],
                        ]
                    ],
                ]
            ];
        }
    } else {
        // 単一のレジオネラ検査の場合
        $legionellaDataSets[] = [
            'number' => null, // 単一の場合は番号なし
            'data' => [
                // 第1行：実施日、検査結果報告書
                [
                    'type' => 'standard',
                    'cells' => [
                        ['label' => '実施日', 'value' => $legionellaInfo['inspection_date'] ?? null, 'type' => 'date'],
                        ['label' => '検査結果報告書', 'value' => $legionellaInfo['report']['report_pdf'] ?? null, 'type' => 'file_display', 'options' => ['route' => 'facilities.lifeline-equipment.download-file', 'params' => [$facility, 'water', 'legionella_report_0'], 'display_name' => 'ダウンロード']],
                    ]
                ],
                // 第2行：検査結果（初回）、数値（陽性の場合）
                [
                    'type' => 'standard',
                    'cells' => [
                        ['label' => '検査結果（初回）', 'value' => $legionellaInfo['first_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($legionellaInfo['first_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success']],
                        ['label' => '数値（陽性の場合）', 'value' => $legionellaInfo['first_value'] ?? null, 'type' => 'text'],
                    ]
                ],
                // 第3行：検査結果（2回目）、数値（陽性の場合）
                [
                    'type' => 'standard',
                    'cells' => [
                        ['label' => '検査結果（2回目）', 'value' => $legionellaInfo['second_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($legionellaInfo['second_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success']],
                        ['label' => '数値（陽性の場合）', 'value' => $legionellaInfo['second_value'] ?? null, 'type' => 'text'],
                    ]
                ],
            ]
        ];
    }

    // 備考テーブルデータの構築
    $notesData = [
        // 第1行：1カラム（備考）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '備考', 'value' => $waterEquipment?->notes ?? null, 'type' => 'text'],
            ]
        ],
    ];
@endphp

<div class="water-equipment-sections">
    <div class="equipment-section mb-4">
        <h6 class="section-title">基本情報</h6>
        <x-common-table 
            :data="$basicInfoData"
            :showHeader="false"
            :tableAttributes="['class' => 'table table-bordered water-info-table']"
            bodyClass=""
            cardClass=""
            tableClass="table table-bordered facility-basic-info-table-clean"
        />
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">ろ過器</h6>
        <x-common-table 
            :data="$filterData"
            :showHeader="false"
            :tableAttributes="['class' => 'table table-bordered water-info-table']"
            bodyClass=""
            cardClass=""
            tableClass="table table-bordered facility-basic-info-table-clean"
        />
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">受水槽</h6>
        <x-common-table 
            :data="$tankData"
            :showHeader="false"
            :tableAttributes="['class' => 'table table-bordered water-info-table']"
            bodyClass=""
            cardClass=""
            tableClass="table table-bordered facility-basic-info-table-clean"
        />
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">加圧ポンプ</h6>
        @foreach($pumpDataSets as $pumpSet)
            <div class="pump-equipment-wrapper mb-3 {{ $pumpSet['number'] ? 'numbered-equipment' : '' }}">
                @if($pumpSet['number'])
                    <div class="equipment-number-badge">
                        <span class="badge bg-primary">{{ $pumpSet['number'] }}</span>
                    </div>
                @endif
                <x-common-table 
                    :data="$pumpSet['data']"
                    :showHeader="false"
                    :tableAttributes="['class' => 'table table-bordered water-info-table']"
                    bodyClass=""
                    cardClass=""
                    tableClass="table table-bordered facility-basic-info-table-clean"
                />
            </div>
        @endforeach
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">浄化槽</h6>
        <x-common-table 
            :data="$septicTankData"
            :showHeader="false"
            :tableAttributes="['class' => 'table table-bordered water-info-table']"
            bodyClass=""
            cardClass=""
            tableClass="table table-bordered facility-basic-info-table-clean"
        />
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">レジオネラ検査</h6>
        @foreach($legionellaDataSets as $legionellaSet)
            <div class="legionella-equipment-wrapper mb-3 {{ $legionellaSet['number'] ? 'numbered-equipment' : '' }}">
                @if($legionellaSet['number'])
                    <div class="equipment-number-badge">
                        <span class="badge bg-warning">{{ $legionellaSet['number'] }}</span>
                    </div>
                @endif
                <x-common-table 
                    :data="$legionellaSet['data']"
                    :showHeader="false"
                    :tableAttributes="['class' => 'table table-bordered water-info-table']"
                    bodyClass=""
                    cardClass=""
                    tableClass="table table-bordered facility-basic-info-table-clean"
                />
            </div>
        @endforeach
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">備考</h6>
        <x-common-table 
            :data="$notesData"
            :showHeader="false"
            :tableAttributes="['class' => 'table table-bordered water-info-table']"
            bodyClass=""
            cardClass=""
            tableClass="table table-bordered facility-basic-info-table-clean"
        />
    </div>
</div>