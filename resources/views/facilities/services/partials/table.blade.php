{{-- Service Table Component --}}
@php
    $services = $services ?? collect();
    $maxServices = config('service-table.display.max_services', 10);
@endphp

<!-- サービステーブルヘッダー（コメント機能付き） -->
<div class="service-table-header mt-4 mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-cogs text-info me-2"></i>サービス種類
        </h6>
        <div class="service-comment-controls">
            <button class="btn btn-outline-primary btn-sm comment-toggle" 
                    data-section="service_info" 
                    data-bs-toggle="tooltip" 
                    title="コメントを表示/非表示">
                <i class="fas fa-comment me-1"></i>
                コメント
                <span class="badge bg-primary ms-1 comment-count" data-section="service_info">0</span>
            </button>
        </div>
    </div>
</div>

<!-- サービス種類テーブル -->
<table class="service-info">
    <tbody id="svc-body">
        @php
            $serviceCount = $services->count();
            $displayCount = max(1, $serviceCount); // At least 1 row for header
        @endphp
        
        @forelse($services as $index => $service)
            <tr>
                @if($index === 0)
                    <th class="service-header" rowspan="{{ $displayCount }}">サービス種類</th>
                @endif
                <td class="service-name">{{ $service->service_type ?? '' }}</td>
                <th class="period-header">有効期限</th>
                <td class="period-start">{{ $service->renewal_start_date ? $service->renewal_start_date->format('Y年n月j日') : '' }}</td>
                <td class="period-separator">〜</td>
                <td class="period-end">{{ $service->renewal_end_date ? $service->renewal_end_date->format('Y年n月j日') : '' }}</td>
            </tr>
        @empty
            {{-- Show one empty row when no services --}}
            <tr>
                <th class="service-header" rowspan="1">サービス種類</th>
                <td class="service-name"></td>
                <th class="period-header">有効期限</th>
                <td class="period-start"></td>
                <td class="period-separator">〜</td>
                <td class="period-end"></td>
            </tr>
        @endforelse
        
        {{-- Additional empty rows for template --}}
        @for($i = $serviceCount; $i < $maxServices; $i++)
            <tr class="template-row">
                <td class="service-name"></td>
                <th class="period-header">有効期限</th>
                <td class="period-start"></td>
                <td class="period-separator">〜</td>
                <td class="period-end"></td>
            </tr>
        @endfor
    </tbody>
</table>

<!-- サービス情報のコメントセクション -->
<div class="comment-section mt-3 d-none" data-section="service_info">
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-comments me-2"></i>サービス情報のコメント
            </h6>
        </div>
        <div class="card-body">
            <div class="comment-form mb-3">
                <div class="input-group">
                    <input type="text" class="form-control comment-input" 
                           placeholder="コメントを入力..." 
                           data-section="service_info">
                    <button class="btn btn-primary comment-submit" 
                            data-section="service_info">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
            <div class="comment-list" data-section="service_info">
                <!-- コメントがここに動的に追加されます -->
            </div>
        </div>
    </div>
</div>

@push('styles')
    @vite(['resources/css/components/service-table.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/service-table-manager.js'])
@endpush

<script>
// サービス種類・開始・終了すべて空の行は削除
(function(){
    const body = document.getElementById('svc-body');
    if (!body) return;
    
    const rows = [...body.querySelectorAll('tr')];
    let hasDataRows = 0;
    
    // Count rows with actual data first
    rows.forEach(tr => {
        const serviceName = tr.querySelector('.service-name')?.textContent.trim();
        const startDate = tr.querySelector('.period-start')?.textContent.trim();
        const endDate = tr.querySelector('.period-end')?.textContent.trim();
        
        if (serviceName || startDate || endDate) {
            hasDataRows++;
        }
    });
    
    // Remove empty template rows
    rows.forEach(tr => {
        if (tr.classList.contains('template-row')) {
            const serviceName = tr.querySelector('.service-name')?.textContent.trim();
            const startDate = tr.querySelector('.period-start')?.textContent.trim();
            const endDate = tr.querySelector('.period-end')?.textContent.trim();
            
            if (!serviceName && !startDate && !endDate) {
                tr.remove();
            }
        }
    });
    
    // Update rowspan for service header
    const serviceHeader = body.querySelector('.service-header');
    if (serviceHeader && hasDataRows > 0) {
        serviceHeader.setAttribute('rowspan', hasDataRows);
    }
})();
</script>