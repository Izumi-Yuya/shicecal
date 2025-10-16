@php
use Illuminate\Support\Facades\Storage;

$hvacLightingEquipment = $facility->getHvacLightingEquipment();
$basicInfo = $hvacLightingEquipment?->basic_info ?? [];
$canEdit = auth()->user()->canEditFacility($facility->id);

// 空調設備の基本情報を取得
$hvacInfo = $basicInfo['hvac'] ?? [];

// 照明設備の基本情報を取得
$lightingInfo = $basicInfo['lighting'] ?? [];
@endphp

<!-- 空調・照明設備ヘッダー -->
<div class="mb-3">
    <h5 class="mb-0">
        <i class="fas fa-wind text-success me-2"></i>空調・照明設備情報
    </h5>
</div>

<!-- 隠しドキュメントトリガー（編集ボタンの隣のドキュメントボタンから呼び出される） -->
<button type="button" id="hvac-lighting-documents-toggle" class="d-none" data-bs-toggle="modal" data-bs-target="#hvac-lighting-documents-modal"></button>

<!-- 空調・照明設備ドキュメント管理モーダル -->
<div class="modal fade" id="hvac-lighting-documents-modal" tabindex="-1" aria-labelledby="hvac-lighting-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="hvac-lighting-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>空調・照明設備 - 関連ドキュメント
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body p-0">
                @if($canEdit)
                    <x-lifeline-document-manager 
                        :facility="$facility" 
                        category="hvac_lighting"
                        category-name="空調・照明設備"
                        height="600px"
                        :show-upload="true"
                        :show-create-folder="true"
                        allowed-file-types="pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif"
                        max-file-size="10MB"
                    />
                @else
                    <x-lifeline-document-manager 
                        :facility="$facility" 
                        category="hvac_lighting"
                        category-name="空調・照明設備"
                        height="600px"
                        :show-upload="false"
                        :show-create-folder="false"
                        allowed-file-types="pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif"
                        max-file-size="10MB"
                    />
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>閉じる
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 空調・照明設備情報表示セクション --}}
<div class="hvac-lighting-equipment-sections">
    @if($hvacLightingEquipment)
    <div class="row">
        {{-- 左側：空調設備テーブルと備考 --}}
        <div class="col-md-6">
            {{-- 空調設備テーブル --}}
            <div class="equipment-section mb-4">
                <div class="section-header d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">空調設備</h5>
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
                    tableClass="table table-bordered facility-basic-info-table-clean" />
            </div>

            {{-- 左側備考テーブル --}}
            <div class="equipment-section mb-4">
                @php
                $hvacNotesData = [
                [
                'type' => 'standard',
                'cells' => [
                [
                'label' => '空調設備備考',
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
                    tableClass="table table-bordered facility-basic-info-table-clean" />
            </div>
        </div>

        {{-- 右側：照明設備テーブルと備考 --}}
        <div class="col-md-6">
            {{-- 照明設備テーブル --}}
            <div class="equipment-section mb-4">
                <div class="section-header d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">照明設備</h5>
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
                    tableClass="table table-bordered facility-basic-info-table-clean" />
            </div>

            {{-- 右側備考テーブル --}}
            <div class="equipment-section lighting-notes-section">
                @php
                $lightingNotesData = [
                [
                'type' => 'standard',
                'cells' => [
                [
                'label' => '照明設備備考',
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
                    tableClass="table table-bordered facility-basic-info-table-clean" />
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        空調・照明設備の詳細情報は現在準備中です。
    </div>
    @endif
</div>



<!-- 空調・照明設備ドキュメント管理用JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // モーダルをbody直下に移動（hoisting）
    const modal = document.getElementById('hvac-lighting-documents-modal');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
        console.log('[HVAC-Lighting] Modal hoisted to body');
    }
    
    // モーダル表示時の処理
    const hvacModal = document.getElementById('hvac-lighting-documents-modal');
    if (hvacModal) {
        hvacModal.addEventListener('shown.bs.modal', function() {
            console.log('[HVAC-Lighting] Modal shown, initializing document manager');
            
            // ドキュメントマネージャーの初期化
            const facilityId = {{ $facility->id }};
            if (window.shiseCalApp && window.shiseCalApp.modules) {
                const lifelineManager = window.shiseCalApp.modules[`lifelineDocumentManager_hvac_lighting`];
                if (lifelineManager && typeof lifelineManager.refresh === 'function') {
                    lifelineManager.refresh();
                    console.log('[HVAC-Lighting] Document manager refreshed');
                }
            }
        });
    }
    
    // z-index動的設定
    document.addEventListener('show.bs.modal', function(ev) {
        if (ev.target && ev.target.id && ev.target.id.includes('hvac-lighting')) {
            const isMainModal = ev.target.id === 'hvac-lighting-documents-modal';
            ev.target.style.zIndex = isMainModal ? '9999' : '10000';
            console.log('[HVAC-Lighting] Modal z-index set:', ev.target.id, ev.target.style.zIndex);
            
            setTimeout(function() {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(bd, i, arr) {
                    const zIndex = i === arr.length - 1 ? (isMainModal ? '9998' : '9999') : '9998';
                    bd.style.zIndex = zIndex;
                });
                console.log('[HVAC-Lighting] Backdrop z-index updated, count:', backdrops.length);
            }, 0);
        }
    });
    
    // バックドロップクリーンアップ
    document.addEventListener('hidden.bs.modal', function(ev) {
        if (ev.target && ev.target.id && ev.target.id.includes('hvac-lighting')) {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 1) {
                for (let i = 0; i < backdrops.length - 1; i++) {
                    backdrops[i].parentNode.removeChild(backdrops[i]);
                }
                console.log('[HVAC-Lighting] Cleaned up extra backdrops');
            }
        }
    });
});
</script>

<!-- 空調・照明設備ドキュメント管理用CSS -->
<style>
/* ==== Modal stacking fixes for hvac-lighting documents ==== */
#hvac-lighting-documents-modal {
    z-index: 9999 !important;
}

#hvac-lighting-documents-modal .modal-dialog {
    max-width: 90%;
    margin: 1.75rem auto;
}

#hvac-lighting-documents-modal .modal-body {
    min-height: 500px;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

/* ネストされたモーダル */
#create-folder-modal-hvac_lighting,
#upload-file-modal-hvac_lighting,
#rename-modal-hvac_lighting,
#properties-modal-hvac_lighting {
    z-index: 10000 !important;
}

/* pointer-events設定 */
.modal button,
.modal input,
.modal select,
.modal textarea,
.modal a,
.modal label {
    pointer-events: auto !important;
}

.document-item,
.document-item * {
    pointer-events: auto !important;
}

/* 照明設備備考だけ下げる */
.lighting-notes-section {
  margin-top: 3.9rem !important;
}

/* 共通：列幅を固定レイアウトにして100%にする */
.table.facility-basic-info-table-clean {
  table-layout: fixed;
  width: 100%;
}

/* 1列目（ラベル列）を固定幅にする：ここを揃えれば縦ラインが揃う */
.table.facility-basic-info-table-clean tr > th:first-child,
.table.facility-basic-info-table-clean tr > td:first-child {
  width: 25%;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    #hvac-lighting-documents-modal .modal-dialog {
        max-width: 95%;
        margin: 0.5rem auto;
    }
}
</style>