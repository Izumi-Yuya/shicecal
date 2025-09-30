@php
$isTemplate = $isTemplate ?? false; // テンプレートフラグ（動的フォーム生成用）
$fixedSubcategory = $fixedSubcategory ?? null; // 外装レイアウト用の固定サブカテゴリ
$displayName = $displayName ?? null; // 外装レイアウト用の表示名
$interiorType = $interiorType ?? null; // 内装タイプ（renovation または design）
$historyId = $history ? $history->id : '';
$maintenanceDate = $history ? $history->maintenance_date?->format('Y-m-d') : old("histories.{$index}.maintenance_date", '');
$contractor = $history ? $history->contractor : old("histories.{$index}.contractor", '');
$contactPerson = $history ? $history->contact_person : old("histories.{$index}.contact_person", '');
$phoneNumber = $history ? $history->phone_number : old("histories.{$index}.phone_number", '');
$notes = $history ? $history->notes : old("histories.{$index}.notes", '');
$subcategory = $fixedSubcategory ?: ($history ? $history->subcategory : old("histories.{$index}.subcategory", ''));

// 内装カテゴリ専用フィールド（内装・意匠履歴用の追加項目）
$content = $history ? $history->content : old("histories.{$index}.content", '');
$cost = $history ? $history->cost : old("histories.{$index}.cost", '');


// 外装カテゴリ専用フィールド
$warrantyPeriodYears = $history ? $history->warranty_period_years : old("histories.{$index}.warranty_period_years", '');
$warrantyStartDate = $history ? $history->warranty_start_date?->format('Y-m-d') : old("histories.{$index}.warranty_start_date", '');
$warrantyEndDate = $history ? $history->warranty_end_date?->format('Y-m-d') : old("histories.{$index}.warranty_end_date", '');

// 保証期間の有無を判定
$hasWarranty = $history ? ($history->warranty_period_years !== null) : false;
@endphp

