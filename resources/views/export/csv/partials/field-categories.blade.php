{{-- Field Categories Partial --}}
@php
    $fieldCategories = config('csv-export-fields');
@endphp

@foreach($fieldCategories as $categoryKey => $category)
    <div class="col-12 mb-4">
        <div class="d-flex align-items-center mb-3">
            <div class="form-check me-3">
                <input class="form-check-input category-checkbox" 
                       type="checkbox" 
                       id="category_{{ $categoryKey }}"
                       data-category="{{ $categoryKey }}"
                       autocomplete="off">
                <label class="form-check-label fw-bold {{ $category['color'] }}" for="category_{{ $categoryKey }}">
                    <i class="{{ $category['icon'] }} me-1"></i>{{ $category['title'] }}
                </label>
            </div>
            <small class="text-muted">
                (<span class="category-count" data-category="{{ $categoryKey }}">0</span>/{{ count($category['fields']) }} 項目選択中)
            </small>
        </div>
        <div class="row">
            @foreach($category['fields'] as $field => $label)
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
@endforeach