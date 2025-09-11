{{-- Service Table Component - Improved Version --}}
@php
    // Use helper service to prepare data and reduce template complexity
    $helper = app(\App\Services\ServiceTable\ServiceTableViewHelper::class);
    $tableData = $helper->prepareServiceTableData($services ?? collect());
    
    // Configuration from config file - centralized configuration
    $config = config('service-table');
    $maxServices = $config['display']['max_services'];
    $showEmptyRows = $config['display']['show_empty_rows'];
    $periodSeparator = $config['display']['period_separator'] ?? '〜';
    $labels = $config['columns'];
    $styling = $config['styling'];
@endphp

<!-- サービステーブルヘッダー（コメント機能付き） -->
<div class="service-table-header mt-4 mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-cogs text-info me-2" aria-hidden="true"></i>
            {{ $labels['service_type']['label'] }}
        </h6>
        <div class="service-comment-controls">
            <button class="btn btn-outline-primary btn-sm comment-toggle" 
                    data-section="service_info" 
                    data-bs-toggle="tooltip" 
                    title="コメントを表示/非表示"
                    aria-label="サービス情報のコメントを表示または非表示にする">
                <i class="fas fa-comment me-1" aria-hidden="true"></i>
                コメント
                <span class="badge bg-primary ms-1 comment-count" 
                      data-section="service_info" 
                      aria-label="コメント数">0</span>
            </button>
        </div>
    </div>
</div>

<!-- サービス種類テーブル -->
<table class="{{ $styling['table_class'] }}" 
       role="table" 
       aria-label="サービス情報テーブル"
       data-service-count="{{ $tableData['serviceCount'] }}">
    <tbody id="svc-body">
        @if($tableData['hasServices'])
            @foreach($tableData['services'] as $index => $service)
                <tr class="{{ $helper->getServiceRowClasses($index) }}" 
                    data-service-index="{{ $index }}">
                    @if($index === 0)
                        <th class="service-header" 
                            rowspan="{{ $tableData['displayCount'] }}"
                            scope="rowgroup"
                            aria-label="{{ $labels['service_type']['label'] }}">
                            {{ $labels['service_type']['label'] }}
                        </th>
                    @endif
                    <td class="service-name {{ $styling['columns']['service_type']['css_class'] ?? '' }}" 
                        data-label="サービス名">
                        {{ $service->service_type ?? '' }}
                    </td>
                    <th class="period-header" 
                        scope="col" 
                        aria-label="{{ $labels['service_period']['label'] }}">
                        {{ $labels['service_period']['label'] }}
                    </th>
                    <td class="period-start" data-label="開始日">
                        {{ $helper->formatServiceDate($service->renewal_start_date) }}
                    </td>
                    <td class="period-separator" aria-hidden="true">{{ $periodSeparator }}</td>
                    <td class="period-end" data-label="終了日">
                        {{ $helper->formatServiceDate($service->renewal_end_date) }}
                    </td>
                </tr>
            @endforeach
        @else
            {{-- Empty state row with proper accessibility --}}
            <tr class="empty-state-row" role="row">
                <th class="service-header" 
                    rowspan="1"
                    scope="rowgroup"
                    aria-label="{{ $labels['service_type']['label'] }}">
                    {{ $labels['service_type']['label'] }}
                </th>
                <td class="service-name empty-cell" data-label="サービス名">
                    <span class="{{ $styling['empty_value_class'] }}" 
                          aria-label="サービス未設定">
                        {{ $styling['empty_value_text'] }}
                    </span>
                </td>
                <th class="period-header" 
                    scope="col" 
                    aria-label="{{ $labels['service_period']['label'] }}">
                    {{ $labels['service_period']['label'] }}
                </th>
                <td class="period-start empty-cell" data-label="開始日">
                    <span class="{{ $styling['empty_value_class'] }}" 
                          aria-label="開始日未設定">
                        {{ $styling['empty_value_text'] }}
                    </span>
                </td>
                <td class="period-separator" aria-hidden="true">{{ $periodSeparator }}</td>
                <td class="period-end empty-cell" data-label="終了日">
                    <span class="{{ $styling['empty_value_class'] }}" 
                          aria-label="終了日未設定">
                        {{ $styling['empty_value_text'] }}
                    </span>
                </td>
            </tr>
        @endif
        
        {{-- Additional template rows if configured --}}
        @if($showEmptyRows && $tableData['emptyRowsCount'] > 0)
            @for($i = 0; $i < $tableData['emptyRowsCount']; $i++)
                <tr class="{{ $config['display']['empty_row_class'] }} {{ $helper->getServiceRowClasses($tableData['serviceCount'] + $i, true) }}"
                    data-template-row="{{ $i }}"
                    style="display: none;" {{-- Hidden by default, can be shown via JS --}}>
                    <td class="service-name template-cell" data-label="サービス名"></td>
                    <th class="period-header" 
                        scope="col" 
                        aria-label="{{ $labels['service_period']['label'] }}">
                        {{ $labels['service_period']['label'] }}
                    </th>
                    <td class="period-start template-cell" data-label="開始日"></td>
                    <td class="period-separator" aria-hidden="true">{{ $periodSeparator }}</td>
                    <td class="period-end template-cell" data-label="終了日"></td>
                </tr>
            @endfor
        @endif
    </tbody>
