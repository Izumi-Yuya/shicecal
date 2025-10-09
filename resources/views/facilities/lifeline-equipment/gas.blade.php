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
                data-bs-toggle="collapse" 
                data-bs-target="#gas-documents-section" 
                aria-expanded="false" 
                aria-controls="gas-documents-section"
                title="ガス設備ドキュメント管理">
            <i class="fas fa-folder-open me-1"></i>
            <span class="d-none d-md-inline">ドキュメント</span>
        </button>
        

    </div>
</div>

<!-- ガス設備ドキュメント管理セクション（折りたたみ式） -->
<div class="collapse mb-4" id="gas-documents-section">
    <div class="card border-danger">
        <div class="card-header bg-danger text-white">
            <h6 class="mb-0">
                <i class="fas fa-folder-open me-2"></i>ガス設備 - 関連ドキュメント
            </h6>
        </div>
        <div class="card-body p-0">
            @if($canEdit)
                <x-lifeline-document-manager 
                    :facility="$facility" 
                    category="gas"
                    category-name="ガス設備"
                    height="500px"
                    :show-upload="true"
                    :show-create-folder="true"
                    allowed-file-types="pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif"
                    max-file-size="10MB"
                />
            @else
                <x-lifeline-document-manager 
                    :facility="$facility" 
                    category="gas"
                    category-name="ガス設備"
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
                <div class="gas-equipment-wrapper mb-3 numbered-equipment">
                    <div class="equipment-number-badge">
                        <span class="badge bg-success">{{ $index + 1 }}</span>
                    </div>
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
                                        <td class="detail-label" style="padding: 0.5rem; border: 1px solid #e9ecef !important;">メーカー</td>
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
document.addEventListener('DOMContentLoaded', function() {
    const documentToggleBtn = document.getElementById('gas-documents-toggle');
    const documentSection = document.getElementById('gas-documents-section');
    
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
            console.log('Gas documents section opened - using auto-initialized manager');
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

<!-- ガス設備ドキュメント管理用CSS -->
<style>
#gas-documents-toggle {
    transition: all 0.3s ease;
}

#gas-documents-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#gas-documents-section .card {
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

#gas-documents-section .card-header {
    border-radius: 8px 8px 0 0;
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
}

/* ドキュメント管理エリアのスタイル調整 */
#gas-documents-section .lifeline-document-manager {
    border-radius: 0 0 8px 8px;
}

/* モーダルスタイルはapp-unified.cssで統一管理 */

/* ==== Modal stacking fixes for gas documents section ==== */
#gas-documents-section { 
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
    #gas-documents-toggle span {
        display: none !important;
    }
    
    #gas-documents-section .card-header h6 {
        font-size: 0.9rem;
    }
}
</style>