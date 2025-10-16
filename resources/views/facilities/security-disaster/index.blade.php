@php
    $securityDisasterEquipment = $facility->getSecurityDisasterEquipment();
    // 防犯カメラ・電子錠の情報
    $cameraLockInfo = $securityDisasterEquipment?->security_systems['camera_lock'] ?? [];
    // 消防・防災の情報
    $fireDisasterInfo = $securityDisasterEquipment?->fire_disaster_prevention ?? [];
@endphp

<!-- セキュリティ・災害対策サブタブ -->
<div class="security-disaster-container">
    <!-- サブタブナビゲーション -->
    <ul class="nav nav-tabs security-disaster-subtabs mb-4" id="securityDisasterTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="camera-lock-tab" data-bs-toggle="tab" data-bs-target="#camera-lock" type="button" role="tab" aria-controls="camera-lock" aria-selected="true">
                <i class="fas fa-video me-2"></i>防犯カメラ・電子錠
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="fire-disaster-tab" data-bs-toggle="tab" data-bs-target="#fire-disaster" type="button" role="tab" aria-controls="fire-disaster" aria-selected="false">
                <i class="fas fa-fire-extinguisher me-2"></i>消防・防災
            </button>
        </li>
    </ul>

    <!-- サブタブコンテンツ -->
    <div class="tab-content" id="securityDisasterTabContent">
        <!-- 防犯カメラ・電子錠タブ -->
        <div class="tab-pane fade show active" id="camera-lock" role="tabpanel" aria-labelledby="camera-lock-tab">
    
    @if($securityDisasterEquipment)
        <div class="security-disaster-equipment-sections">
            <div class="row">
                {{-- Left side: Security camera table and notes --}}
                {{-- 左側：防犯カメラテーブルと備考 --}}
                <div class="col-md-6">
                    {{-- Security camera table --}}
                    {{-- 防犯カメラテーブル --}}
                    <div class="equipment-section mb-4">
                        <div class="section-header mb-3">
                            <h5 class="mb-0">防犯カメラ</h5>
                        </div>

                        @php
                            $cameraData = [
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '管理業者',
                                            'value' => $cameraLockInfo['camera']['management_company'] ?? null,
                                            'type' => 'text',
                                            'colspan' => 5
                                        ]
                                    ]
                                ],
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '年式',
                                            'value' => $cameraLockInfo['camera']['model_year'] ?? null,
                                            'type' => 'text',
                                            'colspan' => 5
                                        ]
                                    ]
                                ],
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '配置図（PDF）',
                                            'value' => !empty($cameraLockInfo['camera']['layout_pdf_name']) ? $cameraLockInfo['camera']['layout_pdf_name'] : null,
                                            'type' => 'file_display',
                                            'colspan' => 5,
                                            'options' => [
                                                'route' => 'facilities.security-disaster.download-file',
                                                'params' => [$facility, 'camera_layout'],
                                                'display_name' => $cameraLockInfo['camera']['layout_pdf_name'] ?? null
                                            ]
                                        ]
                                    ]
                                ]
                            ];
                        @endphp
                        
                        <x-common-table 
                            :data="$cameraData"
                            :showHeader="false"
                            :tableAttributes="['class' => 'table table-bordered camera-equipment-table']"
                            bodyClass=""
                            cardClass=""
                            tableClass="table table-bordered facility-basic-info-table-clean"
                        />
                    </div>

                    {{-- Left side notes table --}}
                    {{-- 左側備考テーブル --}}
                    <div class="equipment-section mb-4">
                        @php
                            $cameraNotesData = [
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '備考',
                                            'value' => $cameraLockInfo['camera']['notes'] ?? null,
                                            'type' => 'text',
                                            'colspan' => 5
                                        ]
                                    ]
                                ]
                            ];
                        @endphp
                        
                        <x-common-table 
                            :data="$cameraNotesData"
                            :showHeader="false"
                            :tableAttributes="['class' => 'table table-bordered camera-notes-table']"
                            bodyClass=""
                            cardClass=""
                            tableClass="table table-bordered facility-basic-info-table-clean"
                        />
                    </div>
                </div>

                {{-- Right side: Electronic lock table and notes --}}
                {{-- 右側：電子錠テーブルと備考 --}}
                <div class="col-md-6">
                    {{-- Electronic lock table --}}
                    {{-- 電子錠テーブル --}}
                    <div class="equipment-section mb-4">
                        <div class="section-header mb-3">
                            <h5 class="mb-0">電子錠</h5>
                        </div>

                        @php
                            $lockData = [
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '管理業者',
                                            'value' => $cameraLockInfo['lock']['management_company'] ?? null,
                                            'type' => 'text',
                                            'colspan' => 5
                                        ]
                                    ]
                                ],
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '年式',
                                            'value' => $cameraLockInfo['lock']['model_year'] ?? null,
                                            'type' => 'text',
                                            'colspan' => 5
                                        ]
                                    ]
                                ],
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '配置図（PDF）',
                                            'value' => !empty($cameraLockInfo['lock']['layout_pdf_name']) ? $cameraLockInfo['lock']['layout_pdf_name'] : null,
                                            'type' => 'file_display',
                                            'colspan' => 5,
                                            'options' => [
                                                'route' => 'facilities.security-disaster.download-file',
                                                'params' => [$facility, 'lock_layout'],
                                                'display_name' => $cameraLockInfo['lock']['layout_pdf_name'] ?? null
                                            ]
                                        ]
                                    ]
                                ]
                            ];
                        @endphp
                        
                        <x-common-table 
                            :data="$lockData"
                            :showHeader="false"
                            :tableAttributes="['class' => 'table table-bordered lock-equipment-table']"
                            bodyClass=""
                            cardClass=""
                            tableClass="table table-bordered facility-basic-info-table-clean"
                        />
                    </div>

                    {{-- Right side notes table --}}
                    {{-- 右側備考テーブル --}}
                    <div class="equipment-section mb-4">
                        @php
                            $lockNotesData = [
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '備考',
                                            'value' => $cameraLockInfo['lock']['notes'] ?? null,
                                            'type' => 'text',
                                            'colspan' => 5
                                        ]
                                    ]
                                ]
                            ];
                        @endphp
                        
                        <x-common-table 
                            :data="$lockNotesData"
                            :showHeader="false"
                            :tableAttributes="['class' => 'table table-bordered lock-notes-table']"
                            bodyClass=""
                            cardClass=""
                            tableClass="table table-bordered facility-basic-info-table-clean"
                        />
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            防犯カメラ・電子錠情報が登録されていません。編集ボタンから情報を登録してください。
        </div>
    @endif
        </div>

        <!-- 消防・防災タブ -->
        <div class="tab-pane fade" id="fire-disaster" role="tabpanel" aria-labelledby="fire-disaster-tab">

            @if($securityDisasterEquipment && $securityDisasterEquipment->hasFireDisasterPrevention())
                <div class="fire-disaster-equipment-sections">
                    <!-- 基本情報 -->
                    <div class="equipment-section mb-4">
                        <div class="section-header mb-3">
                            <h5 class="mb-0">基本情報</h5>
                        </div>

                        @php
                            $basicInfoData = [
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => 'ハザードマップ（PDF）',
                                            'value' => !empty($fireDisasterInfo['basic_info']['hazard_map_pdf_name']) ? $fireDisasterInfo['basic_info']['hazard_map_pdf_name'] : null,
                                            'type' => 'file_display',
                                            'colspan' => 3,
                                            'options' => [
                                                'route' => 'facilities.security-disaster.download-file',
                                                'params' => [$facility, 'hazard_map'],
                                                'display_name' => $fireDisasterInfo['basic_info']['hazard_map_pdf_name'] ?? null
                                            ]
                                        ],
                                        [
                                            'label' => '避難経路（PDF）',
                                            'value' => !empty($fireDisasterInfo['basic_info']['evacuation_route_pdf_name']) ? $fireDisasterInfo['basic_info']['evacuation_route_pdf_name'] : null,
                                            'type' => 'file_display',
                                            'colspan' => 3,
                                            'options' => [
                                                'route' => 'facilities.security-disaster.download-file',
                                                'params' => [$facility, 'evacuation_route'],
                                                'display_name' => $fireDisasterInfo['basic_info']['evacuation_route_pdf_name'] ?? null
                                            ]
                                        ]
                                    ]
                                ]
                            ];
                        @endphp
                        
                        <x-common-table 
                            :data="$basicInfoData"
                            :showHeader="false"
                            :tableAttributes="['class' => 'table table-bordered fire-disaster-basic-info-table']"
                            bodyClass=""
                            cardClass=""
                            tableClass="table table-bordered facility-basic-info-table-clean"
                        />
                    </div>

                    <!-- 消防 -->
                    <div class="equipment-section mb-4">
                        <div class="section-header mb-3">
                            <h5 class="mb-0">消防</h5>
                        </div>

                        
                        {{-- 直接HTMLテーブルで6列均等幅を実現 --}}
                       <table class="table table-bordered fire-prevention-table-direct" style="table-layout: fixed !important; width: 100% !important;">
                            <tbody>
                                <tr style="display: flex !important; width: 100% !important;">
                                    <td class="detail-label" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; display: flex !important; align-items: center !important; font-weight: 600 !important; color: #495057 !important; background-color: #f8f9fa !important; box-sizing: border-box !important;">
                                        防火管理者
                                    </td>
                                    <td class="detail-value" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; border-left: none !important; display: flex !important; align-items: center !important; box-sizing: border-box !important;">
                                        {{ $fireDisasterInfo['fire_prevention']['fire_manager'] ?? '未設定' }}
                                    </td>
                                    <td class="detail-label" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; border-left: none !important; display: flex !important; align-items: center !important; font-weight: 600 !important; color: #495057 !important; background-color: #f8f9fa !important; box-sizing: border-box !important;">
                                        訓練実施日
                                    </td>
                                    <td class="detail-value" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; border-left: none !important; display: flex !important; align-items: center !important; box-sizing: border-box !important;">
                                        @if(!empty($fireDisasterInfo['fire_prevention']['training_date']))
                                            {{ \Carbon\Carbon::parse($fireDisasterInfo['fire_prevention']['training_date'])->format('Y年m月d日') }}
                                        @else
                                            未設定
                                        @endif
                                    </td>
                                    <td class="detail-label" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; border-left: none !important; display: flex !important; align-items: center !important; font-weight: 600 !important; color: #495057 !important; background-color: #f8f9fa !important; box-sizing: border-box !important;">
                                        訓練報告書（PDF）
                                    </td>
                                    <td class="detail-value" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; border-left: none !important; display: flex !important; align-items: center !important; box-sizing: border-box !important;">
                                        @if(!empty($fireDisasterInfo['fire_prevention']['training_report_pdf_name']))
                                            <a href="{{ route('facilities.security-disaster.download-file', [$facility, 'fire_training_report']) }}" 
                                            class="text-decoration-none" target="_blank">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>{{ $fireDisasterInfo['fire_prevention']['training_report_pdf_name'] }}
                                            </a>
                                        @else
                                            未設定
                                        @endif
                                    </td>
                                </tr>
                                <tr style="display: flex !important; width: 100% !important;">
                                    <td class="detail-label" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; border-top: none !important; display: flex !important; align-items: center !important; font-weight: 600 !important; color: #495057 !important; background-color: #f8f9fa !important; box-sizing: border-box !important;">
                                        消防設備点検業者
                                    </td>
                                    <td class="detail-value" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; border-left: none !important; border-top: none !important; display: flex !important; align-items: center !important; box-sizing: border-box !important;">
                                        {{ $fireDisasterInfo['fire_prevention']['inspection_company'] ?? '未設定' }}
                                    </td>
                                    <td class="detail-label" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; border-left: none !important; border-top: none !important; display: flex !important; align-items: center !important; font-weight: 600 !important; color: #495057 !important; background-color: #f8f9fa !important; box-sizing: border-box !important;">
                                        点検実施日
                                    </td>
                                    <td class="detail-value" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; border-left: none !important; border-top: none !important; display: flex !important; align-items: center !important; box-sizing: border-box !important;">
                                        @if(!empty($fireDisasterInfo['fire_prevention']['inspection_date']))
                                            {{ \Carbon\Carbon::parse($fireDisasterInfo['fire_prevention']['inspection_date'])->format('Y年m月d日') }}
                                        @else
                                            未設定
                                        @endif
                                    </td>
                                    <td class="detail-label" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; border-left: none !important; border-top: none !important; display: flex !important; align-items: center !important; font-weight: 600 !important; color: #495057 !important; background-color: #f8f9fa !important; box-sizing: border-box !important;">
                                        点検実施報告書（PDF）
                                    </td>
                                    <td class="detail-value" style="padding: 0.75rem 0.5rem !important; border: 1px solid #dee2e6 !important; border-left: none !important; border-top: none !important; display: flex !important; align-items: center !important; box-sizing: border-box !important;">
                                        @if(!empty($fireDisasterInfo['fire_prevention']['inspection_report_pdf_name']))
                                            <a href="{{ route('facilities.security-disaster.download-file', [$facility, 'fire_inspection_report']) }}" 
                                            class="text-decoration-none" target="_blank">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>{{ $fireDisasterInfo['fire_prevention']['inspection_report_pdf_name'] }}
                                            </a>
                                        @else
                                            未設定
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 防災 -->
                    <div class="equipment-section mb-4">
                        <div class="section-header mb-3">
                            <h5 class="mb-0">防災</h5>
                        </div>

                        @php
                            $disasterPreventionData = [
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '実地訓練実施日',
                                            'value' => $fireDisasterInfo['disaster_prevention']['practical_training_date'] ?? null,
                                            'type' => 'date',
                                            'colspan' => 3
                                        ],
                                        [
                                            'label' => '訓練実施報告書（PDF）',
                                            'value' => !empty($fireDisasterInfo['disaster_prevention']['practical_training_report_pdf_name']) ? $fireDisasterInfo['disaster_prevention']['practical_training_report_pdf_name'] : null,
                                            'type' => 'file_display',
                                            'colspan' => 3,
                                            'options' => [
                                                'route' => 'facilities.security-disaster.download-file',
                                                'params' => [$facility, 'practical_training_report'],
                                                'display_name' => $fireDisasterInfo['disaster_prevention']['practical_training_report_pdf_name'] ?? null
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '起動訓練実施日',
                                            'value' => $fireDisasterInfo['disaster_prevention']['riding_training_date'] ?? null,
                                            'type' => 'date',
                                            'colspan' => 3
                                        ],
                                        [
                                            'label' => '訓練実施報告書（PDF）',
                                            'value' => !empty($fireDisasterInfo['disaster_prevention']['riding_training_report_pdf_name']) ? $fireDisasterInfo['disaster_prevention']['riding_training_report_pdf_name'] : null,
                                            'type' => 'file_display',
                                            'colspan' => 3,
                                            'options' => [
                                                'route' => 'facilities.security-disaster.download-file',
                                                'params' => [$facility, 'riding_training_report'],
                                                'display_name' => $fireDisasterInfo['disaster_prevention']['riding_training_report_pdf_name'] ?? null
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '備蓄品（PDF）',
                                            'value' => !empty($fireDisasterInfo['disaster_prevention']['emergency_supplies_pdf_name']) ? $fireDisasterInfo['disaster_prevention']['emergency_supplies_pdf_name'] : null,
                                            'type' => 'file_display',
                                            'colspan' => 6,
                                            'options' => [
                                                'route' => 'facilities.security-disaster.download-file',
                                                'params' => [$facility, 'emergency_supplies'],
                                                'display_name' => $fireDisasterInfo['disaster_prevention']['emergency_supplies_pdf_name'] ?? null
                                            ]
                                        ]
                                    ]
                                ]
                            ];
                        @endphp
                        
                        <x-common-table 
                            :data="$disasterPreventionData"
                            :showHeader="false"
                            :tableAttributes="['class' => 'table table-bordered disaster-prevention-table']"
                            bodyClass=""
                            cardClass=""
                            tableClass="table table-bordered facility-basic-info-table-clean"
                        />
                    </div>

                    <!-- 備考 -->
                    <div class="equipment-section mb-4">
                        @php
                            $notesData = [
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '備考',
                                            'value' => $fireDisasterInfo['notes'] ?? null,
                                            'type' => 'text',
                                            'colspan' => 6
                                        ]
                                    ]
                                ]
                            ];
                        @endphp
                        
                        <x-common-table 
                            :data="$notesData"
                            :showHeader="false"
                            :tableAttributes="['class' => 'table table-bordered fire-disaster-notes-table']"
                            bodyClass=""
                            cardClass=""
                            tableClass="table table-bordered facility-basic-info-table-clean"
                        />
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    消防・防災情報が登録されていません。編集ボタンから情報を登録してください。
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
/* Security Disaster Subtabs */
.security-disaster-container .security-disaster-subtabs {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 1.5rem;
}

