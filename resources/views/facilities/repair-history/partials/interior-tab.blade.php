{{-- 内装タブの内容 --}}
<div class="repair-history-equipment-sections">
    @if($interiorHistory->isNotEmpty())
    {{-- 内装リニューアルセクション（上部） --}}
    @php
    $renovationHistory = $interiorHistory->filter(function($history) {
        return in_array($history->subcategory, ['renovation', '内装リニューアル']);
    });
    @endphp

    @if($renovationHistory->isNotEmpty())
    <div class="equipment-section mb-4">
        <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">内装リニューアル</h6>

        <div class="table-responsive">
            <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
                <tbody>
                    @foreach($renovationHistory as $history)
                    @if(!$loop->first)
                    <tr>
                        <td colspan="2" style="height: 8px; padding: 0; border: none; background: #f8f9fa;"></td>
                    </tr>
                    @endif
                    <tr>
                        <td class="detail-label" style="padding: 0.5rem;">リニューアル</td>
                        <td class="detail-value {{ empty($history->maintenance_date) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                            {{ $history->maintenance_date ? $history->maintenance_date->format('Y年m月d日') : '未設定' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="detail-label" style="padding: 0.5rem;">会社名</td>
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
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- 内装・意匠履歴セクション（下部・テーブル形式） --}}
    @php
    $designHistory = $interiorHistory->filter(function($history) {
        return in_array($history->subcategory, ['design', '内装・意匠履歴']);
    });
    
    // ID順でソートしてNo.用のマッピングを作成
    $designHistoryById = $designHistory->sortBy('id');
    $idToNoMapping = [];
    $no = 1;
    foreach ($designHistoryById as $history) {
        $idToNoMapping[$history->id] = $no++;
    }
    
    // 表示用は施工日の古いものを上に
    $designHistory = $designHistory->sortBy('maintenance_date');
    @endphp

    @if($designHistory->isNotEmpty())
    <div class="equipment-section mb-4">
        <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">内装・意匠履歴</h6>

        <div class="table-responsive {{ $designHistory->count() > 6 ? 'interior-design-scrollable' : '' }}">
            <table class="table facility-basic-info-table-clean interior-design-table" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0; table-layout: fixed; width: 100%; font-size: 0.75rem; border: none;">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th class="detail-label" style="padding: 0.5rem; width: 5% !important; min-width: 40px; max-width: 60px; text-align: center; border: none;">NO</th>
                        <th class="detail-label" style="padding: 0.5rem; width: 12% !important; min-width: 100px; max-width: 120px; border: none;">施工日</th>
                        <th class="detail-label" style="padding: 0.5rem; width: 12% !important; min-width: 100px; max-width: 120px; border: none;">施工会社</th>
                        <th class="detail-label" style="padding: 0.5rem; width: 10% !important; min-width: 80px; max-width: 100px; border: none;">金額</th>
                        <th class="detail-label" style="padding: 0.5rem; width: 10% !important; min-width: 80px; max-width: 100px; border: none;">区分</th>
                        <th class="detail-label" style="padding: 0.5rem; width: 25.5% !important; min-width: 150px; border: none;">修繕内容</th>
                        <th class="detail-label" style="padding: 0.5rem; width: 25.5% !important; min-width: 150px; border: none;">備考</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($designHistory as $history)
                    <tr>
                        <td class="detail-value" style="padding: 0.5rem; text-align: center; width: 5% !important; max-width: 60px; border: none; background-color: transparent !important;">{{ $idToNoMapping[$history->id] ?? $loop->iteration }}</td>
                        <td class="detail-value {{ empty($history->maintenance_date) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 12% !important; max-width: 120px; border: none; background-color: transparent !important;">
                            {{ $history->maintenance_date ? $history->maintenance_date->format('Y/m/d') : '未設定' }}
                        </td>
                        <td class="detail-value {{ empty($history->contractor) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 12% !important; max-width: 120px; border: none; word-wrap: break-word; white-space: normal; background-color: transparent !important;">
                            {{ $history->contractor ?? '未設定' }}
                        </td>
                        <td class="detail-value {{ empty($history->cost) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 10% !important; max-width: 100px; border: none; background-color: transparent !important;">
                            {{ $history->cost ? number_format($history->cost) . '円' : '未設定' }}
                        </td>
                        <td class="detail-value {{ empty($history->classification) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 10% !important; max-width: 100px; border: none; word-wrap: break-word; white-space: normal; background-color: transparent !important;">
                            {{ $history->classification ?? '未設定' }}
                        </td>
                        <td class="detail-value {{ empty($history->content) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 25.5% !important; word-wrap: break-word; white-space: normal; border: none; background-color: transparent !important;">
                            {{ $history->content ?? '未設定' }}
                        </td>
                        <td class="detail-value {{ empty($history->notes) ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 25.5% !important; word-wrap: break-word; white-space: normal; border: none; background-color: transparent !important;">
                            {{ $history->notes ?? '未設定' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- 特記事項セクション --}}
    @php
        // 施設の内装特記事項を取得
        $specialNotes = $facility->interior_special_notes ?? '';
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
    @else
    <div class="table-responsive">
        <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="detail-value empty-field" style="padding: 0.5rem;">
                        <i class="fas fa-info-circle me-2"></i>内装の修繕履歴が登録されていません。編集ボタンをクリックして履歴を登録してください。
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</div>

<style>
/* 内装・意匠履歴タブ専用スタイル - empty-fieldの背景色を強制的に透明にする */
.interior-design-table .detail-value {
    background-color: transparent !important;
}

.interior-design-table .detail-value.empty-field {
    background-color: transparent !important;
    color: #6c757d !important;
    font-style: italic !important;
}

/* デフォルトのテーブル表示 */
.table-responsive {
    padding-left: 0;
    direction: ltr;
}

/* スクロール機能 - 7行目以降でスクロールバー表示 */
.interior-design-scrollable {
    max-height: calc(6 * 2.5rem + 2.5rem); /* ヘッダー1行 + データ6行分の高さ */
    overflow-y: auto;
    overflow-x: hidden;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    position: relative;
    direction: rtl; /* 右から左の方向に変更してスクロールバーを左に */
}

/* スクロールバーが実際に表示される場合のみ左パディングを追加 */
.interior-design-scrollable::-webkit-scrollbar {
    width: 8px;
}

.interior-design-scrollable::-webkit-scrollbar:horizontal {
    display: none;
}

.interior-design-scrollable::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.interior-design-scrollable::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.interior-design-scrollable::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* スクロール可能な場合のみパディングを適用するJavaScript制御用クラス */
.has-scrollbar {
    padding-left: 12px !important;
}

/* テーブルの方向を元に戻す */
.interior-design-scrollable .table {
    direction: ltr;
    margin-left: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // スクロール可能なコンテナを取得
    const scrollableContainer = document.querySelector('.interior-design-scrollable');
    
    if (scrollableContainer) {
        // スクロールバーが実際に表示されているかチェック
        function checkScrollbar() {
            const hasVerticalScrollbar = scrollableContainer.scrollHeight > scrollableContainer.clientHeight;
            
            if (hasVerticalScrollbar) {
                // スクロールバーがある場合は左パディングを追加
                scrollableContainer.classList.add('has-scrollbar');
            } else {
                // スクロールバーがない場合は左パディングを削除
                scrollableContainer.classList.remove('has-scrollbar');
            }
        }
        
        // 初期チェック
        checkScrollbar();
        
        // ウィンドウリサイズ時にも再チェック
        window.addEventListener('resize', checkScrollbar);
    }
});
</script>