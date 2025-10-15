{{-- 修繕履歴データの準備 --}}
@php
    // データが渡されていない場合（facilities.show から直接インクルードされた場合）は、ここで準備する
    if (!isset($exteriorHistory)) {
        $exteriorHistory = $facility->maintenanceHistories()
            ->where('category', 'exterior')
            ->orderBy('maintenance_date', 'desc')
            ->get()
            ->groupBy('subcategory');
    }
    
    if (!isset($interiorHistory)) {
        $interiorHistory = $facility->maintenanceHistories()
            ->where('category', 'interior')
            ->orderBy('maintenance_date', 'desc')
            ->get();
    }
    
    if (!isset($summerCondensationHistory)) {
        $summerCondensationHistory = $facility->maintenanceHistories()
            ->where('category', 'summer_condensation')
            ->orderBy('maintenance_date', 'desc')
            ->get();
    }
    
    if (!isset($otherHistory)) {
        $otherHistory = $facility->maintenanceHistories()
            ->where('category', 'other')
            ->orderBy('maintenance_date', 'desc')
            ->get();
    }
@endphp

<!-- 修繕履歴サブタブ -->
<div class="repair-history-container">
    <!-- サブタブナビゲーション -->
    <ul class="nav nav-tabs repair-history-subtabs mb-4" id="repairHistoryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="exterior-tab" data-bs-toggle="tab" 
                    data-bs-target="#exterior" type="button" role="tab" 
                    aria-controls="exterior" aria-selected="true">
                <i class="fas fa-building me-2"></i>外装
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="interior-tab" data-bs-toggle="tab" 
                    data-bs-target="#interior" type="button" role="tab" 
                    aria-controls="interior" aria-selected="false">
                <i class="fas fa-paint-brush me-2"></i>内装
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="summer-condensation-tab" data-bs-toggle="tab" 
                    data-bs-target="#summer-condensation" type="button" role="tab" 
                    aria-controls="summer-condensation" aria-selected="false">
                <i class="fas fa-droplet me-2"></i>夏型結露
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="other-tab" data-bs-toggle="tab" 
                    data-bs-target="#other" type="button" role="tab" 
                    aria-controls="other" aria-selected="false">
                <i class="fas fa-tools me-2"></i>その他
            </button>
        </li>
    </ul>

    <!-- サブタブコンテンツ -->
    <div class="tab-content" id="repairHistoryTabContent">
        <!-- 外装タブ -->
        <div class="tab-pane fade show active" id="exterior" role="tabpanel" aria-labelledby="exterior-tab">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1"></div>
                <div class="btn-group" role="group">
                    @if(auth()->user()->canEditFacility($facility->id))
                        <a href="{{ route('facilities.repair-history.edit', ['facility' => $facility->id, 'category' => 'exterior']) }}" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-2"></i>編集
                        </a>
                    @endif
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#maintenance-documents-modal-exterior">
                        <i class="fas fa-folder me-2"></i>ドキュメント
                    </button>
                </div>
            </div>

            @include('facilities.repair-history.partials.exterior-tab')
        </div>

        <!-- 内装タブ -->
        <div class="tab-pane fade" id="interior" role="tabpanel" aria-labelledby="interior-tab">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1"></div>
                <div class="btn-group" role="group">
                    @if(auth()->user()->canEditFacility($facility->id))
                        <a href="{{ route('facilities.repair-history.edit', ['facility' => $facility->id, 'category' => 'interior']) }}" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-2"></i>編集
                        </a>
                    @endif
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#maintenance-documents-modal-interior">
                        <i class="fas fa-folder me-2"></i>ドキュメント
                    </button>
                </div>
            </div>

            @include('facilities.repair-history.partials.interior-tab')
        </div>

        <!-- 夏型結露タブ -->
        <div class="tab-pane fade" id="summer-condensation" role="tabpanel" aria-labelledby="summer-condensation-tab">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1"></div>
                <div class="btn-group" role="group">
                    @if(auth()->user()->canEditFacility($facility->id))
                        <a href="{{ route('facilities.repair-history.edit', ['facility' => $facility->id, 'category' => 'summer_condensation']) }}" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-2"></i>編集
                        </a>
                    @endif
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#maintenance-documents-modal-summer-condensation">
                        <i class="fas fa-folder me-2"></i>ドキュメント
                    </button>
                </div>
            </div>

            @include('facilities.repair-history.partials.summer-condensation-tab')
        </div>

        <!-- その他タブ -->
        <div class="tab-pane fade" id="other" role="tabpanel" aria-labelledby="other-tab">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1"></div>
                <div class="btn-group" role="group">
                    @if(auth()->user()->canEditFacility($facility->id))
                        <a href="{{ route('facilities.repair-history.edit', ['facility' => $facility->id, 'category' => 'other']) }}" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-2"></i>編集
                        </a>
                    @endif
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#maintenance-documents-modal-other">
                        <i class="fas fa-folder me-2"></i>ドキュメント
                    </button>
                </div>
            </div>

            @include('facilities.repair-history.partials.other-tab')
        </div>
    </div>
</div>

