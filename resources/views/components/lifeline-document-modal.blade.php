@props([
    'category',
    'categoryName',
    'facility',
    'headerColor' => 'bg-primary',
    'modalId' => null
])

@php
    $modalId = $modalId ?? $category . '-documents-modal';
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header {{ $headerColor }} text-white">
                <h5 class="modal-title" id="{{ $modalId }}-title">
                    <i class="fas fa-folder-open me-2"></i>{{ $categoryName }}ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <x-lifeline-document-manager 
                    :facility="$facility" 
                    :category="$category" 
                    :categoryName="$categoryName" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>