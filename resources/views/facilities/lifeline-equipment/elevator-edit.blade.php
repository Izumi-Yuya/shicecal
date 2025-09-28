@extends('layouts.app')

@section('title', 'エレベーター設備編集 - ' . $facility->facility_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">エレベーター設備編集</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}">施設一覧</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('facilities.show', $facility) }}">{{ $facility->facility_name }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">エレベーター設備編集</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>戻る
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('facilities.lifeline-equipment.update', [$facility, 'elevator']) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @php
                    $elevatorEquipment = $facility->getElevatorEquipment();
                    $basicInfo = $elevatorEquipment?->basic_info ?? [];
                    $elevators = $basicInfo['elevators'] ?? [];
                @endphp

                {{-- 基本情報 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>基本情報
                        </h5>
                    </div>
                    <div class="card-body">

                        <!-- エレベーター設置の有無 -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="elevator_availability" class="form-label">エレベーター設置の有無</label>
                                <select class="form-select @error('basic_info.availability') is-invalid @enderror" 
                                        id="elevator_availability" 
                                        name="basic_info[availability]"
                                        onchange="toggleElevatorSection(this.value)">
                                    <option value="">選択してください</option>
                                    <option value="有" {{ old('basic_info.availability', $basicInfo['availability'] ?? '') === '有' ? 'selected' : '' }}>有</option>
                                    <option value="無" {{ old('basic_info.availability', $basicInfo['availability'] ?? '') === '無' ? 'selected' : '' }}>無</option>
                                </select>
                                @error('basic_info.availability')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- エレベーター設備一覧（設置の有無が「有」の場合のみ表示） -->
                        <div id="elevator-equipment-section" style="display: {{ old('basic_info.availability', $basicInfo['availability'] ?? '') === '有' ? 'block' : 'none' }}">
                            <div class="equipment-section-header d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">エレベーター設備一覧</h6>
                                <button type="button" class="btn btn-outline-primary btn-sm add-elevator-btn">
                                    <i class="fas fa-plus"></i> エレベーター追加
                                </button>
                            </div>
                            
                            <div id="elevator-equipment-list" class="equipment-list">
                                @if(empty($elevators))
                                    <div class="no-equipment-message">
                                        エレベーターが登録されていません。「エレベーター追加」ボタンをクリックして追加してください。
                                    </div>
                                @else
                                    @foreach($elevators as $index => $elevator)
                                        <div class="equipment-item elevator-equipment-item mb-3" data-index="{{ $index }}">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">
                                                    <span class="equipment-number badge bg-primary me-2">{{ $index + 1 }}</span>
                                                    エレベーター {{ $index + 1 }}
                                                </h6>
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-elevator-btn">
                                                    <i class="fas fa-trash"></i> 削除
                                                </button>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">メーカー</label>
                                                    <input type="text" class="form-control" 
                                                           name="basic_info[elevators][{{ $index }}][manufacturer]" 
                                                           value="{{ old('basic_info.elevators.' . $index . '.manufacturer', $elevator['manufacturer'] ?? '') }}"
                                                           placeholder="例：三菱電機">
                                                </div>
                                                
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">種類</label>
                                                    <div class="input-group">
                                                        <select class="form-select elevator-type-select" 
                                                                onchange="handleElevatorTypeChange(this, {{ $index }})">
                                                            <option value="">選択してください</option>
                                                            <option value="ロープ式" {{ old('basic_info.elevators.' . $index . '.type', $elevator['type'] ?? '') === 'ロープ式' ? 'selected' : '' }}>ロープ式</option>
                                                            <option value="油圧式" {{ old('basic_info.elevators.' . $index . '.type', $elevator['type'] ?? '') === '油圧式' ? 'selected' : '' }}>油圧式</option>
                                                            <option value="その他">その他</option>
                                                        </select>
                                                        <input type="text" 
                                                               class="form-control elevator-type-custom" 
                                                               value="{{ old('basic_info.elevators.' . $index . '.type', $elevator['type'] ?? '') }}"
                                                               placeholder="エレベーターの種類を入力してください"
                                                               style="display: none;">
                                                        <button type="button" 
                                                                class="btn btn-outline-secondary elevator-type-cancel" 
                                                                onclick="cancelCustomElevatorType({{ $index }})"
                                                                style="display: none;"
                                                                title="プルダウンメニューに戻る">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <input type="hidden" 
                                                               class="elevator-type-final" 
                                                               name="basic_info[elevators][{{ $index }}][type]" 
                                                               value="{{ old('basic_info.elevators.' . $index . '.type', $elevator['type'] ?? '') }}">
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">年式</label>
                                                    <input type="number" class="form-control" 
                                                           name="basic_info[elevators][{{ $index }}][model_year]" 
                                                           value="{{ old('basic_info.elevators.' . $index . '.model_year', $elevator['model_year'] ?? '') }}"
                                                           placeholder="例：2020年"
                                                           min="1900" max="{{ date('Y') + 1 }}">
                                                </div>
                                                
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">更新年月日</label>
                                                    <input type="date" class="form-control" 
                                                           name="basic_info[elevators][{{ $index }}][update_date]" 
                                                           value="{{ old('basic_info.elevators.' . $index . '.update_date', $elevator['update_date'] ?? '') }}">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

                {{-- 点検情報 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>点検情報
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $inspectionInfo = $basicInfo['inspection'] ?? [];
                        @endphp

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="maintenance_contractor" class="form-label">保守業者</label>
                                    <input type="text" class="form-control @error('basic_info.inspection.maintenance_contractor') is-invalid @enderror" 
                                           id="maintenance_contractor" 
                                           name="basic_info[inspection][maintenance_contractor]" 
                                           value="{{ old('basic_info.inspection.maintenance_contractor', $inspectionInfo['maintenance_contractor'] ?? '') }}"
                                           placeholder="例：○○保守株式会社">
                                    @error('basic_info.inspection.maintenance_contractor')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="inspection_date" class="form-label">保守点検実施日</label>
                                    <input type="date" class="form-control @error('basic_info.inspection.inspection_date') is-invalid @enderror" 
                                           id="inspection_date" 
                                           name="basic_info[inspection][inspection_date]" 
                                           value="{{ old('basic_info.inspection.inspection_date', $inspectionInfo['inspection_date'] ?? '') }}">
                                    @error('basic_info.inspection.inspection_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    @php
                                        $currentFileData = null;
                                        if (isset($inspectionInfo['inspection_report_path']) && !empty($inspectionInfo['inspection_report_path'])) {
                                            $fileHandlingService = app(\App\Services\FileHandlingService::class);
                                            $fileData = [
                                                'filename' => $inspectionInfo['inspection_report_filename'] ?? 'ファイルあり',
                                                'path' => $inspectionInfo['inspection_report_path']
                                            ];
                                            $currentFileData = $fileHandlingService->generateFileDisplayData($fileData, 'elevator', $facility);
                                            

                                        }
                                    @endphp
                                    
                                    <x-file-upload 
                                        name="inspection_report_file"
                                        label="保守点検報告書"
                                        fileType="pdf"
                                        :currentFile="$currentFileData"
                                        :required="false"
                                        :showRemoveOption="false"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 備考 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note me-2"></i>備考
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="notes" class="form-label">備考欄</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="5"
                                      placeholder="エレベーター設備に関する追加情報や特記事項があれば記入してください。">{{ old('notes', $elevatorEquipment?->notes ?? '') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- 保存ボタン --}}
                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
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

/* エレベーター設備スタイル */
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

.elevator-equipment-item {
    border-left: 4px solid #6f42c1;
}

.elevator-equipment-item .equipment-number {
    background-color: #6f42c1 !important;
}
</style>
@endpush

@push('scripts')
<script>
function handleElevatorTypeChange(selectElement, index) {
    const customInput = selectElement.parentElement.querySelector('.elevator-type-custom');
    const cancelButton = selectElement.parentElement.querySelector('.elevator-type-cancel');
    const finalInput = selectElement.parentElement.querySelector('.elevator-type-final');
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
function cancelCustomElevatorType(index) {
    const equipmentItem = document.querySelector(`[data-index="${index}"]`);
    const selectElement = equipmentItem.querySelector('.elevator-type-select');
    const customInput = equipmentItem.querySelector('.elevator-type-custom');
    const cancelButton = equipmentItem.querySelector('.elevator-type-cancel');
    const finalInput = equipmentItem.querySelector('.elevator-type-final');
    
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

// エレベーター設置の有無による表示切り替え
function toggleElevatorSection(availability) {
    const section = document.getElementById('elevator-equipment-section');
    if (availability === '有') {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }
}

// エレベーター設備の動的追加・削除機能
function initializeElevatorEquipment() {
    let elevatorIndex = {{ count($elevators) }};
    const elevatorList = document.getElementById('elevator-equipment-list');
    const addElevatorBtn = document.querySelector('.add-elevator-btn');
    const noEquipmentMessage = document.querySelector('.no-equipment-message');
    
    // エレベーター追加ボタンのイベントリスナー設定
    if (addElevatorBtn) {
        addElevatorBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addElevatorEquipment();
        });
    }
    
    // エレベーター追加関数
    function addElevatorEquipment() {
        // 「設備なし」メッセージを非表示にする
        if (noEquipmentMessage) {
            noEquipmentMessage.style.display = 'none';
        }
        
        const equipmentHtml = `
            <div class="equipment-item elevator-equipment-item mb-3" data-index="${elevatorIndex}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <span class="equipment-number badge bg-primary me-2">${elevatorIndex + 1}</span>
                        エレベーター ${elevatorIndex + 1}
                    </h6>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-elevator-btn">
                        <i class="fas fa-trash"></i> 削除
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">メーカー</label>
                        <input type="text" class="form-control" 
                               name="basic_info[elevators][${elevatorIndex}][manufacturer]" 
                               placeholder="例：三菱電機">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">種類</label>
                        <div class="input-group">
                            <select class="form-select elevator-type-select" 
                                    onchange="handleElevatorTypeChange(this, ${elevatorIndex})">
                                <option value="">選択してください</option>
                                <option value="ロープ式">ロープ式</option>
                                <option value="油圧式">油圧式</option>
                                <option value="その他">その他</option>
                            </select>
                            <input type="text" 
                                   class="form-control elevator-type-custom" 
                                   placeholder="エレベーターの種類を入力してください"
                                   style="display: none;">
                            <button type="button" 
                                    class="btn btn-outline-secondary elevator-type-cancel" 
                                    onclick="cancelCustomElevatorType(${elevatorIndex})"
                                    style="display: none;"
                                    title="プルダウンメニューに戻る">
                                <i class="fas fa-times"></i>
                            </button>
                            <input type="hidden" 
                                   class="elevator-type-final" 
                                   name="basic_info[elevators][${elevatorIndex}][type]">
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">年式</label>
                        <input type="number" class="form-control" 
                               name="basic_info[elevators][${elevatorIndex}][model_year]" 
                               placeholder="例：2020年"
                               min="1900" max="${new Date().getFullYear() + 1}">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">更新年月日</label>
                        <input type="date" class="form-control" 
                               name="basic_info[elevators][${elevatorIndex}][update_date]">
                    </div>
                </div>
            </div>
        `;
        
        elevatorList.insertAdjacentHTML('beforeend', equipmentHtml);
        elevatorIndex++;
        
        // 新しく追加された削除ボタンにイベントリスナーを追加
        const newItem = elevatorList.lastElementChild;
        const removeBtn = newItem.querySelector('.remove-elevator-btn');
        removeBtn.addEventListener('click', function() {
            removeElevatorEquipment(newItem);
        });
        
        updateElevatorNumbers();
    }
    
    // 設備削除関数
    function removeElevatorEquipment(equipmentItem) {
        equipmentItem.remove();
        updateElevatorNumbers();
        
        // 設備がなくなった場合、メッセージを表示
        const remainingItems = elevatorList.querySelectorAll('.elevator-equipment-item');
        if (remainingItems.length === 0 && noEquipmentMessage) {
            noEquipmentMessage.style.display = 'block';
        }
    }
    
    // 設備番号の更新
    function updateElevatorNumbers() {
        const items = elevatorList.querySelectorAll('.elevator-equipment-item');
        items.forEach((item, index) => {
            const numberBadge = item.querySelector('.equipment-number');
            const title = item.querySelector('h6');
            if (numberBadge) numberBadge.textContent = index + 1;
            if (title) {
                const titleText = title.childNodes[title.childNodes.length - 1];
                if (titleText) titleText.textContent = ` エレベーター ${index + 1}`;
            }
        });
    }
    
    // 既存の削除ボタンにイベントリスナーを追加
    document.querySelectorAll('.remove-elevator-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const equipmentItem = this.closest('.elevator-equipment-item');
            removeElevatorEquipment(equipmentItem);
        });
    });
}

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
    // 既存のエレベーター種類フィールドの初期化
    document.querySelectorAll('.elevator-equipment-item').forEach((item, index) => {
        const selectElement = item.querySelector('.elevator-type-select');
        const customInput = item.querySelector('.elevator-type-custom');
        const cancelButton = item.querySelector('.elevator-type-cancel');
        const finalInput = item.querySelector('.elevator-type-final');
        const currentValue = finalInput.value;
        
        // カスタム入力フィールドのイベントリスナーを追加
        customInput.addEventListener('input', function() {
            finalInput.value = this.value;
        });
        
        // 現在の値がプルダウンメニューの選択肢にない場合、カスタム入力フィールドを表示
        if (currentValue && !['ロープ式', '油圧式'].includes(currentValue)) {
            selectElement.value = 'その他';
            selectElement.style.display = 'none';
            customInput.style.display = 'block';
            cancelButton.style.display = 'block';
            customInput.value = currentValue;
        } else {
            selectElement.value = currentValue;
        }
    });
    
    // エレベーター設備の初期化
    initializeElevatorEquipment();
});
</script>
@endpush