.security-disaster-container .security-disaster-subtabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    background: none;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    margin-bottom: -2px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.security-disaster-container .security-disaster-subtabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.security-disaster-container .security-disaster-subtabs .nav-link.active {
    color: #fd7e14;
    border-bottom-color: #fd7e14;
    background-color: transparent;
    font-weight: 600;
}

/* Fire Disaster Prevention specific styles */
.fire-disaster-equipment-sections .section-header h5 {
    font-weight: 600;
}

.fire-disaster-basic-info-table,
.fire-prevention-table,
.disaster-prevention-table,
.fire-disaster-notes-table {
    margin-bottom: 0;
    table-layout: fixed;
}

/* 消防・防災タブの6列均等幅設定 - 最強セレクター */
#fire-disaster .fire-disaster-basic-info-table,
#fire-disaster .fire-prevention-table,
#fire-disaster .disaster-prevention-table,
#fire-disaster .fire-disaster-notes-table,
.security-disaster-container #fire-disaster .fire-disaster-basic-info-table,
.security-disaster-container #fire-disaster .fire-prevention-table,
.security-disaster-container #fire-disaster .disaster-prevention-table,
.security-disaster-container #fire-disaster .fire-disaster-notes-table {
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    table-layout: auto !important;
}

/* 行をフレックスコンテナに変換 - 最強セレクター */
#fire-disaster .fire-disaster-basic-info-table tbody tr,
#fire-disaster .fire-prevention-table tbody tr,
#fire-disaster .disaster-prevention-table tbody tr,
#fire-disaster .fire-disaster-notes-table tbody tr,
.security-disaster-container #fire-disaster .fire-disaster-basic-info-table tbody tr,
.security-disaster-container #fire-disaster .fire-prevention-table tbody tr,
.security-disaster-container #fire-disaster .disaster-prevention-table tbody tr,
.security-disaster-container #fire-disaster .fire-disaster-notes-table tbody tr {
    display: flex !important;
    width: 100% !important;
}

