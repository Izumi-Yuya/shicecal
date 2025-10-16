@props([
    'facility',
    'category',
    'categoryName' => null,
    'subcategory' => null
])

@php
    $categoryDisplayName = $categoryName ?? ucfirst(str_replace('_', ' ', $category));
    $canEdit = auth()->user()->canEditFacility($facility->id);
    // subcategoryがある場合はユニークなIDを生成
    $uniqueId = $subcategory ? "{$category}_{$subcategory}" : $category;
@endphp

{{-- 動作しているドキュメントタブと同じ構造を使用 --}}
<div class="document-management" data-facility-id="{{ $facility->id }}" data-lifeline-category="{{ $category }}" data-subcategory="{{ $subcategory }}" id="document-management-container-{{ $uniqueId }}">
    
    {{-- ツールバー --}}
    <div class="document-toolbar mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2">
                    {{-- フォルダ作成ボタン --}}
                    @if($canEdit)
                        <button type="button" id="create-folder-btn-{{ $uniqueId }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-folder-plus me-1"></i>新しいフォルダ
                        </button>
                    @endif
                    
                    {{-- ファイルアップロードボタン --}}
                    @if($canEdit)
                        <button type="button" id="upload-file-btn-{{ $uniqueId }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-upload me-1"></i>ファイルアップロード
                        </button>
                    @endif
                    
                    {{-- 表示モード切替 --}}
                    <div class="btn-group btn-group-sm" role="group" aria-label="表示モード選択">
                        <input type="radio" class="btn-check" name="view-mode-{{ $uniqueId }}" id="list-view-{{ $uniqueId }}" value="list" checked>
                        <label class="btn btn-outline-secondary" for="list-view-{{ $uniqueId }}">
                            <i class="fas fa-list" aria-hidden="true"></i>
                            <span class="visually-hidden">リスト表示</span>
                        </label>
                        
                        <input type="radio" class="btn-check" name="view-mode-{{ $uniqueId }}" id="grid-view-{{ $uniqueId }}" value="grid">
                        <label class="btn btn-outline-secondary" for="grid-view-{{ $uniqueId }}">
                            <i class="fas fa-th" aria-hidden="true"></i>
                            <span class="visually-hidden">グリッド表示</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 justify-content-end">
                    {{-- 検索 --}}
                    <div class="input-group input-group-sm" style="max-width: 200px;">
                        <label for="search-input-{{ $uniqueId }}" class="visually-hidden">ファイル・フォルダを検索</label>
                        <input type="text" class="form-control" id="search-input-{{ $uniqueId }}" placeholder="ファイル・フォルダを検索..." aria-label="ファイル・フォルダを検索">
                        <button class="btn btn-outline-secondary" type="button" id="search-btn-{{ $uniqueId }}" aria-label="検索実行">
                            <i class="fas fa-search" aria-hidden="true"></i>
                        </button>
                    </div>
                    
                    {{-- フィルター（PDFのみなので非表示） --}}
                    {{-- 
                    <label for="file-type-filter-{{ $uniqueId }}" class="visually-hidden">ファイルタイプでフィルター</label>
                    <select class="form-select form-select-sm" id="file-type-filter-{{ $uniqueId }}" style="max-width: 120px;" aria-label="ファイルタイプでフィルター">
                        <option value="">すべて</option>
                        <option value="pdf">PDF</option>
                    </select>
                    --}}
                </div>
            </div>
        </div>
    </div>

    {{-- パンくずナビゲーション --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb" id="breadcrumb-nav-{{ $uniqueId }}">
            <li class="breadcrumb-item active">{{ $categoryDisplayName }}</li>
        </ol>
    </nav>

    {{-- ドキュメント一覧エリア --}}
    <div class="document-list-container" style="min-height: 400px;">
        {{-- ローディング表示 --}}
        <div class="text-center py-5" id="loading-indicator-{{ $uniqueId }}">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">読み込み中...</span>
            </div>
            <p class="mt-2 text-muted">ドキュメントを読み込んでいます...</p>
        </div>

        {{-- エラー表示 --}}
        <div class="alert alert-danger d-none" id="error-message-{{ $uniqueId }}">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="error-text-{{ $uniqueId }}"></span>
        </div>

        {{-- 空の状態 --}}
        <div class="text-center py-5 d-none" id="empty-state-{{ $uniqueId }}">
            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">ドキュメントがありません</h5>
            <p class="text-muted mb-3">
                ファイルをアップロードするか、フォルダを作成してドキュメントを整理しましょう。
            </p>
            @if($canEdit)
                <button type="button" class="btn btn-primary" id="empty-upload-btn-{{ $uniqueId }}">
                    <i class="fas fa-upload me-1"></i>ファイルアップロード
                </button>
            @endif
        </div>

        {{-- ドキュメント一覧 --}}
        <div id="document-list-{{ $uniqueId }}" class="d-none">
            {{-- リスト表示 --}}
            <div id="document-list-view-{{ $uniqueId }}" class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>名前</th>
                            <th style="width: 100px;">サイズ</th>
                            <th style="width: 150px;">更新日時</th>
                            <th style="width: 120px;">作成者</th>
                            <th style="width: 100px;">操作</th>
                        </tr>
                    </thead>
                    <tbody id="document-list-body-{{ $uniqueId }}">
                        {{-- 動的に生成される --}}
                    </tbody>
                </table>
            </div>
            
            {{-- グリッド表示 --}}
            <div id="document-grid-{{ $uniqueId }}" class="d-none">
                <div class="row" id="document-grid-body-{{ $uniqueId }}">
                    {{-- 動的に生成される --}}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- フォルダ作成モーダル（シンプル版） --}}
