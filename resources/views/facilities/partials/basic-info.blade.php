<!-- 施設詳細情報カード群 -->
<div class="row">
    <!-- 基本情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="basic_info" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="basic_info">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row">
                        <span class="detail-label">会社名</span>
                        <span class="detail-value">{{ $facility->company_name }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">事業所コード</span>
                        <span class="detail-value">
                            <span class="badge bg-primary">{{ $facility->office_code }}</span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">指定番号</span>
                        <span class="detail-value">{{ $facility->designation_number ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">施設名</span>
                        <span class="detail-value fw-bold">{{ $facility->facility_name }}</span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="basic_info">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="basic_info">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="basic_info">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="basic_info">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 住所・連絡先情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-map-marker-alt text-success me-2"></i>住所・連絡先
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="contact_info" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="contact_info">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row">
                        <span class="detail-label">郵便番号</span>
                        <span class="detail-value">{{ $facility->formatted_postal_code ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">住所</span>
                        <span class="detail-value">{{ $facility->full_address ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">電話番号</span>
                        <span class="detail-value">{{ $facility->phone_number ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">FAX番号</span>
                        <span class="detail-value">{{ $facility->fax_number ?? '未設定' }}</span>
                    </div>
                    @if($facility->toll_free_number)
                    <div class="detail-row">
                        <span class="detail-label">フリーダイヤル</span>
                        <span class="detail-value">{{ $facility->toll_free_number }}</span>
                    </div>
                    @endif
                    @if($facility->email)
                    <div class="detail-row">
                        <span class="detail-label">メールアドレス</span>
                        <span class="detail-value">
                            <a href="mailto:{{ $facility->email }}" class="text-decoration-none">
                                <i class="fas fa-envelope me-1"></i>{{ $facility->email }}
                            </a>
                        </span>
                    </div>
                    @endif
                    @if($facility->website_url)
                    <div class="detail-row">
                        <span class="detail-label">ウェブサイト</span>
                        <span class="detail-value">
                            <a href="{{ $facility->website_url }}" target="_blank" class="text-decoration-none">
                                <i class="fas fa-external-link-alt me-1"></i>{{ $facility->website_url }}
                            </a>
                        </span>
                    </div>
                    @endif
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="contact_info">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="contact_info">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="contact_info">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="contact_info">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 開設・建物情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-alt text-warning me-2"></i>開設・建物情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="building_info" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="building_info">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    @if($facility->opening_date)
                    <div class="detail-row">
                        <span class="detail-label">開設日</span>
                        <span class="detail-value">{{ $facility->opening_date->format('Y年m月d日') }}</span>
                    </div>
                    @endif
                    @if($facility->years_in_operation)
                    <div class="detail-row">
                        <span class="detail-label">開設年数</span>
                        <span class="detail-value">{{ $facility->years_in_operation }}年</span>
                    </div>
                    @endif
                    @if($facility->building_structure)
                    <div class="detail-row">
                        <span class="detail-label">建物構造</span>
                        <span class="detail-value">{{ $facility->building_structure }}</span>
                    </div>
                    @endif
                    @if($facility->building_floors)
                    <div class="detail-row">
                        <span class="detail-label">建物階数</span>
                        <span class="detail-value">{{ $facility->building_floors }}階</span>
                    </div>
                    @endif
                    @if(!$facility->opening_date && !$facility->years_in_operation && !$facility->building_structure && !$facility->building_floors)
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-info-circle me-2"></i>開設・建物情報が未設定です
                    </div>
                    @endif
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="building_info">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="building_info">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="building_info">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="building_info">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 基本施設情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-home text-info me-2"></i>基本施設情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="facility_info" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="facility_info">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    @if($facility->paid_rooms_count !== null)
                    <div class="detail-row">
                        <span class="detail-label">居室数（有料）</span>
                        <span class="detail-value">{{ $facility->paid_rooms_count }}室</span>
                    </div>
                    @endif
                    @if($facility->ss_rooms_count !== null)
                    <div class="detail-row">
                        <span class="detail-label">内SS数</span>
                        <span class="detail-value">{{ $facility->ss_rooms_count }}室</span>
                    </div>
                    @endif
                    @if($facility->capacity)
                    <div class="detail-row">
                        <span class="detail-label">定員数</span>
                        <span class="detail-value">{{ $facility->capacity }}名</span>
                    </div>
                    @endif
                    @if($facility->paid_rooms_count === null && $facility->ss_rooms_count === null && !$facility->capacity)
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-info-circle me-2"></i>基本施設情報が未設定です
                    </div>
                    @endif
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="facility_info">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="facility_info">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="facility_info">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="facility_info">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- サービス種類カード -->
<div class="card-section mb-4">
    <div class="card-section-header">
        <div>
            <i class="fas fa-clipboard-list me-2"></i>サービス種類・指定更新
        </div>
        <button class="btn btn-outline-light btn-sm comment-toggle" 
                data-section="services" 
                data-bs-toggle="tooltip" 
                title="コメントを表示/非表示">
            <i class="fas fa-comment"></i>
            <span class="comment-count" data-section="services">0</span>
        </button>
    </div>
    <div class="card-section-content">
        @php
            $services = $facility->services ?? collect();
        @endphp

        @if($services && $services->count() > 0)
            @foreach($services as $index => $service)
            <div class="service-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="service-card-title">{{ $service->service_type }}</div>
                    <div class="service-card-dates">
                        <span class="text-muted me-2">有効期限:</span>
                        @if($service->renewal_start_date && $service->renewal_end_date)
                            {{ \Carbon\Carbon::parse($service->renewal_start_date)->format('Y年m月d日') }} 〜 {{ \Carbon\Carbon::parse($service->renewal_end_date)->format('Y年m月d日') }}
                        @elseif($service->renewal_start_date)
                            {{ \Carbon\Carbon::parse($service->renewal_start_date)->format('Y年m月d日') }} 〜
                        @elseif($service->renewal_end_date)
                            〜 {{ \Carbon\Carbon::parse($service->renewal_end_date)->format('Y年m月d日') }}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        @else
            <div class="text-muted text-center py-3">
                <i class="fas fa-info-circle me-2"></i>サービス情報が未設定です
            </div>
        @endif
        
        <!-- コメントセクション -->
        <div class="comment-section mt-3 d-none" data-section="services">
            <hr>
            <div class="comment-form mb-3">
                <div class="input-group">
                    <input type="text" class="form-control comment-input" 
                           placeholder="コメントを入力..." 
                           data-section="services">
                    <button class="btn btn-primary comment-submit" 
                            data-section="services">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
            <div class="comment-list" data-section="services">
                <!-- コメントがここに動的に追加されます -->
            </div>
        </div>
    </div>
</div>