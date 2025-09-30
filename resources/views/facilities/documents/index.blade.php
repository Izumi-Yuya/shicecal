@extends('layouts.app')

@section('title', $facility->facility_name . ' - ドキュメント管理')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/document-management.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/document-file-folder-display.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/document-animations.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/document-upload.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/document-context-menu.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/document-folder-management.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card document-management-container">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-folder me-2"></i>
                        {{ $facility->facility_name }} - ドキュメント管理
                    </h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary btn-sm" id="uploadFileBtn">
                            <i class="fas fa-upload me-1"></i>ファイルアップロード
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="createFolderBtn">
                            <i class="fas fa-folder-plus me-1"></i>フォルダ作成
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-cog me-1"></i>設定
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" id="resetPreferencesBtn">
                                    <i class="fas fa-undo me-2"></i>表示設定をリセット
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($error))
                        <div class="alert alert-danger">
                            {{ $error }}
                        </div>
                    @endif

                    <!-- Breadcrumb Navigation -->
                    <nav aria-label="breadcrumb" id="breadcrumbNav" class="document-breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">
                                <i class="fas fa-home me-1"></i>ルート
                            </li>
                        </ol>
                    </nav>

                    <!-- View Controls -->
                    <div class="row mb-3 document-view-controls">
                        <div class="col-md-4">
                            <div class="btn-group" role="group" aria-label="表示モード">
                                <input type="radio" class="btn-check" name="viewMode" id="listView" value="list" 
                                       {{ ($folderContents['sort_options']['view_mode'] ?? 'list') === 'list' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary btn-sm" for="listView">
                                    <i class="fas fa-list me-1"></i>一覧表示
                                </label>
                                <input type="radio" class="btn-check" name="viewMode" id="iconView" value="icon"
                                       {{ ($folderContents['sort_options']['view_mode'] ?? 'list') === 'icon' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary btn-sm" for="iconView">
                                    <i class="fas fa-th me-1"></i>アイコン表示
                                </label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-end align-items-center">
                                <!-- Search -->
                                <div class="input-group input-group-sm me-2 document-search-input" style="width: 200px;">
                                    <input type="text" class="form-control" id="searchInput" placeholder="検索..." 
                                           value="{{ $folderContents['sort_options']['search'] ?? '' }}">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                
                                <!-- File Type Filter -->
                                <select class="form-select form-select-sm me-2" id="filterType" style="width: auto;">
                                    <option value="all">すべてのファイルタイプ</option>
                                    @foreach($availableFileTypes as $fileType)
                                        <option value="{{ $fileType['extension'] }}" 
                                                {{ ($folderContents['sort_options']['filter_type'] ?? '') === $fileType['extension'] ? 'selected' : '' }}>
                                            {{ $fileType['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                
                                <!-- Sort Options -->
                                <select class="form-select form-select-sm me-2" id="sortBy" style="width: auto;">
                                    <option value="name" {{ ($folderContents['sort_options']['sort_by'] ?? 'name') === 'name' ? 'selected' : '' }}>名前順</option>
                                    <option value="date" {{ ($folderContents['sort_options']['sort_by'] ?? 'name') === 'date' ? 'selected' : '' }}>作成日順</option>
                                    <option value="modified" {{ ($folderContents['sort_options']['sort_by'] ?? 'name') === 'modified' ? 'selected' : '' }}>更新日順</option>
                                    <option value="size" {{ ($folderContents['sort_options']['sort_by'] ?? 'name') === 'size' ? 'selected' : '' }}>サイズ順</option>
                                    <option value="type" {{ ($folderContents['sort_options']['sort_by'] ?? 'name') === 'type' ? 'selected' : '' }}>種類順</option>
                                </select>
                                
                                <select class="form-select form-select-sm" id="sortDirection" style="width: auto;">
                                    <option value="asc" {{ ($folderContents['sort_options']['sort_direction'] ?? 'asc') === 'asc' ? 'selected' : '' }}>昇順</option>
                                    <option value="desc" {{ ($folderContents['sort_options']['sort_direction'] ?? 'asc') === 'desc' ? 'selected' : '' }}>降順</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Document List -->
                    <div id="documentList">
                        <div class="text-center py-5">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">読み込み中...</span>
                            </div>
                            <p class="mt-2">ドキュメントを読み込んでいます...</p>
                        </div>
                    </div>

                    <!-- Storage Stats -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="storage-stats">
                                <h6 class="card-title">
                                    <i class="fas fa-chart-bar"></i>
                                    ストレージ使用状況
                                </h6>
                                <div class="row">
                                    <div class="col-md-3 stat-item">
                                        <div class="stat-label">ファイル数</div>
                                        <div class="stat-value">{{ $storageStats['total_files'] ?? 0 }}</div>
                                    </div>
                                    <div class="col-md-3 stat-item">
                                        <div class="stat-label">フォルダ数</div>
                                        <div class="stat-value">{{ $storageStats['total_folders'] ?? 0 }}</div>
                                    </div>
                                    <div class="col-md-3 stat-item">
                                        <div class="stat-label">使用容量</div>
                                        <div class="stat-value large">{{ $storageStats['formatted_total_size'] ?? '0 B' }}</div>
                                    </div>
                                    <div class="col-md-3 stat-item">
                                        <div class="stat-label">最終更新</div>
                                        <div class="stat-value">{{ $storageStats['last_updated']->format('Y-m-d H:i') ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Keyboard Navigation Hint -->
<div id="keyboardHint" class="keyboard-navigation-hint">
    <strong>キーボードショートカット:</strong>
    <kbd>Enter</kbd> 開く
    <kbd>F2</kbd> 名前変更
    <kbd>Delete</kbd> 削除
    <kbd>Esc</kbd> キャンセル
</div>

<!-- File Upload Modal -->
<div class="modal fade upload-modal" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cloud-upload-alt me-2"></i>ファイルアップロード
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Drag & Drop Area -->
                    <div class="file-input-area mb-3" onclick="document.getElementById('files').click()">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <h6>ファイルを選択またはドラッグ&ドロップ</h6>
                        <p class="text-muted mb-2">
                            複数ファイルの同時アップロード対応（最大10ファイルまで）
                        </p>
                        <button type="button" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-folder-open me-1"></i>ファイルを選択
                        </button>
                    </div>
                    
                    <div class="mb-3">
                        <input type="file" class="form-control d-none" id="files" name="files[]" multiple 
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.bmp,.svg,.zip,.rar,.7z">
                        <div class="form-text">
                            <strong>対応形式:</strong> PDF, Word, Excel, PowerPoint, 画像ファイル, アーカイブファイル等<br>
                            <strong>最大ファイルサイズ:</strong> 10MB<br>
                            <strong>同時アップロード:</strong> 最大10ファイル
                        </div>
                    </div>
                    
                    <input type="hidden" id="currentFolderId" name="folder_id" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>キャンセル
                </button>
                <button type="button" class="btn btn-upload-primary" id="uploadSubmit">
                    <i class="fas fa-upload me-1"></i>アップロード
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create Folder Modal -->
<div class="modal fade" id="createFolderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-folder-plus me-2"></i>フォルダ作成
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createFolderForm">
                    @csrf
                    <div class="mb-3">
                        <label for="folderName" class="form-label">フォルダ名</label>
                        <input type="text" class="form-control" id="folderName" name="name" required maxlength="255">
                        <div class="form-text">
                            フォルダ名は255文字以内で入力してください。特殊文字（\ / : * ? " < > |）は使用できません。
                        </div>
                        <div class="invalid-feedback" id="folderNameError"></div>
                    </div>
                    <div class="mb-3" id="parentFolderInfo" style="display: none;">
                        <label class="form-label">作成場所</label>
                        <div class="alert alert-info py-2">
                            <i class="fas fa-folder me-2"></i>
                            <span id="parentFolderPath"></span>
                        </div>
                    </div>
                    <input type="hidden" id="parentFolderId" name="parent_id" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>キャンセル
                </button>
                <button type="button" class="btn btn-primary" id="createFolderSubmit">
                    <i class="fas fa-folder-plus me-1"></i>作成
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Rename Folder Modal -->
<div class="modal fade" id="renameFolderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>フォルダ名変更
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="renameFolderForm">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="newFolderName" class="form-label">新しいフォルダ名</label>
                        <input type="text" class="form-control" id="newFolderName" name="name" required maxlength="255">
                        <div class="form-text">
                            フォルダ名は255文字以内で入力してください。特殊文字（\ / : * ? " < > |）は使用できません。
                        </div>
                        <div class="invalid-feedback" id="newFolderNameError"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">現在の名前</label>
                        <div class="alert alert-light py-2">
                            <i class="fas fa-folder me-2"></i>
                            <span id="currentFolderName"></span>
                        </div>
                    </div>
                    <input type="hidden" id="renameFolderId" name="folder_id" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>キャンセル
                </button>
                <button type="button" class="btn btn-primary" id="renameFolderSubmit">
                    <i class="fas fa-save me-1"></i>変更
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>削除の確認
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>警告：</strong> この操作は元に戻すことができません。
                </div>
                <div id="deleteConfirmContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>キャンセル
                </button>
                <button type="button" class="btn btn-danger" id="deleteConfirmSubmit">
                    <i class="fas fa-trash me-1"></i>削除
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
import { DocumentUploadManager } from '/js/modules/document-upload.js';
import { DocumentFileManager } from '/js/modules/document-file-manager.js';

document.addEventListener('DOMContentLoaded', function() {
    // Initialize document management
    const facilityId = {{ $facility->id }};
    let currentFolderId = null;
    
    // Initialize upload manager
    window.documentUpload = new DocumentUploadManager(facilityId);
    
    // Initialize file manager
    window.documentFileManager = new DocumentFileManager(facilityId);
    
    // Load initial folder contents
    loadFolderContents();
    
    // Event listeners
    document.getElementById('uploadFileBtn').addEventListener('click', function() {
        document.getElementById('currentFolderId').value = currentFolderId || '';
        new bootstrap.Modal(document.getElementById('uploadModal')).show();
    });
    
    document.getElementById('createFolderBtn').addEventListener('click', function() {
        showCreateFolderModal(currentFolderId);
    });
    
    // View mode change
    document.querySelectorAll('input[name="viewMode"]').forEach(radio => {
        radio.addEventListener('change', loadFolderContents);
    });
    
    // Sort and filter changes
    document.getElementById('sortBy').addEventListener('change', loadFolderContents);
    document.getElementById('sortDirection').addEventListener('change', loadFolderContents);
    document.getElementById('filterType').addEventListener('change', loadFolderContents);
    
    // Search functionality
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadFolderContents, 500); // Debounce search
    });
    
    document.getElementById('clearSearch').addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        loadFolderContents();
    });
    
    // Reset preferences
    document.getElementById('resetPreferencesBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        if (confirm('表示設定をリセットしますか？\n（ソート順、表示モード、フィルタ設定がデフォルトに戻ります）')) {
            fetch(`/facilities/${facilityId}/documents/preferences/reset`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    // Reset UI to defaults
                    document.getElementById('sortBy').value = 'name';
                    document.getElementById('sortDirection').value = 'asc';
                    document.getElementById('filterType').value = 'all';
                    document.getElementById('searchInput').value = '';
                    document.querySelector('input[name="viewMode"][value="list"]').checked = true;
                    // Reload folder contents
                    loadFolderContents();
                } else {
                    showError(data.message || '設定のリセットに失敗しました。');
                }
            })
            .catch(error => {
                console.error('Error resetting preferences:', error);
                showError('設定のリセット中にエラーが発生しました。');
            });
        }
    });
    
    // Listen for refresh folder events from upload manager
    document.addEventListener('refreshFolder', function() {
        loadFolderContents();
    });
    
    function loadFolderContents() {
        const viewMode = document.querySelector('input[name="viewMode"]:checked').value;
        const sortBy = document.getElementById('sortBy').value;
        const sortDirection = document.getElementById('sortDirection').value;
        const filterType = document.getElementById('filterType').value;
        const search = document.getElementById('searchInput').value.trim();
        
        const url = new URL(`/facilities/${facilityId}/documents/folders/${currentFolderId || ''}`, window.location.origin);
        url.searchParams.set('view_mode', viewMode);
        url.searchParams.set('sort_by', sortBy);
        url.searchParams.set('sort_direction', sortDirection);
        if (filterType && filterType !== 'all') {
            url.searchParams.set('filter_type', filterType);
        }
        if (search) {
            url.searchParams.set('search', search);
        }
        
        // Show loading state
        const container = document.getElementById('documentList');
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">読み込み中...</span>
                </div>
                <p class="mt-2">ドキュメントを読み込んでいます...</p>
            </div>
        `;
        
        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderFolderContents(data.data);
                updateBreadcrumbs(data.data.breadcrumbs);
                updateFilterOptions(data.data.available_file_types);
                updateSortState(data.data.sort_options);
            } else {
                showError(data.message || 'フォルダの読み込みに失敗しました。');
            }
        })
        .catch(error => {
            console.error('Error loading folder contents:', error);
            showError('フォルダの読み込み中にエラーが発生しました。');
        });
    }
    
    function renderFolderContents(data) {
        const container = document.getElementById('documentList');
        const viewMode = document.querySelector('input[name="viewMode"]:checked').value;
        
        if (data.folders.length === 0 && data.files.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">このフォルダは空です</p>
                    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('uploadFileBtn').click()">
                        <i class="fas fa-upload me-1"></i>ファイルをアップロード
                    </button>
                </div>
            `;
            return;
        }
        
        if (viewMode === 'list') {
            renderListView(container, data);
        } else {
            renderIconView(container, data);
        }
        
        // Notify file manager that content has been loaded
        document.dispatchEvent(new CustomEvent('folderContentLoaded'));
    }
    
    function renderListView(container, data) {
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>名前</th>
                            <th>種類</th>
                            <th>サイズ</th>
                            <th>作成日時</th>
                            <th>更新日時</th>
                            <th>作成者</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        // Show message if no items
        if (data.folders.length === 0 && data.files.length === 0) {
            html += `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-folder-open fa-2x mb-2"></i>
                        <p>表示するアイテムがありません</p>
                    </td>
                </tr>
            `;
        } else {
            // Folders first (always displayed first regardless of sort)
            data.folders.forEach(folder => {
                const folderIcon = folder.file_count > 0 ? 'fas fa-folder text-warning' : 'fas fa-folder text-muted';
                const fileCountText = folder.file_count > 0 
                    ? `<small class="text-muted ms-2">(${folder.file_count} ファイル)</small>` 
                    : '<small class="text-muted ms-2">(空)</small>';
                
                html += `
                    <tr class="folder-row" data-folder-id="${folder.id}" style="cursor: pointer;" onclick="openFolder(${folder.id})" tabindex="0">
                        <td>
                            <i class="${folderIcon} me-2"></i>
                            <span class="folder-name" title="${escapeHtml(folder.name)}">${escapeHtml(folder.name)}</span>
                            ${fileCountText}
                        </td>
                        <td><span class="badge bg-secondary">フォルダ</span></td>
                        <td>-</td>
                        <td>${formatDate(folder.created_at)}</td>
                        <td>${formatDate(folder.updated_at)}</td>
                        <td>${escapeHtml(folder.created_by)}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="event.stopPropagation(); openFolder(${folder.id})" title="開く">
                                    <i class="fas fa-folder-open"></i>
                                </button>
                                <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" onclick="event.stopPropagation();" title="操作">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); openFolder(${folder.id})">
                                        <i class="fas fa-folder-open me-2"></i>開く
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); showRenameFolderModal(${folder.id}, '${escapeHtml(folder.name).replace(/'/g, "\\'")}')">
                                        <i class="fas fa-edit me-2"></i>名前を変更
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); showCreateFolderModal(${folder.id})">
                                        <i class="fas fa-folder-plus me-2"></i>サブフォルダ作成
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); showDeleteConfirmModal('folder', ${folder.id}, '${escapeHtml(folder.name).replace(/'/g, "\\'")}', '${folder.file_count}個のファイルを含む')">
                                        <i class="fas fa-trash me-2"></i>削除
                                    </a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            // Files
            data.files.forEach(file => {
                html += `
                    <tr class="file-row" data-file-id="${file.id}">
                        <td>
                            <i class="${file.icon} ${file.color} me-2"></i>
                            <span class="file-name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span>
                        </td>
                        <td><span class="badge bg-info">${file.extension.toUpperCase()}</span></td>
                        <td>${file.formatted_size}</td>
                        <td>${formatDate(file.created_at)}</td>
                        <td>${formatDate(file.updated_at)}</td>
                        <td>${escapeHtml(file.uploaded_by)}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="${file.download_url}" class="btn btn-outline-primary" title="ダウンロード">
                                    <i class="fas fa-download"></i>
                                </a>
                                ${file.can_preview ? `<button class="btn btn-outline-info" onclick="previewFile(${file.id})" title="プレビュー">
                                    <i class="fas fa-eye"></i>
                                </button>` : ''}
                                <button class="btn btn-outline-secondary" onclick="showFileMenu(${file.id})" title="メニュー">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    function renderIconView(container, data) {
        let html = '<div class="row">';
        
        // Show message if no items
        if (data.folders.length === 0 && data.files.length === 0) {
            html += `
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                        <p class="text-muted">表示するアイテムがありません</p>
                    </div>
                </div>
            `;
        } else {
            // Folders first (always displayed first regardless of sort)
            data.folders.forEach(folder => {
                const folderIcon = folder.file_count > 0 ? 'fas fa-folder fa-3x text-warning' : 'fas fa-folder fa-3x text-muted';
                const displayName = folder.name.length > 15 ? folder.name.substring(0, 15) + '...' : folder.name;
                const fileCountText = folder.file_count > 0 ? `${folder.file_count} ファイル` : '空';
                
                html += `
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6 mb-3">
                        <div class="card h-100 folder-card" data-folder-id="${folder.id}" onclick="openFolder(${folder.id})" style="cursor: pointer;" tabindex="0">
                            <div class="card-body text-center p-3">
                                <i class="${folderIcon} mb-2"></i>
                                <p class="card-text small mb-1 folder-name" title="${escapeHtml(folder.name)}">${escapeHtml(displayName)}</p>
                                <small class="text-muted d-block mb-2">${fileCountText}</small>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); openFolder(${folder.id})" title="開く">
                                        <i class="fas fa-folder-open"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" onclick="event.stopPropagation();" title="操作">
                                        <span class="visually-hidden">操作メニュー</span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); openFolder(${folder.id})">
                                            <i class="fas fa-folder-open me-2"></i>開く
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); showRenameFolderModal(${folder.id}, '${escapeHtml(folder.name).replace(/'/g, "\\'")}')">
                                            <i class="fas fa-edit me-2"></i>名前を変更
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); showCreateFolderModal(${folder.id})">
                                            <i class="fas fa-folder-plus me-2"></i>サブフォルダ作成
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); showDeleteConfirmModal('folder', ${folder.id}, '${escapeHtml(folder.name).replace(/'/g, "\\'")}', '${folder.file_count}個のファイルを含む')">
                                            <i class="fas fa-trash me-2"></i>削除
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Files
            data.files.forEach(file => {
                html += `
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6 mb-3">
                        <div class="card h-100 file-card" data-file-id="${file.id}">
                            <div class="card-body text-center p-3">
                                <i class="${file.icon} ${file.color} fa-3x mb-2"></i>
                                <p class="card-text small mb-1" title="${escapeHtml(file.name)}">${escapeHtml(file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name)}</p>
                                <small class="text-muted d-block mb-2">${file.formatted_size}</small>
                                <div class="btn-group btn-group-sm">
                                    <a href="${file.download_url}" class="btn btn-outline-primary" title="ダウンロード">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    ${file.can_preview ? `<button class="btn btn-outline-info" onclick="previewFile(${file.id})" title="プレビュー">
                                        <i class="fas fa-eye"></i>
                                    </button>` : ''}
                                    <button class="btn btn-outline-secondary" onclick="showFileMenu(${file.id})" title="メニュー">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    function updateBreadcrumbs(breadcrumbs) {
        const nav = document.getElementById('breadcrumbNav');
        let html = '<ol class="breadcrumb">';
        
        breadcrumbs.forEach((crumb, index) => {
            if (crumb.is_current) {
                html += `<li class="breadcrumb-item active">${escapeHtml(crumb.name)}</li>`;
            } else {
                html += `<li class="breadcrumb-item"><a href="#" onclick="navigateToFolder(${crumb.id})">${escapeHtml(crumb.name)}</a></li>`;
            }
        });
        
        html += '</ol>';
        nav.innerHTML = html;
    }
    
    window.openFolder = function(folderId) {
        currentFolderId = folderId;
        if (window.documentUpload) {
            window.documentUpload.setCurrentFolder(folderId);
        }
        document.dispatchEvent(new CustomEvent('folderChanged', { detail: { folderId } }));
        loadFolderContents();
    };
    
    window.navigateToFolder = function(folderId) {
        currentFolderId = folderId;
        if (window.documentUpload) {
            window.documentUpload.setCurrentFolder(folderId);
        }
        document.dispatchEvent(new CustomEvent('folderChanged', { detail: { folderId } }));
        loadFolderContents();
    };
    
    // Folder management functions
    function showCreateFolderModal(parentFolderId = null) {
        const modal = document.getElementById('createFolderModal');
        const form = document.getElementById('createFolderForm');
        const nameInput = document.getElementById('folderName');
        const parentInfo = document.getElementById('parentFolderInfo');
        const parentPath = document.getElementById('parentFolderPath');
        
        // Reset form
        form.reset();
        nameInput.classList.remove('is-invalid');
        document.getElementById('folderNameError').textContent = '';
        
        // Set parent folder
        document.getElementById('parentFolderId').value = parentFolderId || '';
        
        // Show parent folder info if creating in subfolder
        if (parentFolderId) {
            // Get current breadcrumbs to show path
            const breadcrumbs = document.querySelectorAll('#breadcrumbNav .breadcrumb-item');
            let path = 'ルート';
            if (breadcrumbs.length > 1) {
                const pathParts = Array.from(breadcrumbs).map(item => item.textContent.trim());
                path = pathParts.join(' > ');
            }
            parentPath.textContent = path;
            parentInfo.style.display = 'block';
        } else {
            parentInfo.style.display = 'none';
        }
        
        // Show modal and focus on name input
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        modal.addEventListener('shown.bs.modal', function() {
            nameInput.focus();
        }, { once: true });
    }
    
    function showRenameFolderModal(folderId, currentName) {
        const modal = document.getElementById('renameFolderModal');
        const form = document.getElementById('renameFolderForm');
        const nameInput = document.getElementById('newFolderName');
        const currentNameSpan = document.getElementById('currentFolderName');
        
        // Reset form
        form.reset();
        nameInput.classList.remove('is-invalid');
        document.getElementById('newFolderNameError').textContent = '';
        
        // Set current values
        document.getElementById('renameFolderId').value = folderId;
        nameInput.value = currentName;
        currentNameSpan.textContent = currentName;
        
        // Show modal and focus on name input
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        modal.addEventListener('shown.bs.modal', function() {
            nameInput.select();
        }, { once: true });
    }
    
    function showDeleteConfirmModal(type, id, name, additionalInfo = '') {
        const modal = document.getElementById('deleteConfirmModal');
        const content = document.getElementById('deleteConfirmContent');
        const submitBtn = document.getElementById('deleteConfirmSubmit');
        
        // Set content based on type
        let contentHTML = '';
        if (type === 'folder') {
            contentHTML = `
                <p><strong>削除するフォルダ:</strong></p>
                <div class="alert alert-light py-2 mb-3">
                    <i class="fas fa-folder me-2"></i>${escapeHtml(name)}
                </div>
                <p class="text-danger">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    このフォルダとその中のすべてのファイルとサブフォルダが完全に削除されます。
                </p>
                ${additionalInfo ? `<p class="text-muted small">${additionalInfo}</p>` : ''}
            `;
        } else if (type === 'file') {
            contentHTML = `
                <p><strong>削除するファイル:</strong></p>
                <div class="alert alert-light py-2 mb-3">
                    <i class="fas fa-file me-2"></i>${escapeHtml(name)}
                </div>
                <p class="text-danger">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    このファイルが完全に削除されます。
                </p>
                ${additionalInfo ? `<p class="text-muted small">${additionalInfo}</p>` : ''}
            `;
        }
        
        content.innerHTML = contentHTML;
        
        // Set up submit button
        submitBtn.onclick = function() {
            performDelete(type, id, name);
            bootstrap.Modal.getInstance(modal).hide();
        };
        
        // Show modal
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    }
    
    async function performDelete(type, id, name) {
        try {
            const endpoint = type === 'file'
                ? `/facilities/${facilityId}/documents/files/${id}`
                : `/facilities/${facilityId}/documents/folders/${id}`;

            const response = await fetch(endpoint, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const result = await response.json();

            if (result.success) {
                showSuccess(result.message);
                loadFolderContents();
            } else {
                showError(result.message || '削除に失敗しました。');
            }
        } catch (error) {
            console.error('Delete failed:', error);
            showError('削除中にエラーが発生しました。');
        }
    }
    
    // Folder creation handler
    document.getElementById('createFolderSubmit').addEventListener('click', async function() {
        const form = document.getElementById('createFolderForm');
        const nameInput = document.getElementById('folderName');
        const parentId = document.getElementById('parentFolderId').value;
        const submitBtn = this;
        
        // Validate folder name
        const folderName = nameInput.value.trim();
        if (!folderName) {
            nameInput.classList.add('is-invalid');
            document.getElementById('folderNameError').textContent = 'フォルダ名を入力してください。';
            return;
        }
        
        // Check for invalid characters
        const invalidChars = /[\\/:*?"<>|]/;
        if (invalidChars.test(folderName)) {
            nameInput.classList.add('is-invalid');
            document.getElementById('folderNameError').textContent = '特殊文字（\\ / : * ? " < > |）は使用できません。';
            return;
        }
        
        // Disable submit button
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>作成中...';
        
        try {
            const response = await fetch(`/facilities/${facilityId}/documents/folders`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    name: folderName,
                    parent_id: parentId || null
                })
            });

            const result = await response.json();

            if (result.success) {
                showSuccess(result.message);
                bootstrap.Modal.getInstance(document.getElementById('createFolderModal')).hide();
                loadFolderContents();
            } else {
                if (result.errors && result.errors.name) {
                    nameInput.classList.add('is-invalid');
                    document.getElementById('folderNameError').textContent = result.errors.name[0];
                } else {
                    showError(result.message || 'フォルダの作成に失敗しました。');
                }
            }
        } catch (error) {
            console.error('Folder creation failed:', error);
            showError('フォルダの作成中にエラーが発生しました。');
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    
    // Folder rename handler
    document.getElementById('renameFolderSubmit').addEventListener('click', async function() {
        const form = document.getElementById('renameFolderForm');
        const nameInput = document.getElementById('newFolderName');
        const folderId = document.getElementById('renameFolderId').value;
        const submitBtn = this;
        
        // Validate folder name
        const folderName = nameInput.value.trim();
        if (!folderName) {
            nameInput.classList.add('is-invalid');
            document.getElementById('newFolderNameError').textContent = 'フォルダ名を入力してください。';
            return;
        }
        
        // Check for invalid characters
        const invalidChars = /[\\/:*?"<>|]/;
        if (invalidChars.test(folderName)) {
            nameInput.classList.add('is-invalid');
            document.getElementById('newFolderNameError').textContent = '特殊文字（\\ / : * ? " < > |）は使用できません。';
            return;
        }
        
        // Disable submit button
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>変更中...';
        
        try {
            const response = await fetch(`/facilities/${facilityId}/documents/folders/${folderId}`, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    name: folderName
                })
            });

            const result = await response.json();

            if (result.success) {
                showSuccess(result.message);
                bootstrap.Modal.getInstance(document.getElementById('renameFolderModal')).hide();
                loadFolderContents();
            } else {
                if (result.errors && result.errors.name) {
                    nameInput.classList.add('is-invalid');
                    document.getElementById('newFolderNameError').textContent = result.errors.name[0];
                } else {
                    showError(result.message || 'フォルダ名の変更に失敗しました。');
                }
            }
        } catch (error) {
            console.error('Folder rename failed:', error);
            showError('フォルダ名の変更中にエラーが発生しました。');
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    
    // Clear validation errors when user starts typing
    document.getElementById('folderName').addEventListener('input', function() {
        this.classList.remove('is-invalid');
        document.getElementById('folderNameError').textContent = '';
    });
    
    document.getElementById('newFolderName').addEventListener('input', function() {
        this.classList.remove('is-invalid');
        document.getElementById('newFolderNameError').textContent = '';
    });
    
    // Make functions available globally for context menu
    window.showCreateFolderModal = showCreateFolderModal;
    window.showRenameFolderModal = showRenameFolderModal;
    window.showDeleteConfirmModal = showDeleteConfirmModal;
    
    // Keyboard navigation hint management
    let keyboardHintTimeout;
    const keyboardHint = document.getElementById('keyboardHint');
    
    function showKeyboardHint() {
        if (keyboardHint) {
            keyboardHint.classList.add('show');
            clearTimeout(keyboardHintTimeout);
            keyboardHintTimeout = setTimeout(() => {
                keyboardHint.classList.remove('show');
            }, 3000);
        }
    }
    
    // Show keyboard hint when user focuses on folder/file items
    document.addEventListener('focusin', function(e) {
        if (e.target.classList.contains('folder-row') || 
            e.target.classList.contains('folder-card') ||
            e.target.classList.contains('file-row') ||
            e.target.classList.contains('file-card')) {
            showKeyboardHint();
        }
    });
    
    // Show keyboard hint on first tab key press
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            showKeyboardHint();
        }
    }, { once: true });
    
    window.showFolderMenu = function(folderId) {
        // TODO: Implement folder context menu
        console.log('Show folder menu for:', folderId);
    };
    
    window.showFileMenu = function(fileId) {
        // TODO: Implement file context menu
        console.log('Show file menu for:', fileId);
    };
    
    window.previewFile = function(fileId) {
        // TODO: Implement file preview
        console.log('Preview file:', fileId);
    };
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatDate(dateString) {
        return new Date(dateString).toLocaleString('ja-JP');
    }
    
    function updateFilterOptions(availableFileTypes) {
        const filterSelect = document.getElementById('filterType');
        const currentValue = filterSelect.value;
        
        // Clear existing options except "all"
        filterSelect.innerHTML = '<option value="all">すべてのファイル</option>';
        
        // Add available file types
        availableFileTypes.forEach(fileType => {
            const option = document.createElement('option');
            option.value = fileType.extension;
            option.textContent = fileType.label;
            if (fileType.extension === currentValue) {
                option.selected = true;
            }
            filterSelect.appendChild(option);
        });
    }
    
    function updateSortState(sortOptions) {
        // Update UI elements to reflect current sort state
        if (sortOptions) {
            document.getElementById('sortBy').value = sortOptions.sort_by || 'name';
            document.getElementById('sortDirection').value = sortOptions.sort_direction || 'asc';
            document.getElementById('filterType').value = sortOptions.filter_type || 'all';
            document.getElementById('searchInput').value = sortOptions.search || '';
            
            // Update view mode radio buttons
            const viewModeRadio = document.querySelector(`input[name="viewMode"][value="${sortOptions.view_mode || 'list'}"]`);
            if (viewModeRadio) {
                viewModeRadio.checked = true;
            }
        }
    }
    
    function showError(message) {
        const container = document.getElementById('documentList');
        container.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${escapeHtml(message)}
            </div>
        `;
    }
    
    function showSuccess(message) {
        // Create a temporary success message
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ${escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert at the top of the card body
        const cardBody = document.querySelector('.card-body');
        cardBody.insertBefore(alertDiv, cardBody.firstChild);
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
});
</script>
@endpush