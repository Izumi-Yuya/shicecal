{{-- 統合されたドキュメント管理システム --}}
<div class="document-management"
     data-facility-id="{{ $facility->id }}">
    
    {{-- ツールバー --}}
    <div class="document-toolbar mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2">
                    {{-- フォルダ作成ボタン --}}
                    @can('create', [App\Models\DocumentFolder::class, $facility])
                        <button type="button" id="create-folder-btn" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#create-folder-modal">
                            <i class="fas fa-folder-plus me-1"></i> 新しいフォルダ
                        </button>
                    @endcan
                    
                    {{-- ファイルアップロードボタン --}}
                    @can('create', [App\Models\DocumentFile::class, $facility])
                        <button type="button" id="upload-file-btn" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#upload-file-modal">
                            <i class="fas fa-upload me-1"></i> ファイルアップロード
                        </button>
                    @endcan
                    
                    {{-- 表示モード切替 --}}
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="view-mode" id="list-view" value="list" checked>
                        <label class="btn btn-outline-secondary" for="list-view">
                            <i class="fas fa-list"></i>
                        </label>
                        
                        <input type="radio" class="btn-check" name="view-mode" id="grid-view" value="grid">
                        <label class="btn btn-outline-secondary" for="grid-view">
                            <i class="fas fa-th"></i>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 justify-content-end">
                    {{-- 検索 --}}
                    <div class="input-group input-group-sm" style="max-width: 200px;">
                        <input type="text" class="form-control" id="search-input" placeholder="ファイル・フォルダを検索...">
                        <button class="btn btn-outline-secondary" type="button" id="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    {{-- フィルター --}}
                    <select class="form-select form-select-sm" id="file-type-filter" style="max-width: 120px;">
                        <option value="all">すべて</option>
                        <option value="pdf">PDF</option>
                        <option value="image">画像</option>
                        <option value="document">文書</option>
                    </select>
                    
                    {{-- ソート --}}
                    <select class="form-select form-select-sm" id="sort-select" style="max-width: 120px;">
                        <option value="name-asc">名前 ↑</option>
                        <option value="name-desc">名前 ↓</option>
                        <option value="date-desc">作成日 ↓</option>
                        <option value="date-asc">作成日 ↑</option>
                        <option value="size-desc">サイズ ↓</option>
                        <option value="size-asc">サイズ ↑</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    {{-- パンくずナビゲーション --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0" id="breadcrumb-nav">
            <li class="breadcrumb-item">
                <a href="#" data-folder-id="" class="breadcrumb-link">
                    <i class="fas fa-home me-1"></i>ルート
                </a>
            </li>
        </ol>
    </nav>
    
    {{-- 統計情報 --}}
    <div class="document-stats mb-3" id="document-stats" style="display: none;">
        <div class="row">
            <div class="col-auto">
                <small class="text-muted">
                    <i class="fas fa-folder me-1"></i>
                    <span id="folder-count">0</span> フォルダ
                </small>
            </div>
            <div class="col-auto">
                <small class="text-muted">
                    <i class="fas fa-file me-1"></i>
                    <span id="file-count">0</span> ファイル
                </small>
            </div>
            <div class="col-auto">
                <small class="text-muted">
                    <i class="fas fa-hdd me-1"></i>
                    <span id="total-size">0 B</span>
                </small>
            </div>
        </div>
    </div>
    
    {{-- メインコンテンツエリア --}}
    <div class="document-content">
        {{-- ローディング表示 --}}
        <div id="loading-indicator" class="text-center py-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">読み込み中...</span>
            </div>
            <div class="mt-2">
                <small class="text-muted">ドキュメントを読み込んでいます...</small>
            </div>
        </div>
        
        {{-- エラー表示 --}}
        <div id="error-message" class="alert alert-danger" style="display: none;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="error-text"></span>
        </div>
        
        {{-- 空の状態 --}}
        <div id="empty-state" class="text-center py-5" style="display: none;">
            <div class="mb-4">
                <i class="fas fa-folder-open fa-4x text-muted"></i>
            </div>
            <h5 class="text-muted mb-3">このフォルダは空です</h5>
            <p class="text-muted mb-4">
                ファイルをアップロードするか、新しいフォルダを作成してください。
            </p>
            @can('create', [App\Models\DocumentFile::class, $facility])
                <button type="button" class="btn btn-primary" id="empty-upload-btn" data-bs-toggle="modal" data-bs-target="#upload-file-modal">
                    <i class="fas fa-upload me-1"></i> ファイルアップロード
                </button>
            @endcan
        </div>
        
        {{-- ドキュメント一覧 --}}
        <div id="document-list" class="document-list">
            {{-- リスト表示 --}}
            <div id="list-view-content" class="list-view">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="select-all">
                                </th>
                                <th>名前</th>
                                <th style="width: 100px;">サイズ</th>
                                <th style="width: 120px;">更新日時</th>
                                <th style="width: 100px;">作成者</th>
                                <th style="width: 80px;">操作</th>
                            </tr>
                        </thead>
                        <tbody id="document-table-body">
                            {{-- 動的に生成 --}}
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- グリッド表示 --}}
            <div id="grid-view-content" class="grid-view" style="display: none;">
                <div class="row" id="document-grid-container">
                    {{-- 動的に生成 --}}
                </div>
            </div>
        </div>
        
        {{-- ページネーション --}}
        <div id="pagination-container" class="d-flex justify-content-center mt-4">
            {{-- 動的に生成 --}}
        </div>
    </div>
