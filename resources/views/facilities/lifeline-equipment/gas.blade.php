@php
$gasEquipment    = $facility->getGasEquipment();
$basicInfo       = $gasEquipment?->basic_info ?? [];
$waterHeaterInfo = $basicInfo['water_heater_info'] ?? [];
$waterHeaters    = is_array($waterHeaterInfo['water_heaters'] ?? null) ? $waterHeaterInfo['water_heaters'] : [];
$canEdit         = auth()->user()->canEditFacility($facility->id);

// 基本情報テーブル
$basicInfoData = [
    [
        'type'  => 'standard',
        'cells' => [
            ['label' => 'ガス契約会社', 'value' => $basicInfo['gas_supplier'] ?? null, 'type' => 'text', 'width' => '50%'],
            ['label' => 'ガスの種類',   'value' => $basicInfo['gas_type']     ?? null, 'type' => 'text', 'width' => '50%'],
        ],
    ],
];

// 床暖房
$floorHeatingInfo = $basicInfo['floor_heating_info'] ?? [];
$floorHeatingData = [
    [
        'type'  => 'standard',
        'cells' => [
            ['label' => 'メーカー', 'value' => $floorHeatingInfo['manufacturer'] ?? null, 'type' => 'text', 'width' => '33.33%'],
            ['label' => '年式',     'value' => !empty($floorHeatingInfo['model_year']) ? $floorHeatingInfo['model_year'].'年式' : null, 'type' => 'text', 'width' => '33.33%'],
            ['label' => '更新年月日','value' => $floorHeatingInfo['update_date'] ?? null, 'type' => 'date', 'width' => '33.33%'],
        ],
    ],
];

// 備考
$notesData = [
    [
        'type'  => 'standard',
        'cells' => [
            ['label' => '備考', 'value' => $gasEquipment?->notes ?? null, 'type' => 'text', 'width' => '100%'],
        ],
    ],
];
@endphp

<!-- ガス設備ヘッダー -->
<div class="mb-3">
  <h5 class="mb-0">
    <i class="fas fa-fire text-danger me-2"></i>ガス設備情報
  </h5>
</div>

<!-- 隠しドキュメントトリガー（編集ボタンの隣のドキュメントボタンから呼び出される） -->
<button type="button" id="gas-documents-toggle" class="d-none" data-bs-toggle="modal" data-bs-target="#gas-documents-modal"></button>

<div class="gas-equipment-sections equipment-sections">
  <!-- 基本情報 -->
  <div class="equipment-section mb-4">
    <div class="gas-four-column-equal">
      <x-common-table
        :data="$basicInfoData"
        :showHeader="false"
        :tableAttributes="['class' => 'table table-bordered gas-info-table']"
        tableClass="table table-bordered facility-basic-info-table-clean gas-basic-4col" />
    </div>
  </div>

  <!-- 給湯器 -->
  <div class="equipment-section mb-4">
    <div class="section-header mb-3">
      <h6 class="section-title mb-0">給湯器</h6>
    </div>

    <!-- 設置の有無：6列の左2セルだけ表示。右4セルは幅だけ確保。右端はマスクで消す -->
    <div class="gas-six-column-equal">
      <div class="table-responsive position-relative gas-avail-wrap">
        <!-- 右端マスク（テーブル最右の余計な縦線を隠す／「有」セルの枠線は残す） -->
        <div class="right-edge-mask"></div>

        <table class="table facility-basic-info-table-clean gas-availability-row">
          <colgroup>
            <col class="gas-sixcol"><col class="gas-sixcol"><col class="gas-sixcol">
            <col class="gas-sixcol"><col class="gas-sixcol"><col class="gas-sixcol">
          </colgroup>
          <tbody>
            <tr>
              <!-- 1/6：ラベル -->
              <td class="detail-label ga-cell">設置の有無</td>
              <!-- 2/6：値（右端は角丸＆枠線あり） -->
              <td class="detail-value ga-cell ga-rounded-right {{ empty($waterHeaterInfo['availability']) ? 'empty-field' : '' }}">
                {{ $waterHeaterInfo['availability'] ?? '未設定' }}
              </td>
              <!-- 3〜6/6：透明セル（幅だけ確保・非表示） -->
              <td class="ga-blank"></td>
              <td class="ga-blank"></td>
              <td class="ga-blank"></td>
              <td class="ga-blank"></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 給湯器設備一覧（availability が「有」のときのみ表示） -->
    @if(($waterHeaterInfo['availability'] ?? '') === '有' && !empty($waterHeaters))
      @foreach($waterHeaters as $index => $heater)
        <div class="gas-equipment-wrapper mb-3">
          <div class="gas-six-column-equal">
            <div class="table-responsive">
              <table class="table facility-basic-info-table-clean gas-heater-detail" style="border:1px solid #e9ecef; border-radius:.5rem; overflow:hidden;">
                <colgroup>
                  <col class="gas-sixcol"><col class="gas-sixcol"><col class="gas-sixcol">
                  <col class="gas-sixcol"><col class="gas-sixcol"><col class="gas-sixcol">
                </colgroup>
                <tbody>
                  <tr>
                    <td class="detail-label">メーカー{{ $index + 1 }}</td>
                    <td class="detail-value {{ empty($heater['manufacturer']) ? 'empty-field' : '' }}">{{ $heater['manufacturer'] ?? '未設定' }}</td>
                    <td class="detail-label">年式</td>
                    <td class="detail-value {{ empty($heater['model_year']) ? 'empty-field' : '' }}">{{ !empty($heater['model_year']) ? $heater['model_year'].'年式' : '未設定' }}</td>
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
          <i class="fas fa-info-circle me-2"></i>給湯器設備の詳細情報が登録されていません。
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
        tableClass="table table-bordered facility-basic-info-table-clean gas-floor-heating-6col" />
    </div>
  </div>

  <!-- 備考 -->
  <div class="equipment-section mb-4">
    <h6 class="section-title">備考</h6>
    <x-common-table
      :data="$notesData"
      :showHeader="false"
      :tableAttributes="['class' => 'table table-bordered gas-info-table']"
      tableClass="table table-bordered facility-basic-info-table-clean gas-notes-2col" />
  </div>
