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
                            @if(auth()->user()->canEditFacility($facility->id))
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
                            @endif
                        </div>
                        
                        <!-- Security Disaster Content -->
                        @include('facilities.security-disaster.index', ['facility' => $facility])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@vite(['resources/css/pages/facilities.css', 'resources/js/modules/facilities.js'])

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
            
            console.log('Tab activated successfully:', activeTab);
            
            @if(session('activeSubTab'))
                // Handle sub-tab activation for both lifeline equipment and security disaster
                setTimeout(() => {
                    const activeSubTab = '{{ session('activeSubTab') }}';
                    const subTabButton = document.getElementById(activeSubTab + '-tab');
                    const subTabPane = document.getElementById(activeSubTab);
                    
                    console.log('Active sub-tab session:', activeSubTab);
                    console.log('Sub-tab button element:', subTabButton);
                    console.log('Sub-tab pane element:', subTabPane);
                    
                    if (subTabButton && subTabPane) {
                        // Determine which sub-tab container to use based on active main tab
                        let subTabContainer = '#lifelineSubTabs';
                        let subTabContentContainer = '#lifelineSubTabContent';
                        
                        if (activeTab === 'security-disaster') {
                            subTabContainer = '#securityDisasterTabs';
                            subTabContentContainer = '#securityDisasterTabContent';
                        }
                        
                        // Remove active class from current active sub-tab
                        document.querySelectorAll(subTabContainer + ' .nav-link.active').forEach(tab => {
                            tab.classList.remove('active');
                            tab.setAttribute('aria-selected', 'false');
                        });
                        document.querySelectorAll(subTabContentContainer + ' .tab-pane.active').forEach(pane => {
                            pane.classList.remove('active', 'show');
                        });
                        
                        // Activate the target sub-tab
                        subTabButton.classList.add('active');
                        subTabButton.setAttribute('aria-selected', 'true');
                        subTabPane.classList.add('active', 'show');
                        
                        // Trigger Bootstrap tab event
                        const subTabEvent = new bootstrap.Tab(subTabButton);
                        subTabEvent.show();
                        
                        console.log('Sub-tab activated successfully:', activeSubTab);
                    } else {
                        console.log('Sub-tab elements not found:', {
                            activeSubTab: activeSubTab,
                            subTabButtonId: activeSubTab + '-tab',
                            subTabPaneId: activeSubTab,
                            subTabButton: subTabButton,
                            subTabPane: subTabPane
                        });
                    }
                }, 100); // Small delay to ensure main tab is activated first
            @endif
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
    
    // Handle URL fragments for lifeline equipment tabs
    function handleLifelineFragment() {
        const hash = window.location.hash.substring(1); // Remove #
        const lifelineCategories = ['electrical', 'water', 'gas', 'elevator', 'hvac-lighting'];
        
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
        }
    }
    
    // Handle fragment on page load
    handleLifelineFragment();
    
    // Handle fragment changes
    window.addEventListener('hashchange', handleLifelineFragment);

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
});
</script>