{{-- Original land info display card - backup before migration --}}
<!-- 土地情報テーブル -->

<!-- 所有テーブル -->
<div class="card facility-info-card detail-card-improved mb-3">
    <div class="card-body card-body-clean" style="padding: 0;">
        <div class="table-responsive">
            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                <tbody>
                    <tr>
                        <td class="detail-label" style="padding: 0.5rem;">所有</td>
                        <td class="detail-value {{ empty($landInfo->ownership_type) ? 'empty-field' : '' }}" colspan="3" style="padding: 0.5rem;">
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
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 基本情報テーブル -->
<div class="card facility-info-card detail-card-improved mb-3">
    <div class="card-body card-body-clean" style="padding: 0;">
        <div class="table-responsive">
            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                <tbody>
                    <tr>
                        <td class="detail-label" style="padding: 0.5rem;">敷地面積（㎡数）</td>
                        <td class="detail-value {{ $landInfo->site_area_sqm === null ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            @if($landInfo->site_area_sqm !== null)
                                {{ number_format($landInfo->site_area_sqm, 2) }}㎡
                            @else
                                未設定
                            @endif
                        </td>
                        <td class="detail-label" style="padding: 0.5rem;">敷地面積（坪数）</td>
                        <td class="detail-value {{ $landInfo->site_area_tsubo === null ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            @if($landInfo->site_area_tsubo !== null)
                                {{ number_format($landInfo->site_area_tsubo, 2) }}坪
                            @else
                                未設定
                            @endif
                        </td>
                        <td class="detail-label" style="padding: 0.5rem;">敷地内駐車場台数</td>
                        <td class="detail-value {{ $landInfo->parking_spaces === null ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            @if($landInfo->parking_spaces !== null)
                                {{ number_format($landInfo->parking_spaces) }}台
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="detail-label" style="padding: 0.5rem;">購入金額</td>
                        <td class="detail-value {{ $landInfo->purchase_price === null ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            @if($landInfo->purchase_price !== null)
                                {{ number_format($landInfo->purchase_price) }}円
                            @else
                                未設定
                            @endif
                        </td>
                        <td class="detail-label" style="padding: 0.5rem;">坪単価</td>
                        <td class="detail-value {{ $landInfo->unit_price_per_tsubo === null ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            @if($landInfo->unit_price_per_tsubo !== null)
                                {{ number_format($landInfo->unit_price_per_tsubo) }}円/坪
                            @else
                                未設定
                            @endif
                        </td>
                        <td class="detail-label" style="padding: 0.5rem;">謄本</td>
                        <td class="detail-value {{ empty($landInfo->registry_pdf_name) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            @if($landInfo->registry_pdf_name)
                                <a href="{{ route('facilities.land-info.download', ['facility' => $facility, 'type' => 'registry']) }}" 
                                   class="text-decoration-none" target="_blank">
                                    <i class="fas fa-file-alt text-info me-2"></i>{{ $landInfo->registry_pdf_name }}
                                </a>
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="detail-label" style="padding: 0.5rem;">家賃</td>
                        <td class="detail-value {{ $landInfo->monthly_rent === null ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            @if($landInfo->monthly_rent !== null)
                                {{ number_format($landInfo->monthly_rent) }}円
                            @else
                                未設定
                            @endif
                        </td>
                        <td class="detail-label" style="background-color: #495057 !important; color: white !important; padding: 0.5rem;"></td>
                        <td class="detail-value" style="padding: 0.5rem;">-</td>
                        <td class="detail-label" style="padding: 0.5rem;">契約書・覚書</td>
                        <td class="detail-value {{ empty($landInfo->lease_contract_pdf_name) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            @if($landInfo->lease_contract_pdf_name)
                                <a href="{{ route('facilities.land-info.download', ['facility' => $facility, 'type' => 'lease_contract']) }}" 
                                   class="text-decoration-none" target="_blank">
                                    <i class="fas fa-file-contract text-warning me-2"></i>{{ $landInfo->lease_contract_pdf_name }}
                                </a>
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="detail-label" style="padding: 0.5rem;">契約期間</td>
                        <td class="detail-value {{ (!$landInfo->contract_start_date || !$landInfo->contract_end_date) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            @if($landInfo->contract_start_date && $landInfo->contract_end_date)
                                {{ $landInfo->contract_start_date->format('Y年m月d日') }} ～ {{ $landInfo->contract_end_date->format('Y年m月d日') }}
                            @else
                                未設定
                            @endif
                        </td>
                        <td class="detail-label" style="padding: 0.5rem;">自動更新の有無</td>
                        <td class="detail-value {{ empty($landInfo->auto_renewal) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            @if($landInfo->auto_renewal === 'yes')
                                <span class="badge bg-success">あり</span>
                            @elseif($landInfo->auto_renewal === 'no')
                                <span class="badge bg-secondary">なし</span>
                            @else
                                未設定
                            @endif
                        </td>
                        <td class="detail-label" style="padding: 0.5rem;">契約年数</td>
                        <td class="detail-value {{ empty($landInfo->contract_period_text) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            {{ $landInfo->contract_period_text ?? '未設定' }}
                        </td>
                    </tr>

                    <tr>
                        <td class="detail-label" style="padding: 0.5rem;">備考</td>
                        <td class="detail-value {{ empty($landInfo->notes) ? 'empty-field' : '' }}" colspan="5" style="padding: 0.5rem;">
                            {{ $landInfo->notes ?? '未設定' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 管理会社情報・オーナー情報テーブル -->
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
                                <td class="detail-value {{ empty($landInfo->management_company_name) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->management_company_name ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">郵便番号</td>
                                <td class="detail-value {{ empty($landInfo->management_company_postal_code) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->management_company_postal_code ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">住所</td>
                                <td class="detail-value {{ empty($landInfo->management_company_address) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->management_company_address ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">住所（建物名）</td>
                                <td class="detail-value {{ empty($landInfo->management_company_building) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->management_company_building ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">電話番号</td>
                                <td class="detail-value {{ empty($landInfo->management_company_phone) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->management_company_phone ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">FAX番号</td>
                                <td class="detail-value {{ empty($landInfo->management_company_fax) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->management_company_fax ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">メールアドレス</td>
                                <td class="detail-value {{ empty($landInfo->management_company_email) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($landInfo->management_company_email)
                                        <a href="mailto:{{ $landInfo->management_company_email }}" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>{{ $landInfo->management_company_email }}
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">URL</td>
                                <td class="detail-value {{ empty($landInfo->management_company_url) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($landInfo->management_company_url)
                                        <a href="{{ $landInfo->management_company_url }}" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-external-link-alt me-1"></i>{{ $landInfo->management_company_url }}
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">備考</td>
                                <td class="detail-value {{ empty($landInfo->management_company_notes) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->management_company_notes ?? '未設定' }}
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
                                <td class="detail-label" style="padding: 0.5rem;">氏名</td>
                                <td class="detail-value {{ empty($landInfo->owner_name) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->owner_name ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">郵便番号</td>
                                <td class="detail-value {{ empty($landInfo->owner_postal_code) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->owner_postal_code ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">住所</td>
                                <td class="detail-value {{ empty($landInfo->owner_address) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->owner_address ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">住所（建物名）</td>
                                <td class="detail-value {{ empty($landInfo->owner_building) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->owner_building ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">電話番号</td>
                                <td class="detail-value {{ empty($landInfo->owner_phone) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->owner_phone ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">FAX番号</td>
                                <td class="detail-value {{ empty($landInfo->owner_fax) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->owner_fax ?? '未設定' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">メールアドレス</td>
                                <td class="detail-value {{ empty($landInfo->owner_email) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($landInfo->owner_email)
                                        <a href="mailto:{{ $landInfo->owner_email }}" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>{{ $landInfo->owner_email }}
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">URL</td>
                                <td class="detail-value {{ empty($landInfo->owner_url) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if($landInfo->owner_url)
                                        <a href="{{ $landInfo->owner_url }}" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-external-link-alt me-1"></i>{{ $landInfo->owner_url }}
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="detail-label" style="padding: 0.5rem;">備考</td>
                                <td class="detail-value {{ empty($landInfo->owner_notes) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $landInfo->owner_notes ?? '未設定' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>