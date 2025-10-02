@props([
    'section',
    'variant' => 'card', // 'card' or 'table'
    'size' => 'sm',
    'showLabel' => false
])

@php
    $buttonClass = match($variant) {
        'table' => 'btn btn-outline-primary',
        'card' => 'btn btn-outline-secondary',
        default => 'btn btn-outline-secondary'
    };
    
    $containerClass = $variant === 'table' ? 'table-view-comment-controls' : '';
    $badgeClass = $variant === 'table' ? 'badge bg-primary ms-1' : '';
@endphp

<div class="{{ $containerClass }}">
    <button class="{{ $buttonClass }} btn-{{ $size }} comment-toggle" 
            data-section="{{ $section }}" 
            data-bs-toggle="tooltip" 
            title="コメントを表示/非表示"
            {{ $attributes }}>
        <i class="fas fa-comment{{ $showLabel ? ' me-1' : '' }}"></i>
        @if($showLabel)
            コメント
        @endif
        <span class="{{ $badgeClass ?: 'comment-count' }} comment-count" 
              data-section="{{ $section }}">0</span>
    </button>
</div>