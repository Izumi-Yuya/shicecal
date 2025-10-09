@props([
    'name',
    'id' => null,
    'label',
    'value' => '',
    'required' => false,
    'autocomplete' => 'off',
    'placeholder' => null,
    'help' => null,
    'error' => null,
    'class' => '',
    'labelClass' => 'form-label',
    'textareaClass' => 'form-control',
    'rows' => 3,
])

@php
    $textareaId = $id ?? $name;
    $hasError = $error || $errors->has($name);
    $textareaClasses = $textareaClass . ($hasError ? ' is-invalid' : '') . ($class ? ' ' . $class : '');
@endphp

<div class="mb-3">
    <label for="{{ $textareaId }}" class="{{ $labelClass }}{{ $required ? ' required' : '' }}">
        {{ $label }}
        @if($required)
            <span class="text-danger" aria-label="必須">*</span>
        @endif
    </label>
    
    <textarea 
        id="{{ $textareaId }}"
        name="{{ $name }}"
        class="{{ $textareaClasses }}"
        rows="{{ $rows }}"
        autocomplete="{{ $autocomplete }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required aria-required="true" @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $textareaId }}-error" @endif
        @if($help) aria-describedby="{{ $textareaId }}-help{{ $hasError ? ' ' . $textareaId . '-error' : '' }}" @endif
        {{ $attributes }}
    >{{ old($name, $value) }}</textarea>
    
    @if($help)
        <div id="{{ $textareaId }}-help" class="form-text">{{ $help }}</div>
    @endif
    
    @if($hasError)
        <div id="{{ $textareaId }}-error" class="invalid-feedback">
            {{ $error ?? $errors->first($name) }}
        </div>
    @endif
</div>