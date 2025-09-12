{{-- Standardized Service Info Table View Partial --}}
@php
    $services = $services ?? collect();
    $facility = $facility ?? null;
    $tableConfig = app('App\Services\TableConfigService')->getTableConfig('service_info');
    
    // Prepare service data for the universal table
    $serviceData = $services->map(function($service) {
        return [
            'service_type' => $service->service_type ?? '',
            'renewal_start_date' => $service->renewal_start_date,
            'period_separator' => '〜',
            'renewal_end_date' => $service->renewal_end_date,
        ];
    })->toArray();
    
    // If no services, add one empty row
    if (empty($serviceData)) {
        $serviceData = [
            [
                'service_type' => '',
                'renewal_start_date' => null,
                'period_separator' => '〜',
                'renewal_end_date' => null,
            ]
        ];
    }
@endphp

<div class="service-table-view">
    <!-- サービス種類セクション -->
    <div class="table-section mt-4">
        <!-- サービステーブルヘッダー（コメント機能付き） -->
        <div class="service-table-header mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-cogs text-info me-2"></i>サービス種類
                </h6>
                @if($facility)
                <div class="table-actions">
                    <button class="btn btn-sm btn-outline-secondary comment-toggle-btn" 
                            data-section="service_info"
                            data-bs-toggle="tooltip" 
                            title="サービス種類のコメントを表示/非表示">
                        <i class="fas fa-comments me-1"></i>コメント
                        <span class="badge bg-secondary ms-1" id="service-info-comment-count">
                            {{ $facility->comments()->where('section', 'service_info')->count() }}
                        </span>
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- Universal Table Component for Services -->
        <x-universal-table 
            :table-id="'service-info-table'"
            :config="$tableConfig"
            :data="$serviceData"
            :section="'service_info'"
            :comment-enabled="true"
            :responsive="true"
            :facility="$facility"
        />

        <!-- サービス種類コメントセクション -->
        @if($facility)
        <div class="table-comment-wrapper mt-3" id="service-info-comments" style="display: none;">
            <x-table-comment-section 
                section="service_info"
                display-name="サービス種類"
                :facility="$facility"
            />
        </div>
        @endif
    </div>
</div>

@push('styles')
    @vite(['resources/css/components/service-table.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/service-table-manager.js'])
@endpush