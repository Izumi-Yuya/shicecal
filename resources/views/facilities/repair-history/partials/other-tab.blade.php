{{-- その他タブの内容 --}}
<div class="repair-history-equipment-sections">
    @if($otherHistory->isNotEmpty())
    @php
    // ID順でソートしてNo.用のマッピングを作成
    $otherHistoryById = $otherHistory->sortBy('id');
    $idToNoMapping = [];
    $no = 1;
    foreach ($otherHistoryById as $history) {
        $idToNoMapping[$history->id] = $no++;
    }
    
    // 表示用は施工日の古いものを上に
    $otherHistory = $otherHistory->sortBy('maintenance_date');
    @endphp
    <div class="equipment-section mb-4">
        <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">その他（躯体に関わる修繕履歴）</h6>

        <div class="table-responsive {{ $otherHistory->count() > 6 ? 'other-history-scrollable' : '' }}">
            <table class="table facility-basic-info-table-clean other-history-table" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0; table-layout: fixed; width: 100%; font-size: 0.75rem; border: none;">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th class="detail-label" style="padding: 0.5rem; width: 5% !important; min-width: 40px; max-width: 60px; text-align: center; border: none;">NO</th>
                        <th class="detail-label" style="padding: 0.5rem; width: 12% !important; min-width: 100px; max-width: 120px; border: none;">施工日</th>
                        <th class="detail-label" style="padding: 0.5rem; width: 15% !important; min-width: 120px; max-width: 150px; border: none;">施工会社</th>
                        <th class="detail-label" style="padding: 0.5rem; width: 12% !important; min-width: 100px; max-width: 120px; border: none;">金額</th>

                        <th class="detail-label" style="padding: 0.5rem; width: 23% !important; min-width: 180px; border: none;">修繕内容</th>
                        <th class="detail-label" style="padding: 0.5rem; width: 23% !important; min-width: 180px; border: none;">備考</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($otherHistory as $history)
                    <tr>
                        <td class="detail-value" style="padding: 0.5rem; text-align: center; width: 5% !important; max-width: 60px; border: none; background-color: transparent !important;">{{ $idToNoMapping[$history->id] ?? $loop->iteration }}</td>
                        <td class="detail-value {{ !$history->maintenance_date ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 12% !important; max-width: 120px; border: none; background-color: transparent !important;">
                            {{ $history->maintenance_date ? $history->maintenance_date->format('Y/m/d') : '未設定' }}
                        </td>
                        <td class="detail-value {{ !$history->contractor || trim($history->contractor) === '' ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 15% !important; max-width: 150px; border: none; background-color: transparent !important; word-wrap: break-word; white-space: normal;">
                            {{ $history->contractor ?? '未設定' }}
                        </td>
                        <td class="detail-value {{ !$history->cost ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 12% !important; max-width: 120px; border: none; background-color: transparent !important;">
                            {{ $history->cost ? number_format($history->cost) . '円' : '未設定' }}
                        </td>

                        <td class="detail-value {{ !$history->content || trim($history->content) === '' ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 23% !important; word-wrap: break-word; white-space: normal; border: none; background-color: transparent !important;">
                            {{ $history->content ?? '未設定' }}
                        </td>
                        <td class="detail-value {{ !$history->notes || trim($history->notes) === '' ? 'empty-field' : '' }}" style="padding: 0.5rem; width: 23% !important; word-wrap: break-word; white-space: normal; border: none; background-color: transparent !important;">
                            {{ $history->notes ?? '未設定' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- 特記事項セクション --}}
    @php
        // その他の特記事項データを取得
        $specialNotes = $facility->other_special_notes ?? '';
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
                        <i class="fas fa-info-circle me-2"></i>その他の修繕履歴が登録されていません。編集ボタンから修繕履歴を追加してください
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</div>

<style>
/* その他タブ専用スタイル - empty-fieldの背景色を強制的に透明にする */
.other-history-table .detail-value {
    background-color: transparent !important;
}

.other-history-table .detail-value.empty-field {
    background-color: transparent !important;
    color: #6c757d !important;
    font-style: italic !important;
}

/* デフォルトのテーブル表示 */
.table-responsive {
    padding-right: 0;
    direction: ltr;
}

/* スクロール機能 - 7行目以降でスクロールバー表示 */
.other-history-scrollable {
    max-height: calc(6 * 2.5rem + 2.5rem); /* ヘッダー1行 + データ6行分の高さ */
    overflow-y: auto;
    overflow-x: hidden;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    position: relative;
    direction: ltr; /* 通常の左から右の方向でスクロールバーを右に */
}

/* スクロールバーが実際に表示される場合のみ右パディングを追加 */
.other-history-scrollable::-webkit-scrollbar {
    width: 8px;
}

.other-history-scrollable::-webkit-scrollbar:horizontal {
    display: none;
}

/* スクロール可能な場合のみパディングを適用するJavaScript制御用クラス */
.has-scrollbar {
    padding-right: 12px !important;
}

/* テーブルの方向を元に戻す */
.other-history-scrollable .table {
    direction: ltr;
    margin-right: 0;
}

.other-history-scrollable::-webkit-scrollbar {
    width: 8px;
}

.other-history-scrollable::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
    margin: 2px;
}

.other-history-scrollable::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
    border: 1px solid #f1f1f1;
}

.other-history-scrollable::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // スクロール可能なコンテナを取得
    const scrollableContainer = document.querySelector('.other-history-scrollable');
    
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