{{-- ドキュメント管理モーダル --}}
<!-- 外装ドキュメントモーダル -->
<div class="modal fade" id="maintenance-documents-modal-exterior" tabindex="-1" aria-labelledby="maintenance-documents-modal-exterior-label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="maintenance-documents-modal-exterior-label">
                    <i class="fas fa-folder me-2"></i>外装関連ドキュメント
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">契約書、見積書、請求書、施工写真、報告書などの関連ドキュメントを管理できます。</p>
                <x-maintenance-document-manager 
                    :facility="$facility" 
                    category="exterior"
                    categoryName="外装"
                />
            </div>
        </div>
    </div>
</div>

<!-- 内装ドキュメントモーダル -->
<div class="modal fade" id="maintenance-documents-modal-interior" tabindex="-1" aria-labelledby="maintenance-documents-modal-interior-label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="maintenance-documents-modal-interior-label">
                    <i class="fas fa-folder me-2"></i>内装関連ドキュメント
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">契約書、見積書、請求書、施工写真、報告書などの関連ドキュメントを管理できます。</p>
                <x-maintenance-document-manager 
                    :facility="$facility" 
                    category="interior"
                    categoryName="内装"
                />
            </div>
        </div>
    </div>
</div>

<!-- 夏型結露ドキュメントモーダル -->
<div class="modal fade" id="maintenance-documents-modal-summer-condensation" tabindex="-1" aria-labelledby="maintenance-documents-modal-summer-condensation-label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="maintenance-documents-modal-summer-condensation-label">
                    <i class="fas fa-folder me-2"></i>夏型結露関連ドキュメント
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">契約書、見積書、請求書、施工写真、報告書などの関連ドキュメントを管理できます。</p>
                <x-maintenance-document-manager 
                    :facility="$facility" 
                    category="summer_condensation"
                    categoryName="夏型結露"
                />
            </div>
        </div>
    </div>
</div>

<!-- その他ドキュメントモーダル -->
<div class="modal fade" id="maintenance-documents-modal-other" tabindex="-1" aria-labelledby="maintenance-documents-modal-other-label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="maintenance-documents-modal-other-label">
                    <i class="fas fa-folder me-2"></i>その他関連ドキュメント
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">契約書、見積書、請求書、施工写真、報告書などの関連ドキュメントを管理できます。</p>
                <x-maintenance-document-manager 
                    :facility="$facility" 
                    category="other"
                    categoryName="その他"
                />
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Repair History Subtabs Styling */
.repair-history-container .repair-history-subtabs {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 1.5rem;
}

.repair-history-container .repair-history-subtabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    background: none;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    margin-bottom: -2px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.repair-history-container .repair-history-subtabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.repair-history-container .repair-history-subtabs .nav-link.active {
    color: #fd7e14;
    border-bottom-color: #fd7e14;
    background-color: transparent;
    font-weight: 600;
}

/* Repair History specific component styles */
.repair-history-equipment-sections .section-header h5 {
    font-weight: 600;
}

.repair-history-table {
    margin-bottom: 0;
}

.repair-history-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border: 1px solid #dee2e6;
    padding: 0.75rem 0.5rem;
    text-align: center;
    vertical-align: middle;
}

.repair-history-table td {
    border: 1px solid #dee2e6;
    padding: 0.5rem;
    vertical-align: middle;
}

.repair-history-table .table-scroll {
    max-height: 400px;
    overflow-y: auto;
}

/* 金額フォーマット */
.amount-format {
    text-align: right;
    font-family: 'Courier New', monospace;
}

/* 特記事項セクション */
.special-notes-section {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-top: 1rem;
}

.special-notes-section h6 {
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
}

.special-notes-content {
    white-space: pre-wrap;
    word-wrap: break-word;
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.5;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Switch tabs based on URL hash or session activeSubTab
    const hash = window.location.hash;
    const activeSubTab = @json(session('activeSubTab', ''));
    
    // Tab switching based on hash
    if (hash === '#interior' || activeSubTab === 'interior') {
        const interiorTab = document.getElementById('interior-tab');
        if (interiorTab) {
            const interiorTabInstance = new bootstrap.Tab(interiorTab);
            interiorTabInstance.show();
        }
    } else if (hash === '#summer-condensation' || activeSubTab === 'summer_condensation') {
        const summerCondensationTab = document.getElementById('summer-condensation-tab');
        if (summerCondensationTab) {
            const summerCondensationTabInstance = new bootstrap.Tab(summerCondensationTab);
            summerCondensationTabInstance.show();
        }
    } else if (hash === '#other' || activeSubTab === 'other') {
        const otherTab = document.getElementById('other-tab');
        if (otherTab) {
            const otherTabInstance = new bootstrap.Tab(otherTab);
            otherTabInstance.show();
        }
    }
    
    // Event listener for tab switching
    const repairHistoryTabs = document.querySelectorAll('#repairHistoryTabs button[data-bs-toggle="tab"]');
    repairHistoryTabs.forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function(event) {
            const targetId = event.target.getAttribute('data-bs-target');
            // Update URL hash (optional)
            if (targetId !== '#exterior') {
                window.history.replaceState(null, null, targetId);
            } else {
                // Remove hash for exterior tab
                window.history.replaceState(null, null, window.location.pathname + window.location.search);
            }
        });
    });
});
</script>
@endpush