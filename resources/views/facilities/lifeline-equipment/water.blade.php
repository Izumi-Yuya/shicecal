@php
    $waterEquipment = $facility->getWaterEquipment();
    $basicInfo = $waterEquipment?->basic_info ?? [];
    $filterInfo = $basicInfo['filter_info'] ?? [];
    $tankInfo = $basicInfo['tank_info'] ?? [];
    $pumpInfo = $basicInfo['pump_info'] ?? [];
    $septicTankInfo = $basicInfo['septic_tank_info'] ?? [];
    $legionellaInfo = $basicInfo['legionella_info'] ?? [];
    $canEdit = auth()->user()->canEditFacility($facility->id);
@endphp

<!-- 水道設備ヘッダー（ドキュメントアイコン付き） -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="fas fa-tint text-info me-2"></i>水道設備情報
    </h5>
    <div class="d-flex align-items-center gap-2">
        <!-- ドキュメント管理ボタン -->
        <button type="button" 
                class="btn btn-outline-primary btn-sm" 
                id="water-documents-toggle"
                data-bs-toggle="collapse" 
                data-bs-target="#water-documents-section" 
                aria-expanded="false" 
                aria-controls="water-documents-section"
                title="水道設備ドキュメント管理">
            <i class="fas fa-folder-open me-1"></i>
            <span class="d-none d-md-inline">ドキュメント</span>
        </button>
        

    </div>
</div>

<!-- 水道設備ドキュメント管理セクション（折りたたみ式） -->
<div class="collapse mb-4" id="water-documents-section">
    <div class="card border-info">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0">
                <i class="fas fa-folder-open me-2"></i>水道設備 - 関連ドキュメント
            </h6>
        </div>
        <div class="card-body p-0">
            @if($canEdit)
                <x-lifeline-document-manager 
                    :facility="$facility" 
                    category="water"
                    category-name="水道設備"
                    height="500px"
                    :show-upload="true"
                    :show-create-folder="true"
                    allowed-file-types="pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif"
                    max-file-size="10MB"
                />
            @else
                <x-lifeline-document-manager 
                    :facility="$facility" 
                    category="water"
                    category-name="水道設備"
                    height="400px"
                    :show-upload="false"
                    :show-create-folder="false"
                    allowed-file-types="pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif"
                    max-file-size="10MB"
                />
            @endif
        </div>
    </div>
</div>

