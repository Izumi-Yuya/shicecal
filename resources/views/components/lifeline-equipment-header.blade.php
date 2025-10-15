@props([
    'title',
    'icon',
    'iconColor',
    'category',
    'showDocuments' => true
])

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="{{ $icon }} {{ $iconColor }} me-2"></i>{{ $title }}
    </h5>
    @if($showDocuments)
    <div class="d-flex align-items-center gap-2">
        <button type="button" 
                class="btn btn-outline-primary btn-sm" 
                id="{{ $category }}-documents-toggle"
                title="{{ $title }}ドキュメント管理"
                data-bs-toggle="modal" 
                data-bs-target="#{{ $category }}-documents-modal">
            <i class="fas fa-folder-open me-1"></i>
            <span class="d-none d-md-inline">ドキュメント</span>
        </button>
    </div>
    @endif
</div>