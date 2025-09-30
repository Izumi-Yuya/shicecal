{{-- 修繕履歴編集ページ --}}
@extends('layouts.app')

@section('title', '修繕履歴編集 - ' . $facility->facility_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Page header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">修繕履歴編集</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('facilities.index') }}">施設一覧</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('facilities.show', $facility) }}">{{ $facility->facility_name }}</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                {{ \App\Models\MaintenanceHistory::CATEGORIES[$category] }} 修繕履歴編集
                            </li>
                        </ol>
                    </nav>
                </div>
                <div>
                    @php
                        $backFragment = match($category) {
                            'exterior' => 'repair-history',
                            'interior' => 'interior',
                            'other' => 'other',
                            default => 'repair-history'
                        };
                    @endphp
                    <a href="{{ route('facilities.show', $facility) }}#{{ $backFragment }}" 
                       class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>戻る
                    </a>
                </div>
            </div>

            <!-- Error messages -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>入力内容にエラーがあります。</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Edit form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>
                        {{ \App\Models\MaintenanceHistory::CATEGORIES[$category] }} 修繕履歴編集
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('facilities.repair-history.update', ['facility' => $facility, 'category' => $category]) }}" id="repairHistoryForm">
                        @csrf
                        @method('PUT')

                        <!-- Dynamic history entries -->
                        <div id="historiesContainer">
                            @if($category === 'exterior')
                                {{-- 外装カテゴリの場合は塗装と防水の2つの履歴を固定表示 --}}
                                @php
                                    $paintingHistory = $histories->firstWhere('subcategory', '塗装') ?? $histories->firstWhere('subcategory', 'painting');
                                    $waterproofHistory = $histories->firstWhere('subcategory', '防水') ?? $histories->firstWhere('subcategory', 'waterproof');
                                @endphp
                                
                                {{-- 塗装履歴フォーム --}}
                                @include('facilities.repair-history.partials.history-form-row', [
                                    'index' => 0,
                                    'history' => $paintingHistory,
                                    'category' => $category,
                                    'subcategories' => $subcategories,
                                    'fixedSubcategory' => '塗装',
                                    'displayName' => '塗装'
                                ])
                                
                                {{-- 防水履歴フォーム --}}
                                @include('facilities.repair-history.partials.history-form-row', [
                                    'index' => 1,
                                    'history' => $waterproofHistory,
                                    'category' => $category,
                                    'subcategories' => $subcategories,
                                    'fixedSubcategory' => '防水',
                                    'displayName' => '防水'
                                ])
                            @elseif($category === 'interior')
                                {{-- 内装カテゴリの場合：内装リニューアルと内装・意匠履歴 --}}
                                @php
                                    $renovationHistory = $histories->firstWhere('subcategory', '内装リニューアル') ?? $histories->firstWhere('subcategory', 'renovation');
                                    $designHistories = $histories->filter(function($history) {
                                        return in_array($history->subcategory, ['内装・意匠履歴', 'design']);
                                    });
                                @endphp
                                
                                {{-- 内装リニューアル履歴フォーム --}}
                                @include('facilities.repair-history.partials.history-form-row', [
                                    'index' => 0,
                                    'history' => $renovationHistory,
                                    'category' => $category,
                                    'subcategories' => $subcategories,
                                    'fixedSubcategory' => '内装リニューアル',
                                    'displayName' => '内装リニューアル',
                                    'interiorType' => 'renovation'
                                ])
                                
                                {{-- 内装・意匠履歴フォーム（最初の1つ） --}}
                                @include('facilities.repair-history.partials.history-form-row', [
                                    'index' => 1,
                                    'history' => $designHistories->first(),
                                    'category' => $category,
                                    'subcategories' => $subcategories,
                                    'fixedSubcategory' => '内装・意匠履歴',
                                    'displayName' => '内装・意匠履歴',
                                    'interiorType' => 'design'
                                ])
                                
                                {{-- 追加の内装・意匠履歴 --}}
                                @if($designHistories->count() > 1)
                                    @foreach($designHistories->skip(1) as $additionalIndex => $history)
                                        @include('facilities.repair-history.partials.history-form-row', [
                                            'index' => $additionalIndex + 2,
                                            'history' => $history,
                                            'category' => $category,
                                            'subcategories' => $subcategories,
                                            'fixedSubcategory' => '内装・意匠履歴',
                                            'displayName' => '内装・意匠履歴',
                                            'interiorType' => 'design'
                                        ])
                                    @endforeach
                                @endif
                            @else
                                {{-- その他のカテゴリ --}}
                                @if($histories->count() > 0)
                                    @foreach($histories as $index => $history)
                                        @include('facilities.repair-history.partials.history-form-row', [
                                            'index' => $index,
                                            'history' => $history,
                                            'category' => $category,
                                            'subcategories' => $subcategories,
                                            'displayName' => '改修工事履歴'
                                        ])
                                    @endforeach
                                @else
                                    @include('facilities.repair-history.partials.history-form-row', [
                                        'index' => 0,
                                        'history' => null,
                                        'category' => $category,
                                        'subcategories' => $subcategories,
                                        'displayName' => '改修工事履歴'
                                    ])
                                @endif
                            @endif
                        </div>

                        <!-- Add history button -->
                        @if($category === 'interior')
                            <div class="mb-4" style="margin-top: 15px;">
                                <button type="button" class="btn btn-outline-primary" id="addHistoryBtn">
                                    <i class="fas fa-plus me-2"></i>内装・意匠履歴を追加
                                </button>
                            </div>
                        @elseif($category !== 'exterior')
                            <div class="mb-4" style="margin-top: 15px;">
                                <button type="button" class="btn btn-outline-primary" id="addHistoryBtn">
                                    <i class="fas fa-plus me-2"></i>修繕履歴を追加
                                </button>
                            </div>
                        @endif

                        <!-- Special notes section -->
                        <div class="mb-4">
                            <label for="special_notes" class="form-label">
                                <i class="fas fa-sticky-note me-2"></i>特記事項
                            </label>
                            @php
                                $currentSpecialNotes = match($category) {
                                    'exterior' => $facility->exterior_special_notes,
                                    'interior' => $facility->interior_special_notes,
                                    'other' => $facility->other_special_notes,
                                    default => ''
                                };
                            @endphp
                            <textarea class="form-control @error('special_notes') is-invalid @enderror" 
                                      id="special_notes" 
                                      name="special_notes" 
                                      rows="4" 
                                      placeholder="特記事項があれば入力してください">{{ old('special_notes', $currentSpecialNotes) }}</textarea>
                            @error('special_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Save and cancel buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('facilities.show', $facility) }}#{{ $backFragment }}" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>キャンセル
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>保存
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History form row template -->
<div id="historyRowTemplate" style="display: none;">
    @if($category === 'interior')
        @include('facilities.repair-history.partials.history-form-row', [
            'index' => 0,
            'history' => null,
            'category' => $category,
            'subcategories' => $subcategories,
            'fixedSubcategory' => '内装・意匠履歴',
            'displayName' => '内装・意匠履歴',
            'interiorType' => 'design',
            'isTemplate' => true
        ])
    @elseif($category === 'other')
        @include('facilities.repair-history.partials.history-form-row', [
            'index' => 0,
            'history' => null,
            'category' => $category,
            'subcategories' => $subcategories,
            'displayName' => '改修工事履歴',
            'isTemplate' => true
        ])
    @else
        @include('facilities.repair-history.partials.history-form-row', [
            'index' => 0,
            'history' => null,
            'category' => $category,
            'subcategories' => $subcategories,
            'isTemplate' => true
        ])
    @endif
</div>
@endsection

@push('styles')
<style>
/* Edit form specific styles */
.history-form-row {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    background-color: #f8f9fa;
    position: relative;
}

.history-form-row:last-child {
    margin-bottom: 0;
}

.history-form-row .row-header {
    display: flex;
    justify-content-between;
    align-items-center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}

.history-form-row .row-header h6 {
    margin: 0;
    color: #495057;
    font-weight: 600;
}

.history-form-row .remove-row-btn {
    position: absolute;
    top: 0.125rem;
    right: 1rem;
    z-index: 10;
}

.form-row {
    margin-bottom: 1rem;
}

.form-row:last-child {
    margin-bottom: 0;
}

/* Warranty period fields for exterior category */
.warranty-fields {
    background-color: #e3f2fd;
    border: 1px solid #bbdefb;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-top: 1rem;
}

.warranty-fields .form-label {
    color: #1976d2;
    font-weight: 600;
}



/* Responsive design */
@media (max-width: 768px) {
    .history-form-row {
        padding: 1rem;
    }
    
    .history-form-row .remove-row-btn {
        position: static;
        margin-top: 1rem;
        width: 100%;
    }
    
    .d-flex.justify-content-end.gap-2 {
        flex-direction: column;
    }
    
    .d-flex.justify-content-end.gap-2 .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}

/* Validation styles */
.is-invalid {
    border-color: #dc3545 !important;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.is-valid {
    border-color: #28a745 !important;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.94-.94 1.44 1.44L7.4 4.5 6.46 3.56l-1.78 1.78-.94-.94z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.invalid-feedback {
    display: none !important; /* Hide error messages for consistency with basic info edit page */
}

.valid-feedback {
    display: none !important; /* Hide success messages for consistency with basic info edit page */
}

/* Required field marker */
.required::after {
    content: " *";
    color: #dc3545;
    font-weight: bold;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentCategory = '{{ $category }}';
    let historyIndex;
    
    // Calculate the next available index by finding the highest existing index
    const existingRows = document.querySelectorAll('.history-form-row');
    let maxIndex = -1;
    
    existingRows.forEach(function(row) {
        const inputs = row.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            if (input.name) {
                const match = input.name.match(/\[(\d+)\]/);
                if (match) {
                    const index = parseInt(match[1]);
                    if (index > maxIndex) {
                        maxIndex = index;
                    }
                }
            }
        });
    });
    
    historyIndex = maxIndex + 1;
    console.log('Calculated initial historyIndex:', historyIndex);
    
    // Add history button event listener
    const addHistoryBtn = document.getElementById('addHistoryBtn');
    console.log('Looking for addHistoryBtn, found:', addHistoryBtn);
    console.log('Current category:', currentCategory);
    console.log('Initial historyIndex:', historyIndex);
    
    if (addHistoryBtn) {
        console.log('Adding click event listener to addHistoryBtn');
        addHistoryBtn.addEventListener('click', function(e) {
            console.log('Add history button clicked - adding new row');
            e.preventDefault();
            try {
                addHistoryRow();
            } catch (error) {
                console.error('Error in addHistoryRow:', error);
            }
        });
    } else {
        console.error('Add history button not found - checking all buttons on page');
        const allButtons = document.querySelectorAll('button');
        console.log('All buttons found:', allButtons);
    }
    
    // Remove history button event listener (event delegation)
    document.getElementById('historiesContainer').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-history-btn') || e.target.closest('.remove-history-btn')) {
            const button = e.target.classList.contains('remove-history-btn') ? e.target : e.target.closest('.remove-history-btn');
            removeHistoryRow(button);
        }
    });
    

    
    // Form validation before submission
    const form = document.getElementById('repairHistoryForm');
    if (form) {
        console.log('Form element found');
        console.log('Form action:', form.action);
        console.log('Form method:', form.method);
        console.log('CSRF token:', form.querySelector('input[name="_token"]')?.value);
        
        form.addEventListener('submit', function(e) {
            console.log('=== Form submission event triggered ===');
            
            // Debug: Log all form data before submission
            const formData = new FormData(form);
            console.log('Form data being submitted:');
            for (let [key, value] of formData.entries()) {
                if (key.includes('histories')) {
                    console.log(`  ${key}: ${value}`);
                }
            }
            
            // Validation temporarily disabled for testing purposes
            console.log('Form validation skipped - proceeding with submission');
            return true; // Allow form submission to proceed
            
            /* To enable validation, uncomment the following code
            const isValid = validateForm();
            console.log('Validation result:', isValid);
            
            if (!isValid) {
                console.log('Preventing form submission due to validation errors');
                e.preventDefault();
            } else {
                console.log('Validation successful - continuing form submission');
                // Allow form submission (do nothing)
            }
            */
        });
    } else {
        console.error('Form element not found: repairHistoryForm');
    }
    
    /**
     * Add a new history row to the form.
     */
    function addHistoryRow() {
        console.log('addHistoryRow function called');
        
        const template = document.getElementById('historyRowTemplate');
        const container = document.getElementById('historiesContainer');
        
        if (!template) {
            console.error('Template not found: historyRowTemplate');
            return;
        }
        
        if (!container) {
            console.error('Container not found: historiesContainer');
            return;
        }
        
        console.log('Template and container found, cloning template');
        
        // Clone the template
        const clonedTemplate = template.cloneNode(true);
        clonedTemplate.style.display = 'block';
        clonedTemplate.removeAttribute('id');
        
        // Update name and id attributes for all input fields
        const inputs = clonedTemplate.querySelectorAll('input, select, textarea');
        console.log('Found inputs in template:', inputs.length);
        inputs.forEach(function(input, inputIndex) {
            console.log(`Input ${inputIndex}: name="${input.name}", type="${input.type}", value="${input.value}"`);
            
            if (input.name) {
                const oldName = input.name;
                input.name = input.name.replace('[0]', '[' + historyIndex + ']');
                console.log(`Updated name: ${oldName} -> ${input.name}`);
            }
            if (input.id) {
                input.id = input.id.replace('_0_', '_' + historyIndex + '_');
            }
            
            // Clear values (except subcategory hidden field)
            if (input.type !== 'hidden' || input.name.includes('[id]')) {
                const oldValue = input.value;
                input.value = '';
                console.log(`Cleared value for ${input.name}: "${oldValue}" -> "${input.value}"`);
            }
        });
        
        // Update for attributes of labels
        const labels = clonedTemplate.querySelectorAll('label');
        labels.forEach(function(label) {
            if (label.getAttribute('for')) {
                label.setAttribute('for', label.getAttribute('for').replace('_0_', '_' + historyIndex + '_'));
            }
        });
        
        // Update history number and handle category-specific display names
        const header = clonedTemplate.querySelector('.row-header h6');
        if (header) {
            if (currentCategory === 'interior') {
                // For interior category, new additions are always 内装・意匠履歴
                header.innerHTML = '<i class="fas fa-wrench me-2"></i>内装・意匠履歴';
                
                // Set the subcategory hidden field to 内装・意匠履歴
                const subcategoryInput = clonedTemplate.querySelector('input[name*="[subcategory]"]');
                if (subcategoryInput) {
                    subcategoryInput.value = '内装・意匠履歴';
                }
            } else if (currentCategory === 'other') {
                // For other category, new additions are always 改修工事履歴
                header.innerHTML = '<i class="fas fa-wrench me-2"></i>改修工事履歴';
            } else {
                header.innerHTML = '<i class="fas fa-wrench me-2"></i>修繕履歴 #' + (historyIndex + 1);
            }
        }
        
        // Update warranty fields ID
        const warrantyFields = clonedTemplate.querySelector('.warranty-fields');
        if (warrantyFields) {
            warrantyFields.id = 'warranty_fields_' + historyIndex;
            warrantyFields.style.display = 'none'; // Hide by default
        }
        
        // Update interior design fields ID
        const interiorDesignFields = clonedTemplate.querySelector('.interior-design-fields');
        if (interiorDesignFields) {
            interiorDesignFields.id = 'interior_design_fields_' + historyIndex;
            interiorDesignFields.style.display = 'none'; // Hide by default
        }
        
        // Update contact fields ID
        const contactFields = clonedTemplate.querySelector('.contact-fields');
        if (contactFields) {
            contactFields.id = 'contact_fields_' + historyIndex;
            contactFields.style.display = 'none'; // Hide by default
        }
        
        // Update notes field ID
        const notesField = clonedTemplate.querySelector('.notes-field');
        if (notesField) {
            notesField.id = 'notes_field_' + historyIndex;
        }
        
        // Update date label ID
        const dateLabel = clonedTemplate.querySelector('[id*="date_label_"]');
        if (dateLabel) {
            dateLabel.id = 'date_label_' + historyIndex;
        }
        
        // Update company label ID
        const companyLabel = clonedTemplate.querySelector('[id*="company_label_"]');
        if (companyLabel) {
            companyLabel.id = 'company_label_' + historyIndex;
        }
        
        // Add to container
        container.appendChild(clonedTemplate);
        
        // Remove any existing ID fields from the new row to ensure it's treated as a new record
        const idFields = clonedTemplate.querySelectorAll('input[name*="[id]"]');
        console.log('Found ID fields to remove:', idFields.length);
        idFields.forEach(function(idField) {
            console.log('Removing ID field:', idField.name, 'with value:', idField.value);
            idField.remove();
        });
        
        // Also remove any hidden ID fields that might be in the template
        const allHiddenInputs = clonedTemplate.querySelectorAll('input[type="hidden"]');
        allHiddenInputs.forEach(function(input) {
            if (input.name && input.name.includes('[id]')) {
                console.log('Removing hidden ID field:', input.name, 'with value:', input.value);
                input.remove();
            }
        });
        
        // Debug: Check the final state of the added row
        const addedInputs = clonedTemplate.querySelectorAll('input[type="hidden"]');
        console.log('Hidden inputs in added row after ID removal:');
        addedInputs.forEach(function(input) {
            console.log(`  ${input.name}: "${input.value}"`);
        });
        
        // Initialize fields visibility for the new row
        if (currentCategory === 'interior') {
            // For interior category, new additions are always 内装・意匠履歴
            // Show interior design fields for 内装・意匠履歴
            const interiorDesignFields = clonedTemplate.querySelector('.interior-design-fields');
            if (interiorDesignFields) {
                interiorDesignFields.style.display = 'block';
            }
            
            // Hide renovation fields for new additions
            const interiorRenovationFields = clonedTemplate.querySelector('.interior-renovation-fields');
            if (interiorRenovationFields) {
                interiorRenovationFields.style.display = 'none';
            }
            
            // Hide contact fields
            const contactFields = clonedTemplate.querySelector('.contact-fields');
            if (contactFields) {
                contactFields.style.display = 'none';
            }
            
            // Show notes field
            const notesField = clonedTemplate.querySelector('.notes-field');
            if (notesField) {
                notesField.style.display = 'block';
            }
            
            // Update labels
            const dateLabel = clonedTemplate.querySelector('[id*="date_label_"]');
            const companyLabel = clonedTemplate.querySelector('[id*="company_label_"]');
            if (dateLabel) dateLabel.textContent = '施工日';
            if (companyLabel) companyLabel.textContent = '施工会社';
            
        } else if (currentCategory === 'other') {
            // For other category, show other-category-fields directly
            const otherCategoryFields = clonedTemplate.querySelector('.other-category-fields');
            if (otherCategoryFields) {
                otherCategoryFields.style.display = 'block';
            }
        } else if (currentCategory === 'exterior') {
            // For exterior category, no special field visibility handling needed
            // Contact fields are always visible for exterior
        } else {
            const subcategoryField = clonedTemplate.querySelector('select[name*="[subcategory]"], input[name*="[subcategory]"]');
            if (subcategoryField) {
                toggleFieldsVisibility(historyIndex, subcategoryField.value);
            }
        }
        
        historyIndex++;
        
        // Update remove button visibility
        updateRemoveButtons();
        
        console.log('New history row added successfully, new historyIndex:', historyIndex);
    }
    
    /**
     * Remove a history row from the form.
     */
    function removeHistoryRow(button) {
        const row = button.closest('.history-form-row');
        if (row) {
            row.remove();
            updateRemoveButtons();
        }
    }
    
    /**
     * Update remove button visibility (keep at least one row).
     */
    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.history-form-row');
        const removeButtons = document.querySelectorAll('.remove-history-btn');
        
        removeButtons.forEach(function(button) {
            button.style.display = rows.length > 1 ? 'block' : 'none';
        });
        

    }
    

    
    /**
     * Form validation with success/error styling.
     */
    function validateForm() {
        console.log('=== Form validation started ===');
        
        // Apply validation styles to all fields
        validateAllFields();
        
        // Check for error fields
        const errorFields = document.querySelectorAll('.is-invalid');
        console.log('Number of error fields:', errorFields.length);
        
        // Debug: Display error field details
        if (errorFields.length > 0) {
            console.log('Error field list:');
            errorFields.forEach(function(field, index) {
                console.log(`${index + 1}. ${field.name || field.id} (${field.type}): "${field.value}"`);
            });
            
            // Scroll to first error field
            errorFields[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            errorFields[0].focus();
            
            return false;
        }
        
        console.log('Validation successful - allowing form submission');
        return true;
    }
    
    /**
     * Apply validation styling to all fields.
     */
    function validateAllFields() {
        console.log('=== Validating all fields ===');
        
        // History row fields
        const rows = document.querySelectorAll('.history-form-row');
        rows.forEach(function(row, rowIndex) {
            console.log('Checking row', rowIndex + 1);
            
            const allFields = row.querySelectorAll('input, select, textarea');
            allFields.forEach(function(field) {
                validateSingleField(field);
            });
        });
        
        // Special notes field
        const specialNotesField = document.getElementById('special_notes');
        if (specialNotesField) {
            validateSingleField(specialNotesField);
        }
    }
    
    /**
     * Set up real-time validation on input change
     */
    function setupRealTimeValidation() {
        const container = document.getElementById('historiesContainer');
        const specialNotesField = document.getElementById('special_notes');
        
        // Event delegation for dynamic form fields
        container.addEventListener('input', function(e) {
            if (e.target.matches('input, select, textarea')) {
                validateSingleField(e.target);
            }
        });
        
        container.addEventListener('blur', function(e) {
            if (e.target.matches('input, select, textarea')) {
                validateSingleField(e.target);
            }
        });
        
        // Special notes field validation
        if (specialNotesField) {
            specialNotesField.addEventListener('input', function() {
                validateSingleField(this);
            });
        }
    }
    
    /**
     * Validate a single field
     */
    function validateSingleField(field) {
        // Skip hidden fields and buttons
        if (field.type === 'hidden' || field.type === 'button' || field.type === 'submit') {
            return;
        }
        
        console.log('Checking field:', field.name || field.id, field.type, 'value:', field.value);
        
        // Reset validation classes
        field.classList.remove('is-invalid', 'is-valid');
        
        // Remove existing feedback elements
        const existingFeedback = field.parentNode.querySelector('.invalid-feedback, .valid-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }
        
        let fieldValid = true;
        let errorMessage = '';
        let successMessage = '';
        
        // Required field validation - check if field has required attribute
        const isRequired = field.hasAttribute('required');
        console.log('Required field:', isRequired, 'field name:', field.name);
        
        if (isRequired && !field.value.trim()) {
            fieldValid = false;
            errorMessage = 'この項目は必須です';
            console.log('Required field error:', field.name);
        }
        
        // Various validations when field has value
        if (field.value.trim()) {
            // Date field validation
            if (field.type === 'date') {
                const date = new Date(field.value);
                const today = new Date();
                today.setHours(23, 59, 59, 999); // Allow until end of today
                
                if (date > today) {
                    fieldValid = false;
                    errorMessage = '未来の日付は入力できません';
                } else {
                    successMessage = '日付が正しく入力されています';
                }
            }
            
            // Cost field validation
            if (field.name && field.name.includes('cost')) {
                const cost = parseFloat(field.value);
                if (isNaN(cost) || cost < 0) {
                    fieldValid = false;
                    errorMessage = '正の数値を入力してください';
                } else {
                    successMessage = '金額が正しく入力されています';
                }
            }
            
            // Email validation
            if (field.type === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value)) {
                    fieldValid = false;
                    errorMessage = '正しいメールアドレスを入力してください';
                } else {
                    successMessage = 'メールアドレスが正しく入力されています';
                }
            }
            
            // Phone number validation (basic)
            if (field.name && field.name.includes('phone')) {
                const phoneRegex = /^[\d\-\(\)\+\s]+$/;
                if (!phoneRegex.test(field.value)) {
                    fieldValid = false;
                    errorMessage = '正しい電話番号を入力してください';
                } else {
                    successMessage = '電話番号が正しく入力されています';
                }
            }
        }
        
        // Apply validation styling - same style as basic info edit page
        if (!fieldValid) {
            field.classList.add('is-invalid');
            console.log('❌ Error set:', field.name || field.id, 'reason:', errorMessage);
            // Don't display error messages (red border only)
        } else {
            // Apply success style to all fields (regardless of whether they have values)
            field.classList.add('is-valid');
            console.log('✅ Success set:', field.name || field.id);
            // Don't display success messages either (green border only)
        }
    }
    
    // Set remove button visibility on initialization
    updateRemoveButtons();
    
    // Setup real-time validation
    setupRealTimeValidation();
    
    // Setup warranty fields visibility control
    setupWarrantyFieldsControl();
    
    // Log save button click events
    const saveButton = document.querySelector('button[type="submit"]');
    if (saveButton) {
        console.log('Save button found');
        saveButton.addEventListener('click', function(e) {
            console.log('=== Save button clicked ===');
            console.log('Button type:', this.type);
            console.log('Form:', this.form);
        });
    } else {
        console.error('Save button not found');
    }
    
    /**
     * Setup fields visibility control based on subcategory selection
     */
    function setupWarrantyFieldsControl() {
        const container = document.getElementById('historiesContainer');
        
        // Event delegation for subcategory changes (both select and input) - not for exterior
        if (currentCategory !== 'exterior') {
            container.addEventListener('change', function(e) {
                if (e.target.matches('select[name*="[subcategory]"], input[name*="[subcategory]"]')) {
                    const field = e.target;
                    const index = extractIndexFromName(field.name);
                    toggleFieldsVisibility(index, field.value);
                }
            });
            
            // Event delegation for subcategory input changes (for real-time updates)
            container.addEventListener('input', function(e) {
                if (e.target.matches('input[name*="[subcategory]"]')) {
                    const field = e.target;
                    const index = extractIndexFromName(field.name);
                    toggleFieldsVisibility(index, field.value);
                }
            });
        }
        
        // Initialize visibility for existing rows (both select and input)
        if (currentCategory !== 'exterior') {
            const subcategoryFields = container.querySelectorAll('select[name*="[subcategory]"], input[name*="[subcategory]"]');
            subcategoryFields.forEach(function(field) {
                const index = extractIndexFromName(field.name);
                toggleFieldsVisibility(index, field.value);
            });
        }
    }
    
    /**
     * Extract index from field name like "histories[0][subcategory]"
     */
    function extractIndexFromName(name) {
        const match = name.match(/histories\[(\d+)\]/);
        return match ? match[1] : null;
    }
    
    /**
     * Toggle fields visibility and labels based on subcategory
     */
    function toggleFieldsVisibility(index, subcategory) {
        // Handle warranty fields for exterior category
        const warrantyFields = document.getElementById('warranty_fields_' + index);
        if (warrantyFields) {
            if (subcategory === 'waterproof' || subcategory === '防水') {
                warrantyFields.style.display = 'block';
            } else {
                warrantyFields.style.display = 'none';
                // Clear warranty fields when hiding
                const warrantyInputs = warrantyFields.querySelectorAll('input');
                warrantyInputs.forEach(function(input) {
                    input.value = '';
                });
            }
        }
        
        // Handle interior and other category fields
        const interiorDesignFields = document.getElementById('interior_design_fields_' + index);
        const contactFields = document.getElementById('contact_fields_' + index);
        const notesField = document.getElementById('notes_field_' + index);
        const dateLabel = document.getElementById('date_label_' + index);
        const companyLabel = document.getElementById('company_label_' + index);
        
        if (currentCategory === 'interior') {
            if (subcategory === 'design' || subcategory === '内装・意匠履歴') {
                // Interior design history: show design fields, hide contact fields, show notes, change labels
                if (interiorDesignFields) interiorDesignFields.style.display = 'block';
                if (contactFields) contactFields.style.display = 'none';
                if (notesField) notesField.style.display = 'block';
                if (dateLabel) dateLabel.textContent = '施工日';
                if (companyLabel) companyLabel.textContent = '施工会社';
                
                // Clear contact fields when hiding
                if (contactFields) {
                    const inputs = contactFields.querySelectorAll('input');
                    inputs.forEach(function(input) {
                        input.value = '';
                    });
                }
            } else if (subcategory === 'renovation' || subcategory === '内装リニューアル') {
                // Interior renovation: hide design fields, show contact fields, hide notes, change labels
                if (interiorDesignFields) interiorDesignFields.style.display = 'none';
                if (contactFields) contactFields.style.display = 'block';
                if (notesField) notesField.style.display = 'none';
                if (dateLabel) dateLabel.textContent = 'リニューアル';
                if (companyLabel) companyLabel.textContent = '会社名';
                
                // Clear design fields and notes when hiding
                if (interiorDesignFields) {
                    const inputs = interiorDesignFields.querySelectorAll('input, textarea');
                    inputs.forEach(function(input) {
                        input.value = '';
                    });
                }
                if (notesField) {
                    const textarea = notesField.querySelector('textarea');
                    if (textarea) textarea.value = '';
                }
            } else {
                // Default behavior for interior
                if (interiorDesignFields) interiorDesignFields.style.display = 'none';
                if (contactFields) contactFields.style.display = 'none';
                if (notesField) notesField.style.display = 'block';
                if (dateLabel) dateLabel.textContent = '施工日';
                if (companyLabel) companyLabel.textContent = '会社名';
            }
        } else if (currentCategory === 'other') {
            // Other category: hide interior design fields and contact fields, show other category fields
            if (interiorDesignFields) interiorDesignFields.style.display = 'none';
            if (contactFields) contactFields.style.display = 'none';
            if (notesField) notesField.style.display = 'none'; // Notes are handled in other-category-fields
            if (dateLabel) dateLabel.textContent = '施工日';
            if (companyLabel) companyLabel.textContent = '施工会社';
            
            // Show other category specific fields
            const otherCategoryFields = document.querySelector('.other-category-fields');
            if (otherCategoryFields) {
                otherCategoryFields.style.display = 'block';
            }
            
            // Clear interior design and contact fields when hiding
            if (interiorDesignFields) {
                const inputs = interiorDesignFields.querySelectorAll('input, textarea');
                inputs.forEach(function(input) {
                    input.value = '';
                });
            }
            if (contactFields) {
                const inputs = contactFields.querySelectorAll('input');
                inputs.forEach(function(input) {
                    input.value = '';
                });
            }
        } else {
            // Default behavior for exterior and other categories
            if (interiorDesignFields) interiorDesignFields.style.display = 'none';
            if (contactFields) contactFields.style.display = 'none';
            if (notesField) notesField.style.display = 'block';
            if (dateLabel) dateLabel.textContent = '施工日';
            if (companyLabel) companyLabel.textContent = '会社名';
        }
    }
});
</script>
@endpush