@php

    // 基本情報テーブルデータの構築
    $basicInfoData = [
        // 第1行：1カラム（水道契約会社）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '水道契約会社', 'value' => $basicInfo['water_contractor'] ?? null, 'type' => 'text', 'width' => '100%'],
            ]
        ],
        // 第2行：3カラム（受水槽清掃業者、実施日、報告書）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '受水槽清掃業者', 'value' => $basicInfo['tank_cleaning_company'] ?? null, 'type' => 'text', 'width' => '33.33%'],
                ['label' => '受水槽清掃実施日', 'value' => $basicInfo['tank_cleaning_date'] ?? null, 'type' => 'date', 'width' => '33.33%'],
                ['label' => '受水槽清掃報告書', 'value' => $basicInfo['tank_cleaning']['tank_cleaning_report_pdf'] ?? null, 'type' => 'file_display', 'options' => ['route' => 'facilities.lifeline-equipment.download-file', 'params' => [$facility, 'water', 'tank_cleaning_report'], 'display_name' => 'ダウンロード'], 'width' => '33.33%'],
            ]
        ],
    ];

    // ろ過器テーブルデータの構築
    $filterData = [
        // 第1行：1カラム（浴槽循環方式）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '浴槽循環方式', 'value' => $filterInfo['bath_system'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'bg-info'], 'width' => '100%'],
            ]
        ],
        // 第2行：3カラム（設置の有無、メーカー、年式）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '設置の有無', 'value' => $filterInfo['availability'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'availability'], 'width' => '33.33%'],
                ['label' => 'メーカー', 'value' => $filterInfo['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
                ['label' => '年式', 'value' => !empty($filterInfo['model_year']) ? $filterInfo['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
            ]
        ],
    ];

    // 受水槽テーブルデータの構築
    $tankData = [
        // 第1行：3カラム（有無、メーカー、年式）- 6列レイアウト
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '設置の有無', 'value' => $tankInfo['availability'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'availability'], 'width' => '33.33%'],
                ['label' => 'メーカー', 'value' => $tankInfo['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
                ['label' => '年式', 'value' => !empty($tankInfo['model_year']) ? $tankInfo['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
            ]
        ],
    ];

    // 浄化槽テーブルデータの構築
    $septicTankData = [
        // 第1行：3カラム（有無、メーカー、年式）- 6列レイアウト
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '設置の有無', 'value' => $septicTankInfo['availability'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'availability'], 'width' => '33.33%'],
                ['label' => 'メーカー', 'value' => $septicTankInfo['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
                ['label' => '年式', 'value' => !empty($septicTankInfo['model_year']) ? $septicTankInfo['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
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
                            ['label' => 'メーカー', 'value' => $pump['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
                            ['label' => '年式', 'value' => !empty($pump['model_year']) ? $pump['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
                            ['label' => '更新年月日', 'value' => $pump['update_date'] ?? null, 'type' => 'date', 'width' => '33.33%'],
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
                        ['label' => 'メーカー', 'value' => $pumpInfo['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
                        ['label' => '年式', 'value' => !empty($pumpInfo['model_year']) ? $pumpInfo['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
                        ['label' => '更新年月日', 'value' => $pumpInfo['update_date'] ?? null, 'type' => 'date', 'width' => '33.33%'],
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
                            ['label' => '実施日', 'value' => $inspection['inspection_date'] ?? null, 'type' => 'date', 'width' => '50%'],
                            ['label' => '検査結果報告書', 'value' => $inspection['report']['report_pdf'] ?? null, 'type' => 'file_display', 'options' => ['route' => 'facilities.lifeline-equipment.download-file', 'params' => [$facility, 'water', 'legionella_report_' . $index], 'display_name' => 'ダウンロード'], 'width' => '50%'],
                        ]
                    ],
                    // 第2行：検査結果（初回）、数値（陽性の場合）
                    [
                        'type' => 'standard',
                        'cells' => [
                            ['label' => '検査結果（初回）', 'value' => $inspection['first_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($inspection['first_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success'], 'width' => '50%'],
                            ['label' => '数値（陽性の場合）', 'value' => $inspection['first_value'] ?? null, 'type' => 'text', 'width' => '50%'],
                        ]
                    ],
                    // 第3行：検査結果（2回目）、数値（陽性の場合）
                    [
                        'type' => 'standard',
                        'cells' => [
                            ['label' => '検査結果（2回目）', 'value' => $inspection['second_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($inspection['second_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success'], 'width' => '50%'],
                            ['label' => '数値（陽性の場合）', 'value' => $inspection['second_value'] ?? null, 'type' => 'text', 'width' => '50%'],
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
                        ['label' => '実施日', 'value' => $legionellaInfo['inspection_date'] ?? null, 'type' => 'date', 'width' => '50%'],
                        ['label' => '検査結果報告書', 'value' => $legionellaInfo['report']['report_pdf'] ?? null, 'type' => 'file_display', 'options' => ['route' => 'facilities.lifeline-equipment.download-file', 'params' => [$facility, 'water', 'legionella_report_0'], 'display_name' => 'ダウンロード'], 'width' => '50%'],
                    ]
                ],
                // 第2行：検査結果（初回）、数値（陽性の場合）
                [
                    'type' => 'standard',
                    'cells' => [
                        ['label' => '検査結果（初回）', 'value' => $legionellaInfo['first_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($legionellaInfo['first_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success'], 'width' => '50%'],
                        ['label' => '数値（陽性の場合）', 'value' => $legionellaInfo['first_value'] ?? null, 'type' => 'text', 'width' => '50%'],
                    ]
                ],
                // 第3行：検査結果（2回目）、数値（陽性の場合）
                [
                    'type' => 'standard',
                    'cells' => [
                        ['label' => '検査結果（2回目）', 'value' => $legionellaInfo['second_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($legionellaInfo['second_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success'], 'width' => '50%'],
                        ['label' => '数値（陽性の場合）', 'value' => $legionellaInfo['second_value'] ?? null, 'type' => 'text', 'width' => '50%'],
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
                ['label' => '備考', 'value' => $waterEquipment?->notes ?? null, 'type' => 'text', 'width' => '100%'],
            ]
        ],
    ];
@endphp

<div class="water-equipment-sections">
    <div class="equipment-section mb-4">
        <h6 class="section-title">基本情報</h6>
        <div class="water-six-column-equal">
            <div class="table-responsive">
                <table class="table facility-basic-info-table-clean" style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;">
                    <colgroup>
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                    </colgroup>
                    <tbody>
                        @foreach($basicInfoData as $row)
                            <tr>
                                @if($row['type'] === 'standard')
                                    @foreach($row['cells'] as $cell)
                                        @if($cell['width'] === '100%')
                                            <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                                            <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding: 0.5rem; border: 1px solid #e9ecef !important;" colspan="5">
                                                {{ $cell['value'] ?? '未設定' }}
                                            </td>
                                        @elseif($cell['width'] === '33.33%')
                                            <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                                            <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">
                                                @if($cell['type'] === 'date' && !empty($cell['value']))
                                                    {{ \Carbon\Carbon::parse($cell['value'])->format('Y年m月d日') }}
                                                @elseif($cell['type'] === 'file_display' && !empty($cell['value']))
                                                    <a href="{{ route($cell['options']['route'], $cell['options']['params']) }}" 
                                                       class="text-decoration-none" 
                                                       target="_blank">
                                                        <i class="fas fa-file-pdf me-1 text-danger"></i>{{ $cell['options']['display_name'] }}
                                                    </a>
                                                @else
                                                    {{ $cell['value'] ?? '未設定' }}
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">ろ過器</h6>
        <div class="water-six-column-equal">
            <div class="table-responsive">
                <table class="table facility-basic-info-table-clean" style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;">
                    <colgroup>
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                    </colgroup>
                    <tbody>
                        @foreach($filterData as $row)
                            <tr>
                                @if($row['type'] === 'standard')
                                    @foreach($row['cells'] as $cell)
                                        @if($cell['width'] === '100%')
                                            <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                                            <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding: 0.5rem; border: 1px solid #e9ecef !important;" colspan="5">
                                                {{ $cell['value'] ?? '未設定' }}
                                            </td>
                                        @elseif($cell['width'] === '33.33%')
                                            <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                                            <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">
                                                {{ $cell['value'] ?? '未設定' }}
                                            </td>
                                        @endif
                                    @endforeach
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">受水槽</h6>
        <div class="water-six-column-equal">
            <div class="table-responsive">
                <table class="table facility-basic-info-table-clean" style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;">
                    <colgroup>
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                    </colgroup>
                    <tbody>
                        @foreach($tankData as $row)
                            <tr>
                                @if($row['type'] === 'standard')
                                    @foreach($row['cells'] as $cell)
                                        <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                                        <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">
                                            {{ $cell['value'] ?? '未設定' }}
                                        </td>
                                    @endforeach
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
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
                <div class="water-six-column-equal">
                    <div class="table-responsive">
                        <table class="table facility-basic-info-table-clean" style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;">
                            <colgroup>
                                <col style="width: 16.67%;">
                                <col style="width: 16.67%;">
                                <col style="width: 16.67%;">
                                <col style="width: 16.67%;">
                                <col style="width: 16.67%;">
                                <col style="width: 16.67%;">
                            </colgroup>
                            <tbody>
                                @foreach($pumpSet['data'] as $row)
                                    <tr>
                                        @if($row['type'] === 'standard')
                                            @foreach($row['cells'] as $cell)
                                                <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                                                <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">
                                                    @if($cell['type'] === 'date' && !empty($cell['value']))
                                                        {{ \Carbon\Carbon::parse($cell['value'])->format('Y年m月d日') }}
                                                    @else
                                                        {{ $cell['value'] ?? '未設定' }}
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">浄化槽</h6>
        <div class="water-six-column-equal">
            <div class="table-responsive">
                <table class="table facility-basic-info-table-clean" style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;">
                    <colgroup>
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                    </colgroup>
                    <tbody>
                        @foreach($septicTankData as $row)
                            <tr>
                                @if($row['type'] === 'standard')
                                    @foreach($row['cells'] as $cell)
                                        <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                                        <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">
                                            @if($cell['type'] === 'date' && !empty($cell['value']))
                                                {{ \Carbon\Carbon::parse($cell['value'])->format('Y年m月d日') }}
                                            @elseif($cell['type'] === 'file_display' && !empty($cell['value']))
                                                <a href="{{ route($cell['options']['route'], $cell['options']['params']) }}" 
                                                   class="text-decoration-none" 
                                                   target="_blank">
                                                    <i class="fas fa-file-pdf me-1 text-danger"></i>{{ $cell['options']['display_name'] }}
                                                </a>
                                            @else
                                                {{ $cell['value'] ?? '未設定' }}
                                            @endif
                                        </td>
                                    @endforeach
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
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

<!-- 水道設備ドキュメント管理用JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const documentToggleBtn = document.getElementById('water-documents-toggle');
    const documentSection = document.getElementById('water-documents-section');
    
    if (documentToggleBtn && documentSection) {
        // ボタンアイコンとテキストの更新
        function updateButtonState(isExpanded) {
            const icon = documentToggleBtn.querySelector('i');
            const text = documentToggleBtn.querySelector('span');
            
            if (isExpanded) {
                icon.className = 'fas fa-folder-minus me-1';
                if (text) text.textContent = '閉じる';
                documentToggleBtn.classList.remove('btn-outline-primary');
                documentToggleBtn.classList.add('btn-primary');
            } else {
                icon.className = 'fas fa-folder-open me-1';
                if (text) text.textContent = 'ドキュメント';
                documentToggleBtn.classList.remove('btn-primary');
                documentToggleBtn.classList.add('btn-outline-primary');
            }
        }
        
        // Bootstrap collapse イベントリスナー
        documentSection.addEventListener('shown.bs.collapse', function() {
            updateButtonState(true);
            
            // app-unified.jsの自動初期化に任せる
            // ドキュメントマネージャーは既に初期化されているはず
            console.log('Water documents section opened - using auto-initialized manager');
        });
        
        documentSection.addEventListener('hidden.bs.collapse', function() {
            updateButtonState(false);
        });
        
        // 初期状態の設定
        const isExpanded = documentSection.classList.contains('show');
        updateButtonState(isExpanded);
    }

    // ===== Modal hoisting & z-index fix for document manager =====
    // Some modals rendered inside the collapsible section may appear behind the backdrop
    // due to stacking contexts. We hoist them to <body> and enforce z-index.
    function hoistModals(container) {
        if (!container) return;
        container.querySelectorAll('.modal').forEach(function(modal) {
            // Move modal under body if it's not already there
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
        });
    }

    // Run once on load in case the component already rendered modals
    hoistModals(documentSection);

    // Also run when the documents section is opened
    documentSection.addEventListener('shown.bs.collapse', function () {
        hoistModals(documentSection);
    });

    // Ensure correct stacking orders whenever a modal is shown
    document.addEventListener('show.bs.modal', function (ev) {
        var modalEl = ev.target;
        // enforce higher z-index than local backdrops/parents
        if (modalEl) {
            modalEl.style.zIndex = '2010';
        }
        // Defer backdrop z-index adjustment to next tick (after Bootstrap inserts it)
        setTimeout(function () {
            var backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(function (bd) {
                bd.style.zIndex = '2000';
            });
        }, 0);
    });

    // Clean up any stray backdrops if a modal is hidden unexpectedly
    document.addEventListener('hidden.bs.modal', function () {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 1) {
            // keep the last one (most recent), remove extras
            for (var i = 0; i < backdrops.length - 1; i++) {
                backdrops[i].parentNode.removeChild(backdrops[i]);
            }
        }
    });
});
</script>

<!-- 水道設備ドキュメント管理用CSS -->
<style>
#water-documents-toggle {
    transition: all 0.3s ease;
}

#water-documents-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#water-documents-section .card {
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

#water-documents-section .card-header {
    border-radius: 8px 8px 0 0;
    background: linear-gradient(135deg, #17a2b8, #138496) !important;
}

/* ドキュメント管理エリアのスタイル調整 */
#water-documents-section .lifeline-document-manager {
    border-radius: 0 0 8px 8px;
}

/* モーダルスタイルはapp-unified.cssで統一管理 */

/* ==== Modal stacking fixes for water documents section ==== */
#water-documents-section { 
    overflow: visible; /* avoid creating a clipping context for absolute/fixed elements */
}
/* Ensure Bootstrap modal/backdrop are above collapsed/card content */
.modal-backdrop {
    z-index: 2000 !important;
}
.modal {
    z-index: 2010 !important;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    #water-documents-toggle span {
        display: none !important;
    }
    
    #water-documents-section .card-header h6 {
        font-size: 0.9rem;
    }
}
</style>