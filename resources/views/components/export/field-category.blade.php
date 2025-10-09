@props([
    'categoryKey',
    'category',
    'fields' => []
])

<div class="col-12 mb-4">
    <div class="d-flex align-items-center mb-3">
        <div class="form-check me-3">
            <input class="form-check-input category-checkbox" 
                   type="checkbox" 
                   id="category_{{ $categoryKey }}"
                   data-category="{{ $categoryKey }}"
                   autocomplete="off">
            <label class="form-check-label fw-bold {{ $category['color'] ?? 'text-primary' }}" 
                   for="category_{{ $categoryKey }}">
                <i class="{{ $category['icon'] ?? 'fas fa-folder' }} me-1"></i>{{ $category['title'] }}
            </label>
        </div>
        <small class="text-muted">
            (<span class="category-count" data-category="{{ $categoryKey }}">0</span>/{{ count($fields) }} 項目選択中)
        </small>
    </div>
    
    <div class="row">
        @foreach($fields as $field => $label)
            <x-export.field-checkbox 
                :field="$field" 
                :label="$label" 
            />
        @endforeach
    </div>
</div>