@props([
    'field' => '',
    'message' => null
])

@php
    $errorMessage = $message ?? $errors->first($field);
@endphp

@if ($errorMessage)
    <div class="invalid-feedback">
        {{ $errorMessage }}
    </div>
@endif