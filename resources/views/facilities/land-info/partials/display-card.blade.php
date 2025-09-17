<!-- 土地情報表示 -->
<div class="row">
            <!-- 基本情報カード -->
            <div class="col-lg-6 mb-4">
                <div class="card facility-info-card detail-card-improved h-100" data-section="land_basic">
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
                            <div class="detail-row {{ empty($landInfo->ownership_type) ? 'empty-field' : '' }}">
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
                            <div class="detail-row {{ $landInfo->parking_spaces === null ? 'empty-field' : '' }}">
                                <span class="detail-label">敷地内駐車場台数</span>
                                <span class="detail-value">
                                    @if($landInfo->parking_spaces !== null)
                                        {{ number_format($landInfo->parking_spaces) }}台
                                    @else
                                        <span class="text-muted">未設定</span>
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ $landInfo->site_area_sqm === null ? 'empty-field' : '' }}">
                                <span class="detail-label">敷地面積</span>
                                <span class="detail-value">
                                    @if($landInfo->site_area_sqm !== null)
                                        {{ number_format($landInfo->site_area_sqm, 2) }}㎡
                                        @if($landInfo->site_area_tsubo !== null)
                                            ({{ number_format($landInfo->site_area_tsubo, 2) }}坪)
                                        @endif
                                    @else
                                        <span class="text-muted">未設定</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 金額・契約情報カード -->
            <div class="col-lg-6 mb-4">
                <div class="card facility-info-card detail-card-improved h-100" data-section="land_financial">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-yen-sign text-success me-2"></i>金額・契約情報
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
                                <div class="detail-row {{ $landInfo->purchase_price === null ? 'empty-field' : '' }}">
                                    <span class="detail-label">購入金額</span>
                                    <span class="detail-value">
                                        @if($landInfo->purchase_price !== null)
                                            {{ number_format($landInfo->purchase_price) }}円
                                        @else
                                            <span class="text-muted">未設定</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="detail-row {{ $landInfo->unit_price_per_tsubo === null ? 'empty-field' : '' }}">
                                    <span class="detail-label">坪単価</span>
                                    <span class="detail-value">
                                        @if($landInfo->unit_price_per_tsubo !== null)
                                            {{ number_format($landInfo->unit_price_per_tsubo) }}円/坪
                                        @else
                                            <span class="text-muted">未設定</span>
                                        @endif
                                    </span>
                                </div>
                            @elseif(in_array($landInfo->ownership_type, ['leased', 'owned_rental']))
                                <div class="detail-row {{ $landInfo->monthly_rent === null ? 'empty-field' : '' }}">
                                    <span class="detail-label">月額賃料</span>
                                    <span class="detail-value">
                                        @if($landInfo->monthly_rent !== null)
                                            {{ number_format($landInfo->monthly_rent) }}円
                                        @else
                                            <span class="text-muted">未設定</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="detail-row {{ (!$landInfo->contract_start_date || !$landInfo->contract_end_date) ? 'empty-field' : '' }}">
                                    <span class="detail-label">契約期間</span>
                                    <span class="detail-value">
                                        @if($landInfo->contract_start_date && $landInfo->contract_end_date)
                                            {{ $landInfo->contract_start_date->format('Y年m月d日') }} ～ 
                                            {{ $landInfo->contract_end_date->format('Y年m月d日') }}
                                            @if($landInfo->contract_period_text)
                                                <br><small class="text-muted">({{ $landInfo->contract_period_text }})</small>
                                            @endif
                                        @else
                                            <span class="text-muted">未設定</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="detail-row {{ empty($landInfo->auto_renewal) ? 'empty-field' : '' }}">
                                    <span class="detail-label">自動更新</span>
                                    <span class="detail-value">
                                        @if($landInfo->auto_renewal === 'yes')
                                            <span class="badge bg-success">あり</span>
                                        @elseif($landInfo->auto_renewal === 'no')
                                            <span class="badge bg-secondary">なし</span>
                                        @else
                                            <span class="text-muted">未設定</span>
                                        @endif
                                    </span>
                                </div>
                                @if($landInfo->ownership_type === 'owned_rental')
                                    <div class="detail-row {{ $landInfo->purchase_price === null ? 'empty-field' : '' }}">
                                        <span class="detail-label">購入金額</span>
                                        <span class="detail-value">
                                            @if($landInfo->purchase_price !== null)
                                                {{ number_format($landInfo->purchase_price) }}円
                                            @else
                                                <span class="text-muted">未設定</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            @else
                                <div class="detail-row empty-field">
                                    <span class="detail-label">所有形態</span>
                                    <span class="detail-value">
                                        <span class="text-muted">所有形態が設定されていないため、金額・契約情報を表示できません</span>
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 管理会社情報カード -->
            <div class="col-lg-6 mb-4">
                <div class="card facility-info-card detail-card-improved h-100" data-section="land_management">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-building text-secondary me-2"></i>管理会社情報
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
                            <div class="detail-row {{ empty($landInfo->management_company_name) ? 'empty-field' : '' }}">
                                <span class="detail-label">会社名</span>
                                <span class="detail-value">{{ $landInfo->management_company_name ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->management_company_postal_code) ? 'empty-field' : '' }}">
                                <span class="detail-label">郵便番号</span>
                                <span class="detail-value">{{ $landInfo->management_company_postal_code ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->management_company_address) ? 'empty-field' : '' }}">
                                <span class="detail-label">住所</span>
                                <span class="detail-value">{{ $landInfo->management_company_address ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->management_company_building) ? 'empty-field' : '' }}">
                                <span class="detail-label">建物名</span>
                                <span class="detail-value">{{ $landInfo->management_company_building ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->management_company_phone) ? 'empty-field' : '' }}">
                                <span class="detail-label">電話番号</span>
                                <span class="detail-value">{{ $landInfo->management_company_phone ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->management_company_fax) ? 'empty-field' : '' }}">
                                <span class="detail-label">FAX番号</span>
                                <span class="detail-value">{{ $landInfo->management_company_fax ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->management_company_email) ? 'empty-field' : '' }}">
                                <span class="detail-label">メールアドレス</span>
                                <span class="detail-value">
                                    @if($landInfo->management_company_email)
                                        <a href="mailto:{{ $landInfo->management_company_email }}">{{ $landInfo->management_company_email }}</a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->management_company_url) ? 'empty-field' : '' }}">
                                <span class="detail-label">URL</span>
                                <span class="detail-value">
                                    @if($landInfo->management_company_url)
                                        <a href="{{ $landInfo->management_company_url }}" target="_blank">{{ $landInfo->management_company_url }}</a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->management_company_notes) ? 'empty-field' : '' }}">
                                <span class="detail-label">備考</span>
                                <span class="detail-value">{{ $landInfo->management_company_notes ?? '未設定' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- オーナー・テナント情報カード -->
            <div class="col-lg-6 mb-4">
                <div class="card facility-info-card detail-card-improved h-100" data-section="land_owner">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user-tie text-dark me-2"></i>
                            @if($landInfo->ownership_type === 'owned_rental')
                                テナント情報
                            @else
                                オーナー情報
                            @endif
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
                            <div class="detail-row {{ empty($landInfo->owner_name) ? 'empty-field' : '' }}">
                                <span class="detail-label">
                                    @if($landInfo->ownership_type === 'owned_rental')
                                        テナント名
                                    @else
                                        オーナー名
                                    @endif
                                </span>
                                <span class="detail-value">{{ $landInfo->owner_name ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->owner_postal_code) ? 'empty-field' : '' }}">
                                <span class="detail-label">郵便番号</span>
                                <span class="detail-value">{{ $landInfo->owner_postal_code ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->owner_address) ? 'empty-field' : '' }}">
                                <span class="detail-label">住所</span>
                                <span class="detail-value">{{ $landInfo->owner_address ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->owner_building) ? 'empty-field' : '' }}">
                                <span class="detail-label">建物名</span>
                                <span class="detail-value">{{ $landInfo->owner_building ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->owner_phone) ? 'empty-field' : '' }}">
                                <span class="detail-label">電話番号</span>
                                <span class="detail-value">{{ $landInfo->owner_phone ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->owner_fax) ? 'empty-field' : '' }}">
                                <span class="detail-label">FAX番号</span>
                                <span class="detail-value">{{ $landInfo->owner_fax ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->owner_email) ? 'empty-field' : '' }}">
                                <span class="detail-label">メールアドレス</span>
                                <span class="detail-value">
                                    @if($landInfo->owner_email)
                                        <a href="mailto:{{ $landInfo->owner_email }}">{{ $landInfo->owner_email }}</a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->owner_url) ? 'empty-field' : '' }}">
                                <span class="detail-label">URL</span>
                                <span class="detail-value">
                                    @if($landInfo->owner_url)
                                        <a href="{{ $landInfo->owner_url }}" target="_blank">{{ $landInfo->owner_url }}</a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->owner_notes) ? 'empty-field' : '' }}">
                                <span class="detail-label">備考</span>
                                <span class="detail-value">{{ $landInfo->owner_notes ?? '未設定' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 関連書類カード -->
            <div class="col-lg-12 mb-4">
                <div class="card facility-info-card detail-card-improved" data-section="land_documents">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-pdf text-danger me-2"></i>関連書類
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($landInfo->lease_contract_pdf_name) ? 'empty-field' : '' }}">
                                <span class="detail-label">賃貸借契約書・覚書</span>
                                <span class="detail-value">
                                    @if($landInfo->lease_contract_pdf_name)
                                        <a href="{{ route('facilities.land-info.download', ['facility' => $facility, 'type' => 'lease_contract']) }}" 
                                           class="text-decoration-none" target="_blank">
                                            <i class="fas fa-file-contract text-warning me-2"></i>{{ $landInfo->lease_contract_pdf_name }}
                                            <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($landInfo->registry_pdf_name) ? 'empty-field' : '' }}">
                                <span class="detail-label">登記簿謄本</span>
                                <span class="detail-value">
                                    @if($landInfo->registry_pdf_name)
                                        <a href="{{ route('facilities.land-info.download', ['facility' => $facility, 'type' => 'registry']) }}" 
                                           class="text-decoration-none" target="_blank">
                                            <i class="fas fa-file-alt text-info me-2"></i>{{ $landInfo->registry_pdf_name }}
                                            <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 備考カード -->
            <div class="col-lg-12 mb-4">
                <div class="card facility-info-card detail-card-improved" data-section="land_notes">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note text-warning me-2"></i>備考
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($landInfo->notes) ? 'empty-field' : '' }}">
                                <span class="detail-label">備考</span>
                                <span class="detail-value">
                                    @if($landInfo->notes)
                                        <div class="border rounded p-2 bg-light">{{ $landInfo->notes }}</div>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</div>