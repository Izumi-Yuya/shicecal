{{-- Standardized Basic Info Table View Partial --}}
@php
    $tableConfig = app('App\Services\TableConfigService')->getTableConfig('basic_info');
    $tableData = app('App\Services\TableDataFormatter')->formatTableData($facility->toArray(), $tableConfig);
@endphp

<div class="facility-table-view">
    <!-- 基本情報セクション -->
    <div class="table-section mb-4">
        <!-- テーブルヘッダー -->
        <div class="table-header mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-table text-primary me-2"></i>基本情報（テーブル形式）
                </h5>
                <div class="table-actions">
                    <button class="btn btn-sm btn-outline-secondary comment-toggle-btn" 
                            data-section="basic_info"
                            data-bs-toggle="tooltip" 
                            title="基本情報のコメントを表示/非表示">
                        <i class="fas fa-comments me-1"></i>コメント
                        <span class="badge bg-secondary ms-1" id="basic-info-comment-count">
                            {{ $facility->comments()->where('section', 'basic_info')->count() }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Universal Table Component -->
        <x-universal-table 
            :table-id="'basic-info-table'"
            :config="$tableConfig"
            :data="$tableData"
            :section="'basic_info'"
            :comment-enabled="true"
            :responsive="true"
            :facility="$facility"
        />

        <!-- 基本情報コメントセクション -->
        <div class="table-comment-wrapper mt-3" id="basic-info-comments" style="display: none;">
            <x-table-comment-section 
                section="basic_info"
                display-name="基本情報"
                :facility="$facility"
            />
        </div>
    </div>

    <!-- サービス種類テーブル -->
    @include('facilities.services.partials.standardized-table', ['services' => $facility->services ?? collect(), 'facility' => $facility])
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // コメント表示/非表示の切り替え
    document.querySelectorAll('.comment-toggle-btn').forEach(button => {
        button.addEventListener('click', function() {
            const section = this.dataset.section;
            const commentsWrapper = document.getElementById(`${section}-comments`);
            const icon = this.querySelector('i');
            
            if (commentsWrapper.style.display === 'none') {
                commentsWrapper.style.display = 'block';
                icon.classList.remove('fa-comments');
                icon.classList.add('fa-comments-slash');
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-secondary');
            } else {
                commentsWrapper.style.display = 'none';
                icon.classList.remove('fa-comments-slash');
                icon.classList.add('fa-comments');
                this.classList.remove('btn-secondary');
                this.classList.add('btn-outline-secondary');
            }
        });
    });
});
</script>
@endpush