<div class="modal fade" id="create-folder-modal-{{ $uniqueId }}" tabindex="-1" aria-labelledby="create-folder-modal-title-{{ $uniqueId }}" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="create-folder-form-{{ $uniqueId }}" action="/facilities/{{ $facility->id }}/lifeline-documents/{{ $category }}/folders" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="create-folder-modal-title-{{ $uniqueId }}">新しいフォルダ - {{ $categoryDisplayName }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="folder-name-{{ $uniqueId }}" class="form-label">フォルダ名</label>
                        <input type="text" class="form-control" id="folder-name-{{ $uniqueId }}" name="name" required autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-folder-plus me-1"></i>作成
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ファイルアップロードモーダル（シンプル版） --}}
<div class="modal fade" id="upload-file-modal-{{ $uniqueId }}" tabindex="-1" aria-labelledby="upload-file-modal-title-{{ $uniqueId }}" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="upload-file-form-{{ $uniqueId }}" action="/facilities/{{ $facility->id }}/lifeline-documents/{{ $category }}/upload" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="upload-file-modal-title-{{ $uniqueId }}">ファイルアップロード - {{ $categoryDisplayName }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file-input-{{ $uniqueId }}" class="form-label">ファイル選択（PDFのみ）</label>
                        <input type="file" class="form-control" id="file-input-{{ $uniqueId }}" name="files[]" accept=".pdf" multiple required>
                        <div class="form-text">複数のPDFファイルを同時にアップロードできます（最大10MB/ファイル）。</div>
                    </div>
                    
                    {{-- 選択されたファイル一覧 --}}
                    <div id="file-list-{{ $uniqueId }}" style="display: none;">
                        <h6>選択されたファイル:</h6>
                        <div id="selected-files-{{ $uniqueId }}" class="border rounded p-2 bg-light"></div>
                    </div>
                    
                    {{-- アップロード進行状況 --}}
                    <div id="upload-progress-{{ $uniqueId }}" style="display: none;">
                        <div class="progress mt-3">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted">アップロード中...</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">アップロード</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- コンテキストメニュー --}}
<div class="context-menu" id="context-menu-{{ $uniqueId }}" style="display: none;">
    <div class="context-menu-item" data-action="open" data-folder-only="true">
        <i class="fas fa-folder-open me-2"></i>開く
    </div>
    @if($canEdit)
        <div class="context-menu-item" data-action="create-subfolder" data-folder-only="true">
            <i class="fas fa-folder-plus me-2"></i>サブフォルダを作成
        </div>
        <div class="context-menu-divider" data-folder-only="true"></div>
        <div class="context-menu-item" data-action="rename">
            <i class="fas fa-edit me-2"></i>名前変更
        </div>
    @endif
    <div class="context-menu-item" data-action="download" data-file-only="true">
        <i class="fas fa-download me-2"></i>ダウンロード
    </div>
    @if($canEdit)
        <div class="context-menu-item" data-action="move">
            <i class="fas fa-arrows-alt me-2"></i>移動
        </div>
    @endif
    <div class="context-menu-item" data-action="properties">
        <i class="fas fa-info-circle me-2"></i>プロパティ
    </div>
    @if($canEdit)
        <div class="context-menu-divider"></div>
        <div class="context-menu-item text-danger" data-action="delete">
            <i class="fas fa-trash me-2"></i>削除
        </div>
    @endif
</div>

{{-- 名前変更モーダル --}}
@if($canEdit)
<div class="modal fade" id="rename-modal-{{ $uniqueId }}" tabindex="-1" aria-labelledby="rename-modal-title-{{ $uniqueId }}" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="rename-form-{{ $uniqueId }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="rename-modal-title-{{ $uniqueId }}">名前変更</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rename-input-{{ $uniqueId }}" class="form-label">新しい名前</label>
                        <input type="text" class="form-control" id="rename-input-{{ $uniqueId }}" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">変更</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- プロパティモーダル --}}
<div class="modal fade" id="properties-modal-{{ $uniqueId }}" tabindex="-1" aria-labelledby="properties-modal-title-{{ $uniqueId }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="properties-modal-title-{{ $uniqueId }}">プロパティ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="properties-content-{{ $uniqueId }}">
                    {{-- 動的に生成される --}}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

{{-- 初期化スクリプト --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uniqueId = '{{ $uniqueId }}';
    
    // ===== Modal hoisting: Move all modals to body to avoid z-index issues =====
    const modalIds = [
        'create-folder-modal-' + uniqueId,
        'upload-file-modal-' + uniqueId,
        'rename-modal-' + uniqueId,
        'properties-modal-' + uniqueId
    ];
    
    modalIds.forEach(function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal && modal.parentElement !== document.body) {
            console.log('[LifelineDoc] Hoisting modal to body:', modalId);
            document.body.appendChild(modal);
        }
    });
    
    // LifelineDocumentManagerのインスタンスを作成
    if (typeof LifelineDocumentManager !== 'undefined') {
        const facilityId = {{ $facility->id }};
        const category = '{{ $category }}';
        const managerKey = 'lifelineDocManager_' + uniqueId;
        
        // 既存のインスタンスがあればスキップ
        if (window[managerKey]) {
            console.log(`[LifelineDoc] Manager for ${uniqueId} already exists, skipping initialization`);
            return;
        }
        
        console.log(`[LifelineDoc] Initializing LifelineDocumentManager for category: ${category}, uniqueId: ${uniqueId}`);
        
        // インスタンスを作成（コンストラクタ内でグローバルに登録される）
        new LifelineDocumentManager(facilityId, category, uniqueId);
        
        console.log(`[LifelineDoc] Manager registered as window.${managerKey}`);
    } else {
        console.error('[LifelineDoc] LifelineDocumentManager class not found');
    }
});
</script>
@endpush