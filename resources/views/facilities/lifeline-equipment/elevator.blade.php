@php
    use Illuminate\Support\Facades\Storage;
    
    $elevatorEquipment = $facility->getElevatorEquipment();
    $basicInfo = $elevatorEquipment?->basic_info ?? [];
    $elevators = $basicInfo['elevators'] ?? [];
    $inspectionInfo = $basicInfo['inspection'] ?? [];
    $canEdit = auth()->user()->canEditFacility($facility->id);
    
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

<!-- エレベーター設備ヘッダー（ドキュメントアイコン付き） -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="fas fa-elevator text-secondary me-2"></i>エレベーター設備情報
    </h5>
    <div class="d-flex align-items-center gap-2">
        <!-- ドキュメント管理ボタン -->
        <button type="button" 
                class="btn btn-outline-primary btn-sm" 
                id="elevator-documents-toggle"
                data-bs-toggle="collapse" 
                data-bs-target="#elevator-documents-section" 
                aria-expanded="false" 
                aria-controls="elevator-documents-section"
                title="エレベーター設備ドキュメント管理">
            <i class="fas fa-folder-open me-1"></i>
            <span class="d-none d-md-inline">ドキュメント</span>
        </button>
        

    </div>
</div>

<!-- エレベーター設備ドキュメント管理セクション（折りたたみ式） -->
<div class="collapse mb-4" id="elevator-documents-section">
    <div class="card border-secondary">
        <div class="card-header bg-secondary text-white">
            <h6 class="mb-0">
                <i class="fas fa-folder-open me-2"></i>エレベーター設備 - 関連ドキュメント
            </h6>
        </div>
        <div class="card-body p-0">
            @if($canEdit)
                <x-lifeline-document-manager 
                    :facility="$facility" 
                    category="elevator"
                    category-name="エレベーター設備"
                    height="500px"
                    :show-upload="true"
                    :show-create-folder="true"
                    allowed-file-types="pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif"
                    max-file-size="10MB"
                />
            @else
                <x-lifeline-document-manager 
                    :facility="$facility" 
                    category="elevator"
                    category-name="エレベーター設備"
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

{{-- エレベーター設備表示カード --}}
<div class="elevator-equipment-sections">
    @if($elevatorEquipment)
        {{-- 基本情報セクション --}}
        <div class="equipment-section mb-4">
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
        @endif

        {{-- 備考セクション --}}
        <div class="equipment-section mb-4">
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



<!-- エレベーター設備ドキュメント管理用JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const documentToggleBtn = document.getElementById('elevator-documents-toggle');
    const documentSection = document.getElementById('elevator-documents-section');
    
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
            console.log('Elevator documents section opened - using auto-initialized manager');
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

<!-- エレベーター設備ドキュメント管理用CSS -->
<style>
#elevator-documents-toggle {
    transition: all 0.3s ease;
}

#elevator-documents-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#elevator-documents-section .card {
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

#elevator-documents-section .card-header {
    border-radius: 8px 8px 0 0;
    background: linear-gradient(135deg, #6c757d, #5a6268) !important;
}

/* ドキュメント管理エリアのスタイル調整 */
#elevator-documents-section .lifeline-document-manager {
    border-radius: 0 0 8px 8px;
}

/* モーダルスタイルはapp-unified.cssで統一管理 */

/* ==== Modal stacking fixes for elevator documents section ==== */
#elevator-documents-section { 
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
    #elevator-documents-toggle span {
        display: none !important;
    }
    
    #elevator-documents-section .card-header h6 {
        font-size: 0.9rem;
    }
}
</style>