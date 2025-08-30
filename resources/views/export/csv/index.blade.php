@extends('layouts.app')

@section('title', 'CSV出力')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">CSV出力</h1>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">出力設定</h5>
                </div>
                <div class="card-body">
                    <form id="csvExportForm">
                        @csrf
                        
                        <!-- 施設選択セクション -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3">施設選択</h6>
                                
                                <!-- 全選択/全解除ボタン -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="selectAllFacilities">
                                        全選択
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllFacilities">
                                        全解除
                                    </button>
                                    <span class="ms-3 text-muted">
                                        選択中: <span id="selectedFacilitiesCount">0</span> / {{ count($facilities) }} 件
                                    </span>
                                </div>

                                <!-- 施設一覧 -->
                                <div class="facility-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 1rem;">
                                    @if(count($facilities) > 0)
                                        @foreach($facilities as $facility)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input facility-checkbox" 
                                                       type="checkbox" 
                                                       name="facility_ids[]" 
                                                       value="{{ $facility->id }}" 
                                                       id="facility_{{ $facility->id }}">
                                                <label class="form-check-label" for="facility_{{ $facility->id }}">
                                                    <strong>{{ $facility->facility_name }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ $facility->company_name }} 
                                                        @if($facility->office_code)
                                                            ({{ $facility->office_code }})
                                                        @endif
                                                        - {{ $facility->address }}
                                                    </small>
                                                </label>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center text-muted py-4">
                                            <p>出力可能な施設がありません。</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 出力項目選択セクション -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3">出力項目選択</h6>
                                
                                <!-- 全選択/全解除ボタン -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="selectAllFields">
                                        全選択
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllFields">
                                        全解除
                                    </button>
                                    <span class="ms-3 text-muted">
                                        選択中: <span id="selectedFieldsCount">0</span> / {{ count($availableFields) }} 項目
                                    </span>
                                </div>

                                <!-- 項目一覧 -->
                                <div class="row">
                                    @foreach($availableFields as $field => $label)
                                        <div class="col-md-6 col-lg-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input field-checkbox" 
                                                       type="checkbox" 
                                                       name="export_fields[]" 
                                                       value="{{ $field }}" 
                                                       id="field_{{ $field }}">
                                                <label class="form-check-label" for="field_{{ $field }}">
                                                    {{ $label }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- 選択内容プレビュー -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3">選択内容プレビュー</h6>
                                <div class="alert alert-info">
                                    <div id="previewContent">
                                        <p class="mb-1"><strong>選択施設:</strong> <span id="previewFacilities">未選択</span></p>
                                        <p class="mb-2"><strong>出力項目:</strong> <span id="previewFields">未選択</span></p>
                                        
                                        <!-- データプレビューテーブル -->
                                        <div id="dataPreviewContainer" style="display: none;">
                                            <hr>
                                            <h6 class="fw-bold mb-2">データプレビュー（最大3件）</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered" id="previewTable">
                                                    <thead class="table-light">
                                                        <tr id="previewTableHeader">
                                                            <!-- ヘッダーが動的に追加されます -->
                                                        </tr>
                                                    </thead>
                                                    <tbody id="previewTableBody">
                                                        <!-- データが動的に追加されます -->
                                                    </tbody>
                                                </table>
                                            </div>
                                            <small class="text-muted">
                                                <span id="previewInfo"></span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- アクションボタン -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="exportButton" disabled>
                                        <i class="fas fa-download me-1"></i>
                                        CSV出力
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="saveFavoriteButton" disabled>
                                        <i class="fas fa-star me-1"></i>
                                        お気に入りに保存
                                    </button>
                                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#favoritesModal">
                                        <i class="fas fa-list me-1"></i>
                                        お気に入り一覧
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

<!-- お気に入り一覧モーダル -->
<div class="modal fade" id="favoritesModal" tabindex="-1" aria-labelledby="favoritesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="favoritesModalLabel">お気に入り一覧</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="favoritesList">
                    <!-- お気に入り一覧がここに動的に読み込まれます -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- お気に入り保存モーダル -->
<div class="modal fade" id="saveFavoriteModal" tabindex="-1" aria-labelledby="saveFavoriteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveFavoriteModalLabel">お気に入りに保存</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="saveFavoriteForm">
                    @csrf
                    <div class="mb-3">
                        <label for="favoriteName" class="form-label">お気に入り名</label>
                        <input type="text" class="form-control" id="favoriteName" name="name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="saveFavoriteConfirm">保存</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const facilityCheckboxes = document.querySelectorAll('.facility-checkbox');
    const fieldCheckboxes = document.querySelectorAll('.field-checkbox');
    const exportButton = document.getElementById('exportButton');
    const saveFavoriteButton = document.getElementById('saveFavoriteButton');
    
    // 選択状況を更新する関数
    function updateSelectionStatus() {
        const selectedFacilities = document.querySelectorAll('.facility-checkbox:checked');
        const selectedFields = document.querySelectorAll('.field-checkbox:checked');
        
        // カウント更新
        document.getElementById('selectedFacilitiesCount').textContent = selectedFacilities.length;
        document.getElementById('selectedFieldsCount').textContent = selectedFields.length;
        
        // プレビュー更新
        const facilityNames = Array.from(selectedFacilities).map(cb => {
            const label = document.querySelector(`label[for="${cb.id}"]`);
            return label.querySelector('strong').textContent;
        });
        
        const fieldNames = Array.from(selectedFields).map(cb => {
            const label = document.querySelector(`label[for="${cb.id}"]`);
            return label.textContent;
        });
        
        document.getElementById('previewFacilities').textContent = 
            facilityNames.length > 0 ? facilityNames.join(', ') : '未選択';
        document.getElementById('previewFields').textContent = 
            fieldNames.length > 0 ? fieldNames.join(', ') : '未選択';
        
        // ボタンの有効/無効切り替え
        const canExport = selectedFacilities.length > 0 && selectedFields.length > 0;
        exportButton.disabled = !canExport;
        saveFavoriteButton.disabled = !canExport;
        
        // データプレビューの更新
        updateDataPreview();
    }
    
    // データプレビューを更新する関数
    function updateDataPreview() {
        const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked')).map(cb => cb.value);
        const selectedFields = Array.from(document.querySelectorAll('.field-checkbox:checked')).map(cb => cb.value);
        
        const previewContainer = document.getElementById('dataPreviewContainer');
        
        if (selectedFacilities.length === 0 || selectedFields.length === 0) {
            previewContainer.style.display = 'none';
            return;
        }
        
        // プレビューデータを取得
        fetch('{{ route("csv.export.preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                facility_ids: selectedFacilities,
                export_fields: selectedFields
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayDataPreview(data.data);
                previewContainer.style.display = 'block';
            } else {
                previewContainer.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('プレビューデータの取得に失敗しました:', error);
            previewContainer.style.display = 'none';
        });
    }
    
    // データプレビューを表示する関数
    function displayDataPreview(data) {
        const headerRow = document.getElementById('previewTableHeader');
        const bodyElement = document.getElementById('previewTableBody');
        const previewInfo = document.getElementById('previewInfo');
        
        // ヘッダーをクリア
        headerRow.innerHTML = '';
        
        // ヘッダーを追加
        Object.values(data.fields).forEach(fieldLabel => {
            const th = document.createElement('th');
            th.textContent = fieldLabel;
            headerRow.appendChild(th);
        });
        
        // ボディをクリア
        bodyElement.innerHTML = '';
        
        // データ行を追加
        data.preview_data.forEach(row => {
            const tr = document.createElement('tr');
            Object.keys(data.fields).forEach(fieldKey => {
                const td = document.createElement('td');
                td.textContent = row[fieldKey] || '';
                tr.appendChild(td);
            });
            bodyElement.appendChild(tr);
        });
        
        // プレビュー情報を更新
        previewInfo.textContent = `${data.preview_count}件のプレビューを表示中（全${data.total_facilities}件中）`;
    }
    
    // チェックボックスの変更を監視
    facilityCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectionStatus);
    });
    
    fieldCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectionStatus);
    });
    
    // 全選択/全解除ボタン
    document.getElementById('selectAllFacilities').addEventListener('click', function() {
        facilityCheckboxes.forEach(cb => cb.checked = true);
        updateSelectionStatus();
    });
    
    document.getElementById('deselectAllFacilities').addEventListener('click', function() {
        facilityCheckboxes.forEach(cb => cb.checked = false);
        updateSelectionStatus();
    });
    
    document.getElementById('selectAllFields').addEventListener('click', function() {
        fieldCheckboxes.forEach(cb => cb.checked = true);
        updateSelectionStatus();
    });
    
    document.getElementById('deselectAllFields').addEventListener('click', function() {
        fieldCheckboxes.forEach(cb => cb.checked = false);
        updateSelectionStatus();
    });
    
    // お気に入り保存ボタン
    document.getElementById('saveFavoriteButton').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('saveFavoriteModal'));
        modal.show();
    });
    
    // 初期状態の更新
    updateSelectionStatus();
});
</script>
@endsection