</table>

<!-- サービス情報のコメントセクション -->
<div class="comment-section mt-3 d-none" data-section="service_info">
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-comments me-2" aria-hidden="true"></i>
                サービス情報のコメント
            </h6>
        </div>
        <div class="card-body">
            <div class="comment-form mb-3">
                <div class="input-group">
                    <input type="text" 
                           class="form-control comment-input" 
                           placeholder="コメントを入力..." 
                           data-section="service_info"
                           aria-label="サービス情報コメント入力"
                           maxlength="{{ config('comments.validation.max_length', 500) }}">
                    <button class="btn btn-primary comment-submit" 
                            data-section="service_info"
                            aria-label="コメント送信"
                            type="button">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        <span class="visually-hidden">送信</span>
                    </button>
                </div>
            </div>
            <div class="comment-list" 
                 data-section="service_info" 
                 role="log" 
                 aria-live="polite"
                 aria-label="サービス情報コメント一覧">
                <!-- コメントがここに動的に追加されます -->
            </div>
        </div>
    </div>
</div>

{{-- Load required assets --}}
@push('styles')
    @vite(['resources/css/components/service-table.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/service-table-manager.js'])
@endpush

{{-- Improved JavaScript with better error handling and performance --}}
<script type="module">
    // Service Table Manager - Improved version
    class ServiceTableManager {
        constructor() {
            this.tableBody = document.getElementById('svc-body');
            this.config = @json($config);
            this.init();
        }
        
        init() {
            if (!this.tableBody) {
                console.warn('Service table body not found');
                return;
            }
            
            this.cleanupEmptyRows();
            this.updateRowSpan();
            this.setupAccessibility();
        }
        
        cleanupEmptyRows() {
            const rows = [...this.tableBody.querySelectorAll('tr.template-row')];
            let hasDataRows = this.countDataRows();
            
            // Remove completely empty template rows
            rows.forEach(row => {
                if (this.isRowEmpty(row)) {
                    row.remove();
                }
            });
        }
        
        countDataRows() {
            const rows = [...this.tableBody.querySelectorAll('tr:not(.template-row)')];
            return rows.filter(row => !this.isRowEmpty(row)).length;
        }
        
        isRowEmpty(row) {
            const serviceName = row.querySelector('.service-name')?.textContent.trim();
            const startDate = row.querySelector('.period-start')?.textContent.trim();
            const endDate = row.querySelector('.period-end')?.textContent.trim();
            
            return !serviceName && !startDate && !endDate;
        }
        
        updateRowSpan() {
            const serviceHeader = this.tableBody.querySelector('.service-header');
            const dataRowCount = this.countDataRows();
            
            if (serviceHeader && dataRowCount > 0) {
                serviceHeader.setAttribute('rowspan', Math.max(1, dataRowCount));
            }
        }
        
        setupAccessibility() {
            // Add keyboard navigation support
            const rows = this.tableBody.querySelectorAll('tr');
            rows.forEach((row, index) => {
                row.setAttribute('tabindex', '0');
                row.setAttribute('role', 'row');
                
                // Add keyboard event listeners for navigation
                row.addEventListener('keydown', (e) => {
                    this.handleKeyNavigation(e, index, rows);
                });
            });
        }
        
        handleKeyNavigation(event, currentIndex, rows) {
            let targetIndex = currentIndex;
            
            switch(event.key) {
                case 'ArrowDown':
                    targetIndex = Math.min(currentIndex + 1, rows.length - 1);
                    break;
                case 'ArrowUp':
                    targetIndex = Math.max(currentIndex - 1, 0);
                    break;
                case 'Home':
                    targetIndex = 0;
                    break;
                case 'End':
                    targetIndex = rows.length - 1;
                    break;
                default:
                    return; // Don't prevent default for other keys
            }
            
            if (targetIndex !== currentIndex) {
                event.preventDefault();
                rows[targetIndex].focus();
            }
        }
    }
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        new ServiceTableManager();
    });
</script>