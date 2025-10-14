@php
    use Illuminate\Support\Facades\Storage;
    
    $elevatorEquipment = $facility->getElevatorEquipment();
    $basicInfo = $elevatorEquipment?->basic_info ?? [];
    $elevators = $basicInfo['elevators'] ?? [];
    $inspectionInfo = $basicInfo['inspection'] ?? [];
    $canEdit = auth()->user()->canEditFacility($facility->id);
    
    // 基本情報テーブルデータの準備
    $availability = $basicInfo['availability'] ?? null;
    $basicInfoData = [
        [
            'cells' => [
                [
                    'label' => '設置の有無',
                    'value' => $availability,
                    'type' => 'text',
                    'width' => '100%'
                ]
            ]
        ]
    ];
@endphp

<!-- エレベーター設備ヘッダー（ドキュメントアイコン付き） -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="fas fa-elevator text-secondary me-2"></i>エレベーター設備情報
    </h5>
    <div class="d-flex align-items-center gap-2">
        <!-- ドキュメント管理ボタン -->
        <button type="button" 
                class="btn btn-outline-primary btn-sm" 
                id="elevator-documents-toggle"
                title="エレベーター設備ドキュメント管理">
            <i class="fas fa-folder-open me-1"></i>
            <span class="d-none d-md-inline">ドキュメント</span>
        </button>
    </div>
</div>

{{-- エレベーター設備表示カード --}}
<div class="elevator-equipment-sections">
    @if($elevatorEquipment)
        {{-- 基本情報セクション --}}
        <div class="equipment-section mb-4">
            <x-common-table 
                :data="$basicInfoData"
                :showHeader="false"
                :tableAttributes="['class' => 'table table-bordered elevator-basic-info-table']"
                bodyClass=""
                cardClass=""
                tableClass="table table-bordered facility-basic-info-table-clean"
            />
        </div>

        {{-- エレベーター設備一覧セクション（設置の有無が「有」の場合のみ表示） --}}
        @if($availability === '有')
            <div class="equipment-section mb-4">
                <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">エレベーター設備一覧</h6>
                <div class="table-responsive" style="overflow: visible;">
                    <table class="table table-bordered facility-basic-info-table-clean" style="--bs-table-cell-padding-x: 0; --bs-table-cell-padding-y: 0; margin-bottom: 0; table-layout: fixed;">
                        <colgroup>
                            <col style="width: 12.5%;">
                            <col style="width: 12.5%;">
                            <col style="width: 12.5%;">
                            <col style="width: 12.5%;">
                            <col style="width: 12.5%;">
                            <col style="width: 12.5%;">
                            <col style="width: 12.5%;">
                            <col style="width: 12.5%;">
                        </colgroup>
                        <tbody>
                            @if(!empty($elevators) && is_array($elevators))
                                @foreach($elevators as $index => $elevator)
                                    <tr>
                                        <td class="detail-label" style="padding: 0.5rem;">
                                            メーカー{{ $index + 1 }}
                                        </td>
                                        <td class="detail-value {{ empty($elevator['manufacturer']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $elevator['manufacturer'] ?? '未設定' }}
                                        </td>
                                        <td class="detail-label" style="padding: 0.5rem;">
                                            型式{{ $index + 1 }}
                                        </td>
                                        <td class="detail-value {{ empty($elevator['type']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $elevator['type'] ?? '未設定' }}
                                        </td>
                                        <td class="detail-label" style="padding: 0.5rem;">
                                            製造年{{ $index + 1 }}
                                        </td>
                                        <td class="detail-value {{ empty($elevator['model_year']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ $elevator['model_year'] ?? '未設定' }}
                                        </td>
                                        <td class="detail-label" style="padding: 0.5rem;">
                                            更新年月日{{ $index + 1 }}
                                        </td>
                                        <td class="detail-value {{ empty($elevator['update_date']) ? 'empty-field' : '' }}" style="padding: 0.5rem;">
                                            {{ !empty($elevator['update_date']) ? \Carbon\Carbon::parse($elevator['update_date'])->format('Y年m月d日') : '未設定' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="8" class="text-center text-muted" style="padding: 1rem;">
                                        エレベーター設備情報が登録されていません
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 保守点検情報セクション --}}
            <div class="equipment-section mb-4">
                <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #333;">保守点検情報</h6>
                @php
                    $inspectionData = [
                        [
                            'type' => 'standard',
                            'cells' => [
                                [
                                    'label' => '保守業者',
                                    'value' => $inspectionInfo['maintenance_contractor'] ?? null,
                                    'type' => 'text',
                                    'width' => '33.33%'
                                ],
                                [
                                    'label' => '保守点検実施日',
                                    'value' => !empty($inspectionInfo['inspection_date']) ? \Carbon\Carbon::parse($inspectionInfo['inspection_date'])->format('Y年m月d日') : null,
                                    'type' => 'text',
                                    'width' => '33.33%'
                                ],
                                [
                                    'label' => '保守点検報告書',
                                    'value' => !empty($inspectionInfo['inspection_report_filename']) ? $inspectionInfo['inspection_report_filename'] : null,
                                    'type' => 'file_display',
                                    'width' => '33.33%',
                                    'options' => [
                                        'route' => 'facilities.lifeline-equipment.download-file',
                                        'params' => [$facility, 'elevator', 'inspection_report'],
                                        'display_name' => $inspectionInfo['inspection_report_filename'] ?? null
                                    ]
                                ]
                            ]
                        ]
                    ];
                @endphp
                
                <x-common-table 
                    :data="$inspectionData"
                    :showHeader="false"
                    :tableAttributes="['class' => 'table table-bordered elevator-inspection-table']"
                    bodyClass=""
                    cardClass=""
                    tableClass="table table-bordered facility-basic-info-table-clean"
                />
            </div>
        @endif

        {{-- 備考セクション --}}
        <div class="equipment-section mb-4">
            @php
                $notesData = [
                    [
                        'type' => 'standard',
                        'cells' => [
                            [
                                'label' => '備考',
                                'value' => $elevatorEquipment->notes ?? null,
                                'type' => 'text',
                                'width' => '100%'
                            ]
                        ]
                    ]
                ];
            @endphp
            
            <x-common-table 
                :data="$notesData"
                :showHeader="false"
                :tableAttributes="['class' => 'table table-bordered elevator-notes-table']"
                bodyClass=""
                cardClass=""
                tableClass="table table-bordered facility-basic-info-table-clean"
            />
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            詳細仕様は開発中です。基本的なカード構造が準備されています。
        </div>
    @endif
</div>



<!-- エレベーター設備ドキュメント管理用JavaScript -->
<script>
// モーダル形式に変更したため、折りたたみ関連のJavaScriptは不要
// ドキュメントマネージャーはapp-unified.jsで自動初期化される
</script>

<!-- エレベーター設備ドキュメント管理用CSS -->
<style>
/* モーダル形式に変更したため、折りたたみ関連のCSSは不要 */
/* モーダルスタイルはapp-unified.cssで統一管理 */
</style>
<!-- エレベー
ター設備ドキュメント管理モーダル -->
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
                <x-lifeline-document-manager
                    :facility="$facility"
                    category="elevator"
                    categoryName="エレベーター設備"
                />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>
