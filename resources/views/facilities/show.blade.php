@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- 固定ヘッダーカード -->
            <div class="facility-header-card card mb-3 sticky-top">
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
                <div class="tab-navigation mb-4">
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
                            {{-- @if(auth()->user()->canEditFacility($facility->id))
                                <a href="{{ route('facilities.security-disaster.edit', $facility) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>
                                    @php
                                        $securityDisasterEquipment = $facility->getSecurityDisasterEquipment();
                                    @endphp
                                    @if($securityDisasterEquipment)
                                        編集
                                    @else
                                        登録
                                    @endif
                                </a>
                            @endif --}}
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
                        <div class="documents-container">
                            <div id="documentsContent">
                                <div class="text-center py-5">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">読み込み中...</span>
                                    </div>
                                    <p class="mt-2">ドキュメントを読み込んでいます...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@vite(['resources/css/pages/facilities.css', 'resources/css/water-equipment.css', 'resources/js/modules/facilities.js'])

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

/* Documents Styles */
.documents-container {
    margin-top: 1rem;
}

.documents-container .card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.documents-container .card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

#documents .card-header {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    border: none;
}

.documents-toolbar {
    background-color: #f8f9fa;
    border-radius: 0.375rem;
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

@media (max-width: 576px) {
    .documents-icon-view .col-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .documents-toolbar {
        padding: 0.75rem;
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
                loadDocumentsContent();
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
    
    // Handle fragment on page load
    handleFragments();
    
    // Handle fragment changes
    window.addEventListener('hashchange', handleFragments);

    // Lifeline Equipment Tab Functionality
    const lifelineTab = document.getElementById('lifeline-tab');
    if (lifelineTab) {
        lifelineTab.addEventListener('shown.bs.tab', function() {
            console.log('Lifeline Equipment tab activated: initializing animations and components');
            
            // Animate cards in the active sub-tab
            setTimeout(() => {
                const activePane = document.querySelector('#lifeline-equipment .tab-pane.active .card');
                if (activePane) {
                    const cards = activePane.parentElement.querySelectorAll('.card');
                    cards.forEach((card, index) => {
                        card.style.animationDelay = `${index * 0.1}s`;
                        card.classList.add('animate-in');
                    });
                }
            }, 100);
        });
    }
    
    // Handle sub-tab switching
    const subTabs = document.querySelectorAll('#lifelineSubTabs .nav-link');
    subTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            const targetId = event.target.getAttribute('data-bs-target');
            console.log(`Switched to ${targetId.replace('#', '')} Equipment sub-tab`);
            
            // Animate cards in the newly active tab
            setTimeout(() => {
                const activePane = document.querySelector('#lifeline-equipment .tab-pane.active .card');
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
    
    // Documents tab functionality
    window.loadDocumentsContent = function() {
        const documentsContent = document.getElementById('documentsContent');
        const facilityId = window.facilityId;
        
        // Show loading state
        documentsContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">読み込み中...</span>
                </div>
                <p class="mt-2">ドキュメントを読み込んでいます...</p>
            </div>
        `;
        
        // Create documents interface
        setTimeout(() => {
            documentsContent.innerHTML = `
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-folder me-2"></i>ドキュメント管理
                        </h6>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary btn-sm" id="uploadFileBtn">
                                <i class="fas fa-upload me-1"></i>ファイルアップロード
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="createFolderBtn">
                                <i class="fas fa-folder-plus me-1"></i>フォルダ作成
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Breadcrumb Navigation -->
                        <div class="documents-breadcrumb">
                            <nav aria-label="breadcrumb" id="breadcrumbNav">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item active">
                                        <i class="fas fa-home me-1"></i>ルート
                                    </li>
                                </ol>
                            </nav>
                        </div>

                        <!-- View Controls -->
                        <div class="documents-view-controls">
                            <div class="documents-view-mode">
                                <div class="btn-group" role="group" aria-label="表示モード">
                                    <input type="radio" class="btn-check" name="viewMode" id="listView" value="list" checked>
                                    <label class="btn btn-outline-secondary btn-sm" for="listView">
                                        <i class="fas fa-list me-1"></i>一覧表示
                                    </label>
                                    <input type="radio" class="btn-check" name="viewMode" id="iconView" value="icon">
                                    <label class="btn btn-outline-secondary btn-sm" for="iconView">
                                        <i class="fas fa-th me-1"></i>アイコン表示
                                    </label>
                                </div>
                            </div>
                            <div class="documents-sort-controls">
                                <select class="form-select form-select-sm me-2" id="sortBy" style="width: auto;">
                                    <option value="name">名前順</option>
                                    <option value="date">日付順</option>
                                    <option value="size">サイズ順</option>
                                    <option value="type">種類順</option>
                                </select>
                                <select class="form-select form-select-sm" id="sortDirection" style="width: auto;">
                                    <option value="asc">昇順</option>
                                    <option value="desc">降順</option>
                                </select>
                            </div>
                        </div>

                        <!-- Document List -->
                        <div id="documentList">
                            <div class="documents-empty-state">
                                <i class="fas fa-folder-open"></i>
                                <h5>ドキュメントがありません</h5>
                                <p class="text-muted">この施設にはまだドキュメントが登録されていません。</p>
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('uploadFileBtn').click()">
                                    <i class="fas fa-upload me-1"></i>最初のファイルをアップロード
                                </button>
                            </div>
                        </div>


                    </div>
                </div>
            `;
            
            // Initialize document management functionality
            initializeDocumentManagement();
        }, 500);
    };
    
    window.initializeDocumentManagement = function() {
        // Initialize navigation state
        window.currentFolderId = null;
        window.folderHistory = [];
        window.breadcrumbs = [
            { id: null, name: 'ルート', is_current: true }
        ];
        
        // Event listeners for buttons
        const uploadBtn = document.getElementById('uploadFileBtn');
        const createFolderBtn = document.getElementById('createFolderBtn');
        
        if (uploadBtn) {
            uploadBtn.addEventListener('click', function() {
                alert('ファイルアップロード機能は次のタスクで実装予定です。');
            });
        }
        
        if (createFolderBtn) {
            createFolderBtn.addEventListener('click', function() {
                alert('フォルダ作成機能は次のタスクで実装予定です。');
            });
        }
        
        // View mode change listeners
        document.querySelectorAll('input[name="viewMode"]').forEach(radio => {
            radio.addEventListener('change', function() {
                loadFolderContents();
            });
        });
        
        // Sort change listeners
        const sortBy = document.getElementById('sortBy');
        const sortDirection = document.getElementById('sortDirection');
        
        if (sortBy) {
            sortBy.addEventListener('change', loadFolderContents);
        }
        
        if (sortDirection) {
            sortDirection.addEventListener('change', loadFolderContents);
        }
        
        // Load initial content
        loadFolderContents();
    };
    
    window.loadFolderContents = function() {
        const documentList = document.getElementById('documentList');
        const viewMode = document.querySelector('input[name="viewMode"]:checked')?.value || 'list';
        
        // Show loading state
        documentList.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">読み込み中...</span>
                </div>
                <p class="mt-2 mb-0">フォルダ内容を読み込んでいます...</p>
            </div>
        `;
        
        // Simulate loading with sample data for demonstration
        setTimeout(() => {
            const sampleData = getSampleDataForFolder(window.currentFolderId);
            renderFolderContents(sampleData, viewMode);
        }, 300);
    };
    
    window.renderFolderContents = function(data, viewMode) {
        const documentList = document.getElementById('documentList');
        
        if (data.folders.length === 0 && data.files.length === 0) {
            documentList.innerHTML = `
                <div class="documents-empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h5>このフォルダは空です</h5>
                    <p class="text-muted">ファイルやフォルダがありません。</p>
                    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('uploadFileBtn').click()">
                        <i class="fas fa-upload me-1"></i>ファイルをアップロード
                    </button>
                </div>
            `;
            return;
        }
        
        if (viewMode === 'list') {
            renderListView(documentList, data);
        } else {
            renderIconView(documentList, data);
        }
    };
    
    window.renderListView = function(container, data) {
        let html = `
            <div class="documents-list-view">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="fas fa-file me-2"></i>名前</th>
                                <th><i class="fas fa-tag me-2"></i>種類</th>
                                <th><i class="fas fa-weight-hanging me-2"></i>サイズ</th>
                                <th><i class="fas fa-clock me-2"></i>更新日時</th>
                                <th><i class="fas fa-user me-2"></i>作成者</th>
                                <th><i class="fas fa-cog me-2"></i>操作</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        // Folders first
        data.folders.forEach(folder => {
            html += `
                <tr class="folder-row" data-folder-id="${folder.id}" onclick="openFolder(${folder.id})" style="cursor: pointer;">
                    <td>
                        <i class="fas fa-folder text-warning me-2"></i>
                        <span class="folder-name fw-semibold">${escapeHtml(folder.name)}</span>
                        <small class="text-muted ms-2">(${folder.file_count} ファイル)</small>
                    </td>
                    <td><span class="badge bg-warning text-dark">フォルダ</span></td>
                    <td>-</td>
                    <td>${formatDate(folder.created_at)}</td>
                    <td>${escapeHtml(folder.created_by)}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-primary" onclick="event.stopPropagation(); openFolder(${folder.id})" title="開く">
                                <i class="fas fa-folder-open"></i>
                            </button>
                            <button class="btn btn-outline-secondary" onclick="event.stopPropagation(); renameFolder(${folder.id})" title="名前変更">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="event.stopPropagation(); deleteFolder(${folder.id})" title="削除">
                                <i class="fas fa-trash"></i>
                            </button>
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
                        <span class="file-name">${escapeHtml(file.name)}</span>
                    </td>
                    <td><span class="badge bg-secondary">${file.extension.toUpperCase()}</span></td>
                    <td>${file.formatted_size}</td>
                    <td>${formatDate(file.created_at)}</td>
                    <td>${escapeHtml(file.uploaded_by)}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="${file.download_url}" class="btn btn-outline-primary" title="ダウンロード">
                                <i class="fas fa-download"></i>
                            </a>
                            <button class="btn btn-outline-info" onclick="previewFile(${file.id})" title="プレビュー">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteFile(${file.id})" title="削除">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    };
    
    window.renderIconView = function(container, data) {
        let html = '<div class="documents-icon-view"><div class="row">';
        
        // Folders first
        data.folders.forEach(folder => {
            html += `
                <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                    <div class="card h-100 folder-card" data-folder-id="${folder.id}" onclick="openFolder(${folder.id})">
                        <div class="card-body text-center">
                            <i class="fas fa-folder fa-3x text-warning mb-2"></i>
                            <p class="card-text small fw-semibold mb-1">${escapeHtml(folder.name)}</p>
                            <small class="text-muted">${folder.file_count} ファイル</small>
                            <div class="mt-2">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); openFolder(${folder.id})" title="開く">
                                        <i class="fas fa-folder-open"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="event.stopPropagation(); renameFolder(${folder.id})" title="名前変更">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); deleteFolder(${folder.id})" title="削除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        // Files
        data.files.forEach(file => {
            html += `
                <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                    <div class="card h-100 file-card" data-file-id="${file.id}">
                        <div class="card-body text-center">
                            <i class="${file.icon} ${file.color} fa-3x mb-2"></i>
                            <p class="card-text small mb-1" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</p>
                            <small class="text-muted">${file.formatted_size}</small>
                            <div class="mt-2">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="${file.download_url}" class="btn btn-outline-primary btn-sm" title="ダウンロード">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button class="btn btn-outline-info btn-sm" onclick="previewFile(${file.id})" title="プレビュー">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteFile(${file.id})" title="削除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div></div>';
        container.innerHTML = html;
    };
    
    // Utility functions
    window.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };
    
    window.formatDate = function(dateString) {
        return new Date(dateString).toLocaleString('ja-JP', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    };
    
    // Navigation functions
    window.openFolder = function(folderId) {
        // Add current folder to history if not already there
        if (window.currentFolderId !== null) {
            window.folderHistory.push(window.currentFolderId);
        }
        
        // Update current folder
        window.currentFolderId = folderId;
        
        // Update breadcrumbs
        updateBreadcrumbs(folderId);
        
        // Load folder contents
        loadFolderContents();
    };
    
    window.navigateToFolder = function(folderId) {
        // Clear history beyond this point
        window.folderHistory = [];
        
        // Update current folder
        window.currentFolderId = folderId;
        
        // Update breadcrumbs
        updateBreadcrumbs(folderId);
        
        // Load folder contents
        loadFolderContents();
    };
    
    window.navigateBack = function() {
        if (window.folderHistory.length > 0) {
            const previousFolderId = window.folderHistory.pop();
            window.currentFolderId = previousFolderId;
            updateBreadcrumbs(previousFolderId);
            loadFolderContents();
        }
    };
    
    window.updateBreadcrumbs = function(currentFolderId) {
        // Sample folder structure for demonstration
        const folderStructure = {
            null: { name: 'ルート', parent: null },
            1: { name: '契約書類', parent: null },
            2: { name: '図面', parent: null },
            3: { name: '点検記録', parent: null },
            4: { name: '保守契約', parent: 1 },
            5: { name: '清掃契約', parent: 1 },
            6: { name: '建築図面', parent: 2 },
            7: { name: '設備図面', parent: 2 }
        };
        
        // Build breadcrumb path
        const breadcrumbs = [];
        let currentId = currentFolderId;
        
        // Build path from current to root
        const path = [];
        while (currentId !== null && folderStructure[currentId]) {
            path.unshift({
                id: currentId,
                name: folderStructure[currentId].name,
                is_current: false
            });
            currentId = folderStructure[currentId].parent;
        }
        
        // Add root
        breadcrumbs.push({
            id: null,
            name: 'ルート',
            is_current: currentFolderId === null
        });
        
        // Add path folders
        path.forEach((folder, index) => {
            folder.is_current = (index === path.length - 1);
            breadcrumbs.push(folder);
        });
        
        window.breadcrumbs = breadcrumbs;
        renderBreadcrumbs();
    };
    
    window.renderBreadcrumbs = function() {
        const breadcrumbNav = document.getElementById('breadcrumbNav');
        if (!breadcrumbNav) return;
        
        let html = '<ol class="breadcrumb">';
        
        window.breadcrumbs.forEach((crumb, index) => {
            if (crumb.is_current) {
                html += `
                    <li class="breadcrumb-item active" aria-current="page">
                        <i class="fas fa-${crumb.id === null ? 'home' : 'folder'} me-1"></i>
                        ${escapeHtml(crumb.name)}
                    </li>
                `;
            } else {
                html += `
                    <li class="breadcrumb-item">
                        <a href="#" onclick="navigateToFolder(${crumb.id})" class="text-decoration-none">
                            <i class="fas fa-${crumb.id === null ? 'home' : 'folder'} me-1"></i>
                            ${escapeHtml(crumb.name)}
                        </a>
                    </li>
                `;
            }
        });
        
        // Add back button if not at root
        if (window.currentFolderId !== null) {
            html += `
                <li class="breadcrumb-item">
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="navigateBack()" title="戻る">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                </li>
            `;
        }
        
        html += '</ol>';
        breadcrumbNav.innerHTML = html;
    };
    
    window.getSampleDataForFolder = function(folderId) {
        // Sample data structure for different folders
        const folderData = {
            // Root folder
            null: {
                folders: [
                    {
                        id: 1,
                        name: '契約書類',
                        created_at: '2024-01-15T10:30:00Z',
                        created_by: '管理者',
                        file_count: 5
                    },
                    {
                        id: 2,
                        name: '図面',
                        created_at: '2024-01-20T14:15:00Z',
                        created_by: '編集者',
                        file_count: 12
                    },
                    {
                        id: 3,
                        name: '点検記録',
                        created_at: '2024-02-01T09:45:00Z',
                        created_by: '管理者',
                        file_count: 8
                    }
                ],
                files: [
                    {
                        id: 1,
                        name: '施設概要.pdf',
                        extension: 'pdf',
                        formatted_size: '2.5 MB',
                        created_at: '2024-01-10T16:20:00Z',
                        uploaded_by: '管理者',
                        icon: 'fas fa-file-pdf',
                        color: 'text-danger',
                        download_url: '#'
                    },
                    {
                        id: 2,
                        name: '設備一覧.xlsx',
                        extension: 'xlsx',
                        formatted_size: '1.2 MB',
                        created_at: '2024-01-12T11:30:00Z',
                        uploaded_by: '編集者',
                        icon: 'fas fa-file-excel',
                        color: 'text-success',
                        download_url: '#'
                    }
                ]
            },
            // 契約書類フォルダ
            1: {
                folders: [
                    {
                        id: 4,
                        name: '保守契約',
                        created_at: '2024-01-16T09:15:00Z',
                        created_by: '管理者',
                        file_count: 3
                    },
                    {
                        id: 5,
                        name: '清掃契約',
                        created_at: '2024-01-17T14:30:00Z',
                        created_by: '管理者',
                        file_count: 2
                    }
                ],
                files: [
                    {
                        id: 4,
                        name: '基本契約書.pdf',
                        extension: 'pdf',
                        formatted_size: '1.8 MB',
                        created_at: '2024-01-16T10:00:00Z',
                        uploaded_by: '管理者',
                        icon: 'fas fa-file-pdf',
                        color: 'text-danger',
                        download_url: '#'
                    }
                ]
            },
            // 図面フォルダ
            2: {
                folders: [
                    {
                        id: 6,
                        name: '建築図面',
                        created_at: '2024-01-21T11:00:00Z',
                        created_by: '編集者',
                        file_count: 8
                    },
                    {
                        id: 7,
                        name: '設備図面',
                        created_at: '2024-01-22T15:45:00Z',
                        created_by: '編集者',
                        file_count: 4
                    }
                ],
                files: [
                    {
                        id: 5,
                        name: '配置図.dwg',
                        extension: 'dwg',
                        formatted_size: '5.2 MB',
                        created_at: '2024-01-21T12:30:00Z',
                        uploaded_by: '編集者',
                        icon: 'fas fa-file',
                        color: 'text-secondary',
                        download_url: '#'
                    }
                ]
            },
            // 点検記録フォルダ
            3: {
                folders: [],
                files: [
                    {
                        id: 6,
                        name: '2024年1月点検記録.pdf',
                        extension: 'pdf',
                        formatted_size: '3.1 MB',
                        created_at: '2024-02-01T10:15:00Z',
                        uploaded_by: '管理者',
                        icon: 'fas fa-file-pdf',
                        color: 'text-danger',
                        download_url: '#'
                    },
                    {
                        id: 7,
                        name: '2024年2月点検記録.pdf',
                        extension: 'pdf',
                        formatted_size: '2.9 MB',
                        created_at: '2024-03-01T09:30:00Z',
                        uploaded_by: '管理者',
                        icon: 'fas fa-file-pdf',
                        color: 'text-danger',
                        download_url: '#'
                    }
                ]
            }
        };
        
        return folderData[folderId] || { folders: [], files: [] };
    };
    
    window.renameFolder = function(folderId) {
        alert(`フォルダ ${folderId} の名前変更機能は次のタスクで実装予定です。`);
    };
    
    window.deleteFolder = function(folderId) {
        if (confirm('このフォルダを削除しますか？')) {
            alert(`フォルダ ${folderId} の削除機能は次のタスクで実装予定です。`);
        }
    };
    
    window.previewFile = function(fileId) {
        alert(`ファイル ${fileId} のプレビュー機能は次のタスクで実装予定です。`);
    };
    
    window.deleteFile = function(fileId) {
        if (confirm('このファイルを削除しますか？')) {
            alert(`ファイル ${fileId} の削除機能は次のタスクで実装予定です。`);
        }
    };
});
</script>