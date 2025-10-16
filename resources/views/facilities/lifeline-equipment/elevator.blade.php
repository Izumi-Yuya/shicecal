@php
use Illuminate\Support\Facades\Storage;

$elevatorEquipment = $facility->getElevatorEquipment();
$basicInfo        = $elevatorEquipment?->basic_info ?? [];
$elevators        = $basicInfo['elevators'] ?? [];
$inspectionInfo   = $basicInfo['inspection'] ?? [];
$canEdit          = auth()->user()->canEditFacility($facility->id);

// 基本情報
$availability = $basicInfo['availability'] ?? null;
@endphp

<!-- ヘッダー -->
<div class="mb-3">
  <h5 class="mb-0">
    <i class="fas fa-elevator text-secondary me-2"></i>エレベーター設備情報
  </h5>
</div>

<div class="elevator-equipment-sections">

  @if($elevatorEquipment)

    {{-- ▼ 基本情報（設置の有無）…8等分グリッドで下段と縦ラインを合わせる --}}
    <div class="equipment-section mb-4">
      <div class="table-responsive position-relative elevator-avail-wrap">
        <!-- 3〜8列目を覆う白マスク（「有」セルの右枠線は最前面で残す） -->
        <div class="right-hide-mask"></div>

        <table class="table table-bordered facility-basic-info-table-clean elevator-eightcol-table elevator-availability-row" style="margin-bottom:0;">
          <colgroup>
            <col> <!-- 1: ラベル -->
            <col> <!-- 2: 値 -->
            <col><col><col><col><col><col> <!-- 3〜8: 幅だけ確保 -->
          </colgroup>
          <tbody>
            <tr>
              <!-- 1/8：ラベル -->
              <td class="detail-label elev-cell">設置の有無</td>

              <!-- 2/8：値（右辺は枠線あり／角丸） -->
              <td class="detail-value elev-cell elev-rounded-right {{ empty($availability) ? 'empty-field' : '' }}">
                {{ $availability ?? '未設定' }}
              </td>

              <!-- 3〜8/8：完全非表示（幅だけ確保） -->
              <td class="elev-blank"></td>
              <td class="elev-blank"></td>
              <td class="elev-blank"></td>
              <td class="elev-blank"></td>
              <td class="elev-blank"></td>
              <td class="elev-blank"></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    {{-- ▼ エレベーター設備一覧（8等分） --}}
    @if($availability === '有')
      <div class="equipment-section mb-4">
        <h6 class="section-title">エレベーター設備一覧</h6>
        <div class="table-responsive">
          <table class="table table-bordered facility-basic-info-table-clean elevator-eightcol-table" style="margin-bottom:0;">
            <colgroup>
              <col><col><col><col><col><col><col><col>
            </colgroup>
            <tbody>
              @if(!empty($elevators) && is_array($elevators))
                @foreach($elevators as $index => $elevator)
                  <tr>
                    <td class="detail-label">メーカー{{ $index + 1 }}</td>
                    <td class="detail-value {{ empty($elevator['manufacturer']) ? 'empty-field' : '' }}">{{ $elevator['manufacturer'] ?? '未設定' }}</td>
                    <td class="detail-label">型式</td>
                    <td class="detail-value {{ empty($elevator['type']) ? 'empty-field' : '' }}">{{ $elevator['type'] ?? '未設定' }}</td>
                    <td class="detail-label">製造年</td>
                    <td class="detail-value {{ empty($elevator['model_year']) ? 'empty-field' : '' }}">{{ $elevator['model_year'] ?? '未設定' }}</td>
                    <td class="detail-label">更新年月日</td>
                    <td class="detail-value {{ empty($elevator['update_date']) ? 'empty-field' : '' }}">
                      {{ !empty($elevator['update_date']) ? \Carbon\Carbon::parse($elevator['update_date'])->format('Y年m月d日') : '未設定' }}
                    </td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="8" class="text-center text-muted" style="padding:1rem;">エレベーター設備情報が登録されていません</td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>

      {{-- ▼ 保守点検情報（6等分） --}}
      <div class="equipment-section mb-4">
        <h6 class="section-title">保守点検情報</h6>
        <div class="table-responsive">
          <table class="table table-bordered facility-basic-info-table-clean elevator-grid6 elevator-inspection-sixcol" style="margin-bottom:0;">
            <colgroup><col><col><col><col><col><col></colgroup>
            <tbody>
              <tr>
                <td class="detail-label">保守業者</td>
                <td class="detail-value {{ empty($inspectionInfo['maintenance_contractor']) ? 'empty-field' : '' }}">{{ $inspectionInfo['maintenance_contractor'] ?? '未設定' }}</td>
                <td class="detail-label">保守点検実施日</td>
                <td class="detail-value {{ empty($inspectionInfo['inspection_date']) ? 'empty-field' : '' }}">{{ !empty($inspectionInfo['inspection_date']) ? \Carbon\Carbon::parse($inspectionInfo['inspection_date'])->format('Y年m月d日') : '未設定' }}</td>
                <td class="detail-label">保守点検報告書</td>
                <td class="detail-value {{ empty($inspectionInfo['inspection_report_filename']) ? 'empty-field' : '' }}">
                  @if(!empty($inspectionInfo['inspection_report_filename']))
                    <a href="{{ route('facilities.lifeline-equipment.download-file', [$facility, 'elevator', 'inspection_report']) }}">
                      {{ $inspectionInfo['inspection_report_filename'] }}
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
    @endif

    {{-- ▼ 備考（6等分） --}}
    <div class="equipment-section mb-4">
      <h6 class="section-title">備考</h6>
      <div class="table-responsive">
        <table class="table table-bordered facility-basic-info-table-clean elevator-grid6 elevator-notes-table">
          <colgroup><col><col><col><col><col><col></colgroup>
          <tbody>
            <tr>
              <td class="detail-label">備考</td>
              <td class="detail-value {{ empty($elevatorEquipment->notes) ? 'empty-field' : '' }}" colspan="5">
                {{ $elevatorEquipment->notes ?? '未設定' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  @else
    <div class="alert alert-info">
      <i class="fas fa-info-circle me-2"></i>詳細仕様は開発中です。基本的なカード構造が準備されています。
    </div>
  @endif
</div>

@vite(['resources/js/modules/lifeline-modal-manager.js'])
@vite(['resources/css/pages/lifeline-equipment.css'])

<!-- 隠しボタン（ドキュメント管理用） -->
<button type="button" class="d-none" id="elevator-documents-toggle"
        data-bs-toggle="modal" data-bs-target="#elevator-documents-modal"></button>

<!-- モーダル -->
<div class="modal fade" id="elevator-documents-modal" tabindex="-1" aria-labelledby="elevator-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title" id="elevator-documents-modal-title">
          <i class="fas fa-folder-open me-2"></i>エレベーター設備ドキュメント管理
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
      </div>
      <div class="modal-body">
        <x-lifeline-document-manager :facility="$facility" category="elevator" categoryName="エレベーター設備" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
      </div>
    </div>
  </div>
</div>

<style>
/* エレベーター設備ドキュメント管理モーダルのスタイル */
#elevator-documents-modal { z-index: 9999 !important; }
#elevator-documents-modal .modal-dialog { max-width: 90%; margin: 1.75rem auto; }
#elevator-documents-modal .modal-body { min-height: 500px; max-height: calc(100vh - 200px); overflow-y: auto; }
#create-folder-modal-elevator, #upload-file-modal-elevator, #rename-modal-elevator, #properties-modal-elevator { z-index: 10000 !important; }
.modal button, .modal input, .modal select, .modal textarea, .modal a, .modal label { pointer-events: auto !important; }
.document-item, .document-item * { pointer-events: auto !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('elevator-documents-modal');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    document.addEventListener('show.bs.modal', function(ev) {
        if (ev.target && ev.target.id && ev.target.id.includes('elevator')) {
            ev.target.style.zIndex = ev.target.id === 'elevator-documents-modal' ? '9999' : '10000';
            setTimeout(function() {
                document.querySelectorAll('.modal-backdrop').forEach(function(bd, i, arr) {
                    bd.style.zIndex = i === arr.length - 1 ? (ev.target.id === 'elevator-documents-modal' ? '9998' : '9999') : '9998';
                });
            }, 0);
        }
    });
});
</script>

<!-- ▼ スタイル -->
<style>
/* ===== 共通 ===== */
.elevator-equipment-sections .table-responsive{ padding:0 !important; }
.elevator-equipment-sections table{
  table-layout:fixed !important; width:100% !important;
  border-collapse:separate !important; border-spacing:0 !important;
}
.elevator-equipment-sections table td,
.elevator-equipment-sections table th{
  box-sizing:border-box !important; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}

/* 見出し */
.elevator-equipment-sections .section-title{ border:none !important; margin-bottom:.5rem !important; padding-bottom:0 !important; }

/* ===== 6等分グリッド ===== */
.elevator-grid6 col{ width:16.6667% !important; }
.elevator-grid6 td, .elevator-grid6 th{ padding:.5rem !important; }
.elevator-grid6 .detail-label{ white-space:nowrap; font-weight:500; }
.elevator-grid6 .detail-value.empty-field{ font-style:italic; color:#6c757d; }
.elevator-equipment-sections .elevator-grid6{
  margin:0 !important; border-left:1px solid #e9ecef !important; border-right:1px solid #e9ecef !important;
}

/* ===== エレベーター設備一覧（8等分） ===== */
.elevator-eightcol-table col{ width:calc(100% / 8) !important; }
.elevator-eightcol-table td, .elevator-eightcol-table th{
  width:calc(100% / 8) !important; max-width:calc(100% / 8) !important; min-width:calc(100% / 8) !important;
  padding:.5rem !important; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.elevator-eightcol-table .detail-label{ background:#f8f9fa !important; font-weight:500; }
.elevator-eightcol-table .detail-value.empty-field{ font-style:italic; color:#6c757d; }

/* ラベル列の基準幅（設置の有無・一覧1列目を統一） */
.elevator-equipment-sections{ --label-col-w:16.6667%; --value-col-w: calc((100% - var(--label-col-w)) / 7); }
.elevator-eightcol-table col:nth-child(1){ width:var(--label-col-w) !important; }
.elevator-eightcol-table col:nth-child(n+2){ width:calc((100% - var(--label-col-w)) / 7) !important; }
.elevator-eightcol-table tr > *:nth-child(1){
  width:var(--label-col-w) !important; max-width:var(--label-col-w) !important; min-width:var(--label-col-w) !important;
}
.elevator-eightcol-table tr > *:nth-child(n+2){
  width:calc((100% - var(--label-col-w)) / 7) !important;
  max-width:calc((100% - var(--label-col-w)) / 7) !important;
  min-width:calc((100% - var(--label-col-w)) / 7) !important;
}

/* ===== 「設置の有無」行：左2セルのみ表示 ===== */
.elevator-availability-row .elev-cell{
  padding:.5rem; border:1px solid #e9ecef; background:#fff; vertical-align:middle; word-break:break-word;
}

/* 値セル（右端は擬似要素で描画、はみ出しはマスクで抑止） */
.elevator-availability-row .elev-rounded-right {
  border-top-right-radius: .5rem;
  border-bottom-right-radius: .5rem;
  border-right-color: transparent !important; /* ← 既存の右線は使わない */
  position: relative;
  z-index: 2; /* 値セルは前面に */
  overflow: visible;
}

/* 右端の縦線を描き直す（常にぴったり1px） */
.elevator-availability-row .elev-rounded-right::before {
  content: "";
  position: absolute;
  top: 0;
  right: 0;
  width: 1px;
  height: 100%;
  background: #e9ecef;
  z-index: 4;
}

/* 右端外側を白で覆って、上・下の丸みのはみ出しを完全に隠す */
.elevator-availability-row .elev-rounded-right::after {
  content: "";
  position: absolute;
  top: 0;
  right: -1px;
  width: 2px;
  height: 100%;
  background: #fff;
  z-index: 3;
  border-top-right-radius: .5rem;
  border-bottom-right-radius: .5rem;
}

/* 3〜8列目は完全非表示（幅だけ確保） */
.elevator-availability-row .elev-blank{
  padding:0 !important; border:none !important; background:transparent !important;
  outline:none !important; box-shadow:none !important;
}

/* ===== 右側隠しマスク：3〜8列目を覆って消す（右端の縦線も同時に消える） ===== */
.elevator-avail-wrap{ position:relative; }
.elevator-avail-wrap .right-hide-mask {
  position: absolute;
  top: 0;
  left: calc(var(--label-col-w) + var(--value-col-w) - 1px); /* ← 微調整済み */
  right: 0;
  height: 100%;
  background: #fff;
  pointer-events: none;
  z-index: 1; /* 値セルより背面 */
  border-top-right-radius: .5rem;
  border-bottom-right-radius: .5rem;
}

/* ===== 保守点検（6等分） ===== */
.elevator-inspection-sixcol{ table-layout:fixed !important; width:100% !important; border-collapse:collapse !important; }
.elevator-inspection-sixcol col{ width:calc(100% / 6) !important; }
.elevator-inspection-sixcol tr > *{ width:calc(100% / 6) !important; max-width:calc(100% / 6) !important; min-width:calc(100% / 6) !important; }
.elevator-inspection-sixcol td, .elevator-inspection-sixcol th{ padding:.5rem !important; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* ===== 備考（6等分） ===== */
.elevator-notes-table .detail-label{ background:#f8f9fa !important; color:#495057 !important; font-weight:500; }
.elevator-notes-table .detail-value{ background:#fff !important; color:inherit; }
</style>

