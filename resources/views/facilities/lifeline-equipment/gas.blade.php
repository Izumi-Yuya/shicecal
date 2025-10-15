@php
    $gasEquipment = $facility->getGasEquipment();
    $basicInfo = $gasEquipment?->basic_info ?? [];
    $waterHeaterInfo = $basicInfo['water_heater_info'] ?? [];
    $waterHeaters = $waterHeaterInfo['water_heaters'] ?? [];
    $canEdit = auth()->user()->canEditFacility($facility->id);

    // 基本情報テーブル
    $basicInfoData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'ガス契約会社', 'value' => $basicInfo['gas_supplier'] ?? null, 'type' => 'text', 'width' => '50%'],
                ['label' => 'ガスの種類', 'value' => $basicInfo['gas_type'] ?? null, 'type' => 'text', 'width' => '50%'],
            ],
        ],
    ];

    // 給湯器
    $waterHeaterInfo = $basicInfo['water_heater_info'] ?? [];
    $waterHeaters = is_array($waterHeaterInfo['water_heaters'] ?? null)
        ? $waterHeaterInfo['water_heaters']
        : [];

    // 床暖房
    $floorHeatingInfo = $basicInfo['floor_heating_info'] ?? [];
    $floorHeatingData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'メーカー', 'value' => $floorHeatingInfo['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
                ['label' => '年式', 'value' => !empty($floorHeatingInfo['model_year']) ? $floorHeatingInfo['model_year'] . '年式' : null, 'type' => 'text', 'width' => '33.33%'],
                ['label' => '更新年月日', 'value' => $floorHeatingInfo['update_date'] ?? null, 'type' => 'date', 'width' => '33.33%'],
            ],
        ],
    ];

    // 備考
    $notesData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '備考', 'value' => $gasEquipment?->notes ?? null, 'type' => 'text', 'width' => '100%'],
            ],
        ],
    ];
@endphp

<!-- ガス設備ヘッダー -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="fas fa-fire text-danger me-2"></i>ガス設備情報
    </h5>
    <div class="d-flex align-items-center gap-2">
        <button
            type="button"
            class="btn btn-outline-primary btn-sm"
            id="gas-documents-toggle"
            title="ガス設備ドキュメント管理"
        >
            <i class="fas fa-folder-open me-1"></i>
            <span class="d-none d-md-inline">ドキュメント</span>
        </button>
    </div>
</div>

