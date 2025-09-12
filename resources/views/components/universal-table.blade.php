{{-- Universal Table Component --}}
@props([
    'tableId' => null,
    'config' => [],
    'data' => [],
    'section' => null,
    'commentEnabled' => true,
    'responsive' => true,
    'facility' => null
])

@php
    try {
        // Get services
        $tableConfigService = app(\App\Services\TableConfigService::class);
        $tableDataFormatter = app(\App\Services\TableDataFormatter::class);
        $tablePerformanceService = app(\App\Services\TablePerformanceService::class);
        $errorHandler = app(\App\Services\TableErrorHandler::class);
        
        // Get optimized configuration with dynamic columns
        $tableType = $config['table_type'] ?? null;
        if ($tableType && method_exists($tableConfigService, 'getConfigWithDynamicColumns')) {
            $tableConfig = $tableConfigService->getConfigWithDynamicColumns($tableType, $data);
        } elseif ($tableType && in_array($tableType, $tableConfigService->getAvailableTableTypes())) {
            $tableConfig = $tableConfigService->getTableConfig($tableType);
        } else {
            $tableConfig = $tableConfigService->mergeWithDefaults($config);
        }
        
        // Apply performance optimizations
        $performanceResult = $tablePerformanceService->optimizeTableData($data, $tableConfig, [
            'enable_caching' => true,
            'enable_lazy_loading' => count($data) > 50,
            'enable_virtual_scroll' => count($data) > 200
        ]);
        
        $data = $performanceResult['data'];
        $tableConfig = $performanceResult['config'];
        $optimizations = $performanceResult['optimizations'];
        
        // Validate the merged configuration
        $validationResult = $tableConfigService->validateConfigDetailed($tableConfig);
        if (!$validationResult->isValid()) {
            // Handle validation errors with fallback
            $tableConfig = $errorHandler->handleValidationError('universal', $validationResult, $tableConfig);
        }
    } catch (\Exception $e) {
        // Log the error and use fallback
        $errorHandler = app(\App\Services\TableErrorHandler::class);
        $errorHandler->logFailure('universal', 'config', $e, ['config' => $config]);
        
        // Use minimal fallback configuration
        $tableConfig = [
            'columns' => [
                ['key' => 'fallback_data', 'label' => 'データ', 'type' => 'text']
            ],
            'layout' => ['type' => 'key_value_pairs'],
            'styling' => ['table_class' => 'table table-bordered table-fallback'],
            'features' => ['comments' => false]
        ];
        $optimizations = ['strategy' => 'full_render', 'dom' => [], 'css' => [], 'js' => []];
    }
    
    // Apply dynamic column management if enabled
    if (($tableConfig['features']['dynamic_columns'] ?? false) || 
        ($tableConfig['features']['conditional_columns'] ?? false) ||
        ($tableConfig['features']['auto_width'] ?? false)) {
        
        // Add dynamic columns based on data content
        if ($tableConfig['features']['dynamic_columns'] ?? false) {
            $tableConfig['columns'] = $tableDataFormatter->addDynamicColumns($tableConfig['columns'], $data);
        }
        
        // Filter conditional columns
        if ($tableConfig['features']['conditional_columns'] ?? false) {
            $tableConfig['columns'] = $tableDataFormatter->filterConditionalColumns($tableConfig['columns'], $data);
        }
        
        // Calculate optimal column widths
        if ($tableConfig['features']['auto_width'] ?? false) {
            $tableConfig['columns'] = $tableDataFormatter->calculateOptimalColumnWidths($tableConfig['columns'], $data);
        }
    }
    
    // Extract configuration sections
    $columns = $tableConfig['columns'] ?? [];
    $layout = $tableConfig['layout'] ?? [];
    $styling = $tableConfig['styling'] ?? [];
    $features = $tableConfig['features'] ?? [];
    
    // Generate unique table ID if not provided
    $tableId = $tableId ?? 'table-' . uniqid();
    
    // Determine layout type
    $layoutType = $layout['type'] ?? 'key_value_pairs';
    $columnsPerRow = $layout['columns_per_row'] ?? 2;
    $showHeaders = $layout['show_headers'] ?? false;
    $groupBy = $layout['group_by'] ?? null;
    
    // CSS classes (use compiled classes if available)
    $compiledClasses = $tableConfig['compiled_classes'] ?? [];
    $tableClass = $compiledClasses['table'] ?? ($styling['table_class'] ?? 'table table-bordered');
    $headerClass = $compiledClasses['header'] ?? ($styling['header_class'] ?? 'bg-primary text-white');
    $emptyValueClass = $compiledClasses['empty'] ?? ($styling['empty_value_class'] ?? 'text-muted');
    $groupClass = $compiledClasses['group'] ?? ($styling['group_class'] ?? 'table-group');
    
    // Comment settings
    $commentSection = $section ?? 'default';
    $commentDisplayName = $config['comment_display_name'] ?? ucfirst($commentSection);
    
    // Responsive settings (use compiled responsive if available)
    $compiledResponsive = $tableConfig['compiled_responsive'] ?? [];
    $responsiveEnabled = $responsive && ($compiledResponsive['enabled'] ?? ($tableConfig['global_settings']['responsive']['enabled'] ?? true));
    $responsiveBreakpoint = $compiledResponsive['breakpoint'] ?? ($layout['responsive_breakpoint'] ?? 'lg');
    
    // Performance attributes
    $performanceAttributes = $tableConfig['attributes'] ?? [];
    $performanceStrategy = $optimizations['strategy'] ?? 'full_render';
