@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- 固定ヘッダーカード -->
            <div class="facility-header-card card sticky-top">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="facility-icon me-3">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 facility-name">{{ $facility->facility_name }}</h5>
                                    <div class="facility-meta">
                                        <span class="badge bg-primary me-2">{{ $facility->office_code }}</span>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            更新日時: {{ $facility->updated_at->format('Y年m月d日 H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="facility-actions">
                                <a href="{{ route('facilities.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> 施設一覧に戻る
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- アラート表示エリア -->
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

            <!-- タブナビゲーション -->
            <div class="facility-detail-container">
                <div class="tab-navigation mb-3 sticky-top bg-white" style="z-index: 1019; top: 90px;">
                    <ul class="nav nav-tabs" id="facilityTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab" aria-controls="basic-info" aria-selected="true">
                                <i class="fas fa-info-circle me-2"></i>基本
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="land-tab" data-bs-toggle="tab" data-bs-target="#land-info" type="button" role="tab" aria-controls="land-info" aria-selected="false">
                                <i class="fas fa-map me-2"></i>土地
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="building-tab" data-bs-toggle="tab" data-bs-target="#building-info" type="button" role="tab" aria-controls="building-info" aria-selected="false">
                                <i class="fas fa-building me-2"></i>建物
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="lifeline-tab" data-bs-toggle="tab" data-bs-target="#lifeline-equipment" type="button" role="tab" aria-controls="lifeline-equipment" aria-selected="false">
                                <i class="fas fa-plug me-2"></i>ライフライン設備
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-disaster-tab" data-bs-toggle="tab" data-bs-target="#security-disaster" type="button" role="tab" aria-controls="security-disaster" aria-selected="false">
                                <i class="fas fa-shield-alt me-2"></i>防犯・防災
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contracts-tab" data-bs-toggle="tab" data-bs-target="#contracts" type="button" role="tab" aria-controls="contracts" aria-selected="false">
                                <i class="fas fa-file-contract me-2"></i>契約書
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="drawings-tab" data-bs-toggle="tab" data-bs-target="#drawings" type="button" role="tab" aria-controls="drawings" aria-selected="false">
                                <i class="fas fa-drafting-compass me-2"></i>図面
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="repair-history-tab" data-bs-toggle="tab" data-bs-target="#repair-history" type="button" role="tab" aria-controls="repair-history" aria-selected="false">
                                <i class="fas fa-wrench me-2"></i>修繕履歴
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="false">
                                <i class="fas fa-folder me-2"></i>ドキュメント
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="tab-content" id="facilityTabContent">
                    <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-tab">
                        <!-- 基本情報タブヘッダー -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                            </h4>
                            @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                                <a href="{{ route('facilities.edit-basic-info', $facility) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>編集
                                </a>
                            @endif
                        </div>
                        
                        <!-- Basic Info Display Card -->
                        @include('facilities.basic-info.partials.display-card', ['facility' => $facility])
                    </div>
                    
                    <div class="tab-pane fade" id="land-info" role="tabpanel" aria-labelledby="land-tab" data-lazy-load="true">
                        <!-- 土地情報タブヘッダー -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-map text-primary me-2"></i>土地情報
                            </h4>
                            @if(auth()->user()->canEditLandInfo())
                                <a href="{{ route('facilities.land-info.edit', $facility) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>
                                    @if(isset($landInfo) && $landInfo)
                                        編集
                                    @else
                                        登録
                                    @endif
                                </a>
                            @endif
                        </div>
                        
                        <div class="land-info-loading d-none">
                            <div class="d-flex justify-content-center p-4">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">読み込み中...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="land-info-content">
                            @if(isset($landInfo) && $landInfo)
                                <!-- 土地情報詳細表示 -->
                                @include('facilities.land-info.partials.display-card', ['landInfo' => $landInfo, 'landInfoFileData' => $landInfoFileData ?? []])
                                
                                <!-- 土地情報コメントセクション -->
                                <div class="comments-section" data-section="land_basic" style="display: none;">
                                    <div class="card mt-3">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-comments me-2"></i>土地情報に関するコメント
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <!-- コメント表示エリア -->
                                            <div class="comments-list" data-section="land_basic">
                                                <!-- コメントはJavaScriptで動的に読み込まれます -->
                                            </div>
                                            
                                            <!-- 新規コメント投稿フォーム -->
                                            @if(auth()->user()->canEdit())
                                            <form class="comment-form mt-3" data-section="land_basic">
                                                @csrf
                                                <div class="mb-3">
                                                    <textarea name="content" class="form-control" rows="3" 
                                                              placeholder="土地情報に関するコメントを入力してください..." required></textarea>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <label class="form-label">担当者に割り当て（任意）</label>
                                                        <select name="assigned_to" class="form-select form-select-sm" style="width: auto;">
                                                            <option value="">選択してください</option>
                                                            @foreach(\App\Models\User::where('is_active', true)->orderBy('name')->get() as $user)
                                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-paper-plane me-1"></i>投稿
                                                    </button>
                                                </div>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-4">
                                        <i class="fas fa-map-marked-alt fa-4x text-muted"></i>
                                    </div>
                                    <h4 class="text-muted mb-3">土地情報が登録されていません</h4>
                                    <p class="text-muted mb-4">
                                        この施設の土地情報はまだ登録されていません。<br>
                                        土地情報を登録するには、編集ボタンをクリックしてください。
                                    </p>
                                    @if(auth()->user()->canEditLandInfo())
                                        <a href="{{ route('facilities.land-info.edit', $facility) }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>土地情報を登録
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="building-info" role="tabpanel" aria-labelledby="building-tab">
                        <!-- 建物情報タブヘッダー -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-building text-primary me-2"></i>建物情報
                            </h4>
                            @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                                <a href="{{ route('facilities.building-info.edit', $facility) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>
                                    @if(isset($buildingInfo) && $buildingInfo)
                                        編集
                                    @else
                                        登録
                                    @endif
                                </a>
                            @endif
                        </div>
                        
                        <!-- Building Info Display Card -->
                        @include('facilities.building-info.partials.display-card', ['facility' => $facility, 'buildingInfo' => $buildingInfo])
                    </div>
                    
                    <div class="tab-pane fade" id="lifeline-equipment" role="tabpanel" aria-labelledby="lifeline-tab">
                        <!-- ライフライン設備タブヘッダー -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-plug text-primary me-2"></i>ライフライン設備
                            </h4>

                            <div class="d-flex align-items-center gap-2">
                                @if(auth()->user()->canEditFacility($facility->id))
                                <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'electrical']) }}"
                                    id="lifeline-edit-link"
                                    class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i><span class="d-none d-md-inline">編集</span>
                                </a>
                                @endif

                                <button type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        id="lifeline-documents-toggle">
                                <i class="fas fa-folder-open me-1"></i>
                                <span class="d-none d-md-inline">ドキュメント</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Lifeline Equipment Content -->
                        @include('facilities.lifeline-equipment.index', ['facility' => $facility])
                    </div>
                    
                    <div class="tab-pane fade" id="security-disaster" role="tabpanel" aria-labelledby="security-disaster-tab">
                        <!-- 防犯・防災タブヘッダー -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-shield-alt text-primary me-2"></i>防犯・防災
                            </h4>
                            <div class="d-flex align-items-center gap-2">
                                <!-- ドキュメント管理ボタン -->
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm" 
                                        id="security-disaster-documents-toggle"
                                        title="防犯・防災ドキュメント管理">
                                    <i class="fas fa-folder-open me-1"></i>
                                    <span class="d-none d-md-inline">ドキュメント</span>
                                </button>
                                @if(auth()->user()->canEditFacility($facility->id))
                                    <a href="{{ route('facilities.security-disaster.edit', $facility) }}" 
                                       id="security-disaster-edit-link"
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit me-2"></i>編集
                                    </a>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Security Disaster Content -->
                        @include('facilities.security-disaster.index', ['facility' => $facility])
                    </div>
                    
                    <div class="tab-pane fade" id="contracts" role="tabpanel" aria-labelledby="contracts-tab">
                        <!-- 契約書タブヘッダー -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-file-contract text-primary me-2"></i>契約書
                            </h4>
                            {{-- @if(auth()->user()->canEditFacility($facility->id))
                                <a href="{{ route('facilities.contracts.edit', $facility) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>
                                    @php
                                        $contractService = app(\App\Services\ContractService::class);
                                        $contract = $contractService->getContract($facility);
                                    @endphp
                                    @if($contract)
                                        編集
                                    @else
                                        登録
                                    @endif
                                </a>
                            @endif --}}
                        </div>
                        
                        <!-- Contracts Content -->
                        @php
                            $contractService = app(\App\Services\ContractService::class);
                            $contract = $contractService->getContract($facility);
                            $contractsData = [];
                            
                            if ($contract) {
                                $contractsData = $contractService->formatContractDataForDisplay($contract);
                            }
                        @endphp
                        @include('facilities.contracts.index', ['facility' => $facility, 'contractsData' => $contractsData])
                    </div>
                    
                    <div class="tab-pane fade" id="drawings" role="tabpanel" aria-labelledby="drawings-tab">
                        <!-- 図面タブヘッダー -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-drafting-compass text-primary me-2"></i>図面
                            </h4>
                            @if(auth()->user()->canEditFacility($facility->id))
                                <a href="{{ route('facilities.drawings.edit', $facility) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>
                                    @php
                                        $drawingService = app(\App\Services\DrawingService::class);
                                        $drawing = $drawingService->getDrawing($facility);
                                    @endphp
                                    @if($drawing)
                                        編集
                                    @else
                                        登録
                                    @endif
                                </a>
                            @endif
                        </div>
                        
                        <!-- Drawings Content -->
                        @php
                            $drawingService = app(\App\Services\DrawingService::class);
                            $drawing = $drawingService->getDrawing($facility);
                            $drawingsData = [];
                            
                            if ($drawing) {
                                $drawingsData = $drawingService->formatDrawingDataForDisplay($drawing);
                            }
                        @endphp
                        @include('facilities.drawings.index', ['facility' => $facility, 'drawingsData' => $drawingsData])
                    </div>
                    
                    <div class="tab-pane fade" id="repair-history" role="tabpanel" aria-labelledby="repair-history-tab">
                        <!-- 修繕履歴タブヘッダー -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-wrench text-primary me-2"></i>修繕履歴
                            </h4>
                        </div>
                        
                        <!-- Repair History Content -->
                        @include('facilities.repair-history.index', ['facility' => $facility])
                    </div>
                    
                    <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                        <!-- ドキュメントタブヘッダー -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="fas fa-folder text-primary me-2"></i>ドキュメント管理
                            </h4>
                        </div>
                        
                        <!-- Documents Content -->
                        <div id="document-management-container" class="document-management"
                             data-facility-id="{{ $facility->id }}"
                             data-can-create="@json(auth()->user()->can('create', [App\Models\DocumentFile::class, $facility]))"
                             data-can-update="@json(auth()->user()->can('update', [App\Models\DocumentFile::class, $facility]))"
                             data-can-delete="@json(auth()->user()->can('delete', [App\Models\DocumentFile::class, $facility]))">
                            @include('facilities.documents.index', [
                                'facility' => $facility,
                                'folderContents' => ['folders' => [], 'files' => [], 'sort_options' => []],
                                'availableFileTypes' => []
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@vite(['resources/css/pages/facilities.css', 'resources/css/water-equipment.css', 'resources/css/contract-document-management.css', 'resources/js/modules/facilities.js'])

<style>
/* Lifeline Equipment Styles */
.lifeline-equipment-container {
    margin-top: 1rem;
}

.lifeline-equipment-container .nav-tabs {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 1.5rem;
}

.lifeline-equipment-container .nav-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    background: none;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1rem;
    margin-bottom: -2px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.lifeline-equipment-container .nav-tabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.lifeline-equipment-container .nav-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    background-color: transparent;
}

.lifeline-equipment-container .card {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.4s ease, transform 0.4s ease;
}

.lifeline-equipment-container .card.animate-in {
    opacity: 1;
    transform: translateY(0);
}

#electrical .card-header {
    background: linear-gradient(135deg, #ffc107, #ff8f00);
}

#gas .card-header {
    background: linear-gradient(135deg, #dc3545, #c82333);
}

#water .card-header {
    background: linear-gradient(135deg, #17a2b8, #138496);
}

#elevator .card-header {
    background: linear-gradient(135deg, #6f42c1, #59359a);
}

#hvac-lighting .card-header {
    background: linear-gradient(135deg, #28a745, #1e7e34);
}

#security-disaster .card-header {
    background: linear-gradient(135deg, #fd7e14, #e55a00);
}

#contracts .card-header {
    background: linear-gradient(135deg, #6f42c1, #59359a);
}

/* Security Disaster Styles */
.security-disaster-container {
    margin-top: 1rem;
}

.security-disaster-container .nav-tabs {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 1.5rem;
}

.security-disaster-container .nav-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    background: none;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1rem;
    margin-bottom: -2px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.security-disaster-container .nav-tabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.security-disaster-container .nav-tabs .nav-link.active {
    color: #fd7e14;
    border-bottom-color: #fd7e14;
    background-color: transparent;
}

.security-disaster-edit-container .nav-tabs .nav-link.active {
    color: #fd7e14;
    border-bottom-color: #fd7e14;
    background-color: transparent;
}

/* Security Disaster Subtabs */
.security-disaster-subtabs {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 1.5rem;
}

.security-disaster-subtabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    background: none;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    margin-bottom: -2px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.security-disaster-subtabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.security-disaster-subtabs .nav-link.active {
    color: #fd7e14;
    border-bottom-color: #fd7e14;
    background-color: transparent;
    font-weight: 600;
}

.lifeline-equipment-container .comment-toggle {
    border: 1px solid rgba(255, 255, 255, 0.3);
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    transition: all 0.15s ease;
}

.lifeline-equipment-container .comment-toggle:hover {
    border-color: rgba(255, 255, 255, 0.5);
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}

.lifeline-equipment-container .comment-count {
    margin-left: 0.25rem;
    font-size: 0.75rem;
    background-color: rgba(255, 255, 255, 0.2);
    padding: 0.125rem 0.375rem;
    border-radius: 10px;
}

/* Contracts Styles */
.contracts-container {
    margin-top: 1rem;
}

.contracts-container .nav-tabs {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 1.5rem;
}

.contracts-container .nav-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    background: none;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1rem;
    margin-bottom: -2px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.contracts-container .nav-tabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.contracts-container .nav-tabs .nav-link.active {
    color: #6f42c1;
    border-bottom-color: #6f42c1;
    background-color: transparent;
}

.contracts-container .card {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.4s ease, transform 0.4s ease;
}

.contracts-container .card.animate-in {
    opacity: 1;
    transform: translateY(0);
}

/* Contracts Subtabs */
.contracts-subtabs {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 1.5rem;
}

.contracts-subtabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    background: none;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    margin-bottom: -2px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.contracts-subtabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.contracts-subtabs .nav-link.active {
    color: #6f42c1;
    border-bottom-color: #6f42c1;
    background-color: transparent;
    font-weight: 600;
}

/* Drawings Styles */
.drawings-container {
    margin-top: 1rem;
}

.drawings-container .card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.drawings-container .card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

#drawings .card-header {
    background: linear-gradient(135deg, #6f42c1, #59359a);
    color: white;
    border: none;
}

.drawings-container .table td {
    vertical-align: middle;
    padding: 0.75rem 0.5rem;
}

.drawings-container .table .fw-bold {
    color: #495057;
    font-size: 0.9rem;
}

.drawings-container .table a {
    color: #dc3545;
    transition: color 0.2s ease;
}

.drawings-container .table a:hover {
    color: #c82333;
}


    padding: 1rem;
    margin-bottom: 1.5rem;
    border: 1px solid #dee2e6;
}

