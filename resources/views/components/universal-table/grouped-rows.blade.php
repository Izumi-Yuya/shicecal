{{-- Grouped Rows Table Layout --}}
@props([
    'tableId',
    'columns',
    'data',
    'tableClass',
    'headerClass',
    'emptyValueClass',
    'groupClass',
    'groupBy',
    'showHeaders',
    'hierarchicalHeaders' => false,
    'multiLevelGrouping' => false
])

@php
    // Get data formatter service
    $formatter = app(\App\Services\TableDataFormatter::class);
    
    // Format data first
    $formattedData = $formatter->formatTableData($data, ['columns' => $columns]);
    
    // Apply advanced rowspan calculations if needed
    $processedData = $formatter->calculateRowspanValues($formattedData, $columns);
    
    // Group data for display
    $groupedData = $formatter->groupDataBy($processedData, $groupBy);
    
    // Get columns with rowspan grouping
    $rowspanColumns = array_filter($columns, function($column) {
        return isset($column['rowspan_group']) && $column['rowspan_group'] === true;
    });
    
    // Get non-grouping columns for display
    $displayColumns = array_filter($columns, function($column) use ($groupBy) {
        return $column['key'] !== $groupBy;
    });
    
    // Find the grouping column
    $groupColumn = collect($columns)->firstWhere('key', $groupBy);
    
    // Generate hierarchical headers if needed
    $headers = $hierarchicalHeaders ? $formatter->generateHierarchicalHeaders($columns) : null;
@endphp

<table class="{{ $tableClass }} table-layout-grouped-rows" id="{{ $tableId }}" role="table" aria-label="グループ化されたテーブル">
    @if($showHeaders)
        <thead>
            @if($hierarchicalHeaders && $headers)
                {{-- Hierarchical header structure --}}
                @foreach($headers as $levelIndex => $levelHeaders)
                    <tr class="{{ $headerClass }}">
                        @foreach($levelHeaders as $header)
                            <th scope="col" 
                                @if(isset($header['colspan']) && $header['colspan'] > 1) colspan="{{ $header['colspan'] }}" @endif
                                @if(isset($header['rowspan']) && $header['rowspan'] > 1) rowspan="{{ $header['rowspan'] }}" @endif
                                @if(isset($header['width'])) style="width: {{ $header['width'] }}" @endif>
                                {{ $header['label'] }}
                            </th>
                        @endforeach
                    </tr>
                @endforeach
            @else
                {{-- Standard single-level headers --}}
                <tr class="{{ $headerClass }}">
                    @if($groupColumn)
                        <th scope="col">{{ $groupColumn['label'] }}</th>
                    @endif
                    @foreach($displayColumns as $column)
                        <th scope="col" @if(isset($column['width'])) style="width: {{ $column['width'] }}" @endif>
                            {{ $column['label'] }}
                        </th>
                    @endforeach
                </tr>
            @endif
        </thead>
    @endif
    
    <tbody id="{{ $tableId }}-body">
        @if(!empty($groupedData))
            @foreach($groupedData as $groupValue => $groupItems)
                @php
                    $groupItemsCount = count($groupItems);
                @endphp
                
                @foreach($groupItems as $index => $item)
                    <tr class="{{ $groupClass }}" data-group="{{ $groupValue }}" data-index="{{ $index }}">
                        {{-- Handle rowspan columns --}}
                        @foreach($columns as $column)
                            @php
                                $key = $column['key'];
                                $value = $item[$key] ?? null;
                                $displayValue = $formatter->formatValue($value, $column);
                                $emptyText = $column['empty_text'] ?? '未設定';
                                
                                // Check if this column has rowspan grouping
                                $hasRowspan = isset($column['rowspan_group']) && $column['rowspan_group'] === true;
                                $rowspanInfo = $hasRowspan ? ($item['_rowspan_' . $key] ?? null) : null;
                                
                                // Skip cell if it's not the first in a rowspan group
                                if ($hasRowspan && $rowspanInfo && !$rowspanInfo['is_first']) {
                                    continue;
                                }
                            @endphp
                            
                            @if($key === $groupBy && $index === 0)
                                {{-- Primary group header cell with rowspan --}}
                                <th class="group-header" 
                                    rowspan="{{ $groupItemsCount }}"
                                    scope="rowgroup"
                                    aria-label="{{ $column['label'] }}">
                                    @if($displayValue !== null && $displayValue !== '')
                                        {{ $displayValue }}
                                    @else
                                        <span class="{{ $emptyValueClass }}">{{ $emptyText }}</span>
                                    @endif
                                </th>
                            @elseif($key !== $groupBy)
                                {{-- Regular data cell or rowspan cell --}}
                                @php
                                    $cellTag = $hasRowspan ? 'th' : 'td';
                                    $cellClass = $hasRowspan ? 'rowspan-header' : '';
                                    $rowspanAttr = '';
                                    
                                    if ($hasRowspan && $rowspanInfo && $rowspanInfo['group_size'] > 1) {
                                        $rowspanAttr = 'rowspan="' . $rowspanInfo['group_size'] . '"';
                                    }
                                @endphp
                                
                                <{{ $cellTag }} class="{{ $cellClass }}" 
                                    {!! $rowspanAttr !!}
                                    @if(isset($column['width'])) style="width: {{ $column['width'] }}" @endif
                                    @if($hasRowspan) scope="rowgroup" @endif>
                                    @if($displayValue !== null && $displayValue !== '')
                                        {!! $displayValue !!}
                                    @else
                                        <span class="{{ $emptyValueClass }}">{{ $emptyText }}</span>
                                    @endif
                                </{{ $cellTag }}>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
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