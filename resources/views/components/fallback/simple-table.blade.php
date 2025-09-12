{{-- Fallback simple table component --}}
@props([
    'data' => [],
    'tableId' => null,
    'config' => [],
    'section' => null
])

<div class="table-fallback-wrapper">
    @if(!empty($data))
        <div class="alert alert-warning mb-3">
            <i class="fas fa-exclamation-triangle"></i>
            テーブルコンポーネントの読み込みに問題が発生したため、簡易表示モードで表示しています。
        </div>
        
        <table class="table table-bordered table-fallback" @if($tableId) id="{{ $tableId }}" @endif>
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
                            {{ is_string($key) ? $key : "項目 {$key}" }}
                        </td>
                        <td>
                            @if(is_array($value))
                                {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                            @elseif(is_bool($value))
                                {{ $value ? 'はい' : 'いいえ' }}
                            @elseif(is_null($value))
                                <span class="text-muted">未設定</span>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            表示するデータがありません。
        </div>
    @endif
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
</style>