/* 基本情報テーブル - 2列均等（50% + 50%） */
#fire-disaster .fire-disaster-basic-info-table tbody tr td,
.security-disaster-container #fire-disaster .fire-disaster-basic-info-table tbody tr td {
    flex: 1 1 50% !important;
    width: 50% !important;
    max-width: 50% !important;
    min-width: 50% !important;
    display: flex !important;
    align-items: center !important;
    padding: 0.5rem !important;
    border: 1px solid #dee2e6 !important;
    border-right: none !important;
    box-sizing: border-box !important;
}
#fire-disaster .fire-disaster-basic-info-table tbody tr td:last-child,
.security-disaster-container #fire-disaster .fire-disaster-basic-info-table tbody tr td:last-child {
    border-right: 1px solid #dee2e6 !important;
}

/* 消防テーブル - 3列均等（33.33% + 33.33% + 33.33%） */
#fire-disaster .fire-prevention-table tbody tr td,
.security-disaster-container #fire-disaster .fire-prevention-table tbody tr td {
    flex: 1 1 33.333333% !important;
    width: 33.333333% !important;
    max-width: 33.333333% !important;
    min-width: 33.333333% !important;
    display: flex !important;
    align-items: center !important;
    padding: 0.5rem !important;
    border: 1px solid #dee2e6 !important;
    border-right: none !important;
    box-sizing: border-box !important;
}
#fire-disaster .fire-prevention-table tbody tr td:last-child,
.security-disaster-container #fire-disaster .fire-prevention-table tbody tr td:last-child {
    border-right: 1px solid #dee2e6 !important;
}

