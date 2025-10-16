@props([
    'data' => [],
    'columns' => 6,
    'tableClass' => 'table table-bordered facility-basic-info-table-clean',
    'layout' => 'auto', // 'auto', 'fixed', 'equal'
    'showHeader' => false,
    'headerData' => [],
    'containerClass' => 'table-responsive',
    'emptyMessage' => 'データがありません。'
])

@php
    $colWidth = $layout === 'equal' ? (100 / $columns) . '%' : 'auto';
    $tableLayoutClass = match($layout) {
        'fixed' => 'table-layout-fixed',
        'equal' => 'table-layout-equal',
        default => 'table-layout-auto'
    };
    
    // Validate data structure
    if (!is_array($data)) {
        $data = [];
    }
@endphp

<div class="{{ $containerClass }}">
    <table class="{{ $tableClass }} {{ $tableLayoutClass }}" style="margin-bottom: 0; border: 1px solid #e9ecef;">
        @if($layout === 'equal')
            <colgroup>
                @for($i = 0; $i < $columns; $i++)
                    <col style="width: {{ $colWidth }};">
                @endfor
            </colgroup>
        @endif
        
        @if($showHeader && !empty($headerData))
            <thead>
                <tr>
                    @foreach($headerData as $header)
                        <th class="detail-label" 
                            style="padding: .5rem; border: 1px solid #e9ecef !important;"
                            @if(isset($header['colspan'])) colspan="{{ $header['colspan'] }}" @endif>
                            {{ $header['label'] ?? '' }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        
        <tbody>
            @forelse($data as $row)
                <tr>
                    @if(($row['type'] ?? 'standard') === 'standard')
                        @foreach(($row['cells'] ?? []) as $cell)
                            @if(isset($cell['label']))
                                <td class="detail-label" style="padding: .5rem; border: 1px solid #e9ecef !important;">
                                    {{ $cell['label'] }}
                                </td>
                            @endif
                            <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" 
                                style="padding: .5rem; border: 1px solid #e9ecef !important;"
                                @if(isset($cell['colspan'])) colspan="{{ $cell['colspan'] }}" @endif>
                                @if(($cell['type'] ?? 'text') === 'date' && !empty($cell['value']))
                                    {{ \Carbon\Carbon::parse($cell['value'])->format('Y年m月d日') }}
                                @elseif(($cell['type'] ?? 'text') === 'file_display' && !empty($cell['value']))
                                    @if(isset($cell['options']['route']) && isset($cell['options']['params']))
                                        <a href="{{ route($cell['options']['route'], $cell['options']['params']) }}" 
                                           class="text-decoration-none" target="_blank">
                                            <i class="fas fa-file-pdf me-1 text-danger"></i>{{ $cell['options']['display_name'] ?? $cell['value'] }}
                                        </a>
                                    @else
                                        {{ $cell['value'] }}
                                    @endif
                                @else
                                    {{ $cell['value'] ?? '未設定' }}
                                @endif
                            </td>
                        @endforeach
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columns }}" class="text-center text-muted py-3">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>