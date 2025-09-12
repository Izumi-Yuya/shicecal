{{-- Standardized Land Info Table View Partial --}}
@php
    $landInfo = $landInfo ?? null;
    $tableConfig = app('App\Services\TableConfigService')->getTableConfig('land_info');
    
    // Prepare land info data for the universal table
    $landData = [];
    if ($landInfo) {
        $landData = $landInfo->toArray();
    } else {
        // Create empty data structure
        $landData = [
            'ownership_type' => null,
            'parking_spaces' => null,
            'site_area_sqm' => null,
            'site_area_tsubo' => null,
            'purchase_price' => null,
            'monthly_rent' => null,
            'contract_start_date' => null,
            'contract_end_date' => null,
        ];
    }
@endphp

<div class="land-info-table-view">
    <!-- 土地情報テーブルヘッダー（コメント機能付き） -->
    <div class="table-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-map text-primary me-2"></i>土地情報（テーブル形式）
            </h5>
        </div>
    </div>

    @if($landInfo)
        <!-- Universal Table Component for Land Info -->
        <x-universal-table 
            :table-id="'land-info-table'"
            :config="$tableConfig"
            :data="$landData"
            :section="'land_info'"
            :comment-enabled="true"
            :responsive="true"
        />
    @else
        <!-- 土地情報未登録の場合 -->
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-map-marked-alt fa-4x text-muted"></i>
            </div>
            <h4 class="text-muted mb-3">土地情報が登録されていません</h4>
            <p class="text-muted mb-4">
                この施設の土地情報はまだ登録されていません。<br>
                土地情報を登録するには、編集ボタンから登録してください。
            </p>
            @if(auth()->user()->canEditLandInfo())
                <a href="{{ route('facilities.land-info.edit', $facility) }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>土地情報を登録
                </a>
            @endif
        </div>
    @endif
</div>