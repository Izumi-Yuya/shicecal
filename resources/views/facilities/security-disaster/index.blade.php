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
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1"></div>
                @if(auth()->user()->canEditFacility($facility->id))
                    <a href="{{ route('facilities.security-disaster.edit', ['facility' => $facility->id]) }}" 
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-2"></i>編集
                    </a>
                @endif
            </div>
    
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
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1"></div>
                @if(auth()->user()->canEditFacility($facility->id))
                    <a href="{{ route('facilities.security-disaster.edit', ['facility' => $facility->id]) }}#fire-disaster-edit" 
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-2"></i>編集
                    </a>
                @endif
            </div>

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
                                            'colspan' => 2,
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

                        @php
                            $firePreventionData = [
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '防火管理者',
                                            'value' => $fireDisasterInfo['fire_prevention']['fire_manager'] ?? null,
                                            'type' => 'text',
                                            'colspan' => 2
                                        ],
                                        [
                                            'label' => '訓練実施日',
                                            'value' => $fireDisasterInfo['fire_prevention']['training_date'] ?? null,
                                            'type' => 'date',
                                            'colspan' => 1
                                        ],
                                        [
                                            'label' => '訓練報告書（PDF）',
                                            'value' => !empty($fireDisasterInfo['fire_prevention']['training_report_pdf_name']) ? $fireDisasterInfo['fire_prevention']['training_report_pdf_name'] : null,
                                            'type' => 'file_display',
                                            'colspan' => 2,
                                            'options' => [
                                                'route' => 'facilities.security-disaster.download-file',
                                                'params' => [$facility, 'fire_training_report'],
                                                'display_name' => $fireDisasterInfo['fire_prevention']['training_report_pdf_name'] ?? null
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'type' => 'standard',
                                    'cells' => [
                                        [
                                            'label' => '消防設備点検業者',
                                            'value' => $fireDisasterInfo['fire_prevention']['inspection_company'] ?? null,
                                            'type' => 'text',
                                            'colspan' => 2
                                        ],
                                        [
                                            'label' => '点検実施日',
                                            'value' => $fireDisasterInfo['fire_prevention']['inspection_date'] ?? null,
                                            'type' => 'date',
                                            'colspan' => 1
                                        ],
                                        [
                                            'label' => '点検実施報告書（PDF）',
                                            'value' => !empty($fireDisasterInfo['fire_prevention']['inspection_report_pdf_name']) ? $fireDisasterInfo['fire_prevention']['inspection_report_pdf_name'] : null,
                                            'type' => 'file_display',
                                            'colspan' => 2,
                                            'options' => [
                                                'route' => 'facilities.security-disaster.download-file',
                                                'params' => [$facility, 'fire_inspection_report'],
                                                'display_name' => $fireDisasterInfo['fire_prevention']['inspection_report_pdf_name'] ?? null
                                            ]
                                        ]
                                    ]
                                ]
                            ];
                        @endphp
                        
                        <x-common-table 
                            :data="$firePreventionData"
                            :showHeader="false"
                            :tableAttributes="['class' => 'table table-bordered fire-prevention-table']"
                            bodyClass=""
                            cardClass=""
                            tableClass="table table-bordered facility-basic-info-table-clean"
                        />
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
                                            'colspan' => 2
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
                                            'colspan' => 2
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
                                            'colspan' => 5,
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
                                            'colspan' => 5
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