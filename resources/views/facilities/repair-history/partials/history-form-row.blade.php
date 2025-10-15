@php
$isTemplate = $isTemplate ?? false; // テンプレートフラグ（動的フォーム生成用）
$fixedSubcategory = $fixedSubcategory ?? null; // 外装レイアウト用の固定サブカテゴリ
$displayName = $displayName ?? null; // 外装レイアウト用の表示名
$interiorType = $interiorType ?? null; // 内装タイプ（renovation または design）
$summerCondensationType = $summerCondensationType ?? null; // 夏型結露タイプ（countermeasure または history）
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
$warrantyPeriodMonths = $history ? $history->warranty_period_months : old("histories.{$index}.warranty_period_months", '');
$warrantyStartDate = $history ? $history->warranty_start_date?->format('Y-m-d') : old("histories.{$index}.warranty_start_date", '');
$warrantyEndDate = $history ? $history->warranty_end_date?->format('Y-m-d') : old("histories.{$index}.warranty_end_date", '');

// 保証期間の有無を判定
$hasWarranty = $history ? ($history->warranty_period_years !== null || $history->warranty_period_months !== null) : false;
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

    <!-- Remove button (displayed for interior design history, summer condensation history and other categories) -->
    @if(($category === 'interior' && $fixedSubcategory === '内装・意匠履歴') ||
    ($category === 'summer_condensation' && $fixedSubcategory === '夏型結露対策履歴') ||
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
                class="form-control @error("histories.{$index}.subcategory") is-invalid @enderror"
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
                class="form-control @error("histories.{$index}.subcategory") is-invalid @enderror"
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
        @elseif($category === 'summer_condensation' && !$fixedSubcategory)
        <div class="col-md-4">
            <label for="histories_{{ $index }}_subcategory" class="form-label">種別</label>
            <input type="text"
                class="form-control @error("histories.{$index}.subcategory") is-invalid @enderror"
                id="histories_{{ $index }}_subcategory"
                name="histories[{{ $index }}][subcategory]"
                value="{{ $subcategory }}"
                maxlength="50"
                placeholder="例：夏型対策、結露対策など">
            @error("histories.{$index}.subcategory")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @elseif($category === 'summer_condensation' && $fixedSubcategory)
        {{-- 固定サブカテゴリの場合はhiddenフィールド --}}
        <input type="hidden" name="histories[{{ $index }}][subcategory]" value="{{ $fixedSubcategory }}">
        @elseif($category === 'other')
        <!-- Hidden subcategory field for other category with default value -->
        <input type="hidden" name="histories[{{ $index }}][subcategory]" value="renovation_work">
        @endif

        <!-- Date field (label changes based on subcategory) -->
        <div class="{{ $category === 'other' || $category === 'summer_condensation' ? 'col-md-6' : ($fixedSubcategory ? 'col-md-6' : 'col-md-4') }}">
            <label for="histories_{{ $index }}_maintenance_date" class="form-label" id="date_label_{{ $index }}">
                @if($category === 'interior' && $interiorType === 'design')
                施工日
                @elseif($category === 'summer_condensation' && $summerCondensationType === 'history')
                施工日
                @else
                実施日
                @endif
            </label>
            <input type="date"
                class="form-control @error("histories.{$index}.maintenance_date") is-invalid @enderror"
                id="histories_{{ $index }}_maintenance_date"
                name="histories[{{ $index }}][maintenance_date]"
                value="{{ $maintenanceDate }}">
            @error("histories.{$index}.maintenance_date")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Contractor field -->
        <div class="{{ $category === 'other' || $category === 'summer_condensation' ? 'col-md-6' : ($fixedSubcategory ? 'col-md-6' : 'col-md-4') }}">
            <label for="histories_{{ $index }}_contractor" class="form-label" id="company_label_{{ $index }}">
                @if($category === 'interior' && $interiorType === 'design')
                施工会社
                @elseif($category === 'summer_condensation' && $summerCondensationType === 'history')
                施工会社
                @else
                会社名
                @endif
            </label>
            <input type="text"
                class="form-control @error("histories.{$index}.contractor") is-invalid @enderror"
                id="histories_{{ $index }}_contractor"
                name="histories[{{ $index }}][contractor]"
                value="{{ $contractor }}"
                maxlength="100"
                placeholder="施工会社名を入力">
            @error("histories.{$index}.contractor")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Contact information (for exterior and interior renovation only) -->
    @if(($category === 'exterior') || ($category === 'interior' && $interiorType === 'renovation') || ($category === 'summer_condensation' && $summerCondensationType === 'countermeasure'))
    <div class="row form-row contact-fields" id="contact_fields_{{ $index }}">
        <div class="col-md-6">
            <label for="histories_{{ $index }}_contact_person" class="form-label">担当者</label>
            <input type="text"
                class="form-control @error("histories.{$index}.contact_person") is-invalid @enderror"
                id="histories_{{ $index }}_contact_person"
                name="histories[{{ $index }}][contact_person]"
                value="{{ $contactPerson }}"
                maxlength="50"
                placeholder="担当者名を入力">
            @error("histories.{$index}.contact_person")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="histories_{{ $index }}_phone_number" class="form-label">連絡先</label>
            <input type="text"
                class="form-control @error("histories.{$index}.phone_number") is-invalid @enderror"
                id="histories_{{ $index }}_phone_number"
                name="histories[{{ $index }}][phone_number]"
                value="{{ $phoneNumber }}"
                maxlength="20"
                placeholder="電話番号を入力">
            @error("histories.{$index}.phone_number")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    @endif

    <!-- Interior design fields (for interior design history only) -->
    @if($category === 'interior' && $interiorType === 'design')
    <div class="row form-row interior-design-fields" id="interior_design_fields_{{ $index }}">
        <div class="col-md-4">
            <label for="histories_{{ $index }}_cost" class="form-label">金額</label>
            <input type="number"
                class="form-control @error("histories.{$index}.cost") is-invalid @enderror"
                id="histories_{{ $index }}_cost"
                name="histories[{{ $index }}][cost]"
                value="{{ $cost }}"
                min="0"
                step="1"
                placeholder="金額を入力（円）">
            @error("histories.{$index}.cost")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label for="histories_{{ $index }}_content" class="form-label">修繕内容</label>
            <input type="text"
                class="form-control @error("histories.{$index}.content") is-invalid @enderror"
                id="histories_{{ $index }}_content"
                name="histories[{{ $index }}][content]"
                value="{{ $content }}"
                maxlength="200"
                placeholder="修繕内容を入力">
            @error("histories.{$index}.content")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label for="histories_{{ $index }}_notes" class="form-label">備考</label>
            <input type="text"
                class="form-control @error("histories.{$index}.notes") is-invalid @enderror"
                id="histories_{{ $index }}_notes"
                name="histories[{{ $index }}][notes]"
                value="{{ $notes }}"
                maxlength="200"
                placeholder="備考を入力">
            @error("histories.{$index}.notes")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    @endif

    <!-- Summer condensation history fields (for summer condensation history only) -->
    @if($category === 'summer_condensation' && $summerCondensationType === 'history')
    <div class="row form-row summer-condensation-history-fields" id="summer_condensation_history_fields_{{ $index }}">
        <div class="col-md-4">
            <label for="histories_{{ $index }}_cost" class="form-label">金額</label>
            <input type="number"
                class="form-control @error("histories.{$index}.cost") is-invalid @enderror"
                id="histories_{{ $index }}_cost"
                name="histories[{{ $index }}][cost]"
                value="{{ $cost }}"
                min="0"
                step="1"
                placeholder="金額を入力（円）">
            @error("histories.{$index}.cost")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label for="histories_{{ $index }}_content" class="form-label">修繕内容</label>
            <input type="text"
                class="form-control @error("histories.{$index}.content") is-invalid @enderror"
                id="histories_{{ $index }}_content"
                name="histories[{{ $index }}][content]"
                value="{{ $content }}"
                maxlength="200"
                placeholder="修繕内容を入力">
            @error("histories.{$index}.content")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label for="histories_{{ $index }}_notes" class="form-label">備考</label>
            <input type="text"
                class="form-control @error("histories.{$index}.notes") is-invalid @enderror"
                id="histories_{{ $index }}_notes"
                name="histories[{{ $index }}][notes]"
                value="{{ $notes }}"
                maxlength="200"
                placeholder="備考を入力">
            @error("histories.{$index}.notes")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    @endif

    <!-- Other category fields -->
    @if($category === 'other')
    <div class="row form-row other-category-fields" id="other_category_fields_{{ $index }}">
        <div class="col-md-12">
            <label for="histories_{{ $index }}_content" class="form-label">修繕内容</label>
            <textarea class="form-control @error("histories.{$index}.content") is-invalid @enderror"
                id="histories_{{ $index }}_content"
                name="histories[{{ $index }}][content]"
                rows="3"
                maxlength="500"
                placeholder="修繕内容を入力">{{ $content }}</textarea>
            @error("histories.{$index}.content")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="row form-row other-category-fields">
        <div class="col-md-12">
            <label for="histories_{{ $index }}_notes" class="form-label">備考</label>
            <textarea class="form-control @error("histories.{$index}.notes") is-invalid @enderror"
                id="histories_{{ $index }}_notes"
                name="histories[{{ $index }}][notes]"
                rows="2"
                maxlength="500"
                placeholder="備考を入力">{{ $notes }}</textarea>
            @error("histories.{$index}.notes")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    @endif

    <!-- Warranty period fields (for exterior category only) -->
    @if($category === 'exterior')
    <div class="warranty-fields" id="warranty_fields_{{ $index }}" style="{{ $hasWarranty ? '' : 'display: none;' }}">
        <div class="row form-row">
            <div class="col-md-6">
                <label class="form-label">保証期間</label>
                <div class="input-group">
                    <input type="number"
                        class="form-control @error("histories.{$index}.warranty_period_years") is-invalid @enderror"
                        id="histories_{{ $index }}_warranty_period_years"
                        name="histories[{{ $index }}][warranty_period_years]"
                        value="{{ $warrantyPeriodYears }}"
                        min="0"
                        max="99"
                        placeholder="年">
                    <span class="input-group-text">年</span>
                    <input type="number"
                        class="form-control @error("histories.{$index}.warranty_period_months") is-invalid @enderror"
                        id="histories_{{ $index }}_warranty_period_months"
                        name="histories[{{ $index }}][warranty_period_months]"
                        value="{{ $warrantyPeriodMonths }}"
                        min="0"
                        max="11"
                        placeholder="月">
                    <span class="input-group-text">月</span>
                </div>
                @error("histories.{$index}.warranty_period_years")
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                @error("histories.{$index}.warranty_period_months")
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-3">
                <label for="histories_{{ $index }}_warranty_start_date" class="form-label">保証開始日</label>
                <input type="date"
                    class="form-control @error("histories.{$index}.warranty_start_date") is-invalid @enderror"
                    id="histories_{{ $index }}_warranty_start_date"
                    name="histories[{{ $index }}][warranty_start_date]"
                    value="{{ $warrantyStartDate }}">
                @error("histories.{$index}.warranty_start_date")
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-3">
                <label for="histories_{{ $index }}_warranty_end_date" class="form-label">保証終了日</label>
                <input type="date"
                    class="form-control @error("histories.{$index}.warranty_end_date") is-invalid @enderror"
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
