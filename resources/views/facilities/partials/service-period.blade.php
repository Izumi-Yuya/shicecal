{{-- Service Period Display Component --}}
@php
    $hasStartDate = $service && $service->renewal_start_date;
    $hasEndDate = $service && $service->renewal_end_date;
    $showSeparator = $hasStartDate && $hasEndDate;
@endphp

@if($hasStartDate || $hasEndDate)
    <div class="service-period">
        <span class="from">
            @if($hasStartDate)
                {{ $service->renewal_start_date->format('Y年m月d日') }}
            @endif
        </span>

        @if($showSeparator)
            <span class="sep"> ～ </span>
        @endif

        <span class="to">
            @if($hasEndDate)
                {{ $service->renewal_end_date->format('Y年m月d日') }}
                @if(method_exists($service, 'isExpired') && $service->isExpired())
                    <br><span class="badge bg-danger mt-1">期限切れ</span>
                @elseif(method_exists($service, 'isExpiringSoon') && $service->isExpiringSoon())
                    <br><span class="badge bg-warning text-dark mt-1">期限間近</span>
                @endif
            @endif
        </span>
    </div>
@else
    <span class="text-muted">未設定</span>
@endif