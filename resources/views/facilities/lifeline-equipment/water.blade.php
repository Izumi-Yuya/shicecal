@php
    $waterLifeline = $facility->getLifelineEquipmentByCategory('water');
    $waterEquipment = $waterLifeline?->waterEquipment;
    $basicInfo = $waterEquipment->basic_info ?? [];
    $filterInfo = $basicInfo['filter_info'] ?? [];
    $waterTankInfo = $basicInfo['water_tank_info'] ?? [];
    $pressurePumpInfo = $basicInfo['pressure_pump_info'] ?? [];
    $pressurePumpList = $pressurePumpInfo['equipment_list'] ?? [];
    $septicTankInfo = $basicInfo['septic_tank_info'] ?? [];
    $legionellaInfo = $basicInfo['legionella_info'] ?? [];
    $canEdit = auth()->user()->canEditFacility($facility->id);
@endphp

<!-- 基本情報テーブル -->
<div class="mb-3" style="clear: both; width: 100%;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333; display: block; width: 100%;">基本情報</h6>
    <div class="table-responsive" style="clear: both;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
        <tbody>
            <tr>
                <td class="detail-label" style="padding: 0.5rem;">水道契約会社</td>
                <td class="detail-value {{ empty($basicInfo['water_contractor']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    {{ $basicInfo['water_contractor'] ?? '未設定' }}
                </td>
                <td class="detail-label" style="padding: 0.5rem;">受水槽・配管清掃業者</td>
                <td class="detail-value {{ empty($basicInfo['tank_cleaning_company']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    {{ $basicInfo['tank_cleaning_company'] ?? '未設定' }}
                </td>
            </tr>
            <tr>
                <td class="detail-label" style="padding: 0.5rem;">受水槽・配管清掃実施日</td>
                <td class="detail-value {{ empty($basicInfo['tank_cleaning_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    @if(!empty($basicInfo['tank_cleaning_date']))
                        {{ \Carbon\Carbon::parse($basicInfo['tank_cleaning_date'])->format('Y年m月d日') }}
                    @else
                        未設定
                    @endif
                </td>
                <td class="detail-label" style="padding: 0.5rem;">受水槽・配管清掃実施報告書</td>
                <td class="detail-value {{ empty($basicInfo['tank_cleaning_report_pdf']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    @if(!empty($basicInfo['tank_cleaning_report_pdf']))
                        <a href="{{ route('facilities.lifeline-equipment.download', [$facility, 'water', $basicInfo['tank_cleaning_report_pdf']]) }}" 
                           class="text-decoration-none" 
                           aria-label="受水槽・配管清掃実施報告書PDFをダウンロード"
                           target="_blank">
                            <i class="fas fa-file-pdf me-1 text-danger" aria-hidden="true"></i>{{ $basicInfo['tank_cleaning_report_pdf'] }}
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
<!-- ろ過器テーブル -->
<div class="mb-3" style="clear: both; width: 100%;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333; display: block; width: 100%;">ろ過器</h6>
    <div class="table-responsive" style="clear: both;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">浴槽方式</td>
                    <td class="detail-value {{ empty($filterInfo['bath_system']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="3">
                        @if(!empty($filterInfo['bath_system']))
                            <span class="badge bg-info">{{ $filterInfo['bath_system'] }}</span>
                        @else
                            未設定
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">有無</td>
                    <td class="detail-value {{ empty($filterInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($filterInfo['availability']))
                            <span class="badge {{ $filterInfo['availability'] === '有' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $filterInfo['availability'] }}
                            </span>
                        @else
                            未設定
                        @endif
                    </td>
                    <td class="detail-label" style="padding: 0.5rem;">メーカー</td>
                    <td class="detail-value {{ empty($filterInfo['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        {{ $filterInfo['manufacturer'] ?? '未設定' }}
                    </td>
                </tr>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">年式</td>
                    <td class="detail-value {{ empty($filterInfo['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="3">
                        @if(!empty($filterInfo['model_year']))
                            {{ $filterInfo['model_year'] }}年式
                        @else
                            未設定
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 受水槽テーブル -->
<div class="mb-3" style="clear: both; width: 100%;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333; display: block; width: 100%;">受水槽</h6>
    <div class="table-responsive" style="clear: both;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">有無</td>
                    <td class="detail-value {{ empty($waterTankInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($waterTankInfo['availability']))
                            <span class="badge {{ $waterTankInfo['availability'] === '有' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $waterTankInfo['availability'] }}
                            </span>
                        @else
                            未設定
                        @endif
                    </td>
                    <td class="detail-label" style="padding: 0.5rem;">メーカー</td>
                    <td class="detail-value {{ empty($waterTankInfo['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        {{ $waterTankInfo['manufacturer'] ?? '未設定' }}
                    </td>
                </tr>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">年式</td>
                    <td class="detail-value {{ empty($waterTankInfo['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="3">
                        @if(!empty($waterTankInfo['model_year']))
                            {{ $waterTankInfo['model_year'] }}年式
                        @else
                            未設定
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 加圧ポンプテーブル -->
<div class="mb-3" style="position: relative; overflow: visible; clear: both; width: 100%;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333; display: block; width: 100%;">加圧ポンプ</h6>
    <div class="table-responsive" style="overflow: visible; clear: both;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                @if(!empty($pressurePumpList) && is_array($pressurePumpList))
                    @foreach($pressurePumpList as $index => $pump)
                        <tr style="position: relative;">
                            <td class="detail-label" style="padding: 0.5rem; position: relative;">
                                <div style="position: absolute; left: -30px; top: 50%; transform: translateY(-50%); z-index: 1000;">
                                    <span style="background: #007bff; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">{{ $index + 1 }}</span>
                                </div>
                                メーカー
                            </td>
                            <td class="detail-value {{ empty($pump['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                {{ $pump['manufacturer'] ?? '未設定' }}
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">年式</td>
                            <td class="detail-value {{ empty($pump['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if(!empty($pump['model_year']))
                                    {{ $pump['model_year'] }}年式
                                @else
                                    未設定
                                @endif
                            </td>
                            <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                            <td class="detail-value {{ empty($pump['update_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                @if(!empty($pump['update_date']))
                                    {{ \Carbon\Carbon::parse($pump['update_date'])->format('Y年n月j日') }}
                                @else
                                    未設定
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="detail-label" style="padding: 0.5rem;">メーカー</td>
                        <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                        <td class="detail-label" style="padding: 0.5rem;">年式</td>
                        <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                        <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                        <td class="detail-value empty-field" style="padding: 0.5rem;">未設定</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- 浄化槽テーブル -->
