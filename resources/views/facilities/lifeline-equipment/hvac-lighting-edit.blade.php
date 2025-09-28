@extends('layouts.app')

@section('title', '空調・照明設備編集 - ' . $facility->facility_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">空調・照明設備編集</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}">施設一覧</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('facilities.show', $facility) }}">{{ $facility->facility_name }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">空調・照明設備編集</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>戻る
                    </a>
                </div>
            </div>

            {{-- Success/Error Messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('facilities.lifeline-equipment.update', [$facility, 'hvac-lighting']) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @php
                    $hvacLightingEquipment = null;
                    $basicInfo = [];
                    $hvacInfo = [];
                    $lightingInfo = [];
                    
                    try {
                        if (method_exists($facility, 'getHvacLightingEquipment')) {
                            $hvacLightingEquipment = $facility->getHvacLightingEquipment();
                            if ($hvacLightingEquipment && isset($hvacLightingEquipment->basic_info)) {
                                $basicInfo = is_array($hvacLightingEquipment->basic_info) ? $hvacLightingEquipment->basic_info : [];
                                $hvacInfo = isset($basicInfo['hvac']) && is_array($basicInfo['hvac']) ? $basicInfo['hvac'] : [];
                                $lightingInfo = isset($basicInfo['lighting']) && is_array($basicInfo['lighting']) ? $basicInfo['lighting'] : [];
                            }
                        }
                    } catch (Exception $e) {
                        // エラーが発生した場合は空の配列を使用
                    }
                @endphp

                <div class="row">
                    {{-- 左側：空調設備 --}}
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-snowflake me-2"></i>空調設備
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="freon_inspector" class="form-label">フロン点検業者</label>
                                    <input type="text" class="form-control @error('basic_info.hvac.freon_inspector') is-invalid @enderror" 
                                           id="freon_inspector" 
                                           name="basic_info[hvac][freon_inspector]" 
                                           value="{{ old('basic_info.hvac.freon_inspector', $hvacInfo['freon_inspector'] ?? '') }}"
                                           placeholder="例：東京空調サービス株式会社">
                                    @error('basic_info.hvac.freon_inspector')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="hvac_inspection_date" class="form-label">点検実施日</label>
                                    <input type="date" class="form-control @error('basic_info.hvac.inspection_date') is-invalid @enderror" 
                                           id="hvac_inspection_date" 
                                           name="basic_info[hvac][inspection_date]" 
                                           value="{{ old('basic_info.hvac.inspection_date', $hvacInfo['inspection_date'] ?? '') }}">
                                    @error('basic_info.hvac.inspection_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    @php
                                        $currentFileData = null;
                                        if (isset($hvacInfo['inspection']['inspection_report_path']) && !empty($hvacInfo['inspection']['inspection_report_path'])) {
                                            $fileHandlingService = app(\App\Services\FileHandlingService::class);
                                            $fileData = [
                                                'filename' => $hvacInfo['inspection']['inspection_report_filename'] ?? 'ファイルあり',
                                                'path' => $hvacInfo['inspection']['inspection_report_path']
                                            ];
                                            $currentFileData = $fileHandlingService->generateFileDisplayData($fileData, 'hvac-lighting', $facility);
                                        }
                                    @endphp
                                    
                                    <x-file-upload 
                                        name="inspection_report_file"
                                        label="点検報告書"
                                        fileType="pdf"
                                        :currentFile="$currentFileData"
                                        :required="false"
                                        :showRemoveOption="true"
                                        removeFieldName="remove_inspection_report"
                                    />
                                </div>

                                <div class="mb-3">
                                    <label for="target_equipment" class="form-label">点検対象機器</label>
                                    <textarea class="form-control @error('basic_info.hvac.target_equipment') is-invalid @enderror" 
                                              id="target_equipment" 
                                              name="basic_info[hvac][target_equipment]" 
                                              rows="3"
                                              placeholder="点検対象機器の詳細を記入してください">{{ old('basic_info.hvac.target_equipment', $hvacInfo['target_equipment'] ?? '') }}</textarea>
                                    @error('basic_info.hvac.target_equipment')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="hvac_notes" class="form-label">備考</label>
                                    <textarea class="form-control @error('basic_info.hvac.notes') is-invalid @enderror" 
                                              id="hvac_notes" 
                                              name="basic_info[hvac][notes]" 
                                              rows="3"
                                              placeholder="空調設備に関する追加情報があれば記入してください">{{ old('basic_info.hvac.notes', $hvacInfo['notes'] ?? '') }}</textarea>
                                    @error('basic_info.hvac.notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 右側：照明設備 --}}
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-lightbulb me-2"></i>照明設備
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="manufacturer" class="form-label">メーカー</label>
                                    <input type="text" class="form-control @error('basic_info.lighting.manufacturer') is-invalid @enderror" 
                                           id="manufacturer" 
                                           name="basic_info[lighting][manufacturer]" 
                                           value="{{ old('basic_info.lighting.manufacturer', $lightingInfo['manufacturer'] ?? '') }}"
                                           placeholder="例：パナソニック">
                                    @error('basic_info.lighting.manufacturer')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="update_date" class="form-label">更新日</label>
                                    <input type="date" class="form-control @error('basic_info.lighting.update_date') is-invalid @enderror" 
                                           id="update_date" 
                                           name="basic_info[lighting][update_date]" 
                                           value="{{ old('basic_info.lighting.update_date', $lightingInfo['update_date'] ?? '') }}">
                                    @error('basic_info.lighting.update_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="warranty_period" class="form-label">保証期間</label>
                                    <input type="text" class="form-control @error('basic_info.lighting.warranty_period') is-invalid @enderror" 
                                           id="warranty_period" 
                                           name="basic_info[lighting][warranty_period]" 
                                           value="{{ old('basic_info.lighting.warranty_period', $lightingInfo['warranty_period'] ?? '') }}"
                                           placeholder="例：5年">
                                    @error('basic_info.lighting.warranty_period')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="lighting_notes" class="form-label">備考</label>
                                    <textarea class="form-control @error('basic_info.lighting.notes') is-invalid @enderror" 
                                              id="lighting_notes" 
                                              name="basic_info[lighting][notes]" 
                                              rows="3"
                                              placeholder="照明設備に関する追加情報があれば記入してください">{{ old('basic_info.lighting.notes', $lightingInfo['notes'] ?? '') }}</textarea>
                                    @error('basic_info.lighting.notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
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
.form-label {
    font-weight: 500;
    color: #495057;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
</style>
@endpush