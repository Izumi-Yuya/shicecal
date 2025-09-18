@php
    $electricalLifeline = $facility->getLifelineEquipmentByCategory('electrical');
    $electricalEquipment = $electricalLifeline?->electricalEquipment;
    $basicInfo = $electricalEquipment->basic_info ?? [];
    $pasInfo = $electricalEquipment->pas_info ?? [];
    $cubicleInfo = $electricalEquipment->cubicle_info ?? [];
    $generatorInfo = $electricalEquipment->generator_info ?? [];
    $cubicleEquipmentList = $cubicleInfo['equipment_list'] ?? [];
    $generatorEquipmentList = $generatorInfo['equipment_list'] ?? [];
    
    $breadcrumbs = [
        [
            'title' => '施設一覧',
            'route' => 'facilities.index',
            'active' => false
        ],
        [
            'title' => $facility->facility_name,
            'route' => 'facilities.show',
            'params' => [$facility],
            'active' => false
        ],
        [
            'title' => '電気設備編集',
            'active' => true
        ]
    ];
@endphp

<x-facility.edit-layout
    title="電気設備編集"
    :facility="$facility"
    :breadcrumbs="$breadcrumbs"
    :back-route="route('facilities.show', $facility) . '#electrical'"
    :form-action="route('facilities.lifeline-equipment.update', [$facility, 'electrical'])"
    form-method="PUT"
    form-id="electricalEquipmentForm"
