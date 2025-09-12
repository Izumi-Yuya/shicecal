{{-- Nested Table Layout with Cell Merging Support --}}
@props([
    'tableId',
    'columns',
    'data',
    'tableClass',
    'headerClass',
    'emptyValueClass',
    'nestedClass',
    'mergedClass',
    'showHeaders',
    'nestingConfig',
    'mergingConfig'
])

@php
    // Get data formatter service
    $formatter = app(\App\Services\TableDataFormatter::class);
    
    // Process nested data if configuration exists
    $processedData = $data;
    if (!empty($nestingConfig)) {
        $processedData = $formatter->processNestedData($processedData, $nestingConfig);
        $processedData = $formatter->generateNestedDisplayStructure($processedData, $nestingConfig);
    }
    
    // Process cell merging if configuration exists
    if (!empty($mergingConfig)) {
        $processedData = $formatter->processCellMerging($processedData, $mergingConfig);
    }
    
    // Format the data
    $formattedData = $formatter->formatTableData($processedData, ['columns' => $columns]);
@endphp

<table class="{{ $tableClass }} table-layout-nested-table" id="{{ $tableId }}" role="table" aria-label="ネストされたテーブル">
    @if($showHeaders)
        <thead>
            <tr class="{{ $headerClass }}">
                @foreach($columns as $column)
                    <th scope="col" @if(isset($column['width'])) style="width: {{ $column['width'] }}" @endif>
                        {{ $column['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
    @endif
    
    <tbody id="{{ $tableId }}-body">
        @if(!empty($formattedData))
            @foreach($formattedData as $index => $item)
                @php
                    $displayStructure = $item['_display_structure'] ?? [];
                    $mergedHorizontal = $item['_merged_horizontal'] ?? [];
                    $mergedVertical = $item['_merged_vertical'] ?? [];
                    $hiddenColumns = $item['_hidden_columns'] ?? [];
                    $nestedInfo = $item['_nested_info'] ?? [];
                    
                    $rowClass = $nestedClass;
                    if (!empty($displayStructure)) {
                        $rowClass .= ' ' . ($displayStructure['css_class'] ?? '');
                    }
                    if (!empty($nestedInfo)) {
                        $rowClass .= ' nested-depth-' . ($nestedInfo['depth'] ?? 0);
                    }
                @endphp
                
                <tr class="{{ $rowClass }}" 
                    data-index="{{ $index }}"
                    @if(!empty($nestedInfo)) data-nested-depth="{{ $nestedInfo['depth'] ?? 0 }}" @endif
                    @if(!empty($displayStructure)) style="padding-left: {{ $displayStructure['indent'] ?? '0px' }}" @endif>
                    
                    @foreach($columns as $column)
                        @php
                            $key = $column['key'];
                            $value = $item[$key] ?? null;
                            $displayValue = $formatter->formatValue($value, $column);
                            $emptyText = $column['empty_text'] ?? '未設定';
                            
                            // Check if this column should be hidden due to merging
                            if (in_array($key, $hiddenColumns)) {
                                continue;
                            }
                            
                            // Check for horizontal merging
                            $colspan = 1;
                            $cellClass = '';
                            if (isset($mergedHorizontal[$key])) {
                                $colspan = $mergedHorizontal[$key]['colspan'] ?? 1;
                                $cellClass .= ' ' . $mergedClass . ' merged-horizontal';
                            }
                            
                            // Check for vertical merging
                            $rowspan = 1;
                            $skipCell = false;
                            if (isset($mergedVertical[$key])) {
                                $verticalInfo = $mergedVertical[$key];
                                if ($verticalInfo['is_first']) {
                                    $rowspan = $verticalInfo['rowspan'] ?? 1;
                                    $cellClass .= ' ' . $mergedClass . ' merged-vertical';
                                } else {
                                    $skipCell = true; // Skip non-first cells in vertical merge
                                }
                            }
                            
                            if ($skipCell) {
                                continue;
                            }
                            
                            // Add nesting visual elements
                            $nestingPrefix = '';
                            if (!empty($displayStructure) && $displayStructure['show_connector']) {
                                $connectorType = $displayStructure['connector_type'] ?? 'middle';
                                $nestingPrefix = '<span class="nested-connector nested-connector-' . $connectorType . '"></span>';
                            }
                        @endphp
                        
                        <td class="{{ $cellClass }}"
                            @if($colspan > 1) colspan="{{ $colspan }}" @endif
                            @if($rowspan > 1) rowspan="{{ $rowspan }}" @endif
                            @if(isset($column['width'])) style="width: {{ $column['width'] }}" @endif>
                            
                            @if($key === array_keys($columns)[0] && !empty($nestingPrefix))
                                {!! $nestingPrefix !!}
                            @endif
                            
                            @if($displayValue !== null && $displayValue !== '')
                                {!! $displayValue !!}
                            @else
                                <span class="{{ $emptyValueClass }}">{{ $emptyText }}</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        @else
            {{-- Empty state --}}
            <tr>
                <td colspan="{{ count($columns) }}" class="text-center {{ $emptyValueClass }}">
                    データがありません
                </td>
            </tr>
        @endif
    </tbody>
</table>

{{-- Add CSS for nested display and cell merging --}}
@push('styles')
<style>
    /* Nested table styles */
    .nested-row {
        transition: background-color 0.2s ease;
    }
    
    .nested-depth-0 {
        font-weight: 600;
        background-color: rgba(var(--bs-primary-rgb), 0.05);
    }
    
    .nested-depth-1 {
        background-color: rgba(var(--bs-info-rgb), 0.03);
    }
    
    .nested-depth-2 {
        background-color: rgba(var(--bs-secondary-rgb), 0.02);
        font-size: 0.9rem;
    }
    
    /* Nested connectors */
    .nested-connector {
        display: inline-block;
        width: 20px;
        height: 20px;
        margin-right: 8px;
        position: relative;
    }
    
    .nested-connector-start::before {
        content: '├─';
        color: var(--bs-secondary);
        font-family: monospace;
    }
    
    .nested-connector-middle::before {
        content: '├─';
        color: var(--bs-secondary);
        font-family: monospace;
    }
    
    .nested-connector-end::before {
        content: '└─';
        color: var(--bs-secondary);
        font-family: monospace;
    }
    
    /* Merged cell styles */
    .merged-cell {
        background-color: rgba(var(--bs-warning-rgb), 0.1);
        border: 2px solid var(--bs-warning);
        font-weight: 500;
    }
    
    .merged-horizontal {
        text-align: center;
        background-color: rgba(var(--bs-info-rgb), 0.1);
        border-color: var(--bs-info);
    }
    
    .merged-vertical {
        vertical-align: middle;
        text-align: center;
        background-color: rgba(var(--bs-success-rgb), 0.1);
        border-color: var(--bs-success);
    }
    
    /* Hover effects for nested rows */
    .nested-row:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.08) !important;
    }
    
    .nested-depth-1:hover {
        background-color: rgba(var(--bs-info-rgb), 0.08) !important;
    }
    
    .nested-depth-2:hover {
        background-color: rgba(var(--bs-secondary-rgb), 0.08) !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .nested-connector {
            width: 15px;
            height: 15px;
            margin-right: 5px;
        }
        
        .nested-depth-2 {
            font-size: 0.85rem;
        }
        
        .merged-cell {
            font-size: 0.9rem;
        }
    }
</style>
@endpush