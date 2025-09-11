@props([
    'title',
    'icon',
    'iconColor' => 'primary',
    'collapsible' => false,
    'collapsed' => false,
    'class' => 'mb-4',
    'errorFields' => []
])

@php
    $sectionId = 'section-' . Str::slug($title) . '-' . uniqid();
    $contentId = $sectionId . '-content';
    $iconColorClass = match($iconColor) {
        'primary' => 'text-primary',
        'success' => 'text-success',
        'info' => 'text-info',
        'warning' => 'text-warning',
        'danger' => 'text-danger',
        'secondary' => 'text-secondary',
        'dark' => 'text-dark',
        default => 'text-primary'
    };
    
    // Check if any of the specified fields have errors
    $hasErrors = false;
    if (!empty($errorFields)) {
        foreach ($errorFields as $field) {
            if ($errors->has($field)) {
                $hasErrors = true;
                break;
            }
        }
    }
@endphp

<section class="card form-section {{ $class }}" 
         role="region" 
         aria-labelledby="{{ $sectionId }}"
         @if($collapsible) data-collapsible="true" @endif>
    <header class="card-header section-header" 
            id="{{ $sectionId }}"
            @if($collapsible) 
                role="button"
                tabindex="0"
                aria-expanded="{{ $collapsed ? 'false' : 'true' }}"
                aria-controls="{{ $contentId }}"
                aria-label="{{ $title }}セクションを{{ $collapsed ? '展開' : '折りたたみ' }}"
                style="cursor: pointer;"
            @endif>
        <h5 class="mb-0 d-flex align-items-center">
            <i class="{{ $icon }} {{ $iconColorClass }} me-2" 
               aria-hidden="true"
               role="img"
               aria-label="{{ $title }}のアイコン"></i>
            <span class="section-title">{{ $title }}</span>
            @if($hasErrors)
                <i class="fas fa-exclamation-triangle text-danger ms-2" 
                   aria-label="このセクションに入力エラーがあります" 
                   title="このセクションに入力エラーがあります"
                   role="img"></i>
            @endif
            @if($collapsible)
                <i class="fas fa-chevron-{{ $collapsed ? 'down' : 'up' }} ms-auto collapse-icon" 
                   aria-hidden="true"
                   role="img"
                   aria-label="セクション{{ $collapsed ? '展開' : '折りたたみ' }}アイコン"></i>
            @endif
        </h5>
    </header>
    <div class="card-body {{ $collapsible && $collapsed ? 'collapse' : '' }}" 
         id="{{ $contentId }}"
         role="group" 
         aria-labelledby="{{ $sectionId }}"
         @if($collapsible) aria-hidden="{{ $collapsed ? 'true' : 'false' }}" @endif>
        {{ $slot }}
    </div>
</section>