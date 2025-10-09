@props([
    'subcategoryKey',
    'subcategory',
    'parentCategory',
    'fields' => []
])

<div class="mb-4">
    <div class="d-flex align-items-center mb-2">
        <div class="form-check me-3">
            <input class="form-check-input subcategory-checkbox" 
                   type="checkbox" 
                   id="subcategory_{{ $subcategoryKey }}"
                   data-subcategory="{{ $subcategoryKey }}"
                   data-parent-category="{{ $parentCategory }}"
                   autocomplete="off">
            <label class="form-check-label fw-bold {{ $subcategory['color'] ?? 'text-primary' }}" 
                   for="subcategory_{{ $subcategoryKey }}">
                <i class="{{ $subcategory['icon'] ?? 'fas fa-folder' }} me-1"></i>{{ $subcategory['title'] }}
            </label>
        </div>
        <small class="text-muted">
            (<span class="subcategory-count" data-subcategory="{{ $subcategoryKey }}">0</span>/{{ count($fields) }} 項目)
        </small>
    </div>
    
    <div class="row">
        @foreach($fields as $field => $label)
            <x-export.field-checkbox 
                :field="$field" 
                :label="$label"
                :attributes="[
                    'data-category' => $parentCategory,
                    'data-subcategory' => $subcategoryKey
                ]"
            />
        @endforeach
    </div>
</div>