@endphp

<div class="universal-table-wrapper {{ $compiledClasses['container'] ?? 'performance-optimized' }}" 
     data-table-id="{{ $tableId }}"
     data-performance-strategy="{{ $performanceStrategy }}"
     data-row-count="{{ $performanceAttributes['data-row-count'] ?? count($data) }}"
     @if($performanceStrategy === 'lazy_loading')
     data-loaded-rows="{{ $data['loaded_rows'] ?? 0 }}"
     data-total-rows="{{ $data['total_rows'] ?? count($data) }}"
     data-load-increment="{{ $data['load_increment'] ?? 25 }}"
     @endif
     @if($performanceStrategy === 'virtual_scroll')
     data-chunk-size="{{ $data['chunk_size'] ?? 50 }}"
     data-total-chunks="{{ $data['total_chunks'] ?? 0 }}"
     @endif
>


    {{-- Performance-optimized container based on strategy --}}
    @if($performanceStrategy === 'virtual_scroll')
        <div class="virtual-scroll-container" style="height: 400px; overflow-y: auto;">
            <div class="virtual-scroll-spacer" style="height: {{ ($data['total_rows'] ?? 0) * 40 }}px;">
    @elseif($responsiveEnabled)
        <div class="{{ $compiledClasses['responsive_wrapper'] ?? 'table-responsive table-responsive-' . $responsiveBreakpoint }}">
    @endif

    {{-- Render table based on layout type with error handling --}}
    @php
        $renderSuccess = false;
        $renderError = null;
        
        try {
    @endphp
    
        @if($layoutType === 'key_value_pairs')
            @include('components.universal-table.key-value-pairs', [
                'tableId' => $tableId,
                'columns' => $columns,
                'data' => $data,
                'facility' => $facility,
                'tableClass' => $tableClass,
                'emptyValueClass' => $emptyValueClass,
                'columnsPerRow' => $columnsPerRow
            ])
        @elseif($layoutType === 'grouped_rows')
            @include('components.universal-table.grouped-rows', [
                'tableId' => $tableId,
                'columns' => $columns,
                'data' => $data,
                'tableClass' => $tableClass,
                'headerClass' => $headerClass,
                'emptyValueClass' => $emptyValueClass,
                'groupClass' => $groupClass,
                'groupBy' => $groupBy,
                'showHeaders' => $showHeaders,
                'hierarchicalHeaders' => $layout['hierarchical_headers'] ?? false,
                'multiLevelGrouping' => $layout['multi_level_grouping'] ?? false
            ])
        @elseif($layoutType === 'service_table')
            @include('components.universal-table.service-table', [
                'tableId' => $tableId,
                'columns' => $columns,
                'data' => $data,
                'tableClass' => $tableClass,
                'emptyValueClass' => $emptyValueClass,
                'serviceHeaderRowspan' => $layout['service_header_rowspan'] ?? true
            ])
        @elseif($layoutType === 'standard_table')
            @php
                $hasNestedData = $layout['nested_data'] ?? false;
                $hasCellMerging = $layout['cell_merging'] ?? false;
            @endphp
            
            @if($hasNestedData || $hasCellMerging)
                @include('components.universal-table.nested-table', [
                    'tableId' => $tableId,
                    'columns' => $columns,
                    'data' => $data,
                    'tableClass' => $tableClass,
                    'headerClass' => $headerClass,
                    'emptyValueClass' => $emptyValueClass,
                    'nestedClass' => $styling['nested_class'] ?? 'nested-row',
                    'mergedClass' => $styling['merged_class'] ?? 'merged-cell',
                    'showHeaders' => $showHeaders,
                    'nestingConfig' => $tableConfig['nesting_config'] ?? [],
                    'mergingConfig' => $tableConfig['merging_config'] ?? []
                ])
            @else
                @include('components.universal-table.standard-table', [
                    'tableId' => $tableId,
                    'columns' => $columns,
                    'data' => $data,
                    'tableClass' => $tableClass,
                    'headerClass' => $headerClass,
                    'emptyValueClass' => $emptyValueClass,
                    'showHeaders' => $showHeaders
                ])
            @endif
        @else
            {{-- Fallback to key-value pairs --}}
            @include('components.universal-table.key-value-pairs', [
                'tableId' => $tableId,
                'columns' => $columns,
                'data' => $data,
                'facility' => $facility,
                'tableClass' => $tableClass,
                'emptyValueClass' => $emptyValueClass,
                'columnsPerRow' => $columnsPerRow
            ])
        @endif
        
    @php
            $renderSuccess = true;
        } catch (\Exception $e) {
            $renderError = $e;
            // Log the rendering error
            if (isset($errorHandler)) {
                $errorHandler->logFailure('universal', 'render', $e, [
                    'layout_type' => $layoutType,
                    'data_count' => count($data),
                    'columns_count' => count($columns)
                ]);
            }
        }
        
        // If rendering failed, show fallback
        if (!$renderSuccess) {
    @endphp
        
        {{-- Render fallback simple table --}}
        <div class="table-fallback-wrapper">
            <div class="alert alert-warning mb-3">
            <i class="fas fa-exclamation-triangle"></i>
            テーブルコンポーネントの読み込みに問題が発生したため、簡易表示モードで表示しています。
        </div>
        
        <table class="table table-bordered table-fallback"  id="{{ $tableId }}" >
            <thead class="bg-warning text-dark">
                <tr>
                    <th style="width: 30%">項目</th>
                    <th style="width: 70%">値</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $key => $value)
                    <tr>
                        <td class="fw-bold">
                            {{ $key }}
                        </td>
                        <td>
                            @if($value !== null && $value !== '')
                                {{ $value }}
                            @else
                                <span class="text-muted">未設定</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

