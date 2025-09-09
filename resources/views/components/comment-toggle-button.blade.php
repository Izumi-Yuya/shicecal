@props([
    'section' => 'basic_info',
    'variant' => 'outline-primary',
    'size' => 'sm',
    'showText' => true,
    'initialCount' => 0
])

<div class="comment-controls">
    <button class="btn btn-{{ $variant }} btn-{{ $size }} comment-toggle" 
            data-section="{{ $section }}" 
            data-bs-toggle="tooltip" 
            title="コメントを表示/非表示"
            {{ $attributes }}>
        <i class="fas fa-comment{{ $showText ? ' me-1' : '' }}"></i>
        @if($showText)
            コメント
        @endif
        <span class="badge bg-primary{{ $showText ? ' ms-1' : '' }} comment-count" 
              data-section="{{ $section }}">{{ $initialCount }}</span>
    </button>
</div>