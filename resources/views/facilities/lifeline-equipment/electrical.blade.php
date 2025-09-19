@php
    $electricalLifeline = $facility->getLifelineEquipmentByCategory('electrical');
    $electricalEquipment = $electricalLifeline?->electricalEquipment;
    $basicInfo = $electricalEquipment->basic_info ?? [];
    $pasInfo = $electricalEquipment->pas_info ?? [];
    $cubicleInfo = $electricalEquipment->cubicle_info ?? [];
    $generatorInfo = $electricalEquipment->generator_info ?? [];
    $cubicleEquipmentList = $cubicleInfo['equipment_list'] ?? [];
    $generatorEquipmentList = $generatorInfo['equipment_list'] ?? [];
    $canEdit = auth()->user()->canEditFacility($facility->id);
@endphp
<!-- 基本情報テーブル -->
<div class="table-responsive mb-3">
    <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
        <tbody>
            <tr>
                <td class="detail-label" style="padding: 0.5rem;">電気契約会社</td>
                <td class="detail-value {{ empty($basicInfo['electrical_contractor']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    {{ $basicInfo['electrical_contractor'] ?? '未設定' }}
                </td>
                <td class="detail-label" style="padding: 0.5rem;">保安管理業者</td>
                <td class="detail-value {{ empty($basicInfo['safety_management_company']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    {{ $basicInfo['safety_management_company'] ?? '未設定' }}
                </td>
            </tr>
            <tr>
                <td class="detail-label" style="padding: 0.5rem;">電気保守点検実施日</td>
                <td class="detail-value {{ empty($basicInfo['maintenance_inspection_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    @if(!empty($basicInfo['maintenance_inspection_date']))
                        {{ \Carbon\Carbon::parse($basicInfo['maintenance_inspection_date'])->format('Y年m月d日') }}
                    @else
                        未設定
                    @endif
                </td>
                <td class="detail-label" style="padding: 0.5rem;">点検実施報告書</td>
                <td class="detail-value {{ empty($basicInfo['inspection_report_pdf']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                    @if(!empty($basicInfo['inspection_report_pdf']))
                        <a href="{{ route('facilities.lifeline-equipment.download', [$facility, 'electrical', $basicInfo['inspection_report_pdf']]) }}" 
                           class="text-decoration-none" 
                           aria-label="点検実施報告書PDFをダウンロード"
                           target="_blank">
                            <i class="fas fa-file-pdf me-1 text-danger" aria-hidden="true"></i>{{ $basicInfo['inspection_report_pdf'] }}
                        </a>
                    @else
                        未設定
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
</div>
<!-- PASテーブル -->
<div class="mb-3">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">PAS</h6>
    <div class="table-responsive">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">有無</td>
                    <td class="detail-value {{ empty($pasInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($pasInfo['availability']))
                            <span class="badge {{ $pasInfo['availability'] === '有' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $pasInfo['availability'] }}
                            </span>
                        @else
                            未設定
                        @endif
                    </td>
                    <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                    <td class="detail-value {{ empty($pasInfo['update_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($pasInfo['update_date']))
                            {{ \Carbon\Carbon::parse($pasInfo['update_date'])->format('Y年n月j日') }}
                        @else
                            未設定
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<!-- キュービクルテーブル -->
<div class="mb-3" style="position: relative; overflow: visible;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">キュービクル</h6>
    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">有無</td>
                    <td class="detail-value {{ empty($cubicleInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($cubicleInfo['availability']))
                            <span class="badge {{ $cubicleInfo['availability'] === '有' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $cubicleInfo['availability'] }}
                            </span>
                        @else
                            未設定
                        @endif
                    </td>
                </tr>
                @if(!empty($cubicleInfo['availability']) && $cubicleInfo['availability'] === '有')
                    @if(!empty($cubicleEquipmentList) && is_array($cubicleEquipmentList))
                        @foreach($cubicleEquipmentList as $index => $equipment)
                            <tr style="position: relative;">
                                <td class="detail-label" style="padding: 0.5rem; position: relative;">
                                    <div style="position: absolute; left: -30px; top: 50%; transform: translateY(-50%); z-index: 1000;">
                                        <span style="background: #007bff; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">{{ $index + 1 }}</span>
                                    </div>
                                    メーカー
                                </td>
                                <td class="detail-value {{ empty($equipment['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $equipment['manufacturer'] ?? '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">年式</td>
                                <td class="detail-value {{ empty($equipment['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['model_year']))
                                        {{ $equipment['model_year'] }}年式
                                    @else
                                        未設定
                                    @endif
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                                <td class="detail-value {{ empty($equipment['update_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['update_date']))
                                        {{ \Carbon\Carbon::parse($equipment['update_date'])->format('Y年n月j日') }}
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
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- 非常用発電機テーブル -->
<div class="mb-3" style="position: relative; overflow: visible;">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">非常用発電機</h6>
    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-label" style="padding: 0.5rem;">有無</td>
                    <td class="detail-value {{ empty($generatorInfo['availability']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        @if(!empty($generatorInfo['availability']))
                            <span class="badge {{ $generatorInfo['availability'] === '有' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $generatorInfo['availability'] }}
                            </span>
                        @else
                            未設定
                        @endif
                    </td>
                </tr>
                @if(!empty($generatorInfo['availability']) && $generatorInfo['availability'] === '有')
                    @if(!empty($generatorEquipmentList) && is_array($generatorEquipmentList))
                        @foreach($generatorEquipmentList as $index => $equipment)
                            <tr style="position: relative;">
                                <td class="detail-label" style="padding: 0.5rem; position: relative;">
                                    <div style="position: absolute; left: -30px; top: 50%; transform: translateY(-50%); z-index: 1000;">
                                        <span style="background: #007bff; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">{{ $index + 1 }}</span>
                                    </div>
                                    メーカー
                                </td>
                                <td class="detail-value {{ empty($equipment['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    {{ $equipment['manufacturer'] ?? '未設定' }}
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">年式</td>
                                <td class="detail-value {{ empty($equipment['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['model_year']))
                                        {{ $equipment['model_year'] }}年式
                                    @else
                                        未設定
                                    @endif
                                </td>
                                <td class="detail-label" style="padding: 0.5rem;">更新年月日</td>
                                <td class="detail-value {{ empty($equipment['update_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                    @if(!empty($equipment['update_date']))
                                        {{ \Carbon\Carbon::parse($equipment['update_date'])->format('Y年n月j日') }}
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
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- 備考テーブル -->
<div class="mb-3">
    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">備考</h6>
    <div class="table-responsive">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-value {{ empty($electricalEquipment->notes) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                        {{ $electricalEquipment->notes ?? '未設定' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

