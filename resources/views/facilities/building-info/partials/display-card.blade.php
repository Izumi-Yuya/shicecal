{{-- 建物情報表示カード --}}
<div class="building-info-card">
    @if($buildingInfo)
        {{-- 所有区分 --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card facility-info-card detail-card-improved h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-home me-2"></i>所有区分
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($buildingInfo->ownership_type) ? 'empty-field' : '' }}">
                                <span class="detail-label">所有任意項目</span>
                                <span class="detail-value">
                                    @if($buildingInfo->ownership_type)
                                        <span class="badge 
                                            @if($buildingInfo->ownership_type === '自社') bg-success
                                            @elseif($buildingInfo->ownership_type === '賃借') bg-primary
                                            @elseif($buildingInfo->ownership_type === '賃貸') bg-info
                                            @endif
                                        ">
                                            {{ $buildingInfo->ownership_type }}
                                        </span>
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

        {{-- 建築面積・延床面積 --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card facility-info-card detail-card-improved h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-ruler-combined me-2"></i>建築面積・延床面積
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($buildingInfo->building_area_sqm) ? 'empty-field' : '' }}">
                                <span class="detail-label">建築面積（㎡）</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->building_area_sqm ? number_format($buildingInfo->building_area_sqm, 2) . '㎡' : '未設定' }}
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->building_area_tsubo) ? 'empty-field' : '' }}">
                                <span class="detail-label">建築面積（坪数）</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->building_area_tsubo ? number_format($buildingInfo->building_area_tsubo, 2) . '坪' : '未設定' }}
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->total_floor_area_sqm) ? 'empty-field' : '' }}">
                                <span class="detail-label">延床面積（㎡）</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->total_floor_area_sqm ? number_format($buildingInfo->total_floor_area_sqm, 2) . '㎡' : '未設定' }}
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->total_floor_area_tsubo) ? 'empty-field' : '' }}">
                                <span class="detail-label">延床面積（坪数）</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->total_floor_area_tsubo ? number_format($buildingInfo->total_floor_area_tsubo, 2) . '坪' : '未設定' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card facility-info-card detail-card-improved h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-yen-sign me-2"></i>建築費用・賃料
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($buildingInfo->construction_cost) ? 'empty-field' : '' }}">
                                <span class="detail-label">本体価格（建築費用）</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->formatted_construction_cost ?? '未設定' }}
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->cost_per_tsubo) ? 'empty-field' : '' }}">
                                <span class="detail-label">坪単価（自動計算）</span>
                                <span class="detail-value">
                                    @if($buildingInfo->cost_per_tsubo)
                                        ¥{{ number_format($buildingInfo->cost_per_tsubo) }}/坪
                                        <span class="auto-calc-indicator">
                                            <i class="fas fa-calculator"></i>
                                            <small>自動計算</small>
                                        </span>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->construction_cooperation_fee) ? 'empty-field' : '' }}">
                                <span class="detail-label">建設協力金</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->construction_cooperation_fee ? '¥' . number_format($buildingInfo->construction_cooperation_fee) : '未設定' }}
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->monthly_rent) ? 'empty-field' : '' }}">
                                <span class="detail-label">家賃（月）</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->formatted_monthly_rent ?? '未設定' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 契約情報 --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card facility-info-card detail-card-improved">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-file-contract me-2"></i>契約情報
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($buildingInfo->contract_start_date) ? 'empty-field' : '' }}">
                                <span class="detail-label">契約開始日</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->contract_start_date ? $buildingInfo->contract_start_date->format('Y年m月d日') : '未設定' }}
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->contract_end_date) ? 'empty-field' : '' }}">
                                <span class="detail-label">契約終了日</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->contract_end_date ? $buildingInfo->contract_end_date->format('Y年m月d日') : '未設定' }}
                                </span>
                            </div>
                            <div class="detail-row {{ $buildingInfo->auto_renewal === null ? 'empty-field' : '' }}">
                                <span class="detail-label">自動更新</span>
                                <span class="detail-value">
                                    @if($buildingInfo->auto_renewal !== null)
                                        <span class="badge {{ $buildingInfo->auto_renewal ? 'bg-success' : 'bg-warning' }}">
                                            {{ $buildingInfo->auto_renewal ? 'あり' : 'なし' }}
                                        </span>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->contract_years) ? 'empty-field' : '' }}">
                                <span class="detail-label">契約年数</span>
                                <span class="detail-value">
                                    @if($buildingInfo->contract_years)
                                        {{ $buildingInfo->contract_years }}年
                                        <span class="auto-calc-indicator">
                                            <i class="fas fa-calculator"></i>
                                            <small>自動計算</small>
                                        </span>
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

        {{-- 管理会社情報 --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card facility-info-card detail-card-improved h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-building me-2"></i>管理会社情報
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($buildingInfo->management_company_name) ? 'empty-field' : '' }}">
                                <span class="detail-label">会社名</span>
                                <span class="detail-value">{{ $buildingInfo->management_company_name ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->management_company_postal_code) ? 'empty-field' : '' }}">
                                <span class="detail-label">郵便番号</span>
                                <span class="detail-value">{{ $buildingInfo->formatted_management_postal_code ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->management_company_address) ? 'empty-field' : '' }}">
                                <span class="detail-label">住所</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->management_company_address ?? '未設定' }}
                                    @if($buildingInfo->management_company_building_name)
                                        <br><small class="text-muted">{{ $buildingInfo->management_company_building_name }}</small>
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->management_company_phone) ? 'empty-field' : '' }}">
                                <span class="detail-label">電話番号</span>
                                <span class="detail-value">{{ $buildingInfo->management_company_phone ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->management_company_fax) ? 'empty-field' : '' }}">
                                <span class="detail-label">FAX番号</span>
                                <span class="detail-value">{{ $buildingInfo->management_company_fax ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->management_company_email) ? 'empty-field' : '' }}">
                                <span class="detail-label">メールアドレス</span>
                                <span class="detail-value">
                                    @if($buildingInfo->management_company_email)
                                        <a href="mailto:{{ $buildingInfo->management_company_email }}">{{ $buildingInfo->management_company_email }}</a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->management_company_url) ? 'empty-field' : '' }}">
                                <span class="detail-label">URL</span>
                                <span class="detail-value">
                                    @if($buildingInfo->management_company_url)
                                        <a href="{{ $buildingInfo->management_company_url }}" target="_blank">{{ $buildingInfo->management_company_url }}</a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- オーナー情報 --}}
            <div class="col-md-6">
                <div class="card facility-info-card detail-card-improved h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-user-tie me-2"></i>オーナー情報
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($buildingInfo->owner_name) ? 'empty-field' : '' }}">
                                <span class="detail-label">氏名・会社名</span>
                                <span class="detail-value">{{ $buildingInfo->owner_name ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->owner_postal_code) ? 'empty-field' : '' }}">
                                <span class="detail-label">郵便番号</span>
                                <span class="detail-value">{{ $buildingInfo->formatted_owner_postal_code ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->owner_address) ? 'empty-field' : '' }}">
                                <span class="detail-label">住所</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->owner_address ?? '未設定' }}
                                    @if($buildingInfo->owner_building_name)
                                        <br><small class="text-muted">{{ $buildingInfo->owner_building_name }}</small>
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->owner_phone) ? 'empty-field' : '' }}">
                                <span class="detail-label">電話番号</span>
                                <span class="detail-value">{{ $buildingInfo->owner_phone ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->owner_fax) ? 'empty-field' : '' }}">
                                <span class="detail-label">FAX番号</span>
                                <span class="detail-value">{{ $buildingInfo->owner_fax ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->owner_email) ? 'empty-field' : '' }}">
                                <span class="detail-label">メールアドレス</span>
                                <span class="detail-value">
                                    @if($buildingInfo->owner_email)
                                        <a href="mailto:{{ $buildingInfo->owner_email }}">{{ $buildingInfo->owner_email }}</a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->owner_url) ? 'empty-field' : '' }}">
                                <span class="detail-label">URL</span>
                                <span class="detail-value">
                                    @if($buildingInfo->owner_url)
                                        <a href="{{ $buildingInfo->owner_url }}" target="_blank">{{ $buildingInfo->owner_url }}</a>
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

        {{-- 施工会社・建築情報 --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card facility-info-card detail-card-improved h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-hard-hat me-2"></i>施工会社情報
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($buildingInfo->construction_company_name) ? 'empty-field' : '' }}">
                                <span class="detail-label">会社名</span>
                                <span class="detail-value">{{ $buildingInfo->construction_company_name ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->construction_company_phone) ? 'empty-field' : '' }}">
                                <span class="detail-label">電話番号</span>
                                <span class="detail-value">{{ $buildingInfo->construction_company_phone ?? '未設定' }}</span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->construction_company_notes) ? 'empty-field' : '' }}">
                                <span class="detail-label">備考</span>
                                <span class="detail-value">{{ $buildingInfo->construction_company_notes ?? '未設定' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card facility-info-card detail-card-improved h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>建築情報
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($buildingInfo->completion_date) ? 'empty-field' : '' }}">
                                <span class="detail-label">竣工日</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->completion_date ? $buildingInfo->completion_date->format('Y年m月d日') : '未設定' }}
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->building_age) ? 'empty-field' : '' }}">
                                <span class="detail-label">築年数</span>
                                <span class="detail-value">
                                    @if($buildingInfo->building_age)
                                        {{ $buildingInfo->building_age }}年
                                        <span class="auto-calc-indicator">
                                            <i class="fas fa-calculator"></i>
                                            <small>自動計算</small>
                                        </span>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->useful_life) ? 'empty-field' : '' }}">
                                <span class="detail-label">耐用年数</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->useful_life ? $buildingInfo->useful_life . '年' : '未設定' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 法定書類・検査情報 --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card facility-info-card detail-card-improved">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-file-pdf me-2"></i>法定書類・検査情報
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($buildingInfo->building_permit_pdf) ? 'empty-field' : '' }}">
                                <span class="detail-label">建築確認済証</span>
                                <span class="detail-value">
                                    @if($buildingInfo->building_permit_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->building_permit_pdf) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-pdf me-1"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->building_inspection_pdf) ? 'empty-field' : '' }}">
                                <span class="detail-label">建築検査済証</span>
                                <span class="detail-value">
                                    @if($buildingInfo->building_inspection_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->building_inspection_pdf) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-pdf me-1"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->fire_equipment_inspection_pdf) ? 'empty-field' : '' }}">
                                <span class="detail-label">消防用設備等検査済証</span>
                                <span class="detail-value">
                                    @if($buildingInfo->fire_equipment_inspection_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->fire_equipment_inspection_pdf) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-pdf me-1"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->periodic_inspection_type) ? 'empty-field' : '' }}">
                                <span class="detail-label">特定建築物定期調査</span>
                                <span class="detail-value">
                                    @if($buildingInfo->periodic_inspection_type)
                                        <span class="badge bg-info">{{ $buildingInfo->periodic_inspection_type }}</span>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->periodic_inspection_date) ? 'empty-field' : '' }}">
                                <span class="detail-label">調査実施日</span>
                                <span class="detail-value">
                                    {{ $buildingInfo->periodic_inspection_date ? $buildingInfo->periodic_inspection_date->format('Y年m月d日') : '未設定' }}
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->periodic_inspection_pdf) ? 'empty-field' : '' }}">
                                <span class="detail-label">調査報告書</span>
                                <span class="detail-value">
                                    @if($buildingInfo->periodic_inspection_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->periodic_inspection_pdf) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-pdf me-1"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->periodic_inspection_notes) ? 'empty-field' : '' }}">
                                <span class="detail-label">調査備考</span>
                                <span class="detail-value">{{ $buildingInfo->periodic_inspection_notes ?? '未設定' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- その他契約書類 --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card facility-info-card detail-card-improved">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-folder-open me-2"></i>その他契約書類
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($buildingInfo->construction_contract_pdf) ? 'empty-field' : '' }}">
                                <span class="detail-label">工事請負契約書</span>
                                <span class="detail-value">
                                    @if($buildingInfo->construction_contract_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->construction_contract_pdf) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-pdf me-1"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->lease_contract_pdf) ? 'empty-field' : '' }}">
                                <span class="detail-label">賃貸借契約書・覚書</span>
                                <span class="detail-value">
                                    @if($buildingInfo->lease_contract_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->lease_contract_pdf) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-pdf me-1"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </span>
                            </div>
                            <div class="detail-row {{ empty($buildingInfo->registry_pdf) ? 'empty-field' : '' }}">
                                <span class="detail-label">謄本</span>
                                <span class="detail-value">
                                    @if($buildingInfo->registry_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->registry_pdf) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-pdf me-1"></i>PDF表示
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
        </div>

        {{-- 備考欄 --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card facility-info-card detail-card-improved">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-sticky-note me-2"></i>備考
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="facility-detail-table">
                            <div class="detail-row {{ empty($buildingInfo->notes) ? 'empty-field' : '' }}">
                                <span class="detail-label">備考欄</span>
                                <span class="detail-value">
                                    @if($buildingInfo->notes)
                                        {!! nl2br(e($buildingInfo->notes)) !!}
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

    @else
        {{-- 建物情報が未登録の場合の表示 --}}
        <div class="row">
            <div class="col-12">
                <div class="card facility-info-card detail-card-improved">
                    <div class="card-body text-center py-5">
                        <div class="empty-icon mb-4">
                            <i class="fas fa-building fa-4x text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-3">建物情報が登録されていません</h5>
                        <p class="text-muted mb-4">
                            この施設の詳細な建物情報はまだ登録されていません。<br>
                            建物情報を登録するには、編集ボタンから入力してください。
                        </p>
                        @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                            <a href="{{ route('facilities.building-info.edit', $facility) }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>建物情報を登録
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>