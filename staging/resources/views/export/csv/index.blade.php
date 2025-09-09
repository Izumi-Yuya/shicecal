@extends('layouts.app')

@section('title', 'CSV出力')

@vite(['resources/css/pages/export.css'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-file-csv me-2 text-success"></i>
                    CSV出力
                </h1>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="csvOptionsDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> 出力オプション
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="showExportHistory()">
                                <i class="fas fa-history"></i> 出力履歴
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="showExportSettings()">
                                <i class="fas fa-cog"></i> 出力設定
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="exportTemplate()">
                                <i class="fas fa-download"></i> テンプレートダウンロード
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Progress Bar for Export -->
            <div id="exportProgress" class="mb-4 export-progress">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-cog fa-spin text-primary me-2"></i>
                            <strong>CSV出力処理中...</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" class="export-progress-bar" id="exportProgressBar">
                                0%
                            </div>
                        </div>
                        <small class="text-muted mt-1" id="exportProgressText">処理を開始しています...</small>
                    </div>
                </div>
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
                                <div class="facility-list">
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
                                    <!-- 基本情報項目 -->
                                    <div class="col-12 mb-3">
                                        <h6 class="fw-bold text-primary mb-2">
                                            <i class="fas fa-building me-1"></i>基本情報
                                        </h6>
                                        <div class="row">
                                            @php
                                                $facilityFields = [
                                                    'company_name' => '会社名',
                                                    'office_code' => '事業所コード',
                                                    'designation_number' => '指定番号',
                                                    'facility_name' => '施設名',
                                                    'postal_code' => '郵便番号',
                                                    'address' => '住所',
                                                    'phone_number' => '電話番号',
                                                    'fax_number' => 'FAX番号',
                                                    'status' => 'ステータス',
                                                    'approved_at' => '承認日時',
                                                    'created_at' => '作成日時',
                                                    'updated_at' => '更新日時',
                                                ];
                                            @endphp
                                            @foreach($facilityFields as $field => $label)
                                                @if(isset($availableFields[$field]))
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
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- 土地情報項目 -->
                                    <div class="col-12 mb-3">
                                        <h6 class="fw-bold text-success mb-2">
                                            <i class="fas fa-map-marked-alt me-1"></i>土地情報
                                        </h6>
                                        <div class="row">
                                            @foreach($availableFields as $field => $label)
                                                @if(str_starts_with($field, 'land_'))
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
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
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
                                        <div id="dataPreviewContainer" class="data-preview-container">
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

@vite(['resources/js/modules/export.js'])