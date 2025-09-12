{{-- Key-Value Pairs Table Layout --}}
@props([
    'tableId',
    'columns',
    'data',
    'facility',
    'tableClass',
    'emptyValueClass',
    'columnsPerRow'
])

@php
    // Get data formatter service
    $formatter = app(\App\Services\TableDataFormatter::class);
    
    // Prepare data for key-value pairs layout
    $formattedData = $formatter->formatTableData($data, ['columns' => $columns]);
    
    // Group columns into rows based on columnsPerRow setting
    $columnGroups = array_chunk($columns, $columnsPerRow);
@endphp

<table class="{{ $tableClass }} table-layout-key-value-pairs" id="{{ $tableId }}" role="table" aria-label="基本情報テーブル">
    <tbody>
        @php
            $processedColumns = [];
            $currentRow = [];
            $currentRowCells = 0;
            $maxCellsPerRow = $columnsPerRow * 2; // Each column has th + td
            
            foreach ($columns as $column) {
                $colspan = $column['colspan'] ?? 1;
                $cellsNeeded = 2 * $colspan; // th + td with colspan
                
                // If this column would exceed the row limit, start a new row
                if ($currentRowCells + $cellsNeeded > $maxCellsPerRow && !empty($currentRow)) {
                    $processedColumns[] = $currentRow;
                    $currentRow = [];
                    $currentRowCells = 0;
                }
                
                $currentRow[] = $column;
                $currentRowCells += $cellsNeeded;
                
                // If we've filled the row exactly, start a new row
                if ($currentRowCells >= $maxCellsPerRow) {
                    $processedColumns[] = $currentRow;
                    $currentRow = [];
                    $currentRowCells = 0;
                }
            }
            
            // Add any remaining columns
            if (!empty($currentRow)) {
                $processedColumns[] = $currentRow;
            }
        @endphp

        @foreach($processedColumns as $columnGroup)
            <tr>
                @php $cellsInRow = 0; @endphp
                @foreach($columnGroup as $column)
                    @php
                        $key = $column['key'];
                        $label = $column['label'];
                        $type = $column['type'] ?? 'text';
                        $emptyText = $column['empty_text'] ?? '未設定';
                        $width = $column['width'] ?? null;
                        $colspan = $column['colspan'] ?? 1;
                        
                        // Get value from facility object or formatted data
                        $value = null;
                        $accessor = $column['accessor'] ?? null;
                        
                        if ($facility) {
                            if ($accessor && isset($facility->{$accessor})) {
                                $value = $facility->{$accessor};
                            } elseif (isset($facility->{$key})) {
                                $value = $facility->{$key};
                            }
                        } elseif (isset($formattedData[$key])) {
                            $value = $formattedData[$key];
                        }
                        
                        // Format the value based on type
                        $displayValue = $formatter->formatValue($value, $column);
                        
                        $cellsInRow += 2 * $colspan;
                    @endphp
                    
                    <th @if($width) style="width: {{ $width }}" @endif>{{ $label }}</th>
                    <td @if($colspan > 1) colspan="{{ $colspan * 2 - 1 }}" @endif @if($width) style="width: {{ $width }}" @endif>
                        @if($displayValue !== null && $displayValue !== '')
                            @if($type === 'email')
                                <a href="mailto:{{ $displayValue }}" class="text-decoration-none text-primary">{{ $displayValue }}</a>
                            @elseif($type === 'url')
                                <a href="{{ $displayValue }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-primary">{{ $displayValue }}</a>
                            @elseif($type === 'phone')
                                <a href="tel:{{ $displayValue }}" class="text-decoration-none text-primary">{{ $displayValue }}</a>
                            @else
                                {!! $displayValue !!}
                            @endif
                        @else
                            <span class="{{ $emptyValueClass }}">{{ $emptyText }}</span>
                        @endif
                    </td>
                @endforeach
                
                {{-- Fill remaining columns if needed --}}
                @php $maxCellsPerRow = $columnsPerRow * 2; @endphp
                @while($cellsInRow < $maxCellsPerRow)
                    <th></th>
                    <td></td>
                    @php $cellsInRow += 2; @endphp
                @endwhile
            </tr>
        @endforeach
    </tbody>
</table>