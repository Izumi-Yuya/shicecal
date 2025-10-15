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
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">
    <i class="fas fa-elevator text-secondary me-2"></i>エレベーター設備情報
  </h5>
  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-outline-primary btn-sm" id="elevator-documents-toggle" title="エレベーター設備ドキュメント管理" data-bs-toggle="modal" data-bs-target="#elevator-documents-modal">
      <i class="fas fa-folder-open me-1"></i>
      <span class="d-none d-md-inline">ドキュメント</span>
    </button>
  </div>
</div>

<div class="elevator-equipment-sections">

  @if($elevatorEquipment)

    {{-- ▼ 基本情報（設置の有無）…6等分グリッドで縦ラインを合せる --}}
    <div class="equipment-section mb-4">
      <div class="table-responsive">
        <table class="table table-bordered facility-basic-info-table-clean elevator-grid6">
          <colgroup><col><col><col><col><col><col></colgroup>
          <tbody>
            <tr>
              <td class="detail-label">設置の有無</td>
              <td class="detail-value" colspan="5">{{ $availability ?? '未設定' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    {{-- ▼ エレベーター設備一覧（1行8等分） --}}
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
                    <td class="detail-value {{ empty($elevator['manufacturer']) ? 'empty-field' : '' }}">
                      {{ $elevator['manufacturer'] ?? '未設定' }}
                    </td>
                    <td class="detail-label">型式</td>
                    <td class="detail-value {{ empty($elevator['type']) ? 'empty-field' : '' }}">
                      {{ $elevator['type'] ?? '未設定' }}
                    </td>
                    <td class="detail-label">製造年</td>
                    <td class="detail-value {{ empty($elevator['model_year']) ? 'empty-field' : '' }}">
                      {{ $elevator['model_year'] ?? '未設定' }}
                    </td>
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

      {{-- ▼ 保守点検情報（6等分グリッド固定） --}}
      <div class="equipment-section mb-4">
        <h6 class="section-title">保守点検情報</h6>
        <div class="table-responsive">
          <table class="table table-bordered facility-basic-info-table-clean elevator-grid6 elevator-inspection-sixcol" style="margin-bottom:0;">
            <colgroup><col><col><col><col><col><col></colgroup>
            <tbody>
              <tr>
                <td class="detail-label">保守業者</td>
                <td class="detail-value {{ empty($inspectionInfo['maintenance_contractor']) ? 'empty-field' : '' }}">
                  {{ $inspectionInfo['maintenance_contractor'] ?? '未設定' }}
                </td>
                <td class="detail-label">保守点検実施日</td>
                <td class="detail-value {{ empty($inspectionInfo['inspection_date']) ? 'empty-field' : '' }}">
                  {{ !empty($inspectionInfo['inspection_date']) ? \Carbon\Carbon::parse($inspectionInfo['inspection_date'])->format('Y年m月d日') : '未設定' }}
                </td>
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

    {{-- ▼ 備考（6等分グリッドで縦ラインを合わせる） --}}
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

<!-- ▼ スタイル -->
<style>
/* ===== 共通 ===== */
.elevator-equipment-sections .table-responsive { padding: 0 !important; }
.elevator-equipment-sections table {
  table-layout: fixed !important;
  width: 100% !important;
  border-collapse: separate !important;
  border-spacing: 0 !important;
}
.elevator-equipment-sections table td,
.elevator-equipment-sections table th {
  box-sizing: border-box !important;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* 見出し */
.elevator-equipment-sections .section-title {
  border: none !important;
  margin-bottom: .5rem !important;
  padding-bottom: 0 !important;
}

/* ===== 6等分グリッド共通 ===== */
.elevator-grid6 {
  table-layout: fixed !important;
  width: 100% !important;
  border-collapse: separate !important;
  border-spacing: 0 !important;
}
.elevator-grid6 col { width: 16.6667% !important; }
.elevator-grid6 td, .elevator-grid6 th { padding: .5rem !important; }
.elevator-grid6 .detail-label { white-space: nowrap; font-weight: 500; }
.elevator-grid6 .detail-value.empty-field { font-style: italic; color: #6c757d; }
.elevator-equipment-sections .elevator-grid6 {
  margin: 0 !important;
  border-left: 1px solid #e9ecef !important;
  border-right: 1px solid #e9ecef !important;
}

/* ===== 保守点検情報（6等分を厳密に） ===== */
.elevator-inspection-sixcol { table-layout: fixed !important; width: 100% !important; border-collapse: collapse !important; }
.elevator-inspection-sixcol col { width: calc(100% / 6) !important; }
.elevator-inspection-sixcol tr > * {
  width: calc(100% / 6) !important;
  max-width: calc(100% / 6) !important;
  min-width: calc(100% / 6) !important;
}
.elevator-inspection-sixcol td, .elevator-inspection-sixcol th {
  padding: .5rem !important;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* ===== 備考（元のトーン） ===== */
.elevator-notes-table .detail-label {
  background-color: #f8f9fa !important; /* 明るいグレー */
  color: #495057 !important;
  font-weight: 500;
}
.elevator-notes-table .detail-value {
  background-color: #fff !important;
  color: inherit;
}

/* ===== エレベーター設備一覧（8等分） ===== */
.elevator-eightcol-table {
  table-layout: fixed !important;
  width: 100% !important;
  border-collapse: separate !important;
  border-spacing: 0 !important;
}
.elevator-eightcol-table col {
  width: calc(100% / 8) !important;
}
.elevator-eightcol-table td, .elevator-eightcol-table th {
  width: calc(100% / 8) !important;
  max-width: calc(100% / 8) !important;
  min-width: calc(100% / 8) !important;
  padding: .5rem !important;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.elevator-eightcol-table .detail-label {
  background-color: #f8f9fa !important;
  font-weight: 500;
}
.elevator-eightcol-table .detail-value.empty-field {
  font-style: italic;
  color: #6c757d;
}
/* ラベル列の基準幅（設置の有無・備考と同じ幅にしたい場合は 16.6667% のまま） */
.elevator-equipment-sections { --label-col-w: 16.6667%; }

/* 8等分テーブル：1列目=ラベル幅、残り7列で均等割り */
.elevator-eightcol-table {
  table-layout: fixed !important;
  width: 100% !important;
  border-collapse: separate !important;
  border-spacing: 0 !important;
}
/* colgroup を優先して幅を決める */
.elevator-eightcol-table col:nth-child(1) {
  width: var(--label-col-w) !important; /* 1列目はラベル幅 */
}
.elevator-eightcol-table col:nth-child(n+2) {
  width: calc((100% - var(--label-col-w)) / 7) !important; /* 残りを7等分 */
}
.elevator-eightcol-table tr > *:nth-child(1) {
  width: var(--label-col-w) !important;
  max-width: var(--label-col-w) !important;
  min-width: var(--label-col-w) !important;
}
.elevator-eightcol-table tr > *:nth-child(n+2) {
  width: calc((100% - var(--label-col-w)) / 7) !important;
  max-width: calc((100% - var(--label-col-w)) / 7) !important;
  min-width: calc((100% - var(--label-col-w)) / 7) !important;
}

</style>