.documents-breadcrumb {
    background-color: transparent;
    padding: 0.5rem 0;
    margin-bottom: 1rem;
}

.documents-breadcrumb .breadcrumb {
    margin-bottom: 0;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    padding: 0.75rem 1rem;
}

.documents-breadcrumb .breadcrumb-item a {
    color: #0d6efd;
    text-decoration: none;
}

.documents-breadcrumb .breadcrumb-item a:hover {
    text-decoration: underline;
}

.documents-view-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.documents-view-mode .btn-group .btn {
    border-color: #dee2e6;
}

.documents-view-mode .btn-check:checked + .btn {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.documents-sort-controls {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.documents-list-view .table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.documents-list-view .folder-row:hover,
.documents-list-view .file-row:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.documents-icon-view .folder-card,
.documents-icon-view .file-card {
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid #dee2e6;
}

.documents-icon-view .folder-card:hover,
.documents-icon-view .file-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    border-color: #0d6efd;
}

.documents-icon-view .card-body {
    padding: 1rem 0.5rem;
}

.documents-icon-view .card-text {
    font-size: 0.875rem;
    line-height: 1.2;
    margin-bottom: 0.5rem;
    word-break: break-word;
    height: 2.4em;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.documents-empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.documents-empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}



/* Responsive adjustments */
@media (max-width: 768px) {
    .documents-view-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .documents-sort-controls {
        justify-content: center;
    }
    
    .documents-icon-view .col-4 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}


</style>

<script>
// Pass facility ID to the JavaScript module
window.facilityId = {{ $facility->id }};

// Handle active tab from session and initialize building tab functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize building tab animations
    const buildingTab = document.getElementById('building-tab');
    const buildingPane = document.getElementById('building-info');
    
    if (buildingTab && buildingPane) {
        buildingTab.addEventListener('shown.bs.tab', function() {
            // Trigger animation for building info cards
            const buildingCards = buildingPane.querySelectorAll('.card');
            buildingCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate-in');
            });
        });
    }
    
    // Initialize contracts tab functionality
    const contractsTab = document.getElementById('contracts-tab');
    const contractsPane = document.getElementById('contracts');
    
    if (contractsTab && contractsPane) {
        contractsTab.addEventListener('shown.bs.tab', function() {
            console.log('Contracts tab activated: initializing animations and components');
            
            // Trigger animation for contracts cards
            const contractsCards = contractsPane.querySelectorAll('.card');
            contractsCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate-in');
            });
            
            // Animate cards in the active sub-tab
            setTimeout(() => {
                const activePane = document.querySelector('#contracts .tab-pane.active .card');
                if (activePane) {
                    const cards = activePane.parentElement.querySelectorAll('.card');
                    cards.forEach((card, index) => {
                        card.style.animationDelay = `${index * 0.1}s`;
                        card.classList.add('animate-in');
                    });
                }
            }, 100);
            
            // URLフラグメントまたはセッションに基づいてサブタブを設定
            const hash = window.location.hash;
            let targetSubTab = 'others'; // デフォルト
            
            // URLフラグメントから判定
            if (hash === '#contracts-meal-service') {
                targetSubTab = 'meal-service';
            } else if (hash === '#contracts-parking') {
                targetSubTab = 'parking';
            } else if (hash === '#contracts' || hash === '#contracts-others') {
                targetSubTab = 'others';
            }
            
            // セッションからのサブタブ情報があれば優先
            @if(session('activeSubTab'))
                const sessionSubTab = '{{ session('activeSubTab') }}';
                if (sessionSubTab && ['others', 'meal-service', 'parking'].includes(sessionSubTab)) {
                    targetSubTab = sessionSubTab;
                    console.log('Using session sub-tab:', sessionSubTab);
                }
            @endif
            
            setTimeout(() => {
                console.log('Activating contracts sub-tab:', targetSubTab);
                activateContractsSubTab(targetSubTab);
            }, 100);
        });
    }
    
    // Initialize documents tab functionality
    const documentsTab = document.getElementById('documents-tab');
    const documentsPane = document.getElementById('documents');
    let documentsLoaded = false;
    
    if (documentsTab && documentsPane) {
        documentsTab.addEventListener('shown.bs.tab', function() {
            if (!documentsLoaded) {
                // ドキュメント管理は documents/index.blade.php で処理
                documentsLoaded = true;
            }
        });
    }
    
    // URLフラグメントに基づいてタブとサブタブを自動的に開く
    function handleUrlFragment() {
        const hash = window.location.hash;
        if (hash.startsWith('#contracts')) {
            // 契約書タブを開く
            const contractsTab = document.getElementById('contracts-tab');
            const contractsPane = document.getElementById('contracts');
            
            if (contractsTab && contractsPane) {
                // 他のタブを非アクティブにする
                document.querySelectorAll('#facilityTabs .nav-link.active').forEach(tab => {
                    tab.classList.remove('active');
                    tab.setAttribute('aria-selected', 'false');
                });
                document.querySelectorAll('#facilityTabContent .tab-pane.active').forEach(pane => {
                    pane.classList.remove('active', 'show');
                });
                
                // 契約書タブをアクティブにする
                contractsTab.classList.add('active');
                contractsTab.setAttribute('aria-selected', 'true');
                contractsPane.classList.add('active', 'show');
                
                // サブタブの処理
                if (hash === '#contracts-meal-service') {
                    setTimeout(() => activateContractsSubTab('meal-service'), 100);
                } else if (hash === '#contracts-parking') {
                    setTimeout(() => activateContractsSubTab('parking'), 100);
                } else {
                    setTimeout(() => activateContractsSubTab('others'), 100);
                }
            }
        }
    }
    
    // 契約書のサブタブを切り替える関数
    function activateContractsSubTab(subTabName) {
        const subTabButton = document.getElementById(subTabName + '-tab');
        const subTabPane = document.getElementById(subTabName);
        
        console.log('Activating contracts sub-tab:', {
            subTabName: subTabName,
            subTabButtonId: subTabName + '-tab',
            subTabPaneId: subTabName,
            subTabButton: subTabButton,
            subTabPane: subTabPane
        });
        
        if (subTabButton && subTabPane) {
            // 現在のアクティブサブタブを非アクティブにする
            document.querySelectorAll('#contractsTabs .nav-link.active').forEach(tab => {
                tab.classList.remove('active');
                tab.setAttribute('aria-selected', 'false');
            });
            document.querySelectorAll('#contractsTabContent .tab-pane.active').forEach(pane => {
                pane.classList.remove('active', 'show');
            });
            
            // 指定されたサブタブをアクティブにする
            subTabButton.classList.add('active');
            subTabButton.setAttribute('aria-selected', 'true');
            subTabPane.classList.add('active', 'show');
            
            // Bootstrapのタブイベントを発火
            try {
                const subTabEvent = new bootstrap.Tab(subTabButton);
                subTabEvent.show();
            } catch (e) {
                console.warn('Bootstrap tab event failed:', e);
            }
            
            console.log('Contracts sub-tab activated successfully:', subTabName);
        } else {
            console.error('Contracts sub-tab elements not found:', {
                subTabName: subTabName,
                subTabButtonId: subTabName + '-tab',
                subTabPaneId: subTabName,
                availableButtons: Array.from(document.querySelectorAll('#contractsTabs .nav-link')).map(btn => btn.id),
                availablePanes: Array.from(document.querySelectorAll('#contractsTabContent .tab-pane')).map(pane => pane.id)
            });
        }
    }
    
    // ページ読み込み時とハッシュ変更時にフラグメントを処理
    handleUrlFragment();
    window.addEventListener('hashchange', handleUrlFragment);
    
    @if(session('activeTab'))
        const activeTab = '{{ session('activeTab') }}';
        const tabButton = document.getElementById(activeTab + '-tab');
        const tabPane = document.getElementById(activeTab);
        
        console.log('Active tab session:', activeTab);
        console.log('Tab button element:', tabButton);
        console.log('Tab pane element:', tabPane);
        
        if (tabButton && tabPane) {
            // Remove active class from current active tab
            document.querySelectorAll('.nav-link.active').forEach(tab => {
                tab.classList.remove('active');
                tab.setAttribute('aria-selected', 'false');
            });
            document.querySelectorAll('.tab-pane.active').forEach(pane => {
                pane.classList.remove('active', 'show');
            });
            
            // Activate the target tab
            tabButton.classList.add('active');
            tabButton.setAttribute('aria-selected', 'true');
            tabPane.classList.add('active', 'show');
            
            // Trigger Bootstrap tab event
            const tabEvent = new bootstrap.Tab(tabButton);
            tabEvent.show();
            
            // Handle contracts sub-tab activation
            if (activeTab === 'contracts') {
                @if(session('activeSubTab'))
                    const activeSubTab = '{{ session('activeSubTab') }}';
                    console.log('Restoring contracts sub-tab from session:', activeSubTab);
                    setTimeout(() => {
                        activateContractsSubTab(activeSubTab);
                    }, 200); // 少し長めの遅延で確実に実行
                @else
                    // セッションにサブタブ情報がない場合はデフォルトを設定
                    setTimeout(() => {
                        activateContractsSubTab('others');
                    }, 200);
                @endif
            }
            
            console.log('Tab activated successfully:', activeTab);

        } else {
            console.log('Tab elements not found:', {
                activeTab: activeTab,
                tabButtonId: activeTab + '-tab',
                tabPaneId: activeTab,
                tabButton: tabButton,
                tabPane: tabPane
            });
        }
    @endif
    
    // Handle URL fragments for lifeline equipment and repair history tabs
    function handleFragments() {
        if (!window.location.hash) return;
        
        const hash = window.location.hash.substring(1); // Remove #
        const lifelineCategories = ['electrical', 'water', 'gas', 'elevator', 'hvac-lighting'];
        const repairHistoryCategories = ['interior', 'exterior', 'other'];
        
        if (lifelineCategories.includes(hash)) {
            // First activate the lifeline equipment main tab
            const lifelineTab = document.getElementById('lifeline-tab');
            const lifelinePane = document.getElementById('lifeline-equipment');
            
            if (lifelineTab && lifelinePane) {
                // Activate main lifeline tab
                document.querySelectorAll('.nav-tabs .nav-link').forEach(tab => {
                    tab.classList.remove('active');
                    tab.setAttribute('aria-selected', 'false');
                });
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('active', 'show');
                });
                
                lifelineTab.classList.add('active');
                lifelineTab.setAttribute('aria-selected', 'true');
                lifelinePane.classList.add('active', 'show');
                
                // Then activate the specific sub-tab
                setTimeout(() => {
                    const subTabButton = document.getElementById(hash + '-tab');
                    const subTabPane = document.getElementById(hash);
                    
                    if (subTabButton && subTabPane) {
                        // Deactivate all sub-tabs
                        document.querySelectorAll('#lifelineSubTabs .nav-link').forEach(tab => {
                            tab.classList.remove('active');
                            tab.setAttribute('aria-selected', 'false');
                        });
                        document.querySelectorAll('#lifelineSubTabContent .tab-pane').forEach(pane => {
                            pane.classList.remove('active', 'show');
                        });
                        
                        // Activate target sub-tab
                        subTabButton.classList.add('active');
                        subTabButton.setAttribute('aria-selected', 'true');
                        subTabPane.classList.add('active', 'show');
                    }
                }, 100);
            }
        } else if (repairHistoryCategories.includes(hash)) {
            // First activate the repair history main tab
            const repairHistoryTab = document.getElementById('repair-history-tab');
            const repairHistoryPane = document.getElementById('repair-history');
            
            if (repairHistoryTab && repairHistoryPane) {
                // Activate main repair history tab
                document.querySelectorAll('#facilityTabs .nav-link').forEach(tab => {
                    tab.classList.remove('active');
                    tab.setAttribute('aria-selected', 'false');
                });
                document.querySelectorAll('#facilityTabContent .tab-pane').forEach(pane => {
                    pane.classList.remove('active', 'show');
                });
                
                repairHistoryTab.classList.add('active');
                repairHistoryTab.setAttribute('aria-selected', 'true');
                repairHistoryPane.classList.add('active', 'show');
                
                // Then activate the specific repair history sub-tab
                setTimeout(() => {
                    const subTabButton = document.getElementById(hash + '-tab');
                    const subTabPane = document.getElementById(hash);
                    
                    if (subTabButton && subTabPane) {
                        // Deactivate all repair history sub-tabs
                        document.querySelectorAll('#repairHistoryTabs .nav-link').forEach(tab => {
                            tab.classList.remove('active');
                            tab.setAttribute('aria-selected', 'false');
                        });
                        document.querySelectorAll('#repairHistoryTabContent .tab-pane').forEach(pane => {
                            pane.classList.remove('active', 'show');
                        });
                        
                        // Activate target sub-tab
                        subTabButton.classList.add('active');
                        subTabButton.setAttribute('aria-selected', 'true');
                        subTabPane.classList.add('active', 'show');
                    }
                }, 100);
            }
        }
    }
    
    // Handle fragment on page load only
    handleFragments();
    
    // Handle fragment changes
    window.addEventListener('hashchange', handleFragments);
    
    // Ensure Bootstrap tabs work correctly by preventing interference
    document.querySelectorAll('#facilityTabs button[data-bs-toggle="tab"]').forEach(button => {
        button.addEventListener('shown.bs.tab', function (event) {
            // Tab has been shown, no need to do anything special
            console.log('Tab shown:', event.target.id);
        });
    });

    // Lifeline Equipment Tab Functionality is handled by lifeline-equipment.js module

    // Additional contracts tab functionality is handled above in the main contracts tab section

    // Handle contracts sub-tab switching
    const contractsSubTabs = document.querySelectorAll('#contractsTabs .nav-link');
    contractsSubTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            const targetId = event.target.getAttribute('data-bs-target');
            console.log(`Switched to ${targetId.replace('#', '')} Contracts sub-tab`);
            
            // Animate cards in the newly active tab
            setTimeout(() => {
                const activePane = document.querySelector('#contracts .tab-pane.active .card');
                if (activePane) {
                    const cards = activePane.parentElement.querySelectorAll('.card');
                    cards.forEach((card, index) => {
                        card.style.animationDelay = `${index * 0.1}s`;
                        card.classList.add('animate-in');
                    });
                }
            }, 100);
        });
    });
    
    // Handle comment toggles
    const commentToggles = document.querySelectorAll('#lifeline-equipment .comment-toggle');
    commentToggles.forEach(toggle => {
        toggle.addEventListener('click', function(event) {
            event.preventDefault();
            const section = toggle.getAttribute('data-section');
            console.log(`Toggle comments for section: ${section}`);
            alert(`コメント機能は今後実装予定です。\n対象セクション：${section}`);
        });
    });

    // ライフライン設備タブの動的編集・ドキュメントボタン制御
    function updateLifelineButtons() {
        const editLink = document.getElementById('lifeline-edit-link');
        const documentsToggle = document.getElementById('lifeline-documents-toggle');
        
        if (!editLink || !documentsToggle) return;

        // アクティブなタブを取得
        const activeTab = document.querySelector('#lifelineSubTabs .nav-link.active');
        if (!activeTab) return;

        const tabId = activeTab.id;
        let category = 'electrical'; // デフォルト

        // タブIDに基づいてカテゴリを決定
        switch (tabId) {
            case 'electrical-tab':
                category = 'electrical';
                break;
            case 'water-tab':
                category = 'water';
                break;
            case 'gas-tab':
                category = 'gas';
                break;
            case 'elevator-tab':
                category = 'elevator';
                break;
            case 'hvac-lighting-tab':
                category = 'hvac-lighting';
                break;
        }

        // 編集ボタンのリンクを更新
        const facilityId = {{ $facility->id }};
        editLink.href = `/facilities/${facilityId}/lifeline-equipment/${category}/edit`;

        // ドキュメントボタンの動作を更新（既存の処理をそのまま実行）
        documentsToggle.onclick = function() {
            // 各タブの既存のドキュメントボタンをクリック
            const existingDocumentButton = document.getElementById(category + '-documents-toggle');
            if (existingDocumentButton) {
                existingDocumentButton.click();
            }
        };
    }

    // 初期化時にボタンを更新
    updateLifelineButtons();

    // タブ切り替え時にボタンを更新
    const lifelineTabButtons = document.querySelectorAll('#lifelineSubTabs .nav-link');
    lifelineTabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function() {
            updateLifelineButtons();
        });
    });

    // 防犯・防災設備タブの動的編集・ドキュメントボタン制御
    function updateSecurityDisasterButtons() {
        const editLink = document.getElementById('security-disaster-edit-link');
        const documentsToggle = document.getElementById('security-disaster-documents-toggle');
        
        if (!editLink || !documentsToggle) return;

        // アクティブなサブタブを取得
        const activeSubTab = document.querySelector('#securityDisasterTabs .nav-link.active');
        if (!activeSubTab) return;

        const tabId = activeSubTab.id;
        let subcategory = 'camera_lock'; // デフォルト

        // タブIDに基づいてサブカテゴリを決定
        switch (tabId) {
            case 'camera-lock-tab':
                subcategory = 'camera_lock';
                break;
            case 'fire-disaster-tab':
                subcategory = 'fire_disaster';
                break;
        }

        // 編集ボタンのリンクを更新
        const facilityId = {{ $facility->id }};
        if (subcategory === 'fire_disaster') {
            editLink.href = `/facilities/${facilityId}/security-disaster/edit#fire-disaster-edit`;
        } else {
            editLink.href = `/facilities/${facilityId}/security-disaster/edit`;
        }

        // ドキュメントボタンの動作を更新
        documentsToggle.onclick = function() {
            // 各サブタブの既存のドキュメントボタンをクリック
            let existingDocumentButton;
            if (subcategory === 'camera_lock') {
                existingDocumentButton = document.getElementById('camera-lock-documents-modal');
            } else if (subcategory === 'fire_disaster') {
                existingDocumentButton = document.getElementById('fire-disaster-documents-modal');
            }
            
            if (existingDocumentButton) {
                // モーダルを直接開く
                const modal = new bootstrap.Modal(existingDocumentButton);
                modal.show();
            }
        };
    }

    // 初期化時にボタンを更新
    updateSecurityDisasterButtons();

    // サブタブ切り替え時にボタンを更新
    const securityDisasterTabButtons = document.querySelectorAll('#securityDisasterTabs .nav-link');
    securityDisasterTabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function() {
            updateSecurityDisasterButtons();
        });
    });
    
});
</script>
