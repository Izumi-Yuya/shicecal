{{-- 建物情報表示カード --}}
<div class="building-info-card">
    {{-- 開発中表示 --}}
    <div class="alert alert-warning mb-3" role="alert">
        <i class="fas fa-tools me-2"></i>
        <strong>開発中</strong> - 建物情報の詳細機能は現在開発中です。
    </div>
    @if($buildingInfo)
        {{-- 所有テーブル --}}
        <div class="card facility-info-card detail-card-improved mb-3">
            <div class="card-body card-body-clean" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                        <tbody>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">所有</td>
                                <td class="detail-value {{ empty($buildingInfo->ownership_type) ? 'empty-field' : '' }}" colspan="5" style="padding: 0.5rem;">
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
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 基本情報テーブル --}}
        <div class="card facility-info-card detail-card-improved mb-3">
            <div class="card-body card-body-clean" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                        <tbody>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">建築面積（㎡数）</td>
                                <td class="detail-value {{ empty($buildingInfo->building_area_sqm) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $buildingInfo->building_area_sqm ? number_format($buildingInfo->building_area_sqm, 2) . '㎡' : '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">建築面積（坪数）</td>
                                <td class="detail-value {{ empty($buildingInfo->building_area_tsubo) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $buildingInfo->building_area_tsubo ? number_format($buildingInfo->building_area_tsubo, 2) . '坪' : '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">竣工日</td>
                                <td class="detail-value {{ empty($buildingInfo->completion_date) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $buildingInfo->completion_date ? $buildingInfo->completion_date->format('Y年m月d日') : '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">延床面積（㎡数）</td>
                                <td class="detail-value {{ empty($buildingInfo->total_floor_area_sqm) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $buildingInfo->total_floor_area_sqm ? number_format($buildingInfo->total_floor_area_sqm, 2) . '㎡' : '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">延べ床面積（坪数）</td>
                                <td class="detail-value {{ empty($buildingInfo->total_floor_area_tsubo) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $buildingInfo->total_floor_area_tsubo ? number_format($buildingInfo->total_floor_area_tsubo, 2) . '坪' : '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">築年数</td>
                                <td class="detail-value {{ empty($buildingInfo->building_age) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->building_age)
                                        {{ $buildingInfo->building_age }}年
                                        <span class="auto-calc-indicator">
                                            <i class="fas fa-calculator"></i>
                                            <small>自動計算</small>
                                        </span>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">建築費用</td>
                                <td class="detail-value {{ empty($buildingInfo->construction_cost) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $buildingInfo->formatted_construction_cost ?? '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">坪単価</td>
                                <td class="detail-value {{ empty($buildingInfo->cost_per_tsubo) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->cost_per_tsubo)
                                        ¥{{ number_format($buildingInfo->cost_per_tsubo) }}/坪
                                    @else
                                        未設定
                                    @endif
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">耐用年数</td>
                                <td class="detail-value {{ empty($buildingInfo->useful_life) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $buildingInfo->useful_life ? $buildingInfo->useful_life . '年' : '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">建設協力金</td>
                                <td class="detail-value {{ empty($buildingInfo->construction_cooperation_fee) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $buildingInfo->construction_cooperation_fee ? '¥' . number_format($buildingInfo->construction_cooperation_fee) : '未設定' }}
                                </td>
                                <td class="detail-label" style="background-color: #495057 !important; color: white !important; padding: 0.5rem;"></td>
                                <td class="detail-value" style="padding: 0.5rem;">-</td>
                                <td class="detail-label" style="padding: 0.5rem;">工事請負契約書</td>
                                <td class="detail-value {{ empty($buildingInfo->construction_contract_pdf) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->construction_contract_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->construction_contract_pdf) }}" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-file-pdf text-info me-2"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">家賃</td>
                                <td class="detail-value {{ empty($buildingInfo->monthly_rent) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $buildingInfo->formatted_monthly_rent ?? '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">契約年数</td>
                                <td class="detail-value {{ empty($buildingInfo->contract_years) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->contract_years)
                                        {{ $buildingInfo->contract_years }}年
                                        <span class="auto-calc-indicator">
                                            <i class="fas fa-calculator"></i>
                                            <small>自動計算</small>
                                        </span>
                                    @else
                                        未設定
                                    @endif
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">賃貸借契約書・覚書</td>
                                <td class="detail-value {{ empty($buildingInfo->lease_contract_pdf) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->lease_contract_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->lease_contract_pdf) }}" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-file-contract text-warning me-2"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">契約期間</td>
                                <td class="detail-value {{ (!$buildingInfo->contract_start_date || !$buildingInfo->contract_end_date) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->contract_start_date && $buildingInfo->contract_end_date)
                                        {{ $buildingInfo->contract_start_date->format('Y年m月d日') }} ～ {{ $buildingInfo->contract_end_date->format('Y年m月d日') }}
                                    @else
                                        未設定
                                    @endif
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">自動更新の有無</td>
                                <td class="detail-value {{ $buildingInfo->auto_renewal === null ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->auto_renewal !== null)
                                        <span class="badge {{ $buildingInfo->auto_renewal ? 'bg-success' : 'bg-warning' }}">
                                            {{ $buildingInfo->auto_renewal ? 'あり' : 'なし' }}
                                        </span>
                                    @else
                                        未設定
                                    @endif
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">謄本</td>
                                <td class="detail-value {{ empty($buildingInfo->registry_pdf) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->registry_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->registry_pdf) }}" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-file-alt text-info me-2"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">施工会社</td>
                                <td class="detail-value {{ empty($buildingInfo->construction_company_name) ? 'empty-field' : '' }}" colspan="3" style="padding: 0.5rem;">
                                    {{ $buildingInfo->construction_company_name ?? '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">建築確認済証</td>
                                <td class="detail-value {{ empty($buildingInfo->building_permit_pdf) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->building_permit_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->building_permit_pdf) }}" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-file-pdf text-info me-2"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">連絡先</td>
                                <td class="detail-value {{ empty($buildingInfo->construction_company_phone) ? 'empty-field' : '' }}" colspan="3" style="padding: 0.5rem;">
                                    {{ $buildingInfo->construction_company_phone ?? '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">建築検査済証</td>
                                <td class="detail-value {{ empty($buildingInfo->building_inspection_pdf) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->building_inspection_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->building_inspection_pdf) }}" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-file-pdf text-info me-2"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">定期調査会社</td>
                                <td class="detail-value {{ empty($buildingInfo->periodic_inspection_type) ? 'empty-field' : '' }}" colspan="3" style="padding: 0.5rem;">
                                    @if($buildingInfo->periodic_inspection_type)
                                        <span class="badge bg-info">{{ $buildingInfo->periodic_inspection_type }}</span>
                                    @else
                                        未設定
                                    @endif
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">消防用設備等検査</td>
                                <td class="detail-value {{ empty($buildingInfo->fire_equipment_inspection_pdf) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->fire_equipment_inspection_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->fire_equipment_inspection_pdf) }}" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-file-pdf text-info me-2"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">連絡先</td>
                                <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                                <td class="detail-label" style="padding: 0.5rem;">調査日</td>
                                <td class="detail-value {{ empty($buildingInfo->periodic_inspection_date) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $buildingInfo->periodic_inspection_date ? $buildingInfo->periodic_inspection_date->format('Y年m月d日') : '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">調査結果</td>
                                <td class="detail-value {{ empty($buildingInfo->periodic_inspection_pdf) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($buildingInfo->periodic_inspection_pdf)
                                        <a href="{{ asset('storage/' . $buildingInfo->periodic_inspection_pdf) }}" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-file-pdf text-info me-2"></i>PDF表示
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">備考</td>
                                <td class="detail-value {{ empty($buildingInfo->notes) ? 'empty-field' : '' }}" colspan="5" style="padding: 0.5rem;">
                                    @if($buildingInfo->notes)
                                        {!! nl2br(e($buildingInfo->notes)) !!}
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 管理会社情報・オーナー情報テーブル --}}
        <div class="row mb-3">
            <!-- 管理会社情報テーブル -->
            <div class="col-md-6">
                <div class="card facility-info-card detail-card-improved h-100">
                    <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #dee2e6;">
                        <h6 style="margin: 0; font-weight: bold; color: #333;">管理会社情報</h6>
                    </div>
                    <div class="card-body card-body-clean" style="padding: 0;">
                        <div class="table-responsive">
                            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                                <tbody>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">会社名</td>
                                        <td class="detail-value {{ empty($buildingInfo->management_company_name) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $buildingInfo->management_company_name ?? '未設定' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">郵便番号</td>
                                        <td class="detail-value {{ empty($buildingInfo->management_company_postal_code) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $buildingInfo->formatted_management_postal_code ?? '未設定' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">住所</td>
                                        <td class="detail-value {{ empty($buildingInfo->management_company_address) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $buildingInfo->management_company_address ?? '未設定' }}
                                            @if($buildingInfo->management_company_building_name)
                                                <br><small class="text-muted">{{ $buildingInfo->management_company_building_name }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">電話番号</td>
                                        <td class="detail-value {{ empty($buildingInfo->management_company_phone) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $buildingInfo->management_company_phone ?? '未設定' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">FAX番号</td>
                                        <td class="detail-value {{ empty($buildingInfo->management_company_fax) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $buildingInfo->management_company_fax ?? '未設定' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">メールアドレス</td>
                                        <td class="detail-value {{ empty($buildingInfo->management_company_email) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            @if($buildingInfo->management_company_email)
                                                <a href="mailto:{{ $buildingInfo->management_company_email }}" class="text-decoration-none">
                                                    <i class="fas fa-envelope me-1"></i>{{ $buildingInfo->management_company_email }}
                                                </a>
                                            @else
                                                未設定
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">URL</td>
                                        <td class="detail-value {{ empty($buildingInfo->management_company_url) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            @if($buildingInfo->management_company_url)
                                                <a href="{{ $buildingInfo->management_company_url }}" target="_blank" class="text-decoration-none">
                                                    <i class="fas fa-external-link-alt me-1"></i>{{ $buildingInfo->management_company_url }}
                                                </a>
                                            @else
                                                未設定
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- オーナー情報テーブル -->
            <div class="col-md-6">
                <div class="card facility-info-card detail-card-improved h-100">
                    <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #dee2e6;">
                        <h6 style="margin: 0; font-weight: bold; color: #333;">オーナー情報</h6>
                    </div>
                    <div class="card-body card-body-clean" style="padding: 0;">
                        <div class="table-responsive">
                            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                                <tbody>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">氏名・会社名</td>
                                        <td class="detail-value {{ empty($buildingInfo->owner_name) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $buildingInfo->owner_name ?? '未設定' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">郵便番号</td>
                                        <td class="detail-value {{ empty($buildingInfo->owner_postal_code) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $buildingInfo->formatted_owner_postal_code ?? '未設定' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">住所</td>
                                        <td class="detail-value {{ empty($buildingInfo->owner_address) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $buildingInfo->owner_address ?? '未設定' }}
                                            @if($buildingInfo->owner_building_name)
                                                <br><small class="text-muted">{{ $buildingInfo->owner_building_name }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">電話番号</td>
                                        <td class="detail-value {{ empty($buildingInfo->owner_phone) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $buildingInfo->owner_phone ?? '未設定' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">FAX番号</td>
                                        <td class="detail-value {{ empty($buildingInfo->owner_fax) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $buildingInfo->owner_fax ?? '未設定' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">メールアドレス</td>
                                        <td class="detail-value {{ empty($buildingInfo->owner_email) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            @if($buildingInfo->owner_email)
                                                <a href="mailto:{{ $buildingInfo->owner_email }}" class="text-decoration-none">
                                                    <i class="fas fa-envelope me-1"></i>{{ $buildingInfo->owner_email }}
                                                </a>
                                            @else
                                                未設定
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">URL</td>
                                        <td class="detail-value {{ empty($buildingInfo->owner_url) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            @if($buildingInfo->owner_url)
                                                <a href="{{ $buildingInfo->owner_url }}" target="_blank" class="text-decoration-none">
                                                    <i class="fas fa-external-link-alt me-1"></i>{{ $buildingInfo->owner_url }}
                                                </a>
                                            @else
                                                未設定
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
                    <div class="card-body text-center">
                        <i class="fas fa-building fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">建物情報が未登録です</h5>
                        <p class="text-muted">建物情報を登録してください。</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>