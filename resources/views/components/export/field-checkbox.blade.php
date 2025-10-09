@props([
    'field',
    'label',
    'colClass' => 'col-md-6 col-lg-4',
    'attributes' => []
])

<div class="{{ $colClass }} mb-2">
    <div class="form-check">
        <input class="form-check-input field-checkbox" 
               type="checkbox" 
               name="export_fields[]" 
               value="{{ $field }}" 
               id="field_{{ $field }}"
               autocomplete="off"
               @foreach($attributes as $key => $value)
                   {{ $key }}="{{ $value }}"
               @endforeach>
        <label class="form-check-label" for="field_{{ $field }}">
            {{ $label }}
        </label>
    </div>
</div>