<div class="gas-equipment-sections">
    <!-- 基本情報 -->
    <div class="equipment-section mb-4">
        <div class="gas-four-column-equal">
            <x-common-table
                :data="$basicInfoData"
                :showHeader="false"
                :tableAttributes="['class' => 'table table-bordered gas-info-table']"
                tableClass="table table-bordered facility-basic-info-table-clean gas-basic-4col"
            />
        </div>
    </div>

    <!-- 給湯器 -->
    <div class="equipment-section mb-4">
        <div class="section-header mb-3">
            <h6 class="section-title mb-0">給湯器</h6>
        </div>

        <!-- 設置の有無 -->
        <div class="gas-six-column-equal">
            <div class="table-responsive">
                <table
                    class="table facility-basic-info-table-clean gas-availability-2col"
                    style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;"
                >
                    <tbody>
                        <tr>
                            <td class="detail-label" style="padding: .5rem; border: 1px solid #e9ecef !important;">設置の有無</td>
                            <td
                                class="detail-value {{ empty($waterHeaterInfo['availability']) ? 'empty-field' : '' }}"
                                style="padding: .5rem; border: 1px solid #e9ecef !important;"
                            >
                                {{ $waterHeaterInfo['availability'] ?? '未設定' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 給湯器設備一覧 -->
        @if(($waterHeaterInfo['availability'] ?? '') === '有' && !empty($waterHeaters))
            @foreach($waterHeaters as $index => $heater)
                <div class="gas-equipment-wrapper mb-3">
                    <div class="gas-six-column-equal">
                        <div class="table-responsive">
                            <table
                                class="table facility-basic-info-table-clean"
                                style="table-layout: fixed; margin-bottom: 0; border: 1px solid #e9ecef;"
                            >
                                <tbody>
                                    <tr>
                                        <td class="detail-label">メーカー{{ $index + 1 }}</td>
                                        <td class="detail-value {{ empty($heater['manufacturer']) ? 'empty-field' : '' }}">
                                            {{ $heater['manufacturer'] ?? '未設定' }}
                                        </td>
                                        <td class="detail-label">年式</td>
                                        <td class="detail-value {{ empty($heater['model_year']) ? 'empty-field' : '' }}">
                                            {{ !empty($heater['model_year']) ? $heater['model_year'] . '年式' : '未設定' }}
                                        </td>
                                        <td class="detail-label">更新年月日</td>
                                        <td class="detail-value {{ empty($heater['update_date']) ? 'empty-field' : '' }}">
                                            @if(!empty($heater['update_date']))
                                                {{ \Carbon\Carbon::parse($heater['update_date'])->format('Y年m月d日') }}
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
            @endforeach
        @elseif(($waterHeaterInfo['availability'] ?? '') === '有')
            <div class="no-equipment-message">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    給湯器設備の詳細情報が登録されていません。
                </div>
            </div>
        @endif
    </div>

    <!-- 床暖房 -->
    <div class="equipment-section mb-4">
        <div class="section-header mb-3">
            <h6 class="section-title mb-0">床暖房</h6>
        </div>
        <div class="gas-six-column-equal">
            <x-common-table
                :data="$floorHeatingData"
                :showHeader="false"
                :tableAttributes="['class' => 'table table-bordered floor-heating-info-table']"
                tableClass="table table-bordered facility-basic-info-table-clean gas-floor-heating-6col"
            />
        </div>
    </div>

    <!-- 備考 -->
    <div class="equipment-section mb-4">
        <h6 class="section-title">備考</h6>
        <x-common-table
            :data="$notesData"
            :showHeader="false"
            :tableAttributes="['class' => 'table table-bordered gas-info-table']"
            tableClass="table table-bordered facility-basic-info-table-clean gas-notes-2col"
        />
    </div>
</div>

<!-- モーダル -->
<div
    class="modal fade"
    id="gas-documents-modal"
    tabindex="-1"
    aria-labelledby="gas-documents-modal-title"
    aria-hidden="true"
    data-bs-backdrop="static"
    data-bs-keyboard="true"
>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="gas-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>ガス設備ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <x-lifeline-document-manager :facility="$facility" category="gas" categoryName="ガス設備" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- ▼ 幅コントロール用CSS -->
<style>
/* ▼ 共通: table幅・ボックスモデルを完全一致させる */
.gas-equipment-sections .table-responsive {
  padding: 0 !important; /* 左右余白でズレないように */
}
.gas-equipment-sections table {
  table-layout: fixed !important;
  width: 100% !important;
  border-collapse: separate !important;
  border-spacing: 0 !important;
}
.gas-equipment-sections table td,
.gas-equipment-sections table th {
  box-sizing: border-box !important;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ▼ 基本情報（4列）—列ごとに独立して幅を調整可能に */
.gas-equipment-sections table.gas-basic-4col {
  table-layout: auto !important; /* ← 各列の指定幅を優先 */
  width: 100% !important;
  border-collapse: collapse !important;
}
/* 1〜4列の幅: 11% / 67% / 11% / 11%（合計100%） */
.gas-equipment-sections table.gas-basic-4col td:nth-child(1),
.gas-equipment-sections table.gas-basic-4col th:nth-child(1) { width: 11% !important; }
.gas-equipment-sections table.gas-basic-4col td:nth-child(2),
.gas-equipment-sections table.gas-basic-4col th:nth-child(2) { width: 67% !important; }
.gas-equipment-sections table.gas-basic-4col td:nth-child(3),
.gas-equipment-sections table.gas-basic-4col th:nth-child(3) { width: 11% !important; }
.gas-equipment-sections table.gas-basic-4col td:nth-child(4),
.gas-equipment-sections table.gas-basic-4col th:nth-child(4) { width: calc(100% - 11% - 67% - 11%) !important; }

/* ▼ 設置の有無（2列）—ラベル16.6% / 値83.4% */
.gas-equipment-sections table.gas-availability-2col col,
.gas-equipment-sections table.gas-availability-2col td[style*="width"],
.gas-equipment-sections table.gas-availability-2col th[style*="width"] { width: auto !important; }
.gas-equipment-sections table.gas-availability-2col tbody > tr > td:first-child,
.gas-equipment-sections table.gas-availability-2col tbody > tr > th:first-child {
  width: 16.6% !important; min-width: 16.6% !important; max-width: 16.6% !important;
}
.gas-equipment-sections table.gas-availability-2col tbody > tr > td:nth-child(2),
.gas-equipment-sections table.gas-availability-2col tbody > tr > th:nth-child(2) {
  width: 83.4% !important; min-width: 83.4% !important; max-width: 83.4% !important;
}

/* ▼ 備考（2列）—ラベル16.6%固定／値83.4%（長文折返しON） */
.gas-equipment-sections table.gas-notes-2col col,
.gas-equipment-sections table.gas-notes-2col td[style*="width"],
.gas-equipment-sections table.gas-notes-2col th[style*="width"] { width: auto !important; }
.gas-equipment-sections table.gas-notes-2col tbody > tr > td:first-child,
.gas-equipment-sections table.gas-notes-2col tbody > tr > th:first-child {
  width: 16.6% !important; min-width: 16.6% !important; max-width: 16.6% !important;
}
.gas-equipment-sections table.gas-notes-2col tbody > tr > td:nth-child(2),
.gas-equipment-sections table.gas-notes-2col tbody > tr > th:nth-child(2) {
  width: 83.4% !important; min-width: 83.4% !important; max-width: 83.4% !important;
  word-break: break-word; white-space: normal;
}

/* ▼ 視覚的ズレを起こす余白/枠の差を吸収（共通外枠だけ残す） */
.gas-equipment-sections table.gas-availability-2col,
.gas-equipment-sections table.gas-notes-2col,
.gas-equipment-sections table.gas-basic-4col {
  margin: 0 !important;
  border-left: 1px solid #e9ecef !important;
  border-right: 1px solid #e9ecef !important;
}

/* ===== 見出し（給湯器／床暖房／備考）の“線なし＋少し余白”調整 ===== */

/* 見出し自体の線を消す＋少しだけ下に余白を付ける */
.gas-equipment-sections .section-header,
.gas-equipment-sections .section-title {
  border: none !important;
  border-bottom: none !important;
  padding-bottom: 0 !important;
  margin-bottom: .7rem !important;
}

/* 見出し直後のテーブル（や包み要素）の上線は消す。余白は見出し側で付ける */
.gas-equipment-sections .section-header + .gas-six-column-equal,
.gas-equipment-sections .section-title + .gas-six-column-equal,
.gas-equipment-sections .section-header + .gas-six-column-equal table,
.gas-equipment-sections .section-title + .gas-six-column-equal table,
.gas-equipment-sections .section-title + .table,
.gas-equipment-sections .section-title + .x-common-table {
  border-top: none !important;
  margin-top: 0 !important;
}

/* セクション外枠の余計な線や上余白は不要 */
.gas-equipment-sections .equipment-section {
  border-top: none !important;
  margin-top: 0 !important;
  padding-top: 0 !important;
}

</style>
