@php
$waterEquipment = $facility->getWaterEquipment();
$basicInfo = $waterEquipment?->basic_info ?? [];
$filterInfo = $basicInfo['filter_info'] ?? [];
$tankInfo = $basicInfo['tank_info'] ?? [];
$pumpInfo = $basicInfo['pump_info'] ?? [];
$septicTankInfo = $basicInfo['septic_tank_info'] ?? [];
$legionellaInfo = $basicInfo['legionella_info'] ?? [];
$canEdit = auth()->user()->canEditFacility($facility->id);
@endphp

<!-- 水道設備ヘッダー -->
<div class="mb-3">
    <h5 class="mb-0">
        <i class="fas fa-tint text-info me-2"></i>水道設備情報
    </h5>
</div>

<!-- 隠しドキュメントトリガー（編集ボタンの隣のドキュメントボタンから呼び出される） -->
<button type="button" id="water-documents-toggle" class="d-none" data-bs-toggle="modal" data-bs-target="#water-documents-modal"></button>

@php
// 基本情報テーブルデータの構築
$basicInfoData = [
[
'type' => 'standard',
'cells' => [
['label' => '水道契約会社', 'value' => $basicInfo['water_contractor'] ?? null, 'type' => 'text', 'width' => '100%'],
]
],
[
'type' => 'standard',
'cells' => [
['label' => '受水槽清掃業者', 'value' => $basicInfo['tank_cleaning_company'] ?? null, 'type' => 'text', 'width' => '33.33%'],
['label' => '受水槽清掃実施日', 'value' => $basicInfo['tank_cleaning_date'] ?? null, 'type' => 'date', 'width' => '33.33%'],
['label' => '受水槽清掃報告書', 'value' => $basicInfo['tank_cleaning']['tank_cleaning_report_pdf'] ?? null, 'type' => 'file_display', 'options' => ['route' => 'facilities.lifeline-equipment.download-file', 'params' => [$facility, 'water', 'tank_cleaning_report'], 'display_name' => 'ダウンロード'], 'width' => '33.33%'],
]
],
];