/* 防災テーブル - 動的な列幅 */
#fire-disaster .disaster-prevention-table tbody tr td[colspan="3"],
.security-disaster-container #fire-disaster .disaster-prevention-table tbody tr td[colspan="3"] {
    flex: 1 1 50% !important;
    width: 50% !important;
    max-width: 50% !important;
    min-width: 50% !important;
    display: flex !important;
    align-items: center !important;
    padding: 0.5rem !important;
    border: 1px solid #dee2e6 !important;
    border-right: none !important;
    box-sizing: border-box !important;
}
#fire-disaster .disaster-prevention-table tbody tr td[colspan="6"],
.security-disaster-container #fire-disaster .disaster-prevention-table tbody tr td[colspan="6"] {
    flex: 1 1 100% !important;
    width: 100% !important;
    max-width: 100% !important;
    min-width: 100% !important;
    display: flex !important;
    align-items: center !important;
    padding: 0.5rem !important;
    border: 1px solid #dee2e6 !important;
    box-sizing: border-box !important;
}
#fire-disaster .disaster-prevention-table tbody tr td:last-child,
.security-disaster-container #fire-disaster .disaster-prevention-table tbody tr td:last-child {
    border-right: 1px solid #dee2e6 !important;
}

/* 備考テーブル - 1列全幅 */
#fire-disaster .fire-disaster-notes-table tbody tr td,
.security-disaster-container #fire-disaster .fire-disaster-notes-table tbody tr td {
    flex: 1 1 100% !important;
    width: 100% !important;
    max-width: 100% !important;
    min-width: 100% !important;
    display: flex !important;
    align-items: center !important;
    padding: 0.5rem !important;
    border: 1px solid #dee2e6 !important;
    box-sizing: border-box !important;
}

