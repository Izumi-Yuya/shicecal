@php
    $electricalLifeline = $facility->getLifelineEquipmentByCategory('electrical');
    $electricalEquipment = $electricalLifeline?->electricalEquipment;
    $basicInfo = $electricalEquipment->basic_info ?? [];
    $pasInfo = $electricalEquipment->pas_info ?? [];
    $cubicleInfo = $electricalEquipment->cubicle_info ?? [];
    $generatorInfo = $electricalEquipment->generator_info ?? [];
    $cubicleEquipmentList = $cubicleInfo['equipment_list'] ?? [];
    $generatorEquipmentList = $generatorInfo['equipment_list'] ?? [];
    $canEdit = auth()->user()->canEditFacility($facility->id);
@endphp

<!-- 電気設備ヘッダー（ドキュメントアイコン付き） -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="fas fa-bolt text-warning me-2"></i>電気設備情報
    </h5>
    <div class="d-flex align-items-center gap-2">
        <!-- ドキュメント管理ボタン -->
        <button type="button" 
                class="btn btn-outline-primary btn-sm" 
                id="electrical-documents-toggle"
                data-bs-toggle="collapse" 
                data-bs-target="#electrical-documents-section" 
                aria-expanded="false" 
                aria-controls="electrical-documents-section"
                title="電気設備ドキュメント管理">
            <i class="fas fa-folder-open me-1"></i>
            <span class="d-none d-md-inline">ドキュメント</span>
        </button>
        

    </div>
</div>

<!-- 電気設備ドキュメント管理セクション（折りたたみ式） -->
<div class="collapse mb-4" id="electrical-documents-section">
    <div class="card border-primary">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
                <i class="fas fa-folder-open me-2"></i>電気設備 - 関連ドキュメント
            </h6>
        </div>
        <div class="card-body p-0">
            <div data-lifeline-category="electrical">
                @if($canEdit)
                    <x-lifeline-document-manager 
                        :facility="$facility" 
                        category="electrical"
                        category-name="電気設備"
                        height="500px"
                        :show-upload="true"
                        :show-create-folder="true"
                        allowed-file-types="pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif"
                        max-file-size="10MB"
                    />
                @else
                    <x-lifeline-document-manager 
                        :facility="$facility" 
                        category="electrical"
                        category-name="電気設備"
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
</div>

