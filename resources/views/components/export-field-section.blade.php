@props(['title', 'icon', 'color', 'fields'])

<div class="mb-4">
    <h6 class="fw-bold {{ $color }} mb-2">
        <i class="{{ $icon }} me-1"></i>{{ $title }}
    </h6>
    <div class="row">
        @foreach($fields as $field => $label)
            <div class="col-md-6 col-lg-4 mb-2">
                <div class="form-check">
                    <input class="form-check-input field-checkbox" 
                           type="checkbox" 
                           name="export_fields[]" 
                           value="{{ $field }}" 
                           id="field_{{ $field }}"
                           autocomplete="off">
                    <label class="form-check-label" for="field_{{ $field }}">
                        {{ $label }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>
</div>