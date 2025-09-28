@php
    $securityDisasterEquipment = $facility->getSecurityDisasterEquipment();
    // 防犯カメラ・電子錠の情報
    $cameraLockInfo = $securityDisasterEquipment?->security_systems['camera_lock'] ?? [];
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
        <!-- 将来的に他のサブタブ（火災報知器・消火設備等）を追加する場合はここに追加 -->
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
                            <h5 class="mb-0">
                                <i class="fas fa-video me-2"></i>防犯カメラ
                            </h5>
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
                            <h5 class="mb-0">
                                <i class="fas fa-key me-2"></i>電子錠
                            </h5>
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
    </div>
</div>

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