{{-- Service Table Layout --}}
@props([
    'tableId',
    'columns',
    'data',
    'tableClass',
    'emptyValueClass',
    'serviceHeaderRowspan' => true
])

@php
    // Get data formatter service
    $formatter = app(\App\Services\TableDataFormatter::class);
    
    // Format data first
    $formattedData = $formatter->formatTableData($data, ['columns' => $columns]);
    
    // Ensure we have at least one row for display
    if (empty($formattedData)) {
        $formattedData = [
            [
                'service_type' => '',
                'renewal_start_date' => null,
                'period_separator' => '〜',
                'renewal_end_date' => null,
            ]
        ];
    }
    
    // Count rows with actual data for rowspan calculation
    $dataRowCount = 0;
    foreach ($formattedData as $row) {
        $hasData = false;
        foreach ($row as $key => $value) {
            if ($key !== 'period_separator' && $value !== null && $value !== '') {
                $hasData = true;
                break;
            }
        }
        if ($hasData) {
            $dataRowCount++;
        }
    }
    
    // Use at least 1 for rowspan
    $rowspanCount = max(1, $dataRowCount);
@endphp

<table class="{{ $tableClass }} table-layout-service-table" id="{{ $tableId }}" role="table" aria-label="サービス情報テーブル">
    <tbody id="{{ $tableId }}-body">
        @foreach($formattedData as $index => $row)
            <tr>
                @if($index === 0)
                    {{-- Service header with rowspan --}}
                    <th class="service-header" rowspan="{{ $rowspanCount }}">サービス種類</th>
                @endif
                
                {{-- Service name --}}
                <td class="service-name">
                    @php
                        $serviceType = $row['service_type'] ?? '';
                        $displayValue = $formatter->formatValue($serviceType, ['type' => 'text']);
                    @endphp
                    {{ $displayValue ?? '' }}
                </td>
                
                {{-- Period header --}}
                <th class="period-header">有効期限</th>
                
                {{-- Start date --}}
                <td class="period-start">
                    @php
                        $startDate = $row['renewal_start_date'] ?? null;
                        $startColumn = collect($columns)->firstWhere('key', 'renewal_start_date');
                        $displayStartDate = $startDate ? $formatter->formatValue($startDate, $startColumn) : '';
                    @endphp
                    {{ $displayStartDate }}
                </td>
                
                {{-- Separator --}}
                <td class="period-separator">〜</td>
                
                {{-- End date --}}
                <td class="period-end">
                    @php
                        $endDate = $row['renewal_end_date'] ?? null;
                        $endColumn = collect($columns)->firstWhere('key', 'renewal_end_date');
                        $displayEndDate = $endDate ? $formatter->formatValue($endDate, $endColumn) : '';
                    @endphp
                    {{ $displayEndDate }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>