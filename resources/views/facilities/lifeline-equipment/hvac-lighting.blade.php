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

<!-- 空調・照明設備ヘッダー（ドキュメントアイコン付き） -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="fas fa-wind text-success me-2"></i>空調・照明設備情報
    </h5>
    <div class="d-flex align-items-center gap-2">
        <!-- ドキュメント管理ボタン -->
        <button type="button" 
                class="btn btn-outline-primary btn-sm" 
                id="hvac-lighting-documents-toggle"
                data-bs-toggle="collapse" 
                data-bs-target="#hvac-lighting-documents-section" 
                aria-expanded="false" 
                aria-controls="hvac-lighting-documents-section"
                title="空調・照明設備ドキュメント管理">
            <i class="fas fa-folder-open me-1"></i>
            <span class="d-none d-md-inline">ドキュメント</span>
        </button>
        

    </div>
</div>

<!-- 空調・照明設備ドキュメント管理セクション（折りたたみ式） -->
<div class="collapse mb-4" id="hvac-lighting-documents-section">
    <div class="card border-success">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0">
                <i class="fas fa-folder-open me-2"></i>空調・照明設備 - 関連ドキュメント
            </h6>
        </div>
        <div class="card-body p-0">
            @if($canEdit)
                <x-lifeline-document-manager 
                    :facility="$facility" 
                    category="hvac_lighting"
                    category-name="空調・照明設備"
                    height="500px"
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
    const documentToggleBtn = document.getElementById('hvac-lighting-documents-toggle');
    const documentSection = document.getElementById('hvac-lighting-documents-section');
    
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
            
            // ドキュメントマネージャーの初期化
            const facilityId = {{ $facility->id }};
            if (window.shiseCalApp && window.shiseCalApp.modules) {
                const lifelineManager = window.shiseCalApp.modules[`lifelineDocumentManager_hvac_lighting`];
                if (lifelineManager && typeof lifelineManager.refresh === 'function') {
                    lifelineManager.refresh();
                }
            }
        });
        
        documentSection.addEventListener('hidden.bs.collapse', function() {
            updateButtonState(false);
        });
        
        // 初期状態の設定
        const isExpanded = documentSection.classList.contains('show');
        updateButtonState(isExpanded);
    }
});
</script>

<!-- 空調・照明設備ドキュメント管理用CSS -->
<style>
#hvac-lighting-documents-toggle {
    transition: all 0.3s ease;
}

#hvac-lighting-documents-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#hvac-lighting-documents-section .card {
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

#hvac-lighting-documents-section .card-header {
    border-radius: 8px 8px 0 0;
    background: linear-gradient(135deg, #28a745, #1e7e34) !important;
}

/* ドキュメント管理エリアのスタイル調整 */
#hvac-lighting-documents-section .lifeline-document-manager {
    border-radius: 0 0 8px 8px;
}

/* 照明設備備考だけ下げる */
.lighting-notes-section {
  margin-top: 3.9rem !important;  /* ← この値を調整して高さを合わせる */
}

/* 共通：列幅を固定レイアウトにして100%にする */
.table.facility-basic-info-table-clean {
  table-layout: fixed;
  width: 100%;
}

/* 1列目（ラベル列）を固定幅にする：ここを揃えれば縦ラインが揃う */
.table.facility-basic-info-table-clean tr > th:first-child,
.table.facility-basic-info-table-clean tr > td:first-child {
  width: 25%;   /* 好きな比率に。例: 25% ラベル / 75% 値 */
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    #hvac-lighting-documents-toggle span {
        display: none !important;
    }
    
    #hvac-lighting-documents-section .card-header h6 {
        font-size: 0.9rem;
    }
}
</style>