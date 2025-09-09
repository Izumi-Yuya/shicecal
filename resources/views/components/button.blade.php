@props([
    'variant' => 'primary',
    'size' => '',
    'icon' => null,
    'href' => null,
    'type' => 'button'
])

@php
    $baseClasses = 'btn';
    $variantClass = "btn-{$variant}";
    $sizeClass = $size ? "btn-{$size}" : '';
    
    $classes = trim("{$baseClasses} {$variantClass} {$sizeClass} {$attributes->get('class')}");
    
    $tag = $href ? 'a' : 'button';
    $typeAttr = $href ? '' : "type=\"{$type}\"";
    $hrefAttr = $href ? "href=\"{$href}\"" : '';
@endphp

<{{ $tag }} class="{{ $classes }}" {!! $typeAttr !!} {!! $hrefAttr !!} {{ $attributes->except(['class']) }}>
    @if($icon)
        <i class="{{ $icon }} me-1"></i>
    @endif
    {{ $slot }}
</{{ $tag }}>