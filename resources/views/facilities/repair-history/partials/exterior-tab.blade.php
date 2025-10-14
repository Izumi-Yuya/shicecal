{{-- 外装タブの内容 --}}
<div class="repair-history-equipment-sections">
    @if($exteriorHistory->isNotEmpty())
        <div class="row">
            {{-- 左側：防水セクション --}}
            <div class="col-md-4">
                <div class="equipment-section mb-4">
                    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">防水</h6>
                    
                    @php
                        $waterproofHistory = collect();
                        if ($exteriorHistory->has('waterproof')) {
                            $waterproofHistory = $waterproofHistory->merge($exteriorHistory['waterproof']);
                        }
                        // 日本語で入力された防水履歴も含める
                        $allHistory = $exteriorHistory->flatten();
                        $japaneseWaterproof = $allHistory->filter(function($history) {
                            return $history->subcategory === '防水';
                        });
                        $waterproofHistory = $waterproofHistory->merge($japaneseWaterproof);
                    @endphp
                    @if($waterproofHistory->isNotEmpty())
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                                <tbody>
                                    @foreach($waterproofHistory as $index => $history)
                                        @if($index > 0)
                                            <tr><td colspan="2" style="height: 8px; padding: 0; border: none; background: #f8f9fa;"></td></tr>
                                        @endif
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">施工日</td>
                                            <td class="detail-value {{ empty($history->maintenance_date) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->maintenance_date ? $history->maintenance_date->format('Y年m月d日') : '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">保証期間</td>
                                            <td class="detail-value {{ empty($history->warranty_end_date) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                @if($history->warranty_end_date)
                                                    {{ $history->warranty_end_date->format('Y年n月') }}まで
                                                @else
                                                    未設定
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">施工会社</td>
                                            <td class="detail-value {{ empty($history->contractor) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->contractor ?? '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">担当者</td>
                                            <td class="detail-value {{ empty($history->contact_person) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->contact_person ?? '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">連絡先</td>
                                            <td class="detail-value {{ empty($history->phone_number) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->phone_number ?? '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">備考</td>
                                            <td class="detail-value {{ empty($history->notes) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->notes ?? '未設定' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                                <tbody>
                                    <tr>
                                        <td class="detail-value empty-field" style="padding: 0.5rem;">
                                            <i class="fas fa-info-circle me-2"></i>防水工事の修繕履歴が登録されていません。
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 中央：塗装セクション --}}
            <div class="col-md-4">
                <div class="equipment-section mb-4">
                    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">塗装</h6>
                    
                    @php
                        $paintingHistory = collect();
                        if ($exteriorHistory->has('painting')) {
                            $paintingHistory = $paintingHistory->merge($exteriorHistory['painting']);
                        }
                        // 日本語で入力された塗装履歴も含める
                        $allHistory = $exteriorHistory->flatten();
                        $japanesePainting = $allHistory->filter(function($history) {
                            return $history->subcategory === '塗装';
                        });
                        $paintingHistory = $paintingHistory->merge($japanesePainting);
                    @endphp
                    @if($paintingHistory->isNotEmpty())
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                                <tbody>
                                    @foreach($paintingHistory as $index => $history)
                                        @if($index > 0)
                                            <tr><td colspan="2" style="height: 8px; padding: 0; border: none; background: #f8f9fa;"></td></tr>
                                        @endif
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">施工日</td>
                                            <td class="detail-value {{ empty($history->maintenance_date) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->maintenance_date ? $history->maintenance_date->format('Y年m月d日') : '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">保証期間</td>
                                            <td class="detail-value {{ empty($history->warranty_end_date) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                @if($history->warranty_end_date)
                                                    {{ $history->warranty_end_date->format('Y年n月') }}まで
                                                @else
                                                    未設定
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">施工会社</td>
                                            <td class="detail-value {{ empty($history->contractor) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->contractor ?? '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">担当者</td>
                                            <td class="detail-value {{ empty($history->contact_person) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->contact_person ?? '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">連絡先</td>
                                            <td class="detail-value {{ empty($history->phone_number) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->phone_number ?? '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">備考</td>
                                            <td class="detail-value {{ empty($history->notes) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->notes ?? '未設定' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                                <tbody>
                                    <tr>
                                        <td class="detail-value empty-field" style="padding: 0.5rem;">
                                            <i class="fas fa-info-circle me-2"></i>塗装工事の修繕履歴が登録されていません。
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 右側：白アリ駆除セクション --}}
            <div class="col-md-4">
                <div class="equipment-section mb-4">
                    <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">白アリ駆除</h6>
                    
                    @php
                        $termiteHistory = collect();
                        if ($exteriorHistory->has('termite_control')) {
                            $termiteHistory = $termiteHistory->merge($exteriorHistory['termite_control']);
                        }
                        // 日本語で入力された白アリ駆除履歴も含める
                        $allHistory = $exteriorHistory->flatten();
                        $japaneseTermite = $allHistory->filter(function($history) {
                            return $history->subcategory === '白アリ駆除';
                        });
                        $termiteHistory = $termiteHistory->merge($japaneseTermite);
                    @endphp
                    @if($termiteHistory->isNotEmpty())
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                                <tbody>
                                    @foreach($termiteHistory as $index => $history)
                                        @if($index > 0)
                                            <tr><td colspan="2" style="height: 8px; padding: 0; border: none; background: #f8f9fa;"></td></tr>
                                        @endif
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">施工日</td>
                                            <td class="detail-value {{ empty($history->maintenance_date) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->maintenance_date ? $history->maintenance_date->format('Y年m月d日') : '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">保証期間</td>
                                            <td class="detail-value {{ empty($history->warranty_end_date) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                @if($history->warranty_end_date)
                                                    {{ $history->warranty_end_date->format('Y年n月') }}まで
                                                @else
                                                    未設定
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">施工会社</td>
                                            <td class="detail-value {{ empty($history->contractor) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->contractor ?? '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">担当者</td>
                                            <td class="detail-value {{ empty($history->contact_person) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->contact_person ?? '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">連絡先</td>
                                            <td class="detail-value {{ empty($history->phone_number) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->phone_number ?? '未設定' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="detail-label" style="padding: 0.5rem;">備考</td>
                                            <td class="detail-value {{ empty($history->notes) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                                {{ $history->notes ?? '未設定' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                                <tbody>
                                    <tr>
                                        <td class="detail-value empty-field" style="padding: 0.5rem;">
                                            <i class="fas fa-info-circle me-2"></i>白アリ駆除の修繕履歴が登録されていません。
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- 特記事項セクション --}}
        @php
            // 施設の外装特記事項を取得
            $specialNotes = $facility->exterior_special_notes ?? '';
        @endphp
        
        <div class="mb-3">
            <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">特記事項</h6>
            <div class="table-responsive">
                <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0; table-layout: fixed; height: auto;">
                    <tbody>
                        <tr style="height: auto;">
                            <td class="detail-value {{ empty($specialNotes) ? 'empty-field' : '' }}" style="padding: 0.5rem !important; white-space: pre-wrap !important; text-align: left !important; vertical-align: top !important; margin: 0 !important; height: auto !important; line-height: 1.2 !important; justify-content: flex-start !important; align-items: flex-start !important;">
                                <div style="text-align: left !important; width: 100% !important; margin: 0 !important; padding: 0 !important; display: block !important;">{{ $specialNotes ?: '未設定' }}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

<style>
/* Special notes section specific styles - force top-left alignment */
.facility-basic-info-table-clean {
    height: auto !important;
}

.facility-basic-info-table-clean .detail-value {
    text-align: left !important;
    vertical-align: top !important;
    height: auto !important;
    line-height: 1.2 !important;
    padding: 0.5rem !important;
    justify-content: flex-start !important;
    align-items: flex-start !important;
}

.facility-basic-info-table-clean .detail-value div {
    text-align: left !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
}

.facility-basic-info-table-clean td {
    vertical-align: top !important;
    text-align: left !important;
    height: auto !important;
    padding: 0.5rem !important;
    justify-content: flex-start !important;
    align-items: flex-start !important;
}

.facility-basic-info-table-clean tr {
    height: auto !important;
}
</style>
    @else
        <div class="table-responsive">
            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                <tbody>
                    <tr>
                        <td class="detail-value empty-field" style="padding: 0.5rem;">
                            <i class="fas fa-info-circle me-2"></i>外装の修繕履歴が登録されていません。編集ボタンをクリックして履歴を登録してください。
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</div>