@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- ヘッダーカード -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">
                                <i class="fas fa-drafting-compass text-primary me-2"></i>図面編集
                            </h4>
                            <p class="text-muted mb-0">
                                <i class="fas fa-building me-1"></i>{{ $facility->facility_name }}
                                <span class="badge bg-primary ms-2">{{ $facility->office_code }}</span>
                            </p>
                        </div>
                        <div>
                            <a href="{{ route('facilities.show', $facility) }}?tab=drawings" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>戻る
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- アラート表示 -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- 図面編集フォーム -->
            <form method="POST" action="{{ route('facilities.drawings.update', $facility) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- 建物図面 -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-building me-2"></i>建物図面
                                </h5>
                            </div>
                            <div class="card-body">
                                @php
                                    $buildingDrawingTypes = [
                                        'floor_plan' => '平面図',
                                        'site_plan' => '配置図',
                                        'elevation' => '立面図',
                                        'development' => '展開図',
                                        'area_calculation' => '求積図',
                                    ];
                                @endphp

                                @foreach($buildingDrawingTypes as $type => $title)
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">{{ $title }}</label>
                                        
                                        <!-- 現在のファイル表示 -->
                                        @if(isset($drawingsData['building_drawings'][$type]))
                                            @php $drawing = $drawingsData['building_drawings'][$type]; @endphp
                                            <div class="mb-2">
                                                <small class="text-muted">現在のファイル:</small>
                                                <div>
                                                    <a href="{{ route('facilities.drawings.download', [$facility, $type]) }}" 
                                                       class="text-decoration-none" 
                                                       target="_blank">
                                                        <i class="{{ $drawing['icon'] }} {{ $drawing['color'] }} me-1"></i>
                                                        {{ $drawing['filename'] }}
                                                    </a>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- ファイルアップロード -->
                                        <input type="file" 
                                               class="form-control @error('building_drawings.'.$type) is-invalid @enderror" 
                                               name="building_drawings[{{ $type }}]"
                                               accept=".pdf">
                                        @error('building_drawings.'.$type)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror

                                        <!-- 削除オプション -->
                                        @if(isset($drawingsData['building_drawings'][$type]))
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="building_drawings[delete_{{ $type }}]" 
                                                       value="1"
                                                       id="delete_{{ $type }}">
                                                <label class="form-check-label text-danger" for="delete_{{ $type }}">
                                                    <i class="fas fa-trash me-1"></i>このファイルを削除
                                                </label>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                <!-- 追加建物図面 -->
                                <div class="mt-4">
                                    <h6 class="fw-bold">追加図面</h6>
                                    <div id="additional-building-drawings">
                                        @if(isset($drawingsData['building_drawings']))
                                            @php $additionalIndex = 0; @endphp
                                            @foreach($drawingsData['building_drawings'] as $key => $drawing)
                                                @if(!in_array($key, array_keys($buildingDrawingTypes)))
                                                    <div class="additional-drawing-item mb-3 p-3 border rounded">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <label class="form-label">図面名</label>
                                                                <input type="text" 
                                                                       class="form-control" 
                                                                       name="additional_building_drawings[{{ $key }}][title]"
                                                                       value="{{ $drawing['title'] }}"
                                                                       placeholder="図面名を入力">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">ファイル</label>
                                                                <input type="file" 
                                                                       class="form-control" 
                                                                       name="additional_building_drawings[{{ $key }}][file]"
                                                                       accept=".pdf">
                                                            </div>
                                                        </div>
                                                        <div class="mt-2">
                                                            <small class="text-muted">現在のファイル:</small>
                                                            <a href="{{ route('facilities.drawings.download', [$facility, $key]) }}" 
                                                               class="text-decoration-none ms-2" 
                                                               target="_blank">
                                                                <i class="{{ $drawing['icon'] }} {{ $drawing['color'] }} me-1"></i>
                                                                {{ $drawing['filename'] }}
                                                            </a>
                                                        </div>
                                                        <div class="form-check mt-2">
                                                            <input class="form-check-input" 
                                                                   type="checkbox" 
                                                                   name="additional_building_drawings[{{ $key }}][delete]" 
                                                                   value="1">
                                                            <label class="form-check-label text-danger">
                                                                <i class="fas fa-trash me-1"></i>この図面を削除
                                                            </label>
                                                        </div>
                                                    </div>
                                                    @php $additionalIndex++; @endphp
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-building-drawing">
                                        <i class="fas fa-plus me-1"></i>図面を追加
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 設備図面 -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-cogs me-2"></i>設備図面
                                </h5>
                            </div>
                            <div class="card-body">
                                @php
                                    $equipmentDrawingTypes = [
                                        'electrical_equipment' => '電気設備図面',
                                        'lighting_equipment' => '電灯設備図面',
                                        'hvac_equipment' => '空調設備図面',
                                        'plumbing_equipment' => '給排水衛生設備図面',
                                        'kitchen_equipment' => '厨房設備図面',
                                    ];
                                @endphp

                                @foreach($equipmentDrawingTypes as $type => $title)
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">{{ $title }}</label>
                                        
                                        <!-- 現在のファイル表示 -->
                                        @if(isset($drawingsData['equipment_drawings'][$type]))
                                            @php $drawing = $drawingsData['equipment_drawings'][$type]; @endphp
                                            <div class="mb-2">
                                                <small class="text-muted">現在のファイル:</small>
                                                <div>
                                                    <a href="{{ route('facilities.drawings.download', [$facility, $type]) }}" 
                                                       class="text-decoration-none" 
                                                       target="_blank">
                                                        <i class="{{ $drawing['icon'] }} {{ $drawing['color'] }} me-1"></i>
                                                        {{ $drawing['filename'] }}
                                                    </a>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- ファイルアップロード -->
                                        <input type="file" 
                                               class="form-control @error('equipment_drawings.'.$type) is-invalid @enderror" 
                                               name="equipment_drawings[{{ $type }}]"
                                               accept=".pdf">
                                        @error('equipment_drawings.'.$type)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror

                                        <!-- 削除オプション -->
                                        @if(isset($drawingsData['equipment_drawings'][$type]))
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="equipment_drawings[delete_{{ $type }}]" 
                                                       value="1"
                                                       id="delete_equipment_{{ $type }}">
                                                <label class="form-check-label text-danger" for="delete_equipment_{{ $type }}">
                                                    <i class="fas fa-trash me-1"></i>このファイルを削除
                                                </label>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                <!-- 追加設備図面 -->
                                <div class="mt-4">
                                    <h6 class="fw-bold">追加図面</h6>
                                    <div id="additional-equipment-drawings">
                                        @if(isset($drawingsData['equipment_drawings']))
                                            @php $additionalIndex = 0; @endphp
                                            @foreach($drawingsData['equipment_drawings'] as $key => $drawing)
                                                @if(!in_array($key, array_keys($equipmentDrawingTypes)))
                                                    <div class="additional-drawing-item mb-3 p-3 border rounded">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <label class="form-label">図面名</label>
                                                                <input type="text" 
                                                                       class="form-control" 
                                                                       name="additional_equipment_drawings[{{ $key }}][title]"
                                                                       value="{{ $drawing['title'] }}"
                                                                       placeholder="図面名を入力">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">ファイル</label>
                                                                <input type="file" 
                                                                       class="form-control" 
                                                                       name="additional_equipment_drawings[{{ $key }}][file]"
                                                                       accept=".pdf">
                                                            </div>
                                                        </div>
                                                        <div class="mt-2">
                                                            <small class="text-muted">現在のファイル:</small>
                                                            <a href="{{ route('facilities.drawings.download', [$facility, $key]) }}" 
                                                               class="text-decoration-none ms-2" 
                                                               target="_blank">
                                                                <i class="{{ $drawing['icon'] }} {{ $drawing['color'] }} me-1"></i>
                                                                {{ $drawing['filename'] }}
                                                            </a>
                                                        </div>
                                                        <div class="form-check mt-2">
                                                            <input class="form-check-input" 
                                                                   type="checkbox" 
                                                                   name="additional_equipment_drawings[{{ $key }}][delete]" 
                                                                   value="1">
                                                            <label class="form-check-label text-danger">
                                                                <i class="fas fa-trash me-1"></i>この図面を削除
                                                            </label>
                                                        </div>
                                                    </div>
                                                    @php $additionalIndex++; @endphp
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-outline-success btn-sm" id="add-equipment-drawing">
                                        <i class="fas fa-plus me-1"></i>図面を追加
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 引き渡し図面 -->
                <div class="row">
                    <div class="col-12">
                        <!-- 備考テーブル（引き渡しテーブルの上に配置） -->
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-sticky-note me-2"></i>引き渡し図面備考
                                </h6>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control @error('handover_drawings_notes') is-invalid @enderror" 
                                          name="handover_drawings_notes" 
                                          rows="3" 
                                          placeholder="引き渡し図面に関する備考を入力してください">{{ old('handover_drawings_notes', $drawingsData['handover_drawings']['notes'] ?? '') }}</textarea>
                                @error('handover_drawings_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 引き渡し図面テーブル -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fas fa-certificate me-2"></i>引き渡し図面
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 30%;">図面名</th>
                                                <th style="width: 50%;">ファイル</th>
                                                <th style="width: 20%;">操作</th>
                                            </tr>
                                        </thead>
                                        <tbody id="handover-drawings-table">
                                            <!-- 1行目：施工図面一式（固定） -->
                                            <tr>
                                                <td class="align-middle">
                                                    <strong>施工図面一式</strong>
                                                </td>
                                                <td>
                                                    <!-- 現在のファイル表示 -->
                                                    @if(isset($drawingsData['handover_drawings']['construction_drawings']))
                                                        @php $drawing = $drawingsData['handover_drawings']['construction_drawings']; @endphp
                                                        <div class="mb-2">
                                                            <small class="text-muted">現在のファイル:</small>
                                                            <div>
                                                                <a href="{{ route('facilities.drawings.download', [$facility, 'construction_drawings']) }}" 
                                                                   class="text-decoration-none" 
                                                                   target="_blank">
                                                                    <i class="fas fa-file-pdf text-danger me-1"></i>
                                                                    {{ $drawing['filename'] }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <!-- ファイルアップロード -->
                                                    <input type="file" 
                                                           class="form-control @error('handover_drawings.construction_drawings') is-invalid @enderror" 
                                                           name="handover_drawings[construction_drawings]"
                                                           accept=".pdf">
                                                    @error('handover_drawings.construction_drawings')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="align-middle">
                                                    <!-- 削除オプション -->
                                                    @if(isset($drawingsData['handover_drawings']['construction_drawings']))
                                                        <div class="form-check">
                                                            <input class="form-check-input" 
                                                                   type="checkbox" 
                                                                   name="handover_drawings[delete_construction_drawings]" 
                                                                   value="1"
                                                                   id="delete_construction_drawings">
                                                            <label class="form-check-label text-danger" for="delete_construction_drawings">
                                                                <i class="fas fa-trash me-1"></i>削除
                                                            </label>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>

                                            <!-- 2行目以降：動的に追加可能な行 -->
                                            @if(isset($drawingsData['handover_drawings']['additional']) && is_array($drawingsData['handover_drawings']['additional']))
                                                @foreach($drawingsData['handover_drawings']['additional'] as $index => $additionalDrawing)
                                                    <tr class="handover-drawing-row">
                                                        <td>
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   name="handover_drawings[additional][{{ $index }}][title]"
                                                                   value="{{ $additionalDrawing['title'] ?? '' }}"
                                                                   placeholder="図面名を入力">
                                                        </td>
                                                        <td>
                                                            <!-- 現在のファイル表示 -->
                                                            @if(isset($additionalDrawing['filename']))
                                                                <div class="mb-2">
                                                                    <small class="text-muted">現在のファイル:</small>
                                                                    <div>
                                                                        <a href="{{ route('facilities.drawings.download', [$facility, 'handover_additional_' . $index]) }}" 
                                                                           class="text-decoration-none" 
                                                                           target="_blank">
                                                                            <i class="fas fa-file-pdf text-danger me-1"></i>
                                                                            {{ $additionalDrawing['filename'] }}
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <!-- ファイルアップロード -->
                                                            <input type="file" 
                                                                   class="form-control" 
                                                                   name="handover_drawings[additional][{{ $index }}][file]"
                                                                   accept=".pdf">
                                                        </td>
                                                        <td class="align-middle">
                                                            <div class="d-flex flex-column gap-2">
                                                                <!-- 削除オプション -->
                                                                @if(isset($additionalDrawing['filename']))
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" 
                                                                               type="checkbox" 
                                                                               name="handover_drawings[additional][{{ $index }}][delete]" 
                                                                               value="1">
                                                                        <label class="form-check-label text-danger">
                                                                            <i class="fas fa-trash me-1"></i>削除
                                                                        </label>
                                                                    </div>
                                                                @endif
                                                                <!-- 行削除ボタン -->
                                                                <button type="button" class="btn btn-outline-danger btn-sm remove-handover-row">
                                                                    <i class="fas fa-minus me-1"></i>行削除
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- 行追加ボタン -->
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-handover-drawing-row">
                                        <i class="fas fa-plus me-1"></i>図面行を追加
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 備考 -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-sticky-note me-2"></i>備考
                                </h6>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          name="notes" 
                                          rows="4" 
                                          placeholder="図面に関する備考を入力してください">{{ old('notes', $drawingsData['notes'] ?? '') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 保存ボタン -->
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('facilities.show', $facility) }}?tab=drawings" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>キャンセル
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>保存
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let buildingDrawingIndex = 1000;
    let equipmentDrawingIndex = 1000;
    let handoverDrawingIndex = 1000;

    // 建物図面追加
    document.getElementById('add-building-drawing').addEventListener('click', function() {
        const container = document.getElementById('additional-building-drawings');
        const newDrawing = document.createElement('div');
        newDrawing.className = 'additional-drawing-item mb-3 p-3 border rounded';
        newDrawing.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">図面名</label>
                    <input type="text" 
                           class="form-control" 
                           name="additional_building_drawings[new_${buildingDrawingIndex}][title]"
                           placeholder="図面名を入力">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ファイル</label>
                    <input type="file" 
                           class="form-control" 
                           name="additional_building_drawings[new_${buildingDrawingIndex}][file]"
                           accept=".pdf">
                </div>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-drawing">
                    <i class="fas fa-trash me-1"></i>削除
                </button>
            </div>
        `;
        container.appendChild(newDrawing);
        buildingDrawingIndex++;
    });

    // 設備図面追加
    document.getElementById('add-equipment-drawing').addEventListener('click', function() {
        const container = document.getElementById('additional-equipment-drawings');
        const newDrawing = document.createElement('div');
        newDrawing.className = 'additional-drawing-item mb-3 p-3 border rounded';
        newDrawing.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">図面名</label>
                    <input type="text" 
                           class="form-control" 
                           name="additional_equipment_drawings[new_${equipmentDrawingIndex}][title]"
                           placeholder="図面名を入力">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ファイル</label>
                    <input type="file" 
                           class="form-control" 
                           name="additional_equipment_drawings[new_${equipmentDrawingIndex}][file]"
                           accept=".pdf">
                </div>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-drawing">
                    <i class="fas fa-trash me-1"></i>削除
                </button>
            </div>
        `;
        container.appendChild(newDrawing);
        equipmentDrawingIndex++;
    });

    // 引き渡し図面行追加
    document.getElementById('add-handover-drawing-row').addEventListener('click', function() {
        const tableBody = document.getElementById('handover-drawings-table');
        const newRow = document.createElement('tr');
        newRow.className = 'handover-drawing-row';
        newRow.innerHTML = `
            <td>
                <input type="text" 
                       class="form-control" 
                       name="handover_drawings[additional][new_${handoverDrawingIndex}][title]"
                       placeholder="図面名を入力">
            </td>
            <td>
                <input type="file" 
                       class="form-control" 
                       name="handover_drawings[additional][new_${handoverDrawingIndex}][file]"
                       accept=".pdf">
            </td>
            <td class="align-middle">
                <button type="button" class="btn btn-outline-danger btn-sm remove-handover-row">
                    <i class="fas fa-minus me-1"></i>行削除
                </button>
            </td>
        `;
        tableBody.appendChild(newRow);
        handoverDrawingIndex++;
    });

    // 図面削除（建物・設備図面）
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-drawing') || e.target.closest('.remove-drawing')) {
            const drawingItem = e.target.closest('.additional-drawing-item');
            if (drawingItem) {
                drawingItem.remove();
            }
        }
    });

    // 引き渡し図面行削除
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-handover-row') || e.target.closest('.remove-handover-row')) {
            const row = e.target.closest('.handover-drawing-row');
            if (row) {
                row.remove();
            }
        }
    });
});
</script>
@endsection