@extends('layouts.app')

@section('title', 'ガス設備編集 - ' . $facility->facility_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-fire me-2"></i>
                        ガス設備編集 - {{ $facility->facility_name }}
                    </h5>
                    <div>
                        <a href="{{ route('facilities.show', $facility) }}#gas" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>戻る
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('facilities.lifeline-equipment.update', [$facility, 'gas']) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @php
                            $gasEquipment = $facility->getGasEquipment();
                            $basicInfo = $gasEquipment?->basic_info ?? [];
                        @endphp

                        <!-- 基本情報セクション -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="section-title border-bottom pb-2 mb-3">
                                    <i class="fas fa-info-circle me-2"></i>基本情報
                                </h6>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="gas_supplier" class="form-label">ガス契約会社</label>
                                <input type="text" 
                                       class="form-control @error('basic_info.gas_supplier') is-invalid @enderror" 
                                       id="gas_supplier" 
                                       name="basic_info[gas_supplier]" 
                                       value="{{ old('basic_info.gas_supplier', $basicInfo['gas_supplier'] ?? '') }}"
                                       placeholder="例：東京ガス株式会社">
                                @error('basic_info.gas_supplier')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="gas_type" class="form-label">ガス種類</label>
                                <div class="input-group">
                                    <select class="form-select @error('basic_info.gas_type') is-invalid @enderror" 
                                            id="gas_type_select" 
                                            onchange="handleGasTypeChange(this)">
                                        <option value="">選択してください</option>
                                        <option value="プロパンガス" {{ old('basic_info.gas_type', $basicInfo['gas_type'] ?? '') === 'プロパンガス' ? 'selected' : '' }}>プロパンガス</option>
                                        <option value="都市ガス" {{ old('basic_info.gas_type', $basicInfo['gas_type'] ?? '') === '都市ガス' ? 'selected' : '' }}>都市ガス</option>
                                        <option value="その他">その他</option>
                                    </select>
                                    <input type="text" 
                                           class="form-control @error('basic_info.gas_type') is-invalid @enderror" 
                                           id="gas_type_custom" 
                                           value="{{ old('basic_info.gas_type', $basicInfo['gas_type'] ?? '') }}"
                                           placeholder="その他のガス種類を入力してください"
                                           style="display: none;">
                                    <button type="button" 
                                            class="btn btn-outline-secondary" 
                                            id="gas_type_cancel" 
                                            onclick="cancelCustomGasType()"
                                            style="display: none;"
                                            title="プルダウンメニューに戻る">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <input type="hidden" 
                                           id="gas_type_final" 
                                           name="basic_info[gas_type]" 
                                           value="{{ old('basic_info.gas_type', $basicInfo['gas_type'] ?? '') }}">
                                </div>
                                @error('basic_info.gas_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @php
                            $waterHeaterInfo = $basicInfo['water_heater_info'] ?? [];
                            $waterHeaters = $waterHeaterInfo['water_heaters'] ?? [];
                        @endphp

                        <!-- 給湯器セクション -->
                        <div class="row mb-4 mt-5">
                            <div class="col-12">
                                <h6 class="section-title border-bottom pb-2 mb-3">
                                    <i class="fas fa-fire-flame-curved me-2"></i>給湯器
                                </h6>
                            </div>
                        </div>

                        <!-- 給湯器設置の有無 -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="water_heater_availability" class="form-label">設置の有無</label>
                                <select class="form-select @error('basic_info.water_heater_info.availability') is-invalid @enderror" 
                                        id="water_heater_availability" 
                                        name="basic_info[water_heater_info][availability]"
                                        onchange="toggleWaterHeaterSection(this.value)">
                                    <option value="">選択してください</option>
                                    <option value="有" {{ old('basic_info.water_heater_info.availability', $waterHeaterInfo['availability'] ?? '') === '有' ? 'selected' : '' }}>有</option>
                                    <option value="無" {{ old('basic_info.water_heater_info.availability', $waterHeaterInfo['availability'] ?? '') === '無' ? 'selected' : '' }}>無</option>
                                </select>
                                @error('basic_info.water_heater_info.availability')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 給湯器設備一覧（設置の有無が「有」の場合のみ表示） -->
                        <div id="water-heater-equipment-section" style="display: {{ old('basic_info.water_heater_info.availability', $waterHeaterInfo['availability'] ?? '') === '有' ? 'block' : 'none' }}">
                            <div class="equipment-section-header d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">給湯器設備一覧</h6>
                                <button type="button" class="btn btn-outline-primary btn-sm add-water-heater-btn">
                                    <i class="fas fa-plus"></i> 設備追加
                                </button>
                            </div>
                            
                            <div id="water-heater-equipment-list" class="equipment-list">
                                @if(empty($waterHeaters))
                                    <div class="no-equipment-message">
                                        給湯器が登録されていません。「設備追加」ボタンで追加してください。
                                    </div>
                                @else
                                    @foreach($waterHeaters as $index => $heater)
                                        <div class="equipment-item water-heater-equipment-item mb-3" data-index="{{ $index }}">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">
                                                    <span class="equipment-number badge bg-primary me-2">{{ $index + 1 }}</span>
                                                    給湯器 {{ $index + 1 }}
                                                </h6>
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-water-heater-btn">
                                                    <i class="fas fa-trash"></i> 削除
                                                </button>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">メーカー</label>
                                                    <input type="text" class="form-control" 
                                                           name="basic_info[water_heater_info][water_heaters][{{ $index }}][manufacturer]" 
                                                           value="{{ old('basic_info.water_heater_info.water_heaters.' . $index . '.manufacturer', $heater['manufacturer'] ?? '') }}"
                                                           placeholder="例：リンナイ">
                                                </div>
                                                
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">年式</label>
                                                    <input type="number" class="form-control" 
                                                           name="basic_info[water_heater_info][water_heaters][{{ $index }}][model_year]" 
                                                           value="{{ old('basic_info.water_heater_info.water_heaters.' . $index . '.model_year', $heater['model_year'] ?? '') }}"
                                                           placeholder="例：2020"
                                                           min="1900" max="{{ date('Y') + 1 }}">
                                                </div>
                                                
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">更新年月日</label>
                                                    <input type="date" class="form-control" 
                                                           name="basic_info[water_heater_info][water_heaters][{{ $index }}][update_date]" 
                                                           value="{{ old('basic_info.water_heater_info.water_heaters.' . $index . '.update_date', $heater['update_date'] ?? '') }}">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <!-- 備考欄セクション -->
                        <div class="row mb-4 mt-5">
                            <div class="col-12">
                                <h6 class="section-title border-bottom pb-2 mb-3">
                                    <i class="fas fa-sticky-note me-2"></i>備考欄
                                </h6>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="notes" class="form-label">備考欄</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" 
                                          name="notes" 
                                          rows="4"
                                          placeholder="ガス設備に関する追加情報や特記事項があれば記入してください">{{ old('notes', $gasEquipment?->notes ?? '') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 保存ボタン -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('facilities.show', $facility) }}#gas" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>キャンセル
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>保存
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.section-title {
    color: #495057;
    font-weight: 600;
}

.form-label {
    font-weight: 500;
    color: #495057;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* 給湯器設備スタイル */
.equipment-section-header {
    background-color: #f8f9fa;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
}

.equipment-list {
    margin-top: 1rem;
}

.equipment-item {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    background-color: #fff;
    position: relative;
}

.equipment-number {
    font-size: 0.75rem;
    font-weight: 600;
}

.no-equipment-message {
    text-align: center;
    color: #6c757d;
    font-style: italic;
    padding: 2rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border: 1px dashed #dee2e6;
}

.water-heater-equipment-item {
    border-left: 4px solid #fd7e14;
}

.water-heater-equipment-item .equipment-number {
    background-color: #fd7e14 !important;
}
</style>
@endpush

@push('scripts')
<script>
function handleGasTypeChange(selectElement) {
    const customInput = document.getElementById('gas_type_custom');
    const cancelButton = document.getElementById('gas_type_cancel');
    const finalInput = document.getElementById('gas_type_final');
    const selectedValue = selectElement.value;
    
    if (selectedValue === 'その他') {
        // カスタム入力フィールドを表示
        selectElement.style.display = 'none';
        customInput.style.display = 'block';
        cancelButton.style.display = 'block';
        customInput.focus();
        finalInput.value = customInput.value;
        
        // カスタム入力フィールドの値が変更された際に最終値を更新
        customInput.addEventListener('input', function() {
            finalInput.value = this.value;
        });
    } else {
        // プルダウンの値を使用
        finalInput.value = selectedValue;
    }
}

// カスタム入力をキャンセルしてプルダウンメニューに戻る
function cancelCustomGasType() {
    const selectElement = document.getElementById('gas_type_select');
    const customInput = document.getElementById('gas_type_custom');
    const cancelButton = document.getElementById('gas_type_cancel');
    const finalInput = document.getElementById('gas_type_final');
    
    // プルダウンメニューに戻す
    selectElement.style.display = 'block';
    customInput.style.display = 'none';
    cancelButton.style.display = 'none';
    
    // 値をリセット
    selectElement.value = '';
    customInput.value = '';
    finalInput.value = '';
    
    // プルダウンメニューにフォーカス
    selectElement.focus();
}

// Escapeキーでカスタム入力をキャンセル
function handleKeyPress(event) {
    if (event.key === 'Escape') {
        const customInput = document.getElementById('gas_type_custom');
        if (customInput.style.display === 'block') {
            cancelCustomGasType();
        }
    }
}

// 給湯器設置の有無による表示切り替え
function toggleWaterHeaterSection(availability) {
    const section = document.getElementById('water-heater-equipment-section');
    if (availability === '有') {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }
}

// 給湯器設備の動的追加・削除機能
function initializeWaterHeaterEquipment() {
    let waterHeaterIndex = {{ count($waterHeaters) }};
    const waterHeaterList = document.getElementById('water-heater-equipment-list');
    const addWaterHeaterBtn = document.querySelector('.add-water-heater-btn');
    const noEquipmentMessage = document.querySelector('.no-equipment-message');
    
    // 設備追加ボタンのイベント
    if (addWaterHeaterBtn) {
        addWaterHeaterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addWaterHeaterEquipment();
        });
    }
    
    // 設備追加関数
    function addWaterHeaterEquipment() {
        // メッセージを非表示
        if (noEquipmentMessage) {
            noEquipmentMessage.style.display = 'none';
        }
        
        const equipmentHtml = `
            <div class="equipment-item water-heater-equipment-item mb-3" data-index="${waterHeaterIndex}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <span class="equipment-number badge bg-primary me-2">${waterHeaterIndex + 1}</span>
                        給湯器 ${waterHeaterIndex + 1}
                    </h6>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-water-heater-btn">
                        <i class="fas fa-trash"></i> 削除
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">メーカー</label>
                        <input type="text" class="form-control" 
                               name="basic_info[water_heater_info][water_heaters][${waterHeaterIndex}][manufacturer]" 
                               placeholder="例：リンナイ">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">年式</label>
                        <input type="number" class="form-control" 
                               name="basic_info[water_heater_info][water_heaters][${waterHeaterIndex}][model_year]" 
                               placeholder="例：2020"
                               min="1900" max="${new Date().getFullYear() + 1}">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">更新年月日</label>
                        <input type="date" class="form-control" 
                               name="basic_info[water_heater_info][water_heaters][${waterHeaterIndex}][update_date]">
                    </div>
                </div>
            </div>
        `;
        
        waterHeaterList.insertAdjacentHTML('beforeend', equipmentHtml);
        waterHeaterIndex++;
        
        // 新しく追加された削除ボタンにイベントリスナーを追加
        const newItem = waterHeaterList.lastElementChild;
        const removeBtn = newItem.querySelector('.remove-water-heater-btn');
        removeBtn.addEventListener('click', function() {
            removeWaterHeaterEquipment(newItem);
        });
        
        updateWaterHeaterNumbers();
    }
    
    // 設備削除関数
    function removeWaterHeaterEquipment(equipmentItem) {
        equipmentItem.remove();
        updateWaterHeaterNumbers();
        
        // 設備がなくなった場合、メッセージを表示
        const remainingItems = waterHeaterList.querySelectorAll('.water-heater-equipment-item');
        if (remainingItems.length === 0 && noEquipmentMessage) {
            noEquipmentMessage.style.display = 'block';
        }
    }
    
    // 設備番号の更新
    function updateWaterHeaterNumbers() {
        const items = waterHeaterList.querySelectorAll('.water-heater-equipment-item');
        items.forEach((item, index) => {
            const numberBadge = item.querySelector('.equipment-number');
            const title = item.querySelector('h6');
            if (numberBadge) numberBadge.textContent = index + 1;
            if (title) {
                const titleText = title.childNodes[title.childNodes.length - 1];
                if (titleText) titleText.textContent = ` 給湯器 ${index + 1}`;
            }
        });
    }
    
    // 既存の削除ボタンにイベントリスナーを追加
    document.querySelectorAll('.remove-water-heater-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const equipmentItem = this.closest('.water-heater-equipment-item');
            removeWaterHeaterEquipment(equipmentItem);
        });
    });
}

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    const selectElement = document.getElementById('gas_type_select');
    const customInput = document.getElementById('gas_type_custom');
    const cancelButton = document.getElementById('gas_type_cancel');
    const finalInput = document.getElementById('gas_type_final');
    const currentValue = finalInput.value;
    
    // Escapeキーのイベントリスナーを追加
    document.addEventListener('keydown', handleKeyPress);
    
    // カスタム入力フィールドのイベントリスナーを追加
    customInput.addEventListener('input', function() {
        finalInput.value = this.value;
    });
    
    // 現在の値がプルダウンメニューの選択肢にない場合、カスタム入力フィールドを表示
    if (currentValue && !['プロパンガス', '都市ガス'].includes(currentValue)) {
        selectElement.value = 'その他';
        selectElement.style.display = 'none';
        customInput.style.display = 'block';
        cancelButton.style.display = 'block';
        customInput.value = currentValue;
    } else {
        selectElement.value = currentValue;
    }
    
    // 給湯器設備の初期化
    initializeWaterHeaterEquipment();
});
</script>
@endpush