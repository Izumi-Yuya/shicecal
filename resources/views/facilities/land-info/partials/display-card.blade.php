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
                            <div class="detail-row">
                                <span class="detail-label">敷地内駐車場台数</span>
                                <span class="detail-value">
                                    @if($landInfo->parking_spaces !== null)
                                        {{ number_format($landInfo->parking_spaces) }}台
                                    @else
                                        <span class="text-muted">未設定</span>
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
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
                <div class="card facility-info-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-yen-sign text-success me-2"></i>金額・契約情報
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            @if($landInfo->ownership_type === 'owned')
                                <div class="detail-row">
                                    <span class="detail-label">購入金額</span>
                                    <span class="detail-value">
                                        @if($landInfo->purchase_price !== null)
                                            {{ number_format($landInfo->purchase_price) }}円
                                        @else
                                            <span class="text-muted">未設定</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="detail-row">
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
                                <div class="detail-row">
                                    <span class="detail-label">月額賃料</span>
                                    <span class="detail-value">
                                        @if($landInfo->monthly_rent !== null)
                                            {{ number_format($landInfo->monthly_rent) }}円
                                        @else
                                            <span class="text-muted">未設定</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">契約期間</span>
                                    <span class="detail-value">
                                        @if($landInfo->contract_start_date && $landInfo->contract_end_date)
                                            {{ $landInfo->contract_start_date->format('Y/m/d') }} ～ 
                                            {{ $landInfo->contract_end_date->format('Y/m/d') }}
                                            @if($landInfo->contract_period_text)
                                                <br><small class="text-muted">({{ $landInfo->contract_period_text }})</small>
                                            @endif
                                        @else
                                            <span class="text-muted">未設定</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="detail-row">
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
                                @if($landInfo->ownership_type === 'owned_rental' && $landInfo->purchase_price)
                                    <div class="detail-row">
                                        <span class="detail-label">購入金額</span>
                                        <span class="detail-value">{{ number_format($landInfo->purchase_price) }}円</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 管理会社情報カード -->
            @if($landInfo->management_company_name)
            <div class="col-lg-6 mb-4">
                <div class="card facility-info-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-building text-secondary me-2"></i>管理会社情報
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row">
                                <span class="detail-label">会社名</span>
                                <span class="detail-value">{{ $landInfo->management_company_name }}</span>
                            </div>
                            @if($landInfo->management_company_address)
                            <div class="detail-row">
                                <span class="detail-label">住所</span>
                                <span class="detail-value">
                                    @if($landInfo->management_company_postal_code)
                                        〒{{ $landInfo->management_company_postal_code }}<br>
                                    @endif
                                    {{ $landInfo->management_company_address }}
                                    @if($landInfo->management_company_building)
                                        <br>{{ $landInfo->management_company_building }}
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
                            @if($landInfo->management_company_fax)
                            <div class="detail-row">
                                <span class="detail-label">FAX番号</span>
                                <span class="detail-value">{{ $landInfo->management_company_fax }}</span>
                            </div>
                            @endif
                            @if($landInfo->management_company_email)
                            <div class="detail-row">
                                <span class="detail-label">メールアドレス</span>
                                <span class="detail-value">
                                    <a href="mailto:{{ $landInfo->management_company_email }}">{{ $landInfo->management_company_email }}</a>
                                </span>
                            </div>
                            @endif
                            @if($landInfo->management_company_url)
                            <div class="detail-row">
                                <span class="detail-label">URL</span>
                                <span class="detail-value">
                                    <a href="{{ $landInfo->management_company_url }}" target="_blank">{{ $landInfo->management_company_url }}</a>
                                </span>
                            </div>
                            @endif
                            @if($landInfo->management_company_notes)
                            <div class="detail-row">
                                <span class="detail-label">備考</span>
                                <span class="detail-value">{{ $landInfo->management_company_notes }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- オーナー・テナント情報カード -->
            @if($landInfo->owner_name)
            <div class="col-lg-6 mb-4">
                <div class="card facility-info-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user-tie text-dark me-2"></i>
                            @if($landInfo->ownership_type === 'owned_rental')
                                テナント情報
                            @else
                                オーナー情報
                            @endif
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row">
                                <span class="detail-label">
                                    @if($landInfo->ownership_type === 'owned_rental')
                                        テナント名
                                    @else
                                        オーナー名
                                    @endif
                                </span>
                                <span class="detail-value">{{ $landInfo->owner_name }}</span>
                            </div>
                            @if($landInfo->owner_address)
                            <div class="detail-row">
                                <span class="detail-label">住所</span>
                                <span class="detail-value">
                                    @if($landInfo->owner_postal_code)
                                        〒{{ $landInfo->owner_postal_code }}<br>
                                    @endif
                                    {{ $landInfo->owner_address }}
                                    @if($landInfo->owner_building)
                                        <br>{{ $landInfo->owner_building }}
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
                            @if($landInfo->owner_fax)
                            <div class="detail-row">
                                <span class="detail-label">FAX番号</span>
                                <span class="detail-value">{{ $landInfo->owner_fax }}</span>
                            </div>
                            @endif
                            @if($landInfo->owner_email)
                            <div class="detail-row">
                                <span class="detail-label">メールアドレス</span>
                                <span class="detail-value">
                                    <a href="mailto:{{ $landInfo->owner_email }}">{{ $landInfo->owner_email }}</a>
                                </span>
                            </div>
                            @endif
                            @if($landInfo->owner_url)
                            <div class="detail-row">
                                <span class="detail-label">URL</span>
                                <span class="detail-value">
                                    <a href="{{ $landInfo->owner_url }}" target="_blank">{{ $landInfo->owner_url }}</a>
                                </span>
                            </div>
                            @endif
                            @if($landInfo->owner_notes)
                            <div class="detail-row">
                                <span class="detail-label">備考</span>
                                <span class="detail-value">{{ $landInfo->owner_notes }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- 関連書類カード -->
            @if($landInfo->lease_contract_pdf_name || $landInfo->registry_pdf_name)
            <div class="col-lg-12 mb-4">
                <div class="card facility-info-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-pdf text-danger me-2"></i>関連書類
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @if($landInfo->lease_contract_pdf_name)
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-contract text-warning me-2"></i>
                                    <div>
                                        <strong>賃貸借契約書・覚書</strong><br>
                                        <small class="text-muted">{{ $landInfo->lease_contract_pdf_name }}</small>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($landInfo->registry_pdf_name)
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-alt text-info me-2"></i>
                                    <div>
                                        <strong>謄本</strong><br>
                                        <small class="text-muted">{{ $landInfo->registry_pdf_name }}</small>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- 備考カード -->
            @if($landInfo->notes)
            <div class="col-lg-12 mb-4">
                <div class="card facility-info-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note text-warning me-2"></i>備考
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $landInfo->notes }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

    @else
        <!-- 土地情報未登録の場合 -->
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
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">敷地面積（㎡）</span>
                                <span class="detail-value">
                                    @if($landInfo->site_area_sqm)
                                        {{ $landInfo->formatted_site_area_sqm }}
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">敷地面積（坪数）</span>
                                <span class="detail-value">
                                    @if($landInfo->site_area_tsubo)
                                        {{ $landInfo->formatted_site_area_tsubo }}
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
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
                            <i class="fas fa-yen-sign text-success me-2"></i>購入/契約情報
                            @if(auth()->user()->canEditLandFinancialInfo())
                                <small class="text-muted ms-2">(土地総務・経理変更時)</small>
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
                            <div class="detail-row">
                                <span class="detail-label">購入金額</span>
                                <span class="detail-value">
                                    @if($landInfo->purchase_price)
                                        {{ $landInfo->formatted_purchase_price ?? number_format((int)$landInfo->purchase_price) }}円
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            @php
                              $unit = $landInfo->unit_price_per_tsubo;
                              if(is_null($unit) && ($landInfo->site_area_tsubo ?? 0) > 0 && $landInfo->purchase_price){
                                  $unit = (int) round($landInfo->purchase_price / $landInfo->site_area_tsubo);
                              }
                            @endphp
                            <div class="detail-row">
                                <span class="detail-label">坪単価（自動計算）</span>
                                <span class="detail-value">
                                    @if(!is_null($unit))
                                        {{ number_format($unit) }}円/坪
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">家賃</span>
                                <span class="detail-value">
                                    @if($landInfo->monthly_rent)
                                        {{ $landInfo->formatted_monthly_rent ?? number_format((int)$landInfo->monthly_rent) }}円
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">契約開始日</span>
                                <span class="detail-value">
                                    @if($landInfo->contract_start_date)
                                        {{ $landInfo->japanese_contract_start_date ?? \Illuminate\Support\Carbon::parse($landInfo->contract_start_date)->format('Y-m-d') }}
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">契約終了日</span>
                                <span class="detail-value">
                                    @if($landInfo->contract_end_date)
                                        {{ $landInfo->japanese_contract_end_date ?? \Illuminate\Support\Carbon::parse($landInfo->contract_end_date)->format('Y-m-d') }}
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">自動更新の有無</span>
                                <span class="detail-value">
                                    @php
                                      $ar = $landInfo->auto_renewal;
                                      $arBool = filter_var($ar, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                                    @endphp
                                    @if(!is_null($ar) && $ar !== '')
                                        @if($arBool === null)
                                            {{ is_string($ar) ? $ar : ($ar ? 'あり' : 'なし') }}
                                        @else
                                            <span class="badge {{ $arBool ? 'bg-success' : 'bg-secondary' }}">{{ $arBool ? 'あり' : 'なし' }}</span>
                                        @endif
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">契約年数（自動計算）</span>
                                <span class="detail-value">
                                    @if($landInfo->contract_period_text)
                                        {{ $landInfo->contract_period_text }}
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
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


            <!-- 管理会社情報カード -->
            <div class="col-lg-6 mb-4">
                <div class="card facility-info-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-building text-warning me-2"></i>管理会社情報
                            @if(auth()->user()->canEditLandManagementInfo())
                                <small class="text-muted ms-2">(土地総務・経理変更時)</small>
                            @endif
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
                            <div class="detail-row">
                                <span class="detail-label">会社名</span>
                                <span class="detail-value">{{ $landInfo->management_company_name ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">郵便番号</span>
                                <span class="detail-value">{{ $landInfo->management_company_postal_code ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">住所</span>
                                <span class="detail-value">{{ $landInfo->management_company_address ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">住所建物名</span>
                                <span class="detail-value">{{ $landInfo->management_company_building ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">電話番号</span>
                                <span class="detail-value">{{ $landInfo->management_company_phone ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">FAX番号</span>
                                <span class="detail-value">{{ $landInfo->management_company_fax ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">メールアドレス</span>
                                <span class="detail-value">
                                    @if($landInfo->management_company_email)
                                        <a href="mailto:{{ $landInfo->management_company_email }}" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>{{ $landInfo->management_company_email }}
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">URL</span>
                                <span class="detail-value">
                                    @if($landInfo->management_company_url)
                                        <a href="{{ $landInfo->management_company_url }}" target="_blank" rel="noopener">{{ $landInfo->management_company_url }}</a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">備考</span>
                                <span class="detail-value">
                                    @if($landInfo->management_company_notes)
                                        {!! nl2br(e($landInfo->management_company_notes)) !!}
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
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
                            @if(auth()->user()->canEditLandManagementInfo())
                                <small class="text-muted ms-2">(土地総務・経理変更時)</small>
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
                            <div class="detail-row">
                                <span class="detail-label">氏名・会社名</span>
                                <span class="detail-value">{{ $landInfo->owner_name ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">郵便番号</span>
                                <span class="detail-value">{{ $landInfo->owner_postal_code ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">住所</span>
                                <span class="detail-value">{{ $landInfo->owner_address ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">住所建物名</span>
                                <span class="detail-value">{{ $landInfo->owner_building ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">電話番号</span>
                                <span class="detail-value">{{ $landInfo->owner_phone ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">FAX番号</span>
                                <span class="detail-value">{{ $landInfo->owner_fax ?: '未設定' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">メールアドレス</span>
                                <span class="detail-value">
                                    @if($landInfo->owner_email)
                                        <a href="mailto:{{ $landInfo->owner_email }}" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>{{ $landInfo->owner_email }}
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">URL</span>
                                <span class="detail-value">
                                    @if($landInfo->owner_url)
                                        <a href="{{ $landInfo->owner_url }}" target="_blank" rel="noopener">{{ $landInfo->owner_url }}</a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">備考欄</span>
                                <span class="detail-value">
                                    @if($landInfo->owner_notes)
                                        {!! nl2br(e($landInfo->owner_notes)) !!}
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
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
        </div>

        <!-- 書類情報カード -->
        <!-- 書類情報カード -->
        <div class="col-12 mb-4">
            <div class="card facility-info-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-pdf text-danger me-2"></i>関連書類
                        @if(auth()->user()->canEditLandDocuments())
                            <small class="text-muted ms-2">(土地総務・工程表により検討変更時)</small>
                        @endif
                    </h5>
                    <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                            data-section="land_documents" 
                            data-bs-toggle="tooltip" 
                            title="コメントを表示/非表示">
                        <i class="fas fa-comment"></i>
                        <span class="comment-count" data-section="land_documents">0</span>
                    </button>
                </div>
                <div class="card-body">
                    <div class="facility-detail-table">
                        <div class="detail-row">
                            <span class="detail-label">賃貸借契約書・覚書PDF</span>
                            <span class="detail-value">
                                @if($landInfo->lease_contract_pdf_path)
                                    <a href="{{ \Storage::url($landInfo->lease_contract_pdf_path) }}" 
                                       target="_blank" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-file-pdf me-1"></i>
                                        {{ $landInfo->lease_contract_pdf_name ?? 'ダウンロード' }}
                                    </a>
                                @else
                                    <span class="text-muted">未アップロード</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">謄本PDF</span>
                            <span class="detail-value">
                                @if($landInfo->registry_pdf_path)
                                    <a href="{{ \Storage::url($landInfo->registry_pdf_path) }}" 
                                       target="_blank" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-file-pdf me-1"></i>
                                        {{ $landInfo->registry_pdf_name ?? 'ダウンロード' }}
                                    </a>
                                @else
                                    <span class="text-muted">未アップロード</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    
                    <!-- コメントセクション -->
                    <div class="comment-section mt-3 d-none" data-section="land_documents">
                        <hr>
                        <div class="comment-form mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control comment-input" 
                                       placeholder="コメントを入力..." 
                                       data-section="land_documents">
                                <button class="btn btn-primary comment-submit" 
                                        data-section="land_documents">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                        <div class="comment-list" data-section="land_documents">
                            <!-- コメントがここに動的に追加されます -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 備考カード -->
        <!-- 備考カード -->
        <div class="col-12 mb-4">
            <div class="card-section mb-4">
                <div class="card-section-header">
                    <div>
                        <i class="fas fa-sticky-note me-2"></i>備考欄
                        @if(auth()->user()->canEditLandBasicInfo())
                            <small class="text-muted ms-2">(土地総務変更時)</small>
                        @endif
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
                        @if($landInfo->notes)
                            {!! nl2br(e($landInfo->notes)) !!}
                        @else
                            <span class="text-muted">未設定</span>
                        @endif
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
        </div>



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