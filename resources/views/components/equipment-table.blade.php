@props([
    'data' => [],
    'tableClass' => 'table facility-basic-info-table-clean',
    'containerClass' => 'table-responsive',
    'style' => 'table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;'
])

<div class="{{ $containerClass }}">
    <table class="{{ $tableClass }}" style="{{ $style }}">
        <tbody>
            @foreach($data as $row)
                <tr>
                    @foreach($row as $cell)
                        <td class="{{ $cell['class'] ?? 'detail-value' }}" 
                            @if(isset($cell['style'])) style="{{ $cell['style'] }}" @endif>
                            {{ $cell['value'] ?? '未設定' }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>