<!-- ライフライン設備 -->
<div class="mb-4" data-section="lifeline_equipment">
    <!-- ヘッダー部分 -->

    <!-- ライフライン設備サブタブナビゲーション -->
    <div class="lifeline-equipment-container">
        <ul class="nav nav-tabs" id="lifelineSubTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="electrical-tab" data-bs-toggle="tab" data-bs-target="#electrical" type="button" role="tab" aria-controls="electrical" aria-selected="true">
                    <i class="fas fa-bolt me-2"></i>電気
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="water-tab" data-bs-toggle="tab" data-bs-target="#water" type="button" role="tab" aria-controls="water" aria-selected="false">
                    <i class="fas fa-tint me-2"></i>水道
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="gas-tab" data-bs-toggle="tab" data-bs-target="#gas" type="button" role="tab" aria-controls="gas" aria-selected="false">
                    <i class="fas fa-fire me-2"></i>ガス
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="elevator-tab" data-bs-toggle="tab" data-bs-target="#elevator" type="button" role="tab" aria-controls="elevator" aria-selected="false">
                    <i class="fas fa-elevator me-2"></i>エレベーター
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="hvac-lighting-tab" data-bs-toggle="tab" data-bs-target="#hvac-lighting" type="button" role="tab" aria-controls="hvac-lighting" aria-selected="false">
                    <i class="fas fa-fan me-2"></i>空調・照明
                </button>
            </li>

        </ul>
        
        <!-- サブタブコンテンツ -->
        <div class="tab-content mt-4" id="lifelineSubTabContent">
            <div class="tab-pane fade show active" id="electrical" role="tabpanel" aria-labelledby="electrical-tab">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="flex-grow-1"></div>
                    @if(auth()->user()->canEditFacility($facility->id))
                        <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'electrical']) }}" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-2"></i>編集
                        </a>
                    @endif
                </div>
                @include('facilities.lifeline-equipment.electrical', ['facility' => $facility])
            </div>
            
            <div class="tab-pane fade" id="water" role="tabpanel" aria-labelledby="water-tab">
                {{-- <div class="card facility-info-card detail-card-improved">
                    <div class="card-body"> --}}
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1"></div>
                            @if(auth()->user()->canEditFacility($facility->id))
                                <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'water']) }}" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit me-2"></i>編集
                                </a>
                            @endif
                        </div>
                        @include('facilities.lifeline-equipment.water', ['facility' => $facility])
                    {{-- </div>
                </div> --}}
            </div>
            
            <!-- 他のタブはカード内に残す -->
            <div class="tab-pane fade" id="gas" role="tabpanel" aria-labelledby="gas-tab">
                {{-- <div class="card facility-info-card detail-card-improved">
                    <div class="card-body"> --}}
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1"></div>
                            @if(auth()->user()->canEditFacility($facility->id))
                                <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'gas']) }}" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit me-2"></i>編集
                                </a>
                            @endif
                        </div>
                        @include('facilities.lifeline-equipment.gas', ['facility' => $facility])
                    {{-- </div>
                </div> --}}
            </div>
            
            <div class="tab-pane fade" id="elevator" role="tabpanel" aria-labelledby="elevator-tab">
                {{-- <div class="card facility-info-card detail-card-improved">
                    <div class="card-body"> --}}
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1"></div>
                            @if(auth()->user()->canEditFacility($facility->id))
                                <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'elevator']) }}" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit me-2"></i>編集
                                </a>
                            @endif
                        </div>
                        @include('facilities.lifeline-equipment.elevator', [
                            'facility' => $facility,
                            'elevatorEquipment' => $facility->getElevatorEquipment()
                        ])
                    {{-- </div>
                </div> --}}
            </div>
            
            <div class="tab-pane fade" id="hvac-lighting" role="tabpanel" aria-labelledby="hvac-lighting-tab">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="flex-grow-1"></div>
                    @if(auth()->user()->canEditFacility($facility->id))
                        <a href="{{ route('facilities.lifeline-equipment.edit', [$facility, 'hvac-lighting']) }}" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-2"></i>編集
                        </a>
                    @endif
                </div>
                @include('facilities.lifeline-equipment.hvac-lighting', [
                    'facility' => $facility,
                    'hvacLightingEquipment' => $facility->getHvacLightingEquipment()
                ])
            </div>
        </div>
    </div>
    
    <!-- コメントセクション -->
    <div class="comment-section mt-3 d-none" 
         data-section="lifeline_equipment" 
         id="comment-section-lifeline_equipment"
         role="region"
         aria-label="ライフライン設備のコメント">
        <hr>
        <div class="comment-form mb-3">
            <div class="input-group">
                <label for="comment-input-lifeline_equipment" class="sr-only">ライフライン設備にコメントを追加</label>
                <input type="text" 
                       class="form-control comment-input" 
                       id="comment-input-lifeline_equipment"
                       placeholder="コメントを入力..." 
                       data-section="lifeline_equipment"
                       aria-describedby="comment-help-lifeline_equipment">
                <button class="btn btn-primary comment-submit" 
                        data-section="lifeline_equipment"
                        aria-label="ライフライン設備にコメントを投稿">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                </button>
            </div>
            <div id="comment-help-lifeline_equipment" class="sr-only">
                Enterキーまたは投稿ボタンでコメントを追加できます
            </div>
        </div>
        <div class="comment-list" 
             data-section="lifeline_equipment"
             role="log"
             aria-label="ライフライン設備のコメント一覧"
             aria-live="polite">
            <!-- コメントがここに動的に追加されます -->
        </div>
    </div>
</div>