<div class="history-form-row">
    <!-- Row header -->
    <div class="row-header">
        <h6>
            <i class="fas fa-wrench me-2"></i>
            @if($displayName)
            {{ $displayName }}
            @else
            修繕履歴 #{{ is_numeric($index) ? $index + 1 : '新規' }}
            @endif
        </h6>
    </div>

    <!-- Remove button (displayed for interior design history and other categories) -->
    @if(($category === 'interior' && $fixedSubcategory === '内装・意匠履歴') ||
    ($category === 'other'))
    <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn remove-history-btn">
        <i class="fas fa-trash"></i>
    </button>
    @endif

    <!-- Hidden field (existing record ID) -->
    @if($historyId && !($isTemplate ?? false))
    <input type="hidden" name="histories[{{ $index }}][id]" value="{{ $historyId }}">
    @endif

    <!-- Basic information row -->
    <div class="row form-row">
        <!-- Subcategory field (hidden for other category and exterior with fixed subcategory) -->
        @if($category === 'exterior' && !$fixedSubcategory)
        <div class="col-md-4">
            <label for="histories_{{ $index }}_subcategory" class="form-label">種別</label>
            <input type="text"
                class="form-control @error(" histories.{$index}.subcategory") is-invalid @enderror"
                id="histories_{{ $index }}_subcategory"
                name="histories[{{ $index }}][subcategory]"
                value="{{ $subcategory }}"
                maxlength="50"
                placeholder="例：防水工事、外壁塗装、屋根修繕など">
            @error("histories.{$index}.subcategory")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @elseif($category === 'exterior' && $fixedSubcategory)
        {{-- 固定サブカテゴリの場合はhiddenフィールド --}}
        <input type="hidden" name="histories[{{ $index }}][subcategory]" value="{{ $fixedSubcategory }}">
        @elseif($category === 'interior' && !$fixedSubcategory)
        <div class="col-md-4">
            <label for="histories_{{ $index }}_subcategory" class="form-label">種別</label>
            <input type="text"
                class="form-control @error(" histories.{$index}.subcategory") is-invalid @enderror"
                id="histories_{{ $index }}_subcategory"
                name="histories[{{ $index }}][subcategory]"
                value="{{ $subcategory }}"
                maxlength="50"
                placeholder="例：内装改修、意匠変更、設備更新など">
            @error("histories.{$index}.subcategory")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @elseif($category === 'interior' && $fixedSubcategory)
        {{-- 固定サブカテゴリの場合はhiddenフィールド --}}
        <input type="hidden" name="histories[{{ $index }}][subcategory]" value="{{ $fixedSubcategory }}">
        @elseif($category === 'other')
        <!-- Hidden subcategory field for other category with default value -->
        <input type="hidden" name="histories[{{ $index }}][subcategory]" value="renovation_work">
        @endif

        <!-- Date field (label changes based on subcategory) -->
        <div class="{{ $category === 'other' ? 'col-md-6' : ($fixedSubcategory ? 'col-md-6' : 'col-md-4') }}">
            <label for="histories_{{ $index }}_maintenance_date" class="form-label" id="date_label_{{ $index }}">施工日</label>
            <input type="date"
                class="form-control @error(" histories.{$index}.maintenance_date") is-invalid @enderror"
                id="histories_{{ $index }}_maintenance_date"
                name="histories[{{ $index }}][maintenance_date]"
                value="{{ $maintenanceDate }}">
            @error("histories.{$index}.maintenance_date")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Company name -->
        <div class="{{ $category === 'other' ? 'col-md-6' : ($fixedSubcategory ? 'col-md-6' : 'col-md-4') }}">
            <label for="histories_{{ $index }}_contractor" class="form-label" id="company_label_{{ $index }}">会社名</label>
            <input type="text"
                class="form-control @error(" histories.{$index}.contractor") is-invalid @enderror"
                id="histories_{{ $index }}_contractor"
                name="histories[{{ $index }}][contractor]"
                value="{{ $contractor }}"
                maxlength="255">
            @error("histories.{$index}.contractor")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Contact information row (for exterior category only) -->
    @if($category === 'exterior')
    <div class="row form-row">
        <!-- Contact person -->
        <div class="col-md-6">
            <label for="histories_{{ $index }}_contact_person" class="form-label">担当者</label>
            <input type="text"
                class="form-control @error(" histories.{$index}.contact_person") is-invalid @enderror"
                id="histories_{{ $index }}_contact_person"
                name="histories[{{ $index }}][contact_person]"
                value="{{ $contactPerson }}"
                maxlength="255">
            @error("histories.{$index}.contact_person")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Phone number -->
        <div class="col-md-6">
            <label for="histories_{{ $index }}_phone_number" class="form-label">連絡先</label>
            <input type="text"
                class="form-control @error(" histories.{$index}.phone_number") is-invalid @enderror"
                id="histories_{{ $index }}_phone_number"
                name="histories[{{ $index }}][phone_number]"
                value="{{ $phoneNumber }}"
                maxlength="20">
            @error("histories.{$index}.phone_number")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    @endif

    <!-- Other category specific fields -->
    @if($category === 'other')
    <div class="other-category-fields">
        <!-- Cost row -->
        <div class="row form-row">
            <!-- Cost -->
            <div class="col-md-12">
                <label for="histories_{{ $index }}_cost" class="form-label">金額（円）</label>
                <input type="number"
                    class="form-control @error(" histories.{$index}.cost") is-invalid @enderror"
                    id="histories_{{ $index }}_cost"
                    name="histories[{{ $index }}][cost]"
                    value="{{ $cost }}"
                    min="0"
                    step="1">
                @error("histories.{$index}.cost")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Content row -->
        <div class="row form-row">
            <div class="col-md-12">
                <label for="histories_{{ $index }}_content" class="form-label">修繕内容</label>
                <input type="text"
                    class="form-control @error(" histories.{$index}.content") is-invalid @enderror"
                    id="histories_{{ $index }}_content"
                    name="histories[{{ $index }}][content]"
                    value="{{ $content }}"
                    maxlength="500">
                @error("histories.{$index}.content")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Notes row -->
        <div class="row form-row">
            <div class="col-12">
                <label for="histories_{{ $index }}_notes" class="form-label">備考</label>
                <textarea class="form-control @error(" histories.{$index}.notes") is-invalid @enderror"
                    id="histories_{{ $index }}_notes"
                    name="histories[{{ $index }}][notes]"
                    rows="2">{{ $notes }}</textarea>
                @error("histories.{$index}.notes")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
    @endif




    <!-- Interior renovation contact fields -->
    <div class="interior-renovation-fields mb-3" id="interior_renovation_fields_{{ $index }}" style="display: {{ $interiorType === 'renovation' ? 'block' : 'none' }};">
        <div class="row form-row">
            <!-- Contact person -->
            <div class="col-md-6">
                <label for="histories_{{ $index }}_contact_person" class="form-label">担当者</label>
                <input type="text"
                    class="form-control @error(" histories.{$index}.contact_person") is-invalid @enderror"
                    id="histories_{{ $index }}_contact_person"
                    name="histories[{{ $index }}][contact_person]"
                    value="{{ $contactPerson }}"
                    maxlength="255">
                @error("histories.{$index}.contact_person")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Phone number -->
            <div class="col-md-6">
                <label for="histories_{{ $index }}_phone_number" class="form-label">連絡先</label>
                <input type="text"
                    class="form-control @error(" histories.{$index}.phone_number") is-invalid @enderror"
                    id="histories_{{ $index }}_phone_number"
                    name="histories[{{ $index }}][phone_number]"
                    value="{{ $phoneNumber }}"
                    maxlength="20">
                @error("histories.{$index}.phone_number")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Interior design history specific fields -->
    <div class="interior-design-fields mb-3" id="interior_design_fields_{{ $index }}" style="display: {{ $interiorType === 'design' ? 'block' : 'none' }};">
        <!-- Cost row -->
        <div class="row form-row">
            <!-- Cost -->
            <div class="col-md-12">
                <label for="histories_{{ $index }}_cost" class="form-label">金額（円）</label>
                <input type="number"
                    class="form-control @error(" histories.{$index}.cost") is-invalid @enderror"
                    id="histories_{{ $index }}_cost"
                    name="histories[{{ $index }}][cost]"
                    value="{{ $cost }}"
                    min="0"
                    step="1">
                @error("histories.{$index}.cost")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Content row -->
        <div class="row form-row">
            <div class="col-md-12">
                <label for="histories_{{ $index }}_content" class="form-label">修繕内容</label>
                <input type="text"
                    class="form-control @error(" histories.{$index}.content") is-invalid @enderror"
                    id="histories_{{ $index }}_content"
                    name="histories[{{ $index }}][content]"
                    value="{{ $content }}"
                    maxlength="500">
                @error("histories.{$index}.content")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>











    <!-- Notes row (not for renovation and not for other category) -->
    @if($category !== 'other')
    <div class="notes-field" id="notes_field_{{ $index }}">
        <div class="row form-row">
            <div class="col-12">
                <label for="histories_{{ $index }}_notes" class="form-label">備考</label>
                <textarea class="form-control @error(" histories.{$index}.notes") is-invalid @enderror"
                    id="histories_{{ $index }}_notes"
                    name="histories[{{ $index }}][notes]"
                    rows="2">{{ $notes }}</textarea>
                @error("histories.{$index}.notes")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
    @endif

    @if($category === 'exterior' && $fixedSubcategory === '防水')
    <div class="warranty-fields" id="warranty_fields_{{ $index }}">
        <h6 class="mb-3">
            <i class="fas fa-shield-alt me-2"></i>保証期間情報
        </h6>

        <div class="row">
            <!-- Warranty period (years) -->
            <div class="col-md-4">
                <label for="histories_{{ $index }}_warranty_period_years" class="form-label">保証期間（年）</label>
                <input type="number"
                    class="form-control @error(" histories.{$index}.warranty_period_years") is-invalid @enderror"
                    id="histories_{{ $index }}_warranty_period_years"
                    name="histories[{{ $index }}][warranty_period_years]"
                    value="{{ $warrantyPeriodYears }}"
                    min="1"
                    max="50">
                @error("histories.{$index}.warranty_period_years")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Warranty start date -->
            <div class="col-md-4">
                <label for="histories_{{ $index }}_warranty_start_date" class="form-label">保証開始日</label>
                <input type="date"
                    class="form-control @error(" histories.{$index}.warranty_start_date") is-invalid @enderror"
                    id="histories_{{ $index }}_warranty_start_date"
                    name="histories[{{ $index }}][warranty_start_date]"
                    value="{{ $warrantyStartDate }}">
                @error("histories.{$index}.warranty_start_date")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Warranty end date -->
            <div class="col-md-4">
                <label for="histories_{{ $index }}_warranty_end_date" class="form-label">保証終了日</label>
                <input type="date"
                    class="form-control @error(" histories.{$index}.warranty_end_date") is-invalid @enderror"
                    id="histories_{{ $index }}_warranty_end_date"
                    name="histories[{{ $index }}][warranty_end_date]"
                    value="{{ $warrantyEndDate }}">
                @error("histories.{$index}.warranty_end_date")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
    @endif


</div>