<style>
.table-fallback-wrapper .table-fallback {
    margin-bottom: 0;
}

.table-fallback-wrapper .alert {
    font-size: 0.9rem;
}

.table-fallback td {
    vertical-align: top;
    word-break: break-word;
}
</style>    @php } @endphp

    {{-- Lazy loading trigger --}}
    @if($performanceStrategy === 'lazy_loading' && ($data['loaded_rows'] ?? 0) < ($data['total_rows'] ?? 0))
        <div class="lazy-loading-trigger">
            <div class="lazy-loading-spinner"></div>
            <span class="ms-2">さらに読み込み中...</span>
        </div>
    @endif

    {{-- Close performance-optimized containers --}}
    @if($performanceStrategy === 'virtual_scroll')
            </div>
        </div>
    @elseif($responsiveEnabled)
        </div>
    @endif

</div>

{{-- Performance-optimized styles --}}
@push('styles')
    <style>
        {!! isset($tablePerformanceService) ? $tablePerformanceService->generateOptimizedCSS($tableConfig, $optimizations) : '' !!}
        
        @if($responsiveEnabled)
        .table-responsive-{{ $responsiveBreakpoint }} {
            position: relative;
        }
        
        .table-responsive-{{ $responsiveBreakpoint }}::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-responsive-{{ $responsiveBreakpoint }}::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-responsive-{{ $responsiveBreakpoint }}::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        .table-responsive-{{ $responsiveBreakpoint }}::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        @media (max-width: {{ $compiledResponsive['breakpoint_value'] ?? ($tableConfig['global_settings']['responsive']['breakpoints'][$responsiveBreakpoint] ?? '992px') }}) {
            .table-responsive-{{ $responsiveBreakpoint }}::after {
                content: "→ 横スクロールできます";
                position: absolute;
                top: 10px;
                right: 10px;
                background: rgba(0, 123, 255, 0.8);
                color: white;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                pointer-events: none;
                z-index: 10;
            }
        }
        @endif
    </style>
@endpush

{{-- Performance-optimized JavaScript --}}
{{-- Performance JavaScript is handled by the main table-performance.js module --}}
{{-- Inline JavaScript generation disabled to prevent conflicts --}}