</div>

{{-- フォルダ作成モーダル --}}
<div class="modal" id="create-folder-modal" tabindex="-1" aria-labelledby="create-folder-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="create-folder-form" method="POST">
                @csrf
                <input type="hidden" id="parent-folder-id" name="parent_id" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="create-folder-modal-title">新しいフォルダ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="folder-name" class="form-label">フォルダ名</label>
                        <input type="text" class="form-control" id="folder-name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">作成</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ファイルアップロードモーダル --}}
<div class="modal" id="upload-file-modal" tabindex="-1" aria-labelledby="upload-file-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="upload-file-form" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="upload-folder-id" name="folder_id" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="upload-file-modal-title">ファイルアップロード</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file-input" class="form-label">ファイル選択</label>
                        <input type="file" class="form-control" id="file-input" name="files[]" multiple>
                        <div class="form-text">複数のファイルを同時にアップロードできます（最大10MBまで）。</div>
                    </div>
                    
                    {{-- 選択されたファイル一覧 --}}
                    <div id="file-list" style="display: none;">
                        <h6>選択されたファイル:</h6>
                        <div id="selected-files" class="border rounded p-2 bg-light">
                            {{-- 動的に生成 --}}
                        </div>
                    </div>
                    
                    {{-- アップロード進行状況 --}}
                    <div id="upload-progress" style="display: none;">
                        <div class="progress mt-3">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted">アップロード中...</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary" id="upload-submit-btn">アップロード</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 名前変更モーダル --}}
<div class="modal" id="rename-modal" tabindex="-1" aria-labelledby="rename-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rename-modal-title">名前変更</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="new-name" class="form-label">新しい名前</label>
                    <input type="text" class="form-control" id="new-name" placeholder="新しい名前を入力してください">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="save-rename-btn">保存</button>
            </div>
        </div>
    </div>
</div>

{{-- コンテキストメニュー --}}
<div class="context-menu" id="context-menu" style="display: none;">
    <div class="context-menu-item" data-action="rename">
        <i class="fas fa-edit me-2"></i>名前変更
    </div>
    <div class="context-menu-item" data-action="download">
        <i class="fas fa-download me-2"></i>ダウンロード
    </div>
    <div class="context-menu-item" data-action="properties">
        <i class="fas fa-info-circle me-2"></i>プロパティ
    </div>
    <div class="context-menu-divider"></div>
    <div class="context-menu-item text-danger" data-action="delete">
        <i class="fas fa-trash me-2"></i>削除
    </div>
</div>



{{-- 
  統合されたドキュメント管理システム
  - CSSは app.css に統合済み (document-management-unified.css)
  - JavaScriptは app-unified.js で自動初期化
  - 追加のスクリプトやスタイルは不要
--}}