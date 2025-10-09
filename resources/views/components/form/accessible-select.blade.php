@props([
    'name',
    'id' => null,
    'label',
    'value' => '',
    'required' => false,
    'autocomplete' => null,
    'help' => null,
    'error' => null,
    'class' => '',
    'labelClass' => 'form-label',
    'selectClass' => 'form-select',
    'options' => [],
    'placeholder' => null,
])

@php
    $selectId = $id ?? $name;
    $hasError = $error || $errors->has($name);
    $selectClasses = $selectClass . ($hasError ? ' is-invalid' : '') . ($class ? ' ' . $class : '');
    
    // Auto-generate autocomplete attribute based on field name if not provided
    if (!$autocomplete) {
        $autocomplete = match($name) {
            'country' => 'country',
            'state', 'prefecture' => 'address-level1',
            'language' => 'language',
            default => 'off'
        };
    }
@endphp

<div class="mb-3">
    <label for="{{ $selectId }}" class="{{ $labelClass }}{{ $required ? ' required' : '' }}">
        {{ $label }}
        @if($required)
            <span class="text-danger" aria-label="必須">*</span>
        @endif
    </label>
    
    <select 
        id="{{ $selectId }}"
        name="{{ $name }}"
        class="{{ $selectClasses }}"
        autocomplete="{{ $autocomplete }}"
        @if($required) required aria-required="true" @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $selectId }}-error" @endif
        @if($help) aria-describedby="{{ $selectId }}-help{{ $hasError ? ' ' . $selectId . '-error' : '' }}" @endif
        {{ $attributes }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        
        @if(is_array($options))
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" {{ old($name, $value) == $optionValue ? 'selected' : '' }}>
                    {{ $optionLabel }}
                </option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>
    
    @if($help)
        <div id="{{ $selectId }}-help" class="form-text">{{ $help }}</div>
    @endif
    
    @if($hasError)
        <div id="{{ $selectId }}-error" class="invalid-feedback">
            {{ $error ?? $errors->first($name) }}
        </div>
    @endif
</div>