<div class="mb-3" style="clear: both; width: 100%;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333; display: block; width: 100%;">浄化槽</h6>
    <div class="table-responsive" style="clear: both;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">有無</td>
                    <td class="detail-value {{ empty($septicTankInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($septicTankInfo['availability']))
                            <span class="badge {{ $septicTankInfo['availability'] === '有' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $septicTankInfo['availability'] }}
                            </span>
                        @else
                            未設定
                        @endif
                    </td>
                    <td class="detail-label" style="padding: 0.5rem;">メーカー</td>
                    <td class="detail-value {{ empty($septicTankInfo['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        {{ $septicTankInfo['manufacturer'] ?? '未設定' }}
                    </td>
                </tr>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">年式</td>
                    <td class="detail-value {{ empty($septicTankInfo['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="3">
                        @if(!empty($septicTankInfo['model_year']))
                            {{ $septicTankInfo['model_year'] }}年式
                        @else
                            未設定
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">点検・清掃業者</td>
                    <td class="detail-value {{ empty($septicTankInfo['inspection_company']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        {{ $septicTankInfo['inspection_company'] ?? '未設定' }}
                    </td>
                    <td class="detail-label" style="padding: 0.5rem;">点検・清掃実施日</td>
                    <td class="detail-value {{ empty($septicTankInfo['inspection_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($septicTankInfo['inspection_date']))
                            {{ \Carbon\Carbon::parse($septicTankInfo['inspection_date'])->format('Y年m月d日') }}
                        @else
                            未設定
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">点検・清掃実施報告書</td>
                    <td class="detail-value {{ empty($septicTankInfo['inspection_report_pdf']) ? 'empty-field' : '' }}" style="padding: 0.5rem;" colspan="3">
                        @if(!empty($septicTankInfo['inspection_report_pdf']))
                            <a href="{{ route('facilities.lifeline-equipment.download', [$facility, 'water', $septicTankInfo['inspection_report_pdf']]) }}" 
                               class="text-decoration-none" 
                               aria-label="点検・清掃実施報告書PDFをダウンロード"
                               target="_blank">
                                <i class="fas fa-file-pdf me-1 text-danger" aria-hidden="true"></i>{{ $septicTankInfo['inspection_report_pdf'] }}
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

