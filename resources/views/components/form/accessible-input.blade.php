@props([
    'type' => 'text',
    'name',
    'id' => null,
    'label',
    'value' => '',
    'required' => false,
    'autocomplete' => null,
    'placeholder' => null,
    'help' => null,
    'error' => null,
    'class' => '',
    'labelClass' => 'form-label',
    'inputClass' => 'form-control',
])

@php
    $inputId = $id ?? $name;
    $hasError = $error || $errors->has($name);
    $inputClasses = $inputClass . ($hasError ? ' is-invalid' : '') . ($class ? ' ' . $class : '');
    
    // Auto-generate autocomplete attribute based on field name if not provided
    if (!$autocomplete) {
        $autocomplete = match($name) {
            'email', 'user_email' => 'email',
            'password' => 'current-password',
            'new_password', 'password_confirmation' => 'new-password',
            'name', 'user_name', 'full_name' => 'name',
            'first_name' => 'given-name',
            'last_name' => 'family-name',
            'phone', 'telephone', 'tel' => 'tel',
            'address' => 'street-address',
            'city' => 'address-level2',
            'state', 'prefecture' => 'address-level1',
            'zip', 'postal_code' => 'postal-code',
            'country' => 'country',
            'organization', 'company' => 'organization',
            'job_title' => 'organization-title',
            'url', 'website' => 'url',
            default => 'off'
        };
    }
@endphp

<div class="mb-3">
    <label for="{{ $inputId }}" class="{{ $labelClass }}{{ $required ? ' required' : '' }}">
        {{ $label }}
        @if($required)
            <span class="text-danger" aria-label="必須">*</span>
        @endif
    </label>
    
    <input 
        type="{{ $type }}"
        id="{{ $inputId }}"
        name="{{ $name }}"
        class="{{ $inputClasses }}"
        value="{{ old($name, $value) }}"
        autocomplete="{{ $autocomplete }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required aria-required="true" @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
        @if($help) aria-describedby="{{ $inputId }}-help{{ $hasError ? ' ' . $inputId . '-error' : '' }}" @endif
        {{ $attributes }}
    >
    
    @if($help)
        <div id="{{ $inputId }}-help" class="form-text">{{ $help }}</div>
    @endif
    
    @if($hasError)
        <div id="{{ $inputId }}-error" class="invalid-feedback">
            {{ $error ?? $errors->first($name) }}
        </div>
    @endif
</div>