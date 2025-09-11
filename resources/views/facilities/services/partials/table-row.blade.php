{{-- Service Table Data Row --}}
@php
    $serviceTableService = app(\App\Services\ServiceTableService::class);
    $hasData = $serviceTableService->hasValidServiceData($service);
    $isFirstRow = isset($isFirstRow) && $isFirstRow;
    $config = $serviceTableService->getStylingConfig();
    $emptyText = $config['empty_value_text'];
    $headerBgClass = $config['header_bg_class'];
    $headerTextClass = $config['header_text_class'];
    $emptyValueClass = $config['empty_value_class'];
@endphp

<tr class="svc-row {{ $isFirstRow ? 'first-row' : '' }}" data-has-data="{{ $hasData ? 'true' : 'false' }}">
    @if($isFirstRow)
        {{-- First row includes headers --}}
        <th class="label-cell text-center {{ $headerBgClass }} {{ $headerTextClass }}">サービス種類</th>
        <td class="value-cell svc-name">
            @if($hasData)
                {{ e($service->service_type) }}
            @else
                <span class="{{ $emptyValueClass }}">{{ $emptyText }}</span>
            @endif
        </td>
        <th class="label-cell text-center {{ $headerBgClass }} {{ $headerTextClass }}">有効期限</th>
        <td class="value-cell term">
            @if($hasData)
                @include('facilities.services.partials.period-form', ['service' => $service])
            @else
                <span class="{{ $emptyValueClass }}">{{ $emptyText }}</span>
            @endif
        </td>
    @else
        {{-- Subsequent rows --}}
        <td class="label-cell-empty"></td>
        <td class="value-cell svc-name">
            @if($hasData)
                {{ e($service->service_type) }}
            @endif
        </td>
        <td class="label-cell-empty"></td>
        <td class="value-cell term">
            @if($hasData)
                @include('facilities.services.partials.period-form', ['service' => $service])
            @endif
        </td>
    @endif
</tr>