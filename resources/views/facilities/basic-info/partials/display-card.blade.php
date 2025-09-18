<!-- 基本情報テーブル -->
<div class="card facility-info-card detail-card-improved mb-3">
    <div class="card-body card-body-clean">
        <div class="table-responsive">
            <table class="table table-bordered facility-basic-info-table-clean">
                <tbody>
                    <tr >
                        <td class="detail-label" >会社名</td>
                        <td class="detail-value {{ empty($facility->company_name) ? 'empty-field' : '' }}" >
                            {{ $facility->company_name ?? '未設定' }}
                        </td>
                        <td class="detail-label" >事業所コード</td>
                        <td class="detail-value {{ empty($facility->office_code) ? 'empty-field' : '' }}" >
                            @if($facility->office_code)
                                <span class="badge bg-primary">{{ $facility->office_code }}</span>
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>
                    <tr >
                        <td class="detail-label" >施設名</td>
                        <td class="detail-value {{ empty($facility->facility_name) ? 'empty-field' : '' }} fw-bold" >
                            {{ $facility->facility_name ?? '未設定' }}
                        </td>
                        <td class="detail-label" >指定番号</td>
                        <td class="detail-value {{ empty($facility->designation_number) ? 'empty-field' : '' }}" >
                            {{ $facility->designation_number ?? '未設定' }}
                        </td>
                    </tr>
                    <tr >
                        <td class="detail-label" >郵便番号</td>
                        <td class="detail-value {{ empty($facility->formatted_postal_code) ? 'empty-field' : '' }}" >
                            {{ $facility->formatted_postal_code ?? '未設定' }}
                        </td>
                        <td class="detail-label" >開設日</td>
                        <td class="detail-value {{ empty($facility->opening_date) ? 'empty-field' : '' }}" >
                            @if($facility->opening_date)
                                {{ $facility->opening_date->format('Y年m月d日') }}
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>
                    <tr >
                        <td class="detail-label" >住所</td>
                        <td class="detail-value {{ empty($facility->full_address) ? 'empty-field' : '' }}" >
                            {{ $facility->full_address ?? '未設定' }}
                        </td>
                        <td class="detail-label" >開設年数</td>
                        <td class="detail-value {{ empty($facility->opening_date) ? 'empty-field' : '' }}" >
                            @if($facility->opening_date)
                                @php
                                    $yearsInOperation = $facility->opening_date->diffInYears(now());
                                @endphp
                                {{ $yearsInOperation }}年
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>
                    <tr >
                        <td class="detail-label" >住所（建物名）</td>
                        <td class="detail-value {{ empty($facility->building_name) ? 'empty-field' : '' }}" >
                            {{ $facility->building_name ?? '未設定' }}
                        </td>
                        <td class="detail-label" >建物構造</td>
                        <td class="detail-value {{ empty($facility->building_structure) ? 'empty-field' : '' }}" >
                            {{ $facility->building_structure ?? '未設定' }}
                        </td>
                    </tr>
                    <tr >
                        <td class="detail-label" >電話番号</td>
                        <td class="detail-value {{ empty($facility->phone_number) ? 'empty-field' : '' }}" >
                            {{ $facility->phone_number ?? '未設定' }}
                        </td>
                        <td class="detail-label" >建物階数</td>
                        <td class="detail-value {{ empty($facility->building_floors) ? 'empty-field' : '' }}" >
                            @if($facility->building_floors)
                                {{ $facility->building_floors }}階
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>
                    <tr >
                        <td class="detail-label" >FAX番号</td>
                        <td class="detail-value {{ empty($facility->fax_number) ? 'empty-field' : '' }}" >
                            {{ $facility->fax_number ?? '未設定' }}
                        </td>
                        <td class="detail-label" >居室数</td>
                        <td class="detail-value {{ $facility->paid_rooms_count === null ? 'empty-field' : '' }}" >
                            @if($facility->paid_rooms_count !== null)
                                {{ $facility->paid_rooms_count }}室
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>
                    <tr >
                        <td class="detail-label" >フリーダイヤル</td>
                        <td class="detail-value {{ empty($facility->toll_free_number) ? 'empty-field' : '' }}" >
                            {{ $facility->toll_free_number ?? '未設定' }}
                        </td>
                        <td class="detail-label" >内SS数</td>
                        <td class="detail-value {{ $facility->ss_rooms_count === null ? 'empty-field' : '' }}" >
                            @if($facility->ss_rooms_count !== null)
                                {{ $facility->ss_rooms_count }}室
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>
                    <tr >
                        <td class="detail-label" >メールアドレス</td>
                        <td class="detail-value {{ empty($facility->email) ? 'empty-field' : '' }}" >
                            @if($facility->email)
                                <a href="mailto:{{ $facility->email }}" 
                                   class="text-decoration-none"
                                   aria-label="メールアドレス {{ $facility->email }} にメールを送信">
                                    <i class="fas fa-envelope me-1" aria-hidden="true"></i>{{ $facility->email }}
                                </a>
                            @else
                                未設定
                            @endif
                        </td>
                        <td class="detail-label" >定員数</td>
                        <td class="detail-value {{ empty($facility->capacity) ? 'empty-field' : '' }}" >
                            @if($facility->capacity)
                                {{ $facility->capacity }}名
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>
                    <tr >
                        <td class="detail-label" >URL</td>
                        <td class="detail-value {{ empty($facility->website_url) ? 'empty-field' : '' }}" colspan="3" >
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
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- サービス種類テーブル -->
<div class="card facility-info-card detail-card-improved mb-3" data-section="facility_services">
    <div class="card-body card-body-clean">
        @php
            $services = $facility->services ?? collect();
            $serviceCount = $services && $services->count() > 0 ? $services->count() : 1;
        @endphp

        <div class="table-responsive">
            <table class="table table-bordered facility-basic-info-table-clean">
                <tbody>
                    @if($services && $services->count() > 0)
                        @foreach($services as $index => $service)
                            <tr>
                                @if($index === 0)
                                    <td class="detail-label" rowspan="{{ $serviceCount }}">サービス種類</td>
                                @endif
                                <td class="detail-value {{ empty($service->service_type) ? 'empty-field' : '' }}">
                                    {{ $service->service_type ?? '未設定' }}
                                </td>
                                <td class="detail-label">有効期限</td>
                                <td class="detail-value {{ (!$service->renewal_start_date && !$service->renewal_end_date) ? 'empty-field' : '' }}">
                                    @if($service->renewal_start_date && $service->renewal_end_date)
                                        {{ \Carbon\Carbon::parse($service->renewal_start_date)->format('Y年m月d日') }} 〜 {{ \Carbon\Carbon::parse($service->renewal_end_date)->format('Y年m月d日') }}
                                    @elseif($service->renewal_start_date)
                                        {{ \Carbon\Carbon::parse($service->renewal_start_date)->format('Y年m月d日') }} 〜
                                    @elseif($service->renewal_end_date)
                                        〜 {{ \Carbon\Carbon::parse($service->renewal_end_date)->format('Y年m月d日') }}
                                    @else
                                        未設定
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="detail-label">サービス種類</td>
                            <td class="detail-value empty-field">未設定</td>
                            <td class="detail-label">有効期限</td>
                            <td class="detail-value empty-field">未設定</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