>
    <!-- 基本情報セクション -->
    <x-form.section title="基本情報" icon="fas fa-info-circle" icon-color="primary">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="electrical_contractor" class="form-label">電気契約会社</label>
                <input type="text" class="form-control @error('basic_info.electrical_contractor') is-invalid @enderror" 
                       id="electrical_contractor" name="basic_info[electrical_contractor]" 
                       value="{{ old('basic_info.electrical_contractor', $basicInfo['electrical_contractor'] ?? '') }}">
                <x-form.field-error field="basic_info.electrical_contractor" />
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="safety_management_company" class="form-label">保安管理業者</label>
                <input type="text" class="form-control @error('basic_info.safety_management_company') is-invalid @enderror" 
                       id="safety_management_company" name="basic_info[safety_management_company]" 
                       value="{{ old('basic_info.safety_management_company', $basicInfo['safety_management_company'] ?? '') }}">
                <x-form.field-error field="basic_info.safety_management_company" />
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="maintenance_inspection_date" class="form-label">電気保守点検実施日</label>
                <input type="date" class="form-control @error('basic_info.maintenance_inspection_date') is-invalid @enderror" 
                       id="maintenance_inspection_date" name="basic_info[maintenance_inspection_date]" 
                       value="{{ old('basic_info.maintenance_inspection_date', $basicInfo['maintenance_inspection_date'] ?? '') }}">
                <x-form.field-error field="basic_info.maintenance_inspection_date" />
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="inspection_report_pdf_file" class="form-label">点検実施報告書</label>
                @if(!empty($basicInfo['inspection_report_pdf']))
                    <div class="mb-2">
                        <small class="text-muted">現在のファイル:</small>
                        <a href="{{ route('facilities.lifeline-equipment.download', [$facility, 'electrical', $basicInfo['inspection_report_pdf']]) }}" 
                           class="text-decoration-none ms-1" target="_blank">
                            <i class="fas fa-file-pdf me-1 text-danger"></i>{{ $basicInfo['inspection_report_pdf'] }}
                        </a>
                    </div>
                @endif
                <input type="file" class="form-control @error('basic_info.inspection_report_pdf_file') is-invalid @enderror" 
                       id="inspection_report_pdf_file" name="basic_info[inspection_report_pdf_file]" 
                       accept=".pdf">
                <div class="form-text">PDFファイルのみアップロード可能です（最大10MB）</div>
                <x-form.field-error field="basic_info.inspection_report_pdf_file" />
                
                <!-- Hidden field to preserve existing filename -->
                @if(!empty($basicInfo['inspection_report_pdf']))
                    <input type="hidden" name="basic_info[inspection_report_pdf]" value="{{ $basicInfo['inspection_report_pdf'] }}">
                @endif
            </div>
        </div>
    </x-form.section>

    <!-- PASセクション -->
    <x-form.section title="PAS" icon="fas fa-plug" icon-color="warning">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="pas_availability" class="form-label">有無</label>
                <select class="form-select @error('pas_info.availability') is-invalid @enderror" 
                        id="pas_availability" name="pas_info[availability]">
                    <option value="">選択してください</option>
                    <option value="有" {{ old('pas_info.availability', $pasInfo['availability'] ?? '') === '有' ? 'selected' : '' }}>有</option>
                    <option value="無" {{ old('pas_info.availability', $pasInfo['availability'] ?? '') === '無' ? 'selected' : '' }}>無</option>
                </select>
                <x-form.field-error field="pas_info.availability" />
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="pas_update_date" class="form-label">更新年月日</label>
                <input type="date" class="form-control @error('pas_info.update_date') is-invalid @enderror" 
                       id="pas_update_date" name="pas_info[update_date]" 
                       value="{{ old('pas_info.update_date', $pasInfo['update_date'] ?? '') }}">
                <x-form.field-error field="pas_info.update_date" />
            </div>
        </div>
    </x-form.section>

    <!-- キュービクルセクション -->
    <x-form.section title="キュービクル" icon="fas fa-cube" icon-color="info">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="cubicle_availability" class="form-label">有無</label>
                <select class="form-select @error('cubicle_info.availability') is-invalid @enderror" 
                        id="cubicle_availability" name="cubicle_info[availability]"
                        onchange="toggleCubicleEquipment(this.value)">
                    <option value="">選択してください</option>
                    <option value="有" {{ old('cubicle_info.availability', $cubicleInfo['availability'] ?? '') === '有' ? 'selected' : '' }}>有</option>
                    <option value="無" {{ old('cubicle_info.availability', $cubicleInfo['availability'] ?? '') === '無' ? 'selected' : '' }}>無</option>
                </select>
                <x-form.field-error field="cubicle_info.availability" />
            </div>
        </div>
        
        <!-- キュービクル設備リスト -->
        <div id="cubicle-equipment-section" style="display: {{ old('cubicle_info.availability', $cubicleInfo['availability'] ?? '') === '有' ? 'block' : 'none' }};">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">設備一覧</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCubicleEquipment()">
                    <i class="fas fa-plus me-1"></i>設備追加
                </button>
            </div>
            
            <div id="cubicle-equipment-list">
                @if(!empty($cubicleEquipmentList))
                    @foreach($cubicleEquipmentList as $index => $equipment)
                        <div class="equipment-item border rounded p-3 mb-3" data-index="{{ $index }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">設備 {{ $index + 1 }}</h6>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCubicleEquipment(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">メーカー</label>
                                    <input type="text" class="form-control" 
                                           name="cubicle_info[equipment_list][{{ $index }}][manufacturer]" 
                                           value="{{ old('cubicle_info.equipment_list.' . $index . '.manufacturer', $equipment['manufacturer'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">年式</label>
                                    <input type="text" class="form-control" 
                                           name="cubicle_info[equipment_list][{{ $index }}][model_year]" 
                                           value="{{ old('cubicle_info.equipment_list.' . $index . '.model_year', $equipment['model_year'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">更新年月日</label>
                                    <input type="date" class="form-control" 
                                           name="cubicle_info[equipment_list][{{ $index }}][update_date]" 
                                           value="{{ old('cubicle_info.equipment_list.' . $index . '.update_date', $equipment['update_date'] ?? '') }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </x-form.section>    <!-- 非常用発電機セクション -->
    <x-form.section title="非常用発電機" icon="fas fa-bolt" icon-color="success">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="generator_availability" class="form-label">有無</label>
                <select class="form-select @error('generator_info.availability') is-invalid @enderror" 
                        id="generator_availability" name="generator_info[availability]"
                        onchange="toggleGeneratorEquipment(this.value)">
                    <option value="">選択してください</option>
                    <option value="有" {{ old('generator_info.availability', $generatorInfo['availability'] ?? '') === '有' ? 'selected' : '' }}>有</option>
                    <option value="無" {{ old('generator_info.availability', $generatorInfo['availability'] ?? '') === '無' ? 'selected' : '' }}>無</option>
                </select>
                <x-form.field-error field="generator_info.availability" />
            </div>
        </div>
        
        <!-- ジェネレーター設備リスト -->
        <div id="generator-equipment-section" style="display: {{ old('generator_info.availability', $generatorInfo['availability'] ?? '') === '有' ? 'block' : 'none' }};">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">設備一覧</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addGeneratorEquipment()">
                    <i class="fas fa-plus me-1"></i>設備追加
                </button>
            </div>
            
            <div id="generator-equipment-list">
                @if(!empty($generatorEquipmentList))
                    @foreach($generatorEquipmentList as $index => $equipment)
                        <div class="equipment-item border rounded p-3 mb-3" data-index="{{ $index }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">設備 {{ $index + 1 }}</h6>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeGeneratorEquipment(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">メーカー</label>
                                    <input type="text" class="form-control" 
                                           name="generator_info[equipment_list][{{ $index }}][manufacturer]" 
                                           value="{{ old('generator_info.equipment_list.' . $index . '.manufacturer', $equipment['manufacturer'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">年式</label>
                                    <input type="text" class="form-control" 
                                           name="generator_info[equipment_list][{{ $index }}][model_year]" 
                                           value="{{ old('generator_info.equipment_list.' . $index . '.model_year', $equipment['model_year'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">更新年月日</label>
                                    <input type="date" class="form-control" 
                                           name="generator_info[equipment_list][{{ $index }}][update_date]" 
                                           value="{{ old('generator_info.equipment_list.' . $index . '.update_date', $equipment['update_date'] ?? '') }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </x-form.section>

    <!-- 備考セクション -->
    <x-form.section title="備考" icon="fas fa-sticky-note" icon-color="secondary">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="notes" class="form-label">備考</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" 
                          id="notes" name="notes" rows="4"
                          placeholder="電気設備に関する備考を入力してください">{{ old('notes', $electricalEquipment->notes ?? '') }}</textarea>
                <x-form.field-error field="notes" />
            </div>
        </div>
    </x-form.section>
</x-facility.edit-layout>

<script>
// キュービクル設備の表示/非表示切り替え
function toggleCubicleEquipment(value) {
    const section = document.getElementById('cubicle-equipment-section');
    if (value === '有') {
        section.style.display = 'block';
        // 設備が1つもない場合は1つ追加
        const list = document.getElementById('cubicle-equipment-list');
        if (list.children.length === 0) {
            addCubicleEquipment();
        }
    } else {
        section.style.display = 'none';
    }
}

// ジェネレーター設備の表示/非表示切り替え
function toggleGeneratorEquipment(value) {
    const section = document.getElementById('generator-equipment-section');
    if (value === '有') {
        section.style.display = 'block';
        // 設備が1つもない場合は1つ追加
        const list = document.getElementById('generator-equipment-list');
        if (list.children.length === 0) {
            addGeneratorEquipment();
        }
    } else {
        section.style.display = 'none';
    }
}

// キュービクル設備追加
function addCubicleEquipment() {
    const list = document.getElementById('cubicle-equipment-list');
    const index = list.children.length;
    
    const equipmentHtml = `
        <div class="equipment-item border rounded p-3 mb-3" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">設備 ${index + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCubicleEquipment(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">メーカー</label>
                    <input type="text" class="form-control" name="cubicle_info[equipment_list][${index}][manufacturer]">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">年式</label>
                    <input type="text" class="form-control" name="cubicle_info[equipment_list][${index}][model_year]">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">更新年月日</label>
                    <input type="date" class="form-control" name="cubicle_info[equipment_list][${index}][update_date]">
                </div>
            </div>
        </div>
    `;
    
    list.insertAdjacentHTML('beforeend', equipmentHtml);
    updateCubicleEquipmentNumbers();
}

// ジェネレーター設備追加
function addGeneratorEquipment() {
    const list = document.getElementById('generator-equipment-list');
    const index = list.children.length;
    
    const equipmentHtml = `
        <div class="equipment-item border rounded p-3 mb-3" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">設備 ${index + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeGeneratorEquipment(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">メーカー</label>
                    <input type="text" class="form-control" name="generator_info[equipment_list][${index}][manufacturer]">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">年式</label>
                    <input type="text" class="form-control" name="generator_info[equipment_list][${index}][model_year]">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">更新年月日</label>
                    <input type="date" class="form-control" name="generator_info[equipment_list][${index}][update_date]">
                </div>
            </div>
        </div>
    `;
    
    list.insertAdjacentHTML('beforeend', equipmentHtml);
    updateGeneratorEquipmentNumbers();
}

// キュービクル設備削除
function removeCubicleEquipment(button) {
    const equipmentItem = button.closest('.equipment-item');
    equipmentItem.remove();
    updateCubicleEquipmentNumbers();
}

// ジェネレーター設備削除
function removeGeneratorEquipment(button) {
    const equipmentItem = button.closest('.equipment-item');
    equipmentItem.remove();
    updateGeneratorEquipmentNumbers();
}

// キュービクル設備番号更新
function updateCubicleEquipmentNumbers() {
    const list = document.getElementById('cubicle-equipment-list');
    const items = list.querySelectorAll('.equipment-item');
    
    items.forEach((item, index) => {
        item.dataset.index = index;
        const title = item.querySelector('h6');
        title.textContent = `設備 ${index + 1}`;
        
        // input要素のname属性を更新
        const inputs = item.querySelectorAll('input');
        inputs.forEach(input => {
            const name = input.name;
            const fieldName = name.split('[').pop().replace(']', '');
            input.name = `cubicle_info[equipment_list][${index}][${fieldName}]`;
        });
    });
}

// ジェネレーター設備番号更新
function updateGeneratorEquipmentNumbers() {
    const list = document.getElementById('generator-equipment-list');
    const items = list.querySelectorAll('.equipment-item');
    
    items.forEach((item, index) => {
        item.dataset.index = index;
        const title = item.querySelector('h6');
        title.textContent = `設備 ${index + 1}`;
        
        // input要素のname属性を更新
        const inputs = item.querySelectorAll('input');
        inputs.forEach(input => {
            const name = input.name;
            const fieldName = name.split('[').pop().replace(']', '');
            input.name = `generator_info[equipment_list][${index}][${fieldName}]`;
        });
    });
}

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    // 初期状態での表示制御
    const cubicleAvailability = document.getElementById('cubicle_availability').value;
    toggleCubicleEquipment(cubicleAvailability);
    
    const generatorAvailability = document.getElementById('generator_availability').value;
    toggleGeneratorEquipment(generatorAvailability);
});
</script>