@props([
    'facility',
    'category',
    'categoryName',
    'canEdit' => false,
    'sectionId' => null,
    'height' => '500px',
    'heightReadonly' => '400px',
    'allowedFileTypes' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
    'maxFileSize' => '10MB',
    'borderColor' => 'border-secondary',
    'headerBg' => 'bg-secondary',
    'headerText' => 'text-white'
])

@php
    $sectionId = $sectionId ?? $category . '-documents-section';
@endphp

<!-- {{ $categoryName }}ドキュメント管理セクション（折りたたみ式） -->
<div class="collapse mb-4" id="{{ $sectionId }}">
    <div class="card {{ $borderColor }}">
        <div class="card-header {{ $headerBg }} {{ $headerText }}">
            <h6 class="mb-0">
                <i class="fas fa-folder-open me-2"></i>{{ $categoryName }} - 関連ドキュメント
            </h6>
        </div>
        <div class="card-body p-0">
            @if($canEdit)
                <x-lifeline-document-manager 
                    :facility="$facility" 
                    :category="$category"
                    :category-name="$categoryName"
                    :height="$height"
                    :show-upload="true"
                    :show-create-folder="true"
                    :allowed-file-types="$allowedFileTypes"
                    :max-file-size="$maxFileSize"
                />
            @else
                <x-lifeline-document-manager 
                    :facility="$facility" 
                    :category="$category"
                    :category-name="$categoryName"
                    :height="$heightReadonly"
                    :show-upload="false"
                    :show-create-folder="false"
                    :allowed-file-types="$allowedFileTypes"
                    :max-file-size="$maxFileSize"
                />
            @endif
        </div>
    </div>
</div>