@extends('layouts.app')

@section('title', '年次確認依頼の作成')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>年次確認依頼の作成</h1>
                <a href="{{ route('annual-confirmation.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> 一覧に戻る
                </a>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('annual-confirmation.store') }}">
                @csrf
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">確認依頼設定</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="year" class="form-label">確認年度 <span class="text-danger">*</span></label>
                                <select name="year" id="year" class="form-select" required>
                                    @foreach(range(date('Y'), date('Y') - 2) as $yearOption)
                                        <option value="{{ $yearOption }}" {{ $yearOption == date('Y') ? 'selected' : '' }}>
                                            {{ $yearOption }}年度
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">確認対象施設の選択</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">全選択</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">全解除</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search -->
                        <div class="mb-3">
                            <input type="text" id="facilitySearch" class="form-control" 
                                   placeholder="施設名、事業所コード、会社名で検索...">
                        </div>

                        <!-- Facilities List -->
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll()">
                                        </th>
                                        <th>施設名</th>
                                        <th>事業所コード</th>
                                        <th>会社名</th>
                                        <th>施設責任者</th>
                                    </tr>
                                </thead>
                                <tbody id="facilitiesTableBody">
                                    @foreach($facilities as $facility)
                                        <tr class="facility-row" data-search="{{ strtolower($facility->facility_name . ' ' . $facility->office_code . ' ' . $facility->company_name) }}">
                                            <td>
                                                <input type="checkbox" name="facility_ids[]" value="{{ $facility->id }}" 
                                                       class="facility-checkbox">
                                            </td>
                                            <td>{{ $facility->facility_name }}</td>
                                            <td>{{ $facility->office_code }}</td>
                                            <td>{{ $facility->company_name }}</td>
                                            <td>
                                                <span class="text-muted">システム設定</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                選択された施設数: <span id="selectedCount">0</span>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> 確認依頼を送信
                    </button>
                    <a href="{{ route('annual-confirmation.index') }}" class="btn btn-outline-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('facilitySearch');
    const facilityRows = document.querySelectorAll('.facility-row');
    const checkboxes = document.querySelectorAll('.facility-checkbox');
    const selectedCountSpan = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');

    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        facilityRows.forEach(row => {
            const searchData = row.getAttribute('data-search');
            if (searchData.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        updateSelectAllCheckbox();
    });

    // Update selected count
    function updateSelectedCount() {
        const checkedBoxes = document.querySelectorAll('.facility-checkbox:checked');
        selectedCountSpan.textContent = checkedBoxes.length;
    }

    // Update select all checkbox state
    function updateSelectAllCheckbox() {
        const visibleCheckboxes = Array.from(checkboxes).filter(cb => 
            cb.closest('.facility-row').style.display !== 'none'
        );
        const checkedVisibleBoxes = visibleCheckboxes.filter(cb => cb.checked);
        
        if (visibleCheckboxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedVisibleBoxes.length === visibleCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else if (checkedVisibleBoxes.length > 0) {
            selectAllCheckbox.indeterminate = true;
        } else {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        }
    }

    // Add event listeners to checkboxes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
            updateSelectAllCheckbox();
        });
    });

    // Initial count update
    updateSelectedCount();
    updateSelectAllCheckbox();
});

function selectAll() {
    const visibleCheckboxes = Array.from(document.querySelectorAll('.facility-checkbox')).filter(cb => 
        cb.closest('.facility-row').style.display !== 'none'
    );
    
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    
    document.getElementById('selectedCount').textContent = visibleCheckboxes.length;
    document.getElementById('selectAllCheckbox').checked = true;
    document.getElementById('selectAllCheckbox').indeterminate = false;
}

function deselectAll() {
    const visibleCheckboxes = Array.from(document.querySelectorAll('.facility-checkbox')).filter(cb => 
        cb.closest('.facility-row').style.display !== 'none'
    );
    
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    document.getElementById('selectedCount').textContent = '0';
    document.getElementById('selectAllCheckbox').checked = false;
    document.getElementById('selectAllCheckbox').indeterminate = false;
}

function toggleAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    if (selectAllCheckbox.checked) {
        selectAll();
    } else {
        deselectAll();
    }
}
</script>
@endsection