<!-- 基本情報テーブル -->
<div class="table-responsive mb-3">
    <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
        <tbody>
            <tr>
                <td class="detail-label" style="padding: 0.5rem;">電力会社</td>
                <td class="detail-value {{ empty($basicInfo['electrical_contractor']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    {{ $basicInfo['electrical_contractor'] ?? '未設定' }}
                </td>
                <td class="detail-label" style="padding: 0.5rem;">保安管理業者</td>
                <td class="detail-value {{ empty($basicInfo['safety_management_company']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    {{ $basicInfo['safety_management_company'] ?? '未設定' }}
                </td>
            </tr>
            <tr>
                <td class="detail-label" style="padding: 0.5rem;">保守点検実施日</td>
                <td class="detail-value {{ empty($basicInfo['maintenance_inspection_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    @if(!empty($basicInfo['maintenance_inspection_date']))
                        {{ \Carbon\Carbon::parse($basicInfo['maintenance_inspection_date'])->format('Y年m月d日') }}
                    @else
                        未設定
                    @endif
                </td>
                <td class="detail-label" style="padding: 0.5rem;">点検報告書</td>
                <td class="detail-value {{ empty($basicInfo['inspection']['inspection_report_pdf']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    @if(!empty($basicInfo['inspection']['inspection_report_pdf']))
                        <a href="{{ route('facilities.lifeline-equipment.download-file', [$facility, 'electrical', 'inspection_report']) }}" 
                           class="text-decoration-none" 
                           aria-label="点検報告書PDFをダウンロード"
                           target="_blank">
                            <i class="fas fa-file-pdf me-1 text-danger" aria-hidden="true"></i>{{ $basicInfo['inspection']['inspection_report_pdf'] }}
                        </a>
                    @else
                        未設定
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
</div>
<!-- PASテーブル -->
<div class="mb-3">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">PAS</h6>
    <div class="table-responsive">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">有無</td>
                    <td class="detail-value {{ empty($pasInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        {{ $pasInfo['availability'] ?? '未設定' }}
                    </td>
                    <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                    <td class="detail-value {{ empty($pasInfo['update_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($pasInfo['update_date']))
                            {{ \Carbon\Carbon::parse($pasInfo['update_date'])->format('Y年m月d日') }}
                        @else
                            未設定
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<!-- キュービクルテーブル -->
<div class="mb-3" style="position: relative; overflow: visible;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">キュービクル</h6>
    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0; table-layout: fixed;">
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
                    <td class="detail-label" style="padding: 0.5rem;">有無</td>
                    <td class="detail-value {{ empty($cubicleInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="5">
                        {{ $cubicleInfo['availability'] ?? '未設定' }}
                    </td>
                </tr>
                @if(!empty($cubicleInfo['availability']) && $cubicleInfo['availability'] === '有')
                    @if(!empty($cubicleEquipmentList) && is_array($cubicleEquipmentList))
                        @foreach($cubicleEquipmentList as $index => $equipment)
                            <tr style="position: relative;">
                                <td class="detail-label" style="padding: 0.5rem; position: relative;">
                                    <div style="position: absolute; left: -30px; top: 50%; transform: translateY(-50%); z-index: 1000;">
                                        <span style="background: #007bff; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">{{ $index + 1 }}</span>
                                    </div>
                                    メーカー
                                </td>
                                <td class="detail-value {{ empty($equipment['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $equipment['manufacturer'] ?? '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">年式</td>
                                <td class="detail-value {{ empty($equipment['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['model_year']))
                                        {{ $equipment['model_year'] }}年式
                                    @else
                                        未設定
                                    @endif
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                                <td class="detail-value {{ empty($equipment['update_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['update_date']))
                                        {{ \Carbon\Carbon::parse($equipment['update_date'])->format('Y年m月d日') }}
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">メーカー</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                            <td class="detail-label" style="padding: 0.5rem;">年式</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                            <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                        </tr>
                    @endif
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- 非常用発電機テーブル -->
<div class="mb-3" style="position: relative; overflow: visible;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">非常用発電機</h6>
    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0; table-layout: fixed;">
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
                    <td class="detail-label" style="padding: 0.5rem;">有無</td>
                    <td class="detail-value {{ empty($generatorInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="5">
                        {{ $generatorInfo['availability'] ?? '未設定' }}
                    </td>
                </tr>
                @if(!empty($generatorInfo['availability']) && $generatorInfo['availability'] === '有')
                    @if(!empty($generatorEquipmentList) && is_array($generatorEquipmentList))
                        @foreach($generatorEquipmentList as $index => $equipment)
                            <tr style="position: relative;">
                                <td class="detail-label" style="padding: 0.5rem; position: relative;">
                                    <div style="position: absolute; left: -30px; top: 50%; transform: translateY(-50%); z-index: 1000;">
                                        <span style="background: #007bff; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">{{ $index + 1 }}</span>
                                    </div>
                                    メーカー
                                </td>
                                <td class="detail-value {{ empty($equipment['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $equipment['manufacturer'] ?? '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">年式</td>
                                <td class="detail-value {{ empty($equipment['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['model_year']))
                                        {{ $equipment['model_year'] }}年式
                                    @else
                                        未設定
                                    @endif
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                                <td class="detail-value {{ empty($equipment['update_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['update_date']))
                                        {{ \Carbon\Carbon::parse($equipment['update_date'])->format('Y年m月d日') }}
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="detail-label" style="padding: 0.5rem;">メーカー</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                            <td class="detail-label" style="padding: 0.5rem;">年式</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                            <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                            <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                        </tr>
                    @endif
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- 備考テーブル -->
<div class="mb-3">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">備考</h6>
    <div class="table-responsive">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-value {{ empty($electricalEquipment->notes) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        {{ $electricalEquipment->notes ?? '未設定' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>




<!-- 電気設備ドキュメント管理用JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const documentToggleBtn = document.getElementById('electrical-documents-toggle');
    const documentSection = document.getElementById('electrical-documents-section');
    
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
            console.log('Electrical documents section opened - using auto-initialized manager');
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

<!-- 電気設備ドキュメント管理用CSS -->
<style>
#electrical-documents-toggle {
    transition: all 0.3s ease;
}

#electrical-documents-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#electrical-documents-section .card {
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

#electrical-documents-section .card-header {
    border-radius: 8px 8px 0 0;
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

/* ドキュメント管理エリアのスタイル調整 */
#electrical-documents-section .lifeline-document-manager {
    border-radius: 0 0 8px 8px;
}

/* モーダルスタイルはapp-unified.cssで統一管理 */

/* ==== Modal stacking fixes for electrical documents section ==== */
#electrical-documents-section { 
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
    #electrical-documents-toggle span {
        display: none !important;
    }
    
    #electrical-documents-section .card-header h6 {
        font-size: 0.9rem;
    }
}
</style>