<!-- レジオネラ検査テーブル -->
<div class="mb-3" style="clear: both; width: 100%;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333; display: block; width: 100%;">レジオネラ検査</h6>
    <div class="table-responsive" style="clear: both;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">実施月</td>
                    <td class="detail-value {{ empty($legionellaInfo['inspection_month']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        {{ $legionellaInfo['inspection_month'] ?? '未設定' }}
                    </td>
                    <td class="detail-label" style="padding: 0.5rem;">検査結果報告書</td>
                    <td class="detail-value {{ empty($legionellaInfo['inspection_report_pdf']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($legionellaInfo['inspection_report_pdf']))
                            <a href="{{ route('facilities.lifeline-equipment.download', [$facility, 'water', $legionellaInfo['inspection_report_pdf']]) }}" 
                               class="text-decoration-none" 
                               aria-label="レジオネラ検査結果報告書PDFをダウンロード"
                               target="_blank">
                                <i class="fas fa-file-pdf me-1 text-danger" aria-hidden="true"></i>{{ $legionellaInfo['inspection_report_pdf'] }}
                            </a>
                        @else
                            未設定
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">検査結果（初回）</td>
                    <td class="detail-value {{ empty($legionellaInfo['first_result']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($legionellaInfo['first_result']))
                            <span class="badge {{ $legionellaInfo['first_result'] === '陰性' ? 'bg-success' : 'bg-warning' }}">
                                {{ $legionellaInfo['first_result'] }}
                            </span>
                        @else
                            未設定
                        @endif
                    </td>
                    <td class="detail-label" style="padding: 0.5rem;">数値（陽性の場合）</td>
                    <td class="detail-value {{ empty($legionellaInfo['first_result_value']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        {{ $legionellaInfo['first_result_value'] ?? '未設定' }}
                    </td>
                </tr>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">検査結果（２回目）</td>
                    <td class="detail-value {{ empty($legionellaInfo['second_result']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($legionellaInfo['second_result']))
                            <span class="badge {{ $legionellaInfo['second_result'] === '陰性' ? 'bg-success' : 'bg-warning' }}">
                                {{ $legionellaInfo['second_result'] }}
                            </span>
                        @else
                            未設定
                        @endif
                    </td>
                    <td class="detail-label" style="padding: 0.5rem;">数値（陽性の場合）</td>
                    <td class="detail-value {{ empty($legionellaInfo['second_result_value']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        {{ $legionellaInfo['second_result_value'] ?? '未設定' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 備考テーブル -->
<div class="mb-3" style="clear: both; width: 100%;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333; display: block; width: 100%;">備考</h6>
    <div class="table-responsive" style="clear: both;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-value {{ empty($waterEquipment->notes) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        {{ $waterEquipment->notes ?? '未設定' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>