@php
    $gasEquipment = $facility->getGasEquipment();
    $basicInfo = $gasEquipment?->basic_info ?? [];
    $waterHeaterInfo = $basicInfo['water_heater_info'] ?? [];
    $waterHeaters = $waterHeaterInfo['water_heaters'] ?? [];
    $canEdit = auth()->user()->canEditFacility($facility->id);
@endphp

<!-- ガス設備ヘッダー（ドキュメントアイコン付き） -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="fas fa-fire text-danger me-2"></i>ガス設備情報
    </h5>
    <div class="d-flex align-items-center gap-2">
        <!-- ドキュメント管理ボタン -->
        <button type="button" 
                class="btn btn-outline-primary btn-sm" 
                id="gas-documents-toggle"
                title="ガス設備ドキュメント管理">
            <i class="fas fa-folder-open me-1"></i>
            <span class="d-none d-md-inline">ドキュメント</span>
        </button>
    </div>
</div>

@php

    // 基本情報テーブルデータの構築
    $basicInfoData = [
        // 第1行：2カラム（ガス契約会社、種類）
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'ガス契約会社', 'value' => $basicInfo['gas_supplier'] ?? null, 'type' => 'text', 'width' => '50%'],
                ['label' => 'ガスの種類', 'value' => $basicInfo['gas_type'] ?? null, 'type' => 'text', 'width' => '50%'],
            ]
        ],
    ];

    // 給湯器情報の処理
    $waterHeaterInfo = $basicInfo['water_heater_info'] ?? [];
    $waterHeaters = [];
    
    if (isset($waterHeaterInfo['water_heaters']) && is_array($waterHeaterInfo['water_heaters'])) {
        $waterHeaters = $waterHeaterInfo['water_heaters'];
    }



    // 床暖房テーブルデータの構築
    $floorHeatingInfo = $basicInfo['floor_heating_info'] ?? [];
    $floorHeatingData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'メーカー', 'value' => $floorHeatingInfo['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
                ['label' => '年式', 'value' => !empty($floorHeatingInfo['model_year']) ? $floorHeatingInfo['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
                ['label' => '更新年月日', 'value' => $floorHeatingInfo['update_date'] ?? null, 'type' => 'date', 'width' => '33.33%'],
            ]
        ],
    ];



    // 備考テーブルデータの構築
    $notesData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '備考', 'value' => $gasEquipment?->notes ?? null, 'type' => 'text', 'width' => '100%'],
            ]
        ],
    ];

    // 給湯器データセットの構築（水道の加圧ポンプロジックを参考）
    $waterHeaterDataSets = [];
    
    // 設置の有無を最初に追加
    $waterHeaterDataSets[] = [
        'type' => 'availability',
        'data' => [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '設置の有無', 'value' => $waterHeaterInfo['availability'] ?? null, 'type' => 'text', 'width' => '100%'],
                ]
            ],
        ]
    ];
    
    // 給湯器が配列形式（複数台）の場合
    if (($waterHeaterInfo['availability'] ?? '') === '有' && !empty($waterHeaters)) {
        foreach ($waterHeaters as $index => $heater) {
            $waterHeaterDataSets[] = [
                'type' => 'equipment',
                'number' => $index + 1,
                'data' => [
                    [
                        'type' => 'standard',
                        'cells' => [
                            ['label' => 'メーカー', 'value' => $heater['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
                            ['label' => '年式', 'value' => !empty($heater['model_year']) ? $heater['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
                            ['label' => '更新年月日', 'value' => $heater['update_date'] ?? null, 'type' => 'date', 'width' => '33.33%'],
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
        <div class="gas-four-column-equal">
            <x-common-table 
                :data="$basicInfoData"
                :showHeader="false"
                :tableAttributes="['class' => 'table table-bordered gas-info-table']"
                bodyClass=""
                cardClass=""
                tableClass="table table-bordered facility-basic-info-table-clean"
            />
        </div>
    </div>

    <!-- 給湯器セクション -->
    <div class="equipment-section mb-4">
        <div class="section-header mb-3">
            <h6 class="section-title mb-0">
                給湯器
            </h6>
        </div>

        <!-- 設置の有無テーブル -->
        <div class="gas-six-column-equal">
            <div class="table-responsive">
                <table class="table facility-basic-info-table-clean" style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;">
                    <colgroup>
                        <col style="width: 16.67%;">
                        <col style="width: 83.33%;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">設置の有無</td>
                            <td class="detail-value {{ empty($waterHeaterInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">
                                {{ $waterHeaterInfo['availability'] ?? '未設定' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 給湯器設備一覧（設置の有無が「有」の場合のみ表示） -->
        @if(($waterHeaterInfo['availability'] ?? '') === '有' && !empty($waterHeaters))
            @foreach($waterHeaters as $index => $heater)
                <div class="gas-equipment-wrapper mb-3">
                    <div class="gas-six-column-equal">
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
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">メーカー{{ $index + 1 }}</td>
                                        <td class="detail-value {{ empty($heater['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">
                                            {{ $heater['manufacturer'] ?? '未設定' }}
                                        </td>
                                        <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">年式</td>
                                        <td class="detail-value {{ empty($heater['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">
                                            {{ !empty($heater['model_year']) ? $heater['model_year'] . '年式' : '未設定' }}
                                        </td>
                                        <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">更新年月日</td>
                                        <td class="detail-value {{ empty($heater['update_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">
                                            @if(!empty($heater['update_date']))
                                                {{ \Carbon\Carbon::parse($heater['update_date'])->format('Y年m月d日') }}
                                            @else
                                                未設定
                                            @endif
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @elseif(($waterHeaterInfo['availability'] ?? '') === '有')
            <div class="no-equipment-message">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    給湯器設備の詳細情報が登録されていません。
                </div>
            </div>
        @endif
    </div>


    <!-- 床暖房セクション -->
    <div class="equipment-section mb-4">
        <div class="section-header mb-3">
            <h6 class="section-title mb-0">
                床暖房
            </h6>
        </div>

        <div class="gas-six-column-equal">
            <x-common-table 
                :data="$floorHeatingData"
                :showHeader="false"
                :tableAttributes="['class' => 'table table-bordered floor-heating-info-table']"
                bodyClass=""
                cardClass=""
                tableClass="table table-bordered facility-basic-info-table-clean"
            />
        </div>
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


<!-- ガス設備ドキュメント管理用JavaScript -->
<script>
// モーダル形式に変更したため、折りたたみ関連のJavaScriptは不要
// ドキュメントマネージャーはapp-unified.jsで自動初期化される
</script>

<!-- ガス設備ドキュメント管理用CSS -->
<style>
/* モーダル形式に変更したため、折りたたみ関連のCSSは不要 */
/* モーダルスタイルはapp-unified.cssで統一管理 */
</style>

<!-- ガス設備ドキュメント管理モーダル -->
<div class="modal fade" id="gas-documents-modal" tabindex="-1" aria-labelledby="gas-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="gas-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>ガス設備ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <x-lifeline-document-manager
                    :facility="$facility"
                    category="gas"
                    categoryName="ガス設備"
                />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>
