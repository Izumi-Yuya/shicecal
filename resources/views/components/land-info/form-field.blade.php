@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'maxlength' => null,
    'pattern' => null,
    'helpText' => null,
    'required' => false,
    'readonly' => false,
    'inputmode' => null,
    'step' => null,
    'min' => null,
    'max' => null,
    'suffix' => null,
    'prefix' => null,
    'colClass' => 'col-12 col-md-6 mb-3',
    'options' => null, // For select fields
    'rows' => 3, // For textarea
    'multiple' => false, // For file inputs
    'accept' => null, // For file inputs
    'autocomplete' => null,
    'validation' => [], // Custom validation rules
    'characterCounter' => false,
    'currency' => false, // Auto-format as currency
    'conditional' => null, // Show/hide based on other field
    'group' => null, // Field grouping for layout
    'icon' => null, // Icon for the field
    'tooltip' => null, // Tooltip text
    'dataAttributes' => [] // Additional data attributes
])

@php
    $fieldId = $name;
    $fieldValue = old($name, $value ?? '');
    $isInvalid = $errors->has($name);
    $helpId = $helpText ? $fieldId . '_help' : null;
    $counterId = $characterCounter ? $fieldId . '_count' : null;
    $tooltipId = $tooltip ? $fieldId . '_tooltip' : null;
    
    // Build CSS classes
    $inputClasses = ['form-control'];
    if ($type === 'select') $inputClasses = ['form-select'];
    if ($currency) $inputClasses[] = 'currency-input';
    if ($isInvalid) $inputClasses[] = 'is-invalid';
    
    // Build data attributes
    $dataAttrs = [];
    foreach ($dataAttributes as $key => $val) {
        $dataAttrs["data-{$key}"] = $val;
    }
    if ($conditional) {
        $dataAttrs['data-conditional'] = $conditional;
    }
    if ($validation) {
        $dataAttrs['data-validation'] = json_encode($validation);
    }
@endphp

<div class="{{ $colClass }}" @if($conditional) data-conditional-field="{{ $conditional }}" @endif>
    <label for="{{ $fieldId }}" class="form-label{{ $required ? ' required' : '' }}">
        @if($icon)
            <i class="{{ $icon }} me-2"></i>
        @endif
        {{ $label }}
        @if($tooltip)
            <i class="fas fa-question-circle ms-1 text-muted" 
               data-bs-toggle="tooltip" 
               data-bs-placement="top" 
               title="{{ $tooltip }}"
               id="{{ $tooltipId }}"></i>
        @endif
    </label>
    
    <div class="{{ ($suffix || $prefix) ? 'input-group' : '' }}">
        @if($prefix)
            <span class="input-group-text">{{ $prefix }}</span>
        @endif
        
        @if($type === 'select')
            <select name="{{ $name }}" id="{{ $fieldId }}" 
                    class="{{ implode(' ', $inputClasses) }}"
                    @foreach($dataAttrs as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                    @if($helpId) aria-describedby="{{ $helpId }}" @endif
                    @if($tooltipId) aria-describedby="{{ $tooltipId }}" @endif
                    @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                    @if($required) aria-required="true" required @endif>
                @if($options)
                    @foreach($options as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" 
                                {{ $fieldValue === $optionValue ? 'selected' : '' }}>
                            {{ $optionLabel }}
                        </option>
                    @endforeach
                @endif
            </select>
        @elseif($type === 'textarea')
            <textarea name="{{ $name }}" id="{{ $fieldId }}" 
                      class="{{ implode(' ', $inputClasses) }}"
                      @foreach($dataAttrs as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                      @if($maxlength) maxlength="{{ $maxlength }}" @endif
                      @if($placeholder) placeholder="{{ $placeholder }}" @endif
                      @if($helpId) aria-describedby="{{ $helpId }}" @endif
                      @if($tooltipId) aria-describedby="{{ $tooltipId }}" @endif
                      @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                      @if($required) aria-required="true" required @endif
                      rows="{{ $rows }}">{{ $fieldValue }}</textarea>
        @elseif($type === 'file')
            <input type="file" name="{{ $name }}" id="{{ $fieldId }}" 
                   class="{{ implode(' ', $inputClasses) }}"
                   @foreach($dataAttrs as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                   @if($accept) accept="{{ $accept }}" @endif
                   @if($multiple) multiple @endif
                   @if($helpId) aria-describedby="{{ $helpId }}" @endif
                   @if($tooltipId) aria-describedby="{{ $tooltipId }}" @endif
                   @if($required) aria-required="true" required @endif>
        @else
            <input type="{{ $type }}" name="{{ $name }}" id="{{ $fieldId }}" 
                   class="{{ implode(' ', $inputClasses) }}"
                   @foreach($dataAttrs as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                   value="{{ $fieldValue }}"
                   @if($maxlength) maxlength="{{ $maxlength }}" @endif
                   @if($pattern) pattern="{{ $pattern }}" @endif
                   @if($placeholder) placeholder="{{ $placeholder }}" @endif
                   @if($inputmode) inputmode="{{ $inputmode }}" @endif
                   @if($step) step="{{ $step }}" @endif
                   @if($min) min="{{ $min }}" @endif
                   @if($max) max="{{ $max }}" @endif
                   @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                   @if($readonly) readonly @endif
                   @if($helpId) aria-describedby="{{ $helpId }}" @endif
                   @if($tooltipId) aria-describedby="{{ $tooltipId }}" @endif
                   @if($required) aria-required="true" required @endif>
        @endif
        
        @if($suffix)
            <span class="input-group-text">{{ $suffix }}</span>
        @endif
    </div>
    
    @if($helpText)
        <small id="{{ $helpId }}" class="form-text text-muted{{ $type === 'hidden' ? ' visually-hidden' : '' }}">
            {{ $helpText }}
        </small>
    @endif
    
    @if($characterCounter && $maxlength)
        <small class="form-text text-muted">
            <span id="{{ $counterId }}">{{ strlen($fieldValue) }}</span> / {{ $maxlength }} 文字
        </small>
    @endif
    
    <x-form.field-error field="{{ $name }}" />
</div>