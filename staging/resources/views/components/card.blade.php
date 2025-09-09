@props([
    'title' => null,
    'headerActions' => null,
    'bodyClass' => 'card-body',
    'headerClass' => 'card-header',
    'cardClass' => 'card'
])

<div class="{{ $cardClass }}">
    @if($title || $headerActions)
        <div class="{{ $headerClass }}">
            @if($title && $headerActions)
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $title }}</h5>
                    <div>{{ $headerActions }}</div>
                </div>
            @elseif($title)
                <h5 class="mb-0">{{ $title }}</h5>
            @else
                {{ $headerActions }}
            @endif
        </div>
    @endif
    
    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>