/* テキスト調整 */
#fire-disaster .fire-disaster-basic-info-table td .detail-label,
#fire-disaster .fire-prevention-table td .detail-label,
#fire-disaster .disaster-prevention-table td .detail-label,
#fire-disaster .fire-disaster-notes-table td .detail-label {
    font-weight: 600 !important;
    color: #495057 !important;
    margin-right: 0.5rem !important;
    white-space: nowrap !important;
}

#fire-disaster .fire-disaster-basic-info-table td .detail-value,
#fire-disaster .fire-prevention-table td .detail-value,
#fire-disaster .disaster-prevention-table td .detail-value,
#fire-disaster .fire-disaster-notes-table td .detail-value {
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    flex: 1 !important;
}
/* 消防テーブル 列幅を個別に指定 */
.fire-prevention-table-direct tr > td:nth-child(1) { 
    flex: 0 0 15% !important;
}
.fire-prevention-table-direct tr > td:nth-child(2) { 
    flex: 0 0 22% !important;
}
.fire-prevention-table-direct tr > td:nth-child(3) { 
    flex: 0 0 10% !important;
}
.fire-prevention-table-direct tr > td:nth-child(4) { 
    flex: 0 0 13% !important;
}
.fire-prevention-table-direct tr > td:nth-child(5) { 
    flex: 0 0 15% !important;
}
.fire-prevention-table-direct tr > td:nth-child(6) { 
    flex: 0 0 25% !important;
}

