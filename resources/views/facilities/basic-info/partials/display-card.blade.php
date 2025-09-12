<!-- 施設詳細情報カード群 -->
<div class="row">
    <!-- 基本情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="facility_basic">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="basic_info" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="基本情報のコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-basic_info">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="basic_info" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($facility->company_name) ? 'empty-field' : '' }}">
                        <span class="detail-label">会社名</span>
                        <span class="detail-value">{{ $facility->company_name ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($facility->office_code) ? 'empty-field' : '' }}">
                        <span class="detail-label">事業所コード</span>
                        <span class="detail-value">
                            @if($facility->office_code)
                                <span class="badge bg-primary">{{ $facility->office_code }}</span>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($facility->designation_number) ? 'empty-field' : '' }}">
                        <span class="detail-label">指定番号</span>
                        <span class="detail-value">{{ $facility->designation_number ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($facility->facility_name) ? 'empty-field' : '' }}">
                        <span class="detail-label">施設名</span>
                        <span class="detail-value fw-bold">{{ $facility->facility_name ?? '未設定' }}</span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="basic_info" 
                     id="comment-section-basic_info"
                     role="region"
                     aria-label="基本情報のコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-basic_info" class="sr-only">基本情報にコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-basic_info"
                                   placeholder="コメントを入力..." 
                                   data-section="basic_info"
                                   aria-describedby="comment-help-basic_info">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="basic_info"
                                    aria-label="基本情報にコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="comment-help-basic_info" class="sr-only">
                            Enterキーまたは投稿ボタンでコメントを追加できます
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="basic_info"
                         role="log"
                         aria-label="基本情報のコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 住所・連絡先情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="facility_contact">
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
                    <div class="detail-row {{ empty($facility->formatted_postal_code) ? 'empty-field' : '' }}">
                        <span class="detail-label">郵便番号</span>
                        <span class="detail-value">{{ $facility->formatted_postal_code ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($facility->full_address) ? 'empty-field' : '' }}">
                        <span class="detail-label">住所</span>
                        <span class="detail-value">{{ $facility->full_address ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($facility->building_name) ? 'empty-field' : '' }}">
                        <span class="detail-label">住所（建物名）</span>
                        <span class="detail-value">{{ $facility->building_name ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($facility->phone_number) ? 'empty-field' : '' }}">
                        <span class="detail-label">電話番号</span>
                        <span class="detail-value">{{ $facility->phone_number ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($facility->fax_number) ? 'empty-field' : '' }}">
                        <span class="detail-label">FAX番号</span>
                        <span class="detail-value">{{ $facility->fax_number ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($facility->toll_free_number) ? 'empty-field' : '' }}">
                        <span class="detail-label">フリーダイヤル</span>
                        <span class="detail-value">
                            @if($facility->toll_free_number)
                                {{ $facility->toll_free_number }}
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($facility->email) ? 'empty-field' : '' }}">
                        <span class="detail-label">メールアドレス</span>
                        <span class="detail-value">
                            @if($facility->email)
                                <a href="mailto:{{ $facility->email }}" 
                                   class="text-decoration-none"
                                   aria-label="メールアドレス {{ $facility->email }} にメールを送信">
                                    <i class="fas fa-envelope me-1" aria-hidden="true"></i>{{ $facility->email }}
                                </a>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($facility->website_url) ? 'empty-field' : '' }}">
                        <span class="detail-label">ウェブサイト</span>
                        <span class="detail-value">
                            @if($facility->website_url)
                                <a href="{{ $facility->website_url }}" 
                                   target="_blank" 
                                   class="text-decoration-none"
                                   aria-label="ウェブサイト {{ $facility->website_url }} を新しいタブで開く">
                                    <i class="fas fa-external-link-alt me-1" aria-hidden="true"></i>{{ $facility->website_url }}
                                </a>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
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
        <div class="card facility-info-card detail-card-improved h-100" data-section="facility_building">
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
                    <div class="detail-row {{ empty($facility->opening_date) ? 'empty-field' : '' }}">
                        <span class="detail-label">開設日</span>
                        <span class="detail-value">
                            @if($facility->opening_date)
                                {{ $facility->opening_date->format('Y年m月d日') }}
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($facility->opening_date) ? 'empty-field' : '' }}">
                        <span class="detail-label">開設年数（自動計算）</span>
                        <span class="detail-value">
                            @if($facility->opening_date)
                                @php
                                    $yearsInOperation = $facility->opening_date->diffInYears(now());
                                @endphp
                                {{ $yearsInOperation }}年
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($facility->building_structure) ? 'empty-field' : '' }}">
                        <span class="detail-label">建物構造</span>
                        <span class="detail-value">{{ $facility->building_structure ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($facility->building_floors) ? 'empty-field' : '' }}">
                        <span class="detail-label">建物階数</span>
                        <span class="detail-value">
                            @if($facility->building_floors)
                                {{ $facility->building_floors }}階
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
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
        <div class="card facility-info-card detail-card-improved h-100" data-section="facility_service">
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
                    <div class="detail-row {{ $facility->paid_rooms_count === null ? 'empty-field' : '' }}">
                        <span class="detail-label">居室数（有料）</span>
                        <span class="detail-value">
                            @if($facility->paid_rooms_count !== null)
                                {{ $facility->paid_rooms_count }}室
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ $facility->ss_rooms_count === null ? 'empty-field' : '' }}">
                        <span class="detail-label">内SS数</span>
                        <span class="detail-value">
                            @if($facility->ss_rooms_count !== null)
                                {{ $facility->ss_rooms_count }}室
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($facility->capacity) ? 'empty-field' : '' }}">
                        <span class="detail-label">定員数</span>
                        <span class="detail-value">
                            @if($facility->capacity)
                                {{ $facility->capacity }}名
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
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
<div class="card facility-info-card detail-card-improved mb-4" data-section="facility_services">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-list me-2"></i>サービス種類・指定更新
        </h5>
        <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                data-section="services" 
                data-bs-toggle="tooltip" 
                title="コメントを表示/非表示">
            <i class="fas fa-comment"></i>
            <span class="comment-count" data-section="services">0</span>
        </button>
    </div>
    <div class="card-body">
        @php
            $services = $facility->services ?? collect();
        @endphp

        <div class="facility-detail-table">
            <div class="detail-row {{ (!$services || $services->count() === 0) ? 'empty-field' : '' }}">
                <span class="detail-label">サービス種類</span>
                <span class="detail-value">
                    @if($services && $services->count() > 0)
                        @foreach($services as $index => $service)
                            <div class="service-item mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="service-name fw-bold">{{ $service->service_type }}</div>
                                    <div class="service-dates">
                                        <small class="text-muted">有効期限:</small>
                                        @if($service->renewal_start_date && $service->renewal_end_date)
                                            <small>{{ \Carbon\Carbon::parse($service->renewal_start_date)->format('Y/m/d') }} 〜 {{ \Carbon\Carbon::parse($service->renewal_end_date)->format('Y/m/d') }}</small>
                                        @elseif($service->renewal_start_date)
                                            <small>{{ \Carbon\Carbon::parse($service->renewal_start_date)->format('Y/m/d') }} 〜</small>
                                        @elseif($service->renewal_end_date)
                                            <small>〜 {{ \Carbon\Carbon::parse($service->renewal_end_date)->format('Y/m/d') }}</small>
                                        @else
                                            <small class="text-muted">未設定</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        未設定
                    @endif
                </span>
            </div>
        </div>
        
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