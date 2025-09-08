<!-- 土地情報フォーム -->
<div class="land-info-form">
    @if(isset($landInfo) && $landInfo)

        
        <!-- 土地情報表示 -->
        <div class="row">
            <!-- 基本情報カード -->
            <div class="col-lg-6 mb-4">
                <div class="card facility-info-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-map text-primary me-2"></i>基本情報
                        </h5>
                        <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                                data-section="land_basic" 
                                data-bs-toggle="tooltip" 
                                title="コメントを表示/非表示">
                            <i class="fas fa-comment"></i>
                            <span class="comment-count" data-section="land_basic">0</span>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row">
                                <span class="detail-label">所有形態</span>
                                <span class="detail-value">
                                    @switch($landInfo->ownership_type)
                                        @case('owned')
                                            <span class="badge bg-success">自社</span>
                                            @break
                                        @case('leased')
                                            <span class="badge bg-warning">賃借</span>
                                            @break
                                        @case('owned_rental')
                                            <span class="badge bg-info">自社（賃貸）</span>
                                            @break
                                        @default
                                            <span class="text-muted">未設定</span>
                                    @endswitch
                                </span>
                            </div>
                            @if($landInfo->parking_spaces !== null)
                            <div class="detail-row">
                                <span class="detail-label">敷地内駐車場台数</span>
                                <span class="detail-value">{{ number_format($landInfo->parking_spaces) }}台</span>
                            </div>
                            @endif
                            @if($landInfo->site_area_sqm)
                            <div class="detail-row">
                                <span class="detail-label">敷地面積（㎡）</span>
                                <span class="detail-value">{{ $landInfo->formatted_site_area_sqm }}</span>
                            </div>
                            @endif
                            @if($landInfo->site_area_tsubo)
                            <div class="detail-row">
                                <span class="detail-label">敷地面積（坪数）</span>
                                <span class="detail-value">{{ $landInfo->formatted_site_area_tsubo }}</span>
                            </div>
                            @endif
                        </div>
                        
                        <!-- コメントセクション -->
                        <div class="comment-section mt-3 d-none" data-section="land_basic">
                            <hr>
                            <div class="comment-form mb-3">
                                <div class="input-group">
                                    <input type="text" class="form-control comment-input" 
                                           placeholder="コメントを入力..." 
                                           data-section="land_basic">
                                    <button class="btn btn-primary comment-submit" 
                                            data-section="land_basic">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="comment-list" data-section="land_basic">
                                <!-- コメントがここに動的に追加されます -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 金額・契約情報カード -->
            <div class="col-lg-6 mb-4">
                <div class="card facility-info-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-yen-sign text-success me-2"></i>
                            @if($landInfo->ownership_type === 'owned')
                                購入情報
                            @else
                                契約情報
                            @endif
                        </h5>
                        <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                                data-section="land_financial" 
                                data-bs-toggle="tooltip" 
                                title="コメントを表示/非表示">
                            <i class="fas fa-comment"></i>
                            <span class="comment-count" data-section="land_financial">0</span>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            @if($landInfo->ownership_type === 'owned')
                                @if($landInfo->purchase_price)
                                <div class="detail-row">
                                    <span class="detail-label">購入金額</span>
                                    <span class="detail-value">{{ $landInfo->formatted_purchase_price }}円</span>
                                </div>
                                @endif
                                @if($landInfo->unit_price_per_tsubo)
                                <div class="detail-row">
                                    <span class="detail-label">坪単価</span>
                                    <span class="detail-value">{{ number_format($landInfo->unit_price_per_tsubo) }}円/坪</span>
                                </div>
                                @endif
                            @else
                                @if($landInfo->monthly_rent)
                                <div class="detail-row">
                                    <span class="detail-label">家賃</span>
                                    <span class="detail-value">{{ $landInfo->formatted_monthly_rent }}円</span>
                                </div>
                                @endif
                                @if($landInfo->contract_start_date)
                                <div class="detail-row">
                                    <span class="detail-label">契約開始日</span>
                                    <span class="detail-value">{{ $landInfo->japanese_contract_start_date }}</span>
                                </div>
                                @endif
                                @if($landInfo->contract_end_date)
                                <div class="detail-row">
                                    <span class="detail-label">契約終了日</span>
                                    <span class="detail-value">{{ $landInfo->japanese_contract_end_date }}</span>
                                </div>
                                @endif
                                @if($landInfo->contract_period_text)
                                <div class="detail-row">
                                    <span class="detail-label">契約年数</span>
                                    <span class="detail-value">{{ $landInfo->contract_period_text }}</span>
                                </div>
                                @endif
                                @if($landInfo->auto_renewal)
                                <div class="detail-row">
                                    <span class="detail-label">自動更新</span>
                                    <span class="detail-value">
                                        @if($landInfo->auto_renewal === 'yes')
                                            <span class="badge bg-success">あり</span>
                                        @else
                                            <span class="badge bg-secondary">なし</span>
                                        @endif
                                    </span>
                                </div>
                                @endif
                            @endif
                        </div>
                        
                        <!-- コメントセクション -->
                        <div class="comment-section mt-3 d-none" data-section="land_financial">
                            <hr>
                            <div class="comment-form mb-3">
                                <div class="input-group">
                                    <input type="text" class="form-control comment-input" 
                                           placeholder="コメントを入力..." 
                                           data-section="land_financial">
                                    <button class="btn btn-primary comment-submit" 
                                            data-section="land_financial">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="comment-list" data-section="land_financial">
                                <!-- コメントがここに動的に追加されます -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($landInfo->ownership_type === 'leased')
            <!-- 管理会社情報カード -->
            <div class="col-lg-6 mb-4">
                <div class="card facility-info-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-building text-warning me-2"></i>管理会社情報
                        </h5>
                        <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                                data-section="land_management" 
                                data-bs-toggle="tooltip" 
                                title="コメントを表示/非表示">
                            <i class="fas fa-comment"></i>
                            <span class="comment-count" data-section="land_management">0</span>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            @if($landInfo->management_company_name)
                            <div class="detail-row">
                                <span class="detail-label">会社名</span>
                                <span class="detail-value">{{ $landInfo->management_company_name }}</span>
                            </div>
                            @endif
                            @if($landInfo->management_company_postal_code || $landInfo->management_company_address)
                            <div class="detail-row">
                                <span class="detail-label">住所</span>
                                <span class="detail-value">
                                    @if($landInfo->management_company_postal_code)
                                        〒{{ $landInfo->management_company_postal_code }}<br>
                                    @endif
                                    {{ $landInfo->management_company_address }}
                                    @if($landInfo->management_company_building)
                                        {{ $landInfo->management_company_building }}
                                    @endif
                                </span>
                            </div>
                            @endif
                            @if($landInfo->management_company_phone)
                            <div class="detail-row">
                                <span class="detail-label">電話番号</span>
                                <span class="detail-value">{{ $landInfo->management_company_phone }}</span>
                            </div>
                            @endif
                            @if($landInfo->management_company_email)
                            <div class="detail-row">
                                <span class="detail-label">メールアドレス</span>
                                <span class="detail-value">
                                    <a href="mailto:{{ $landInfo->management_company_email }}" class="text-decoration-none">
                                        <i class="fas fa-envelope me-1"></i>{{ $landInfo->management_company_email }}
                                    </a>
                                </span>
                            </div>
                            @endif
                        </div>
                        
                        <!-- コメントセクション -->
                        <div class="comment-section mt-3 d-none" data-section="land_management">
                            <hr>
                            <div class="comment-form mb-3">
                                <div class="input-group">
                                    <input type="text" class="form-control comment-input" 
                                           placeholder="コメントを入力..." 
                                           data-section="land_management">
                                    <button class="btn btn-primary comment-submit" 
                                            data-section="land_management">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="comment-list" data-section="land_management">
                                <!-- コメントがここに動的に追加されます -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- オーナー情報カード -->
            <div class="col-lg-6 mb-4">
                <div class="card facility-info-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user-tie text-info me-2"></i>オーナー情報
                        </h5>
                        <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                                data-section="land_owner" 
                                data-bs-toggle="tooltip" 
                                title="コメントを表示/非表示">
                            <i class="fas fa-comment"></i>
                            <span class="comment-count" data-section="land_owner">0</span>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            @if($landInfo->owner_name)
                            <div class="detail-row">
                                <span class="detail-label">氏名・会社名</span>
                                <span class="detail-value">{{ $landInfo->owner_name }}</span>
                            </div>
                            @endif
                            @if($landInfo->owner_postal_code || $landInfo->owner_address)
                            <div class="detail-row">
                                <span class="detail-label">住所</span>
                                <span class="detail-value">
                                    @if($landInfo->owner_postal_code)
                                        〒{{ $landInfo->owner_postal_code }}<br>
                                    @endif
                                    {{ $landInfo->owner_address }}
                                    @if($landInfo->owner_building)
                                        {{ $landInfo->owner_building }}
                                    @endif
                                </span>
                            </div>
                            @endif
                            @if($landInfo->owner_phone)
                            <div class="detail-row">
                                <span class="detail-label">電話番号</span>
                                <span class="detail-value">{{ $landInfo->owner_phone }}</span>
                            </div>
                            @endif
                            @if($landInfo->owner_email)
                            <div class="detail-row">
                                <span class="detail-label">メールアドレス</span>
                                <span class="detail-value">
                                    <a href="mailto:{{ $landInfo->owner_email }}" class="text-decoration-none">
                                        <i class="fas fa-envelope me-1"></i>{{ $landInfo->owner_email }}
                                    </a>
                                </span>
                            </div>
                            @endif
                        </div>
                        
                        <!-- コメントセクション -->
                        <div class="comment-section mt-3 d-none" data-section="land_owner">
                            <hr>
                            <div class="comment-form mb-3">
                                <div class="input-group">
                                    <input type="text" class="form-control comment-input" 
                                           placeholder="コメントを入力..." 
                                           data-section="land_owner">
                                    <button class="btn btn-primary comment-submit" 
                                            data-section="land_owner">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="comment-list" data-section="land_owner">
                                <!-- コメントがここに動的に追加されます -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        @if($landInfo->notes)
        <!-- 備考カード -->
        <div class="card-section mb-4">
            <div class="card-section-header">
                <div>
                    <i class="fas fa-sticky-note me-2"></i>備考
                </div>
                <button class="btn btn-outline-light btn-sm comment-toggle" 
                        data-section="land_notes" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="land_notes">0</span>
                </button>
            </div>
            <div class="card-section-content">
                <div class="p-3">
                    {!! nl2br(e($landInfo->notes)) !!}
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="land_notes">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="land_notes">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="land_notes">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="land_notes">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
        @endif



    @else
        <!-- 土地情報が未登録の場合 -->
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-map-marked-alt fa-4x text-muted"></i>
            </div>
            <h4 class="text-muted mb-3">土地情報が未登録です</h4>
            <p class="text-muted mb-4">
                この施設の土地情報（所有形態、面積、契約情報など）がまだ登録されていません。<br>
                土地情報を登録すると、施設の詳細な管理が可能になります。
            </p>
            
            @if(!auth()->user()->isEditor() && !auth()->user()->isAdmin())
            <div class="alert alert-info d-inline-block">
                <i class="fas fa-info-circle me-2"></i>
                土地情報の登録には編集権限が必要です。
            </div>
            @endif
        </div>
    @endif
</div>

@push('styles')
<style>
.land-info-form .detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.land-info-form .detail-row:last-child {
    border-bottom: none;
}

.land-info-form .detail-label {
    font-weight: 600;
    color: #495057;
    min-width: 120px;
    flex-shrink: 0;
}

.land-info-form .detail-value {
    text-align: right;
    flex-grow: 1;
    margin-left: 1rem;
}

.land-info-form .card-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.land-info-form .card-section-header {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    padding: 1.5rem;
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.land-info-form .card-section-content {
    background: white;
    padding: 0;
}

@media (max-width: 768px) {
    .land-info-form .detail-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .land-info-form .detail-value {
        text-align: left;
        margin-left: 0;
        margin-top: 0.25rem;
    }
    
    .land-info-form .detail-label {
        min-width: auto;
    }
}
</style>
@endpush