</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // URLハッシュまたはセッションのactiveSubTabに基づいてタブを切り替え
    const hash = window.location.hash;
    const activeSubTab = @json(session('activeSubTab'));
    
    if (hash === '#fire-disaster' || activeSubTab === 'fire-disaster') {
        const fireDisasterTab = document.getElementById('fire-disaster-tab');
        const cameraLockTab = document.getElementById('camera-lock-tab');
        
        if (fireDisasterTab && cameraLockTab) {
            // Bootstrap tab instance を作成して切り替え
            const fireDisasterTabInstance = new bootstrap.Tab(fireDisasterTab);
            fireDisasterTabInstance.show();
        }
    }
    
    // 消防・防災テーブルの幅を強制的に均等にする
    function adjustFireDisasterTableWidths() {
        // 全てのテーブルに対して強制的にスタイルを適用
        const tables = [
            { selector: '#fire-disaster .fire-disaster-basic-info-table', columns: 2 },
            { selector: '#fire-disaster .fire-prevention-table', columns: 3 },
            { selector: '#fire-disaster .disaster-prevention-table', columns: 'dynamic' },
            { selector: '#fire-disaster .fire-disaster-notes-table', columns: 1 }
        ];
        
        tables.forEach(tableConfig => {
            const table = document.querySelector(tableConfig.selector);
            if (table) {
                // テーブル自体のスタイル強制適用
                table.style.setProperty('width', '100%', 'important');
                table.style.setProperty('table-layout', 'fixed', 'important');
                table.style.setProperty('border-collapse', 'separate', 'important');
                
                // 全ての行をフレックスに変換
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach((row, rowIndex) => {
                    row.style.setProperty('display', 'flex', 'important');
                    row.style.setProperty('width', '100%', 'important');
                    
                    const cells = row.querySelectorAll('td');
                    cells.forEach((cell, cellIndex) => {
                        // セルの基本スタイル
                        cell.style.setProperty('display', 'flex', 'important');
                        cell.style.setProperty('align-items', 'center', 'important');
                        cell.style.setProperty('padding', '0.75rem 0.5rem', 'important');
                        cell.style.setProperty('border', '1px solid #dee2e6', 'important');
                        cell.style.setProperty('box-sizing', 'border-box', 'important');
                        cell.style.setProperty('vertical-align', 'middle', 'important');
                        
                        // テーブル別の幅設定
                        if (tableConfig.columns === 2) {
                            // 基本情報テーブル: 2列均等
                            cell.style.setProperty('flex', '1 1 50%', 'important');
                            cell.style.setProperty('width', '50%', 'important');
                            cell.style.setProperty('max-width', '50%', 'important');
                            cell.style.setProperty('min-width', '50%', 'important');
                        } else if (tableConfig.columns === 3) {
                            // 消防テーブル: 3列均等
                            cell.style.setProperty('flex', '1 1 33.333333%', 'important');
                            cell.style.setProperty('width', '33.333333%', 'important');
                            cell.style.setProperty('max-width', '33.333333%', 'important');
                            cell.style.setProperty('min-width', '33.333333%', 'important');
                        } else if (tableConfig.columns === 'dynamic') {
                            // 防災テーブル: colspanに応じて動的
                            const colspan = parseInt(cell.getAttribute('colspan') || '1');
                            if (colspan === 3) {
                                cell.style.setProperty('flex', '1 1 50%', 'important');
                                cell.style.setProperty('width', '50%', 'important');
                                cell.style.setProperty('max-width', '50%', 'important');
                                cell.style.setProperty('min-width', '50%', 'important');
                            } else if (colspan === 6) {
                                cell.style.setProperty('flex', '1 1 100%', 'important');
                                cell.style.setProperty('width', '100%', 'important');
                                cell.style.setProperty('max-width', '100%', 'important');
                                cell.style.setProperty('min-width', '100%', 'important');
                            }
                        } else if (tableConfig.columns === 1) {
                            // 備考テーブル: 1列全幅
                            cell.style.setProperty('flex', '1 1 100%', 'important');
                            cell.style.setProperty('width', '100%', 'important');
                            cell.style.setProperty('max-width', '100%', 'important');
                            cell.style.setProperty('min-width', '100%', 'important');
                        }
                        
                        // ボーダー調整
                        if (cellIndex === cells.length - 1) {
                            cell.style.setProperty('border-right', '1px solid #dee2e6', 'important');
                        } else {
                            cell.style.setProperty('border-right', 'none', 'important');
                        }
                    });
                });
            }
        });
    }

    
    // 初期調整
    adjustFireDisasterTableWidths();
    
    // タブ切り替え時にも調整
    document.addEventListener('shown.bs.tab', function(event) {
        if (event.target.id === 'fire-disaster-tab') {
            setTimeout(adjustFireDisasterTableWidths, 100);
        }
    });
    
    // ウィンドウリサイズ時にも調整
    window.addEventListener('resize', function() {
        adjustFireDisasterTableWidths();
    });
});
</script>
@endpush