// ろ過器
$filterData = [
[
'type' => 'standard',
'cells' => [
['label' => '浴槽循環方式', 'value' => $filterInfo['bath_system'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'bg-info'], 'width' => '100%'],
]
],
[
'type' => 'standard',
'cells' => [
['label' => '設置の有無', 'value' => $filterInfo['availability'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'availability'], 'width' => '33.33%'],
['label' => 'メーカー', 'value' => $filterInfo['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
['label' => '年式', 'value' => !empty($filterInfo['model_year']) ? $filterInfo['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
]
],
];

// 受水槽
$tankData = [
[
'type' => 'standard',
'cells' => [
['label' => '設置の有無', 'value' => $tankInfo['availability'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'availability'], 'width' => '33.33%'],
['label' => 'メーカー', 'value' => $tankInfo['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
['label' => '年式', 'value' => !empty($tankInfo['model_year']) ? $tankInfo['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
]
],
];

// 浄化槽
$septicTankData = [
[
'type' => 'standard',
'cells' => [
['label' => '設置の有無', 'value' => $septicTankInfo['availability'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => 'availability'], 'width' => '33.33%'],
['label' => 'メーカー', 'value' => $septicTankInfo['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
['label' => '年式', 'value' => !empty($septicTankInfo['model_year']) ? $septicTankInfo['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
]
],
[
'type' => 'standard',
'cells' => [
['label' => '点検・清掃業者', 'value' => $septicTankInfo['inspection_company'] ?? null, 'type' => 'text'],
['label' => '点検・清掃実施日', 'value' => $septicTankInfo['inspection_date'] ?? null, 'type' => 'date'],
['label' => '点検・清掃実施報告書', 'value' => $septicTankInfo['inspection']['inspection_report_pdf'] ?? null, 'type' => 'file_display', 'options' => ['route' => 'facilities.lifeline-equipment.download-file', 'params' => [$facility, 'water', 'septic_tank_inspection_report'], 'display_name' => 'ダウンロード']],
]
],
];

// 加圧ポンプ（複数対応）
$pumpDataSets = [];
if (isset($pumpInfo['pumps']) && is_array($pumpInfo['pumps'])) {
foreach ($pumpInfo['pumps'] as $index => $pump) {
$pumpNumber = $index + 1;
$pumpDataSets[] = [
'data' => [
[
'type' => 'standard',
'cells' => [
['label' => 'メーカー' . $pumpNumber, 'value' => $pump['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
['label' => '年式', 'value' => !empty($pump['model_year']) ? $pump['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
['label' => '更新年月日', 'value' => $pump['update_date'] ?? null, 'type' => 'date', 'width' => '33.33%'],
]
],
]
];
}
} else {
$pumpDataSets[] = [
'data' => [
[
'type' => 'standard',
'cells' => [
['label' => 'メーカー', 'value' => $pumpInfo['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
['label' => '年式', 'value' => !empty($pumpInfo['model_year']) ? $pumpInfo['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
['label' => '更新年月日', 'value' => $pumpInfo['update_date'] ?? null, 'type' => 'date', 'width' => '33.33%'],
]
],
]
];
}

// レジオネラ検査（複数対応）
$legionellaDataSets = [];
if (isset($legionellaInfo['inspections']) && is_array($legionellaInfo['inspections'])) {
foreach ($legionellaInfo['inspections'] as $index => $inspection) {
$legionellaDataSets[] = [
'data' => [
[
'type' => 'standard',
'cells' => [
['label' => '実施日', 'value' => $inspection['inspection_date'] ?? null, 'type' => 'date'],
['label' => '検査結果報告書', 'value' => $inspection['report']['report_pdf'] ?? null, 'type' => 'file_display', 'options' => ['route' => 'facilities.lifeline-equipment.download-file', 'params' => [$facility, 'water', 'legionella_report_' . $index], 'display_name' => 'ダウンロード']],
]
],
[
'type' => 'standard',
'cells' => [
['label' => '検査結果（初回）', 'value' => $inspection['first_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($inspection['first_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success']],
['label' => '数値（陽性の場合）', 'value' => $inspection['first_value'] ?? null, 'type' => 'text'],
]
],
[
'type' => 'standard',
'cells' => [
['label' => '検査結果（2回目）', 'value' => $inspection['second_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($inspection['second_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success']],
['label' => '数値（陽性の場合）', 'value' => $inspection['second_value'] ?? null, 'type' => 'text'],
]
],
]
];
}
} else {
$legionellaDataSets[] = [
'data' => [
[
'type' => 'standard',
'cells' => [
['label' => '実施日', 'value' => $legionellaInfo['inspection_date'] ?? null, 'type' => 'date'],
['label' => '検査結果報告書', 'value' => $legionellaInfo['report']['report_pdf'] ?? null, 'type' => 'file_display', 'options' => ['route' => 'facilities.lifeline-equipment.download-file', 'params' => [$facility, 'water', 'legionella_report_0'], 'display_name' => 'ダウンロード']],
]
],
[
'type' => 'standard',
'cells' => [
['label' => '検査結果（初回）', 'value' => $legionellaInfo['first_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($legionellaInfo['first_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success']],
['label' => '数値（陽性の場合）', 'value' => $legionellaInfo['first_value'] ?? null, 'type' => 'text'],
]
],
[
'type' => 'standard',
'cells' => [
['label' => '検査結果（2回目）', 'value' => $legionellaInfo['second_result'] ?? null, 'type' => 'badge', 'options' => ['badge_class' => ($legionellaInfo['second_result'] ?? '') === '陽性' ? 'bg-danger' : 'bg-success']],
['label' => '数値（陽性の場合）', 'value' => $legionellaInfo['second_value'] ?? null, 'type' => 'text'],
]
],
]
];
}

// 備考
$notesData = [
[
'type' => 'standard',
'cells' => [
['label' => '備考', 'value' => $waterEquipment?->notes ?? null, 'type' => 'text', 'width' => '100%'],
]
],
];
@endphp

<div class="water-equipment-sections">
    <div class="equipment-section mb-4">
        <h6 class="section-title">基本情報</h6>
        <div class="water-six-column-equal">
            <div class="table-responsive">
                <table class="table facility-basic-info-table-clean" style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;">
                    <colgroup>
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                    </colgroup>
                    <tbody>
                        @foreach($basicInfoData as $row)
                        <tr>
                            @if($row['type'] === 'standard')
                            @foreach($row['cells'] as $cell)
                            @if($cell['width'] === '100%')
                            <td class="detail-label" style="padding: .5rem; border: 1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                            <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding: .5rem; border: 1px solid #e9ecef !important;" colspan="5">
                                {{ $cell['value'] ?? '未設定' }}
                            </td>
                            @elseif($cell['width'] === '33.33%')
                            <td class="detail-label" style="padding: .5rem; border: 1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                            <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding: .5rem; border: 1px solid #e9ecef !important;">
                                @if($cell['type'] === 'date' && !empty($cell['value']))
                                {{ \Carbon\Carbon::parse($cell['value'])->format('Y年m月d日') }}
                                @elseif($cell['type'] === 'file_display' && !empty($cell['value']))
                                <a href="{{ route($cell['options']['route'], $cell['options']['params']) }}" class="text-decoration-none" target="_blank">
                                    <i class="fas fa-file-pdf me-1 text-danger"></i>{{ $cell['options']['display_name'] }}
                                </a>
                                @else
                                {{ $cell['value'] ?? '未設定' }}
                                @endif
                            </td>
                            @endif
                            @endforeach
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">ろ過器</h6>
        <div class="water-six-column-equal">
            <div class="table-responsive">
                <table class="table facility-basic-info-table-clean" style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;">
                    <colgroup>
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                    </colgroup>
                    <tbody>
                        @foreach($filterData as $row)
                        <tr>
                            @if($row['type'] === 'standard')
                            @foreach($row['cells'] as $cell)
                            @if($cell['width'] === '100%')
                            <td class="detail-label" style="padding:.5rem;border:1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                            <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding:.5rem;border:1px solid #e9ecef !important;" colspan="5">
                                {{ $cell['value'] ?? '未設定' }}
                            </td>
                            @elseif($cell['width'] === '33.33%')
                            <td class="detail-label" style="padding:.5rem;border:1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                            <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding:.5rem;border:1px solid #e9ecef !important;">
                                {{ $cell['value'] ?? '未設定' }}
                            </td>
                            @endif
                            @endforeach
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">受水槽</h6>
        <div class="water-six-column-equal">
            <div class="table-responsive">
                <table class="table facility-basic-info-table-clean" style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;">
                    <colgroup>
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                        <col style="width: 16.67%;">
                    </colgroup>
                    <tbody>
                        @foreach($tankData as $row)
                        <tr>
                            @if($row['type'] === 'standard')
                            @foreach($row['cells'] as $cell)
                            <td class="detail-label" style="padding:.5rem;border:1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                            <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding:.5rem;border:1px solid #e9ecef !important;">
                                {{ $cell['value'] ?? '未設定' }}
                            </td>
                            @endforeach
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">加圧ポンプ</h6>
        @foreach($pumpDataSets as $pumpSet)
        <div class="pump-equipment-wrapper mb-3">
            <div class="water-six-column-equal">
                <div class="table-responsive">
                    <table class="table facility-basic-info-table-clean" style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;">
                        <colgroup>
                            <col style="width:16.67%;">
                            <col style="width:16.67%;">
                            <col style="width:16.67%;">
                            <col style="width:16.67%;">
                            <col style="width:16.67%;">
                            <col style="width:16.67%;">
                        </colgroup>
                        <tbody>
                            @foreach($pumpSet['data'] as $row)
                            <tr>
                                @if($row['type'] === 'standard')
                                @foreach($row['cells'] as $cell)
                                <td class="detail-label" style="padding:.5rem;border:1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                                <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding:.5rem;border:1px solid #e9ecef !important;">
                                    @if($cell['type'] === 'date' && !empty($cell['value']))
                                    {{ \Carbon\Carbon::parse($cell['value'])->format('Y年m月d日') }}
                                    @else
                                    {{ $cell['value'] ?? '未設定' }}
                                    @endif
                                </td>
                                @endforeach
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="equipment-section mb-4">
        <h6 class="section-title">浄化槽</h6>
        <div class="water-six-column-equal">
            <div class="table-responsive">
                <table class="table facility-basic-info-table-clean" style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;">
                    <colgroup>
                        <col style="width:16.67%;">
                        <col style="width:16.67%;">
                        <col style="width:16.67%;">
                        <col style="width:16.67%;">
                        <col style="width:16.67%;">
                        <col style="width:16.67%;">
                    </colgroup>
                    <tbody>
                        @foreach($septicTankData as $row)
                        <tr>
                            @if($row['type'] === 'standard')
                            @foreach($row['cells'] as $cell)
                            <td class="detail-label" style="padding:.5rem;border:1px solid #e9ecef !important;">{{ $cell['label'] }}</td>
                            <td class="detail-value {{ empty($cell['value']) ? 'empty-field' : '' }}" style="padding:.5rem;border:1px solid #e9ecef !important;">
                                @if($cell['type'] === 'date' && !empty($cell['value']))
                                {{ \Carbon\Carbon::parse($cell['value'])->format('Y年m月d日') }}
                                @elseif($cell['type'] === 'file_display' && !empty($cell['value']))
                                <a href="{{ route($cell['options']['route'], $cell['options']['params']) }}" class="text-decoration-none" target="_blank">
                                    <i class="fas fa-file-pdf me-1 text-danger"></i>{{ $cell['options']['display_name'] }}
                                </a>
                                @else
                                {{ $cell['value'] ?? '未設定' }}
                                @endif
                            </td>
                            @endforeach
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ▼▼ レジオネラ検査（ここだけ列幅をCSS変数で1列ずつ調整可能） ▼▼ -->
    <div class="equipment-section mb-4">
        <h6 class="section-title">レジオネラ検査</h6>
        @foreach($legionellaDataSets as $legionellaSet)
        <div class="legionella-equipment-wrapper mb-3">
            <x-common-table
                :data="$legionellaSet['data']"
                :showHeader="false"
                tableClass="table table-bordered facility-basic-info-table-clean legionella-4col"
                :tableAttributes="[]" />
        </div>
        @endforeach
    </div>
    <!-- ▲▲ レジオネラ検査 ▲▲ -->

    <div class="equipment-section mb-4">
        <h6 class="section-title">備考</h6>
        <x-common-table
            :data="$notesData"
            :showHeader="false"
            tableClass="table table-bordered facility-basic-info-table-clean notes-single-col"
            :tableAttributes="[]" />
    </div>
</div>

@vite(['resources/js/modules/lifeline-modal-manager.js'])
@vite(['resources/css/pages/lifeline-equipment.css'])

<!-- 隠しボタン（ヘッダーのドキュメントボタンから呼び出される） -->
<button type="button" class="d-none" id="water-documents-toggle" data-bs-toggle="modal" data-bs-target="#water-documents-modal"></button>

<!-- モーダル -->
<div class="modal fade" id="water-documents-modal" tabindex="-1" aria-labelledby="water-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="water-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>水道設備ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <x-lifeline-document-manager
                    :facility="$facility"
                    category="water"
                    categoryName="水道設備" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<style>
/* 水道設備ドキュメント管理モーダルのスタイル */
#water-documents-modal { z-index: 9999 !important; }
#water-documents-modal .modal-dialog { max-width: 90%; margin: 1.75rem auto; }
#water-documents-modal .modal-body { min-height: 500px; max-height: calc(100vh - 200px); overflow-y: auto; }
#create-folder-modal-water, #upload-file-modal-water, #rename-modal-water, #properties-modal-water { z-index: 10000 !important; }
.modal button, .modal input, .modal select, .modal textarea, .modal a, .modal label { pointer-events: auto !important; }
.document-item, .document-item * { pointer-events: auto !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('water-documents-modal');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    document.addEventListener('show.bs.modal', function(ev) {
        if (ev.target && ev.target.id && ev.target.id.includes('water')) {
            ev.target.style.zIndex = ev.target.id === 'water-documents-modal' ? '9999' : '10000';
            setTimeout(function() {
                document.querySelectorAll('.modal-backdrop').forEach(function(bd, i, arr) {
                    bd.style.zIndex = i === arr.length - 1 ? (ev.target.id === 'water-documents-modal' ? '9998' : '9999') : '9998';
                });
            }, 0);
        }
    });
});
</script>

<style>
    /* 4列（ラベル/値 ×2）をCSS変数で制御 */
  .legionella-4col {
    --w1: 16.6%;
    --w2: 50.2%;
    --w3: 16.6%;
    --w4: 16.6%;
    }

    .legionella-4col tbody>tr>td:nth-child(1),
    .legionella-4col tbody>tr>th:nth-child(1) {
        width: var(--w1) !important;
    }

    .legionella-4col tbody>tr>td:nth-child(2),
    .legionella-4col tbody>tr>th:nth-child(2) {
        width: var(--w2) !important;
    }

    .legionella-4col tbody>tr>td:nth-child(3),
    .legionella-4col tbody>tr>th:nth-child(3) {
        width: var(--w3) !important;
    }

    .legionella-4col tbody>tr>td:nth-child(4),
    .legionella-4col tbody>tr>th:nth-child(4) {
        width: var(--w4) !important;
    }

    /* 備考テーブルの横幅調整 */
    .notes-single-col {
        --w1: 16.6%;
        --w2: 83.4%;
    }

    .notes-single-col tbody > tr > td:nth-child(1),
    .notes-single-col tbody > tr > th:nth-child(1) {
        width: var(--w1) !important;
    }

    .notes-single-col tbody > tr > td:nth-child(2),
    .notes-single-col tbody > tr > th:nth-child(2) {
        width: var(--w2) !important;
    }

</style>