{{-- Standard Table Layout --}}
@props([
    'tableId',
    'columns',
    'data',
    'tableClass',
    'headerClass',
    'emptyValueClass',
    'showHeaders'
])

@php
    // Get data formatter service
    $formatter = app(\App\Services\TableDataFormatter::class);
    
    // Format data for standard table layout
    $formattedData = $formatter->formatTableData($data, ['columns' => $columns]);
    
    // Ensure data is an array of rows
    if (!is_array($formattedData) || (isset($formattedData[0]) && !is_array($formattedData[0]))) {
        // Convert single row data to array of rows
        $formattedData = [$formattedData];
    }
@endphp

<table class="{{ $tableClass }} table-layout-standard-table" id="{{ $tableId }}" role="table" aria-label="標準テーブル">
    @if($showHeaders)
        <thead>
            <tr class="{{ $headerClass }}">
                @foreach($columns as $column)
                    <th scope="col" @if(isset($column['width'])) style="width: {{ $column['width'] }}" @endif>
                        {{ $column['label'] }}
                        @if($column['required'] ?? false)
                            <span class="text-danger">*</span>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
    @endif
    
    <tbody>
        @if(!empty($formattedData))
            @foreach($formattedData as $rowIndex => $row)
                <tr data-row-index="{{ $rowIndex }}">
                    @foreach($columns as $column)
                        @php
                            $key = $column['key'];
                            $value = $row[$key] ?? null;
                            $displayValue = $formatter->formatValue($value, $column);
                            $emptyText = $column['empty_text'] ?? '未設定';
                        @endphp
                        
                        <td @if(isset($column['width'])) style="width: {{ $column['width'] }}" @endif>
                            @if($displayValue !== null && $displayValue !== '')
                                @if($column['type'] === 'email')
                                    <a href="mailto:{{ $displayValue }}" class="text-decoration-none text-primary">{{ $displayValue }}</a>
                                @elseif($column['type'] === 'url')
                                    <a href="{{ $displayValue }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-primary">{{ $displayValue }}</a>
                                @elseif($column['type'] === 'phone')
                                    <a href="tel:{{ $displayValue }}" class="text-decoration-none text-primary">{{ $displayValue }}</a>
                                @else
                                    {!! $displayValue !!}
                                @endif
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