<!-- コメントセクション -->
<div class="comment-section mt-3 d-none" 
     data-section="security_disaster" 
     id="comment-section-security_disaster"
     role="region"
     aria-label="防犯・防災のコメント">
    <hr>
    <div class="comment-form mb-3">
        <div class="input-group">
            <label for="comment-input-security_disaster" class="visually-hidden">防犯・防災にコメントを追加</label>
            <input type="text" 
                   class="form-control comment-input" 
                   id="comment-input-security_disaster"
                   placeholder="コメントを入力..." 
                   data-section="security_disaster"
                   aria-describedby="comment-help-security_disaster">
            <button class="btn btn-primary comment-submit" 
                    data-section="security_disaster"
                    aria-label="防犯・防災にコメントを投稿">
                <i class="fas fa-paper-plane" aria-hidden="true"></i>
            </button>
        </div>
        <div id="comment-help-security_disaster" class="visually-hidden">
            Enterキーまたは投稿ボタンでコメントを追加できます
        </div>
    </div>
    <div class="comment-list" 
         data-section="security_disaster"
         role="log"
         aria-label="防犯・防災のコメント一覧"
         aria-live="polite">
        <!-- コメントがここに動的に追加されます -->
    </div>
</div>

<!-- 防犯カメラ・電子錠ドキュメント管理モーダル -->
<div class="modal fade" id="camera-lock-documents-modal" tabindex="-1" aria-labelledby="camera-lock-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="camera-lock-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>防犯カメラ・電子錠ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <x-lifeline-document-manager
                    :facility="$facility"
                    category="security_disaster"
                    categoryName="防犯カメラ・電子錠"
                    subcategory="camera_lock"
                />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- 消防・防災ドキュメント管理モーダル -->
<div class="modal fade" id="fire-disaster-documents-modal" tabindex="-1" aria-labelledby="fire-disaster-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="fire-disaster-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>消防・防災ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <x-lifeline-document-manager
                    :facility="$facility"
                    category="security_disaster"
                    categoryName="消防・防災"
                    subcategory="fire_disaster"
                />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

