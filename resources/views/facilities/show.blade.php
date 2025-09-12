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
                                    <i class="fas fa-arrow-left"></i> 一覧に戻る
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
                                @include('facilities.land-info.partials.display-card', ['landInfo' => $landInfo])
                                
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
                                        土地情報を登録するには、編集ボタンから登録してください。
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@vite(['resources/css/pages/facilities.css', 'resources/js/modules/facilities.js'])

<script>
// Pass facility ID to the JavaScript module
window.facilityId = {{ $facility->id }};

// Handle active tab from session
document.addEventListener('DOMContentLoaded', function() {
    @if(session('activeTab'))
        const activeTab = '{{ session('activeTab') }}';
        const tabButton = document.getElementById(activeTab.replace('-info', '') + '-tab');
        const tabPane = document.getElementById(activeTab);
        
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
        }
    @endif
});
</script>