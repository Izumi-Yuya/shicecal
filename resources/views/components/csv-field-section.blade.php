@props(['section', 'fields'])

<div class="col-12 mb-4">
    <h6 class="fw-bold {{ $section['color'] }} mb-3">
        <i class="{{ $section['icon'] }} me-1"></i>{{ $section['title'] }}
    </h6>
    <div class="row">
        @foreach($fields as $field => $label)
            <div class="col-md-6 col-lg-4 mb-2">
                <div class="form-check">
                    <input class="form-check-input field-checkbox" 
                           type="checkbox" 
                           name="export_fields[]" 
                           value="{{ $field }}" 
                           id="field_{{ $field }}">
                    <label class="form-check-label" for="field_{{ $field }}">
                        {{ $label }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>
</div>