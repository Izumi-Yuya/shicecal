@props([
    'id',
    'title',
    'icon' => 'fas fa-info-circle',
    'iconColor' => 'primary',
    'errorFields' => [],
    'visible' => false
])

<div id="{{ $id }}" 
     class="conditional-section {{ $visible ? 'd-block' : 'd-none' }} mb-4" 
     aria-hidden="{{ $visible ? 'false' : 'true' }}" 
     aria-expanded="{{ $visible ? 'true' : 'false' }}" 
     role="region" 
     aria-labelledby="{{ $id }}_title">
    
    <x-form.section 
        title="{{ $title }}" 
        icon="{{ $icon }}" 
        icon-color="{{ $iconColor }}"
        :error-fields="$errorFields">
        
        {{ $slot }}
        
    </x-form.section>
</div>