</div>

<!-- 隠しボタン（ヘッダーのドキュメントボタンから呼び出される） -->
<button type="button" class="d-none" id="gas-documents-toggle" data-bs-toggle="modal" data-bs-target="#gas-documents-modal"></button>

<!-- モーダル -->
<div class="modal fade" id="gas-documents-modal" tabindex="-1" aria-labelledby="gas-documents-modal-title"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
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

<style>
/* ガス設備ドキュメント管理モーダルのスタイル */
#gas-documents-modal { z-index: 9999 !important; }
#gas-documents-modal .modal-dialog { max-width: 90%; margin: 1.75rem auto; }
#gas-documents-modal .modal-body { min-height: 500px; max-height: calc(100vh - 200px); overflow-y: auto; }
#create-folder-modal-gas, #upload-file-modal-gas, #rename-modal-gas, #properties-modal-gas { z-index: 10000 !important; }
.modal button, .modal input, .modal select, .modal textarea, .modal a, .modal label { pointer-events: auto !important; }
.document-item, .document-item * { pointer-events: auto !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('gas-documents-modal');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    document.addEventListener('show.bs.modal', function(ev) {
        if (ev.target && ev.target.id && ev.target.id.includes('gas')) {
            ev.target.style.zIndex = ev.target.id === 'gas-documents-modal' ? '9999' : '10000';
            setTimeout(function() {
                document.querySelectorAll('.modal-backdrop').forEach(function(bd, i, arr) {
                    bd.style.zIndex = i === arr.length - 1 ? (ev.target.id === 'gas-documents-modal' ? '9998' : '9999') : '9998';
                });
            }, 0);
        }
    });
});
</script>

@vite(['resources/js/modules/lifeline-modal-manager.js'])
@vite(['resources/css/pages/lifeline-equipment.css'])
@vite(['resources/css/pages/lifeline-equipment-gas.css'])

<style>
/* ===== 「設置の有無」行（上段2セルのみ表示） ===== */
.table.gas-availability-row{
  width:100%;
  table-layout:fixed;
  border-collapse:separate !important;
  border-spacing:0;
  margin-bottom:0;
  background:#fff;
}

/* 6等分（下段と同じ基準線に揃える） */
.gas-availability-row col.gas-sixcol{ width:16.6667%; }

/* 左2セル */
.gas-availability-row .ga-cell{
  padding:.5rem;
  border:1px solid #e9ecef;
  background:#fff;
  vertical-align:middle;
  word-break:break-word;
}

/* 「有」セル：右端は角丸＆枠線あり（消さない） */
.gas-availability-row .ga-rounded-right{
  border-top-right-radius:.5rem;
  border-bottom-right-radius:.5rem;
  border-right:1px solid #e9ecef !important;
  position:relative;
  overflow:hidden;
}

/* 右4セルは非表示（幅だけ確保） */
.gas-availability-row .ga-blank{
  padding:0 !important;
  border:none !important;
  background:transparent !important;
}

/* ===== 右端マスク（テーブル右外側の縦線だけ隠す） ===== */
.gas-avail-wrap{ position:relative; }
.gas-avail-wrap .right-edge-mask{
  position:absolute;
  top:0;
  right:0;
  width:2px;
  height:100%;
  background:#fff;
  pointer-events:none;
  z-index:2;
  border-top-right-radius:.5rem;
  border-bottom-right-radius:.5rem;
}

/* ===== 下段テーブルの体裁（参考） ===== */
.table.gas-heater-detail{
  table-layout:fixed;
  width:100%;
  margin-bottom:0;
  border-collapse:separate;
  border-spacing:0;
}

.facility-basic-info-table-clean td.detail-label,
.facility-basic-info-table-clean td.detail-value{
  vertical-align:middle;
}
</style>
