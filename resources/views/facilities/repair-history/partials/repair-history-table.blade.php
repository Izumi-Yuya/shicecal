{{-- 共通修繕履歴テーブルコンポーネント --}}
@php
    $histories = $histories ?? collect();
    $showNo = $showNo ?? true;
    $showAmount = $showAmount ?? true;

    $showContent = $showContent ?? true;
    $tableClass = $tableClass ?? 'repair-history-table';
    $columns = $columns ?? [];
@endphp

@php
    // デフォルトカラム設定
    $defaultColumns = [
        'no' => ['label' => 'NO', 'width' => '8%', 'show' => $showNo],
        'maintenance_date' => ['label' => '施工日', 'width' => '15%', 'show' => true],
        'contractor' => ['label' => '施工会社', 'width' => '20%', 'show' => true],
        'cost' => ['label' => '金額', 'width' => '15%', 'show' => $showAmount],

        'content' => ['label' => '修繕内容', 'width' => '20%', 'show' => $showContent],
        'notes' => ['label' => '備考', 'width' => '10%', 'show' => true]
    ];
    
    // カスタムカラムがある場合はマージ
    $finalColumns = array_merge($defaultColumns, $columns);
    $visibleColumns = array_filter($finalColumns, function($col) { return $col['show']; });
@endphp

@if($histories->isNotEmpty())
    <div class="table-responsive">
        <table class="table table-bordered {{ $tableClass }}">
            <thead>
                <tr>
                    @foreach($visibleColumns as $key => $column)
                        <th style="width: {{ $column['width'] }};">{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($histories as $index => $history)
                    <tr>
                        @foreach($visibleColumns as $key => $column)
                            <td @if($key === 'no') class="text-center" @elseif($key === 'cost') class="amount-format" @endif>
                                @switch($key)
                                    @case('no')
                                        {{ $index + 1 }}
                                        @break
                                    @case('maintenance_date')
                                        {{ $history->maintenance_date ? $history->maintenance_date->format('Y/m/d') : '' }}
                                        @break
                                    @case('contractor')
                                        {{ $history->contractor ?? '' }}
                                        @break
                                    @case('cost')
                                        @if($history->cost)
                                            ¥{{ number_format($history->cost) }}
                                        @endif
                                        @break

                                    @case('content')
                                        {{ $history->content ?? '' }}
                                        @break
                                    @case('notes')
                                        {{ $history->notes ?? '' }}
                                        @break
                                    @default
                                        {{ $history->{$key} ?? '' }}
                                @endswitch
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        修繕履歴が登録されていません。
    </div>
@endif