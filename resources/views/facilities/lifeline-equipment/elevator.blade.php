<div class="row">
    <!-- 基本情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="elevator_basic">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="elevator_basic" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="エレベーター基本情報のコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-elevator_basic">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="elevator_basic" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($elevatorEquipment->basic_info['manufacturer']) ? 'empty-field' : '' }}">
                        <span class="detail-label">メーカー</span>
                        <span class="detail-value">{{ $elevatorEquipment->basic_info['manufacturer'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($elevatorEquipment->basic_info['model']) ? 'empty-field' : '' }}">
                        <span class="detail-label">型式</span>
                        <span class="detail-value">{{ $elevatorEquipment->basic_info['model'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($elevatorEquipment->basic_info['capacity']) ? 'empty-field' : '' }}">
                        <span class="detail-label">定員</span>
                        <span class="detail-value">
                            @if(!empty($elevatorEquipment->basic_info['capacity']))
                                {{ $elevatorEquipment->basic_info['capacity'] }}人
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($elevatorEquipment->basic_info['installation_year']) ? 'empty-field' : '' }}">
                        <span class="detail-label">設置年</span>
                        <span class="detail-value">
                            @if(!empty($elevatorEquipment->basic_info['installation_year']))
                                {{ $elevatorEquipment->basic_info['installation_year'] }}年
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="elevator_basic" 
                     id="comment-section-elevator_basic"
                     role="region"
                     aria-label="エレベーター基本情報のコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-elevator_basic" class="sr-only">エレベーター基本情報にコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-elevator_basic"
                                   placeholder="コメントを入力..." 
                                   data-section="elevator_basic"
                                   aria-describedby="comment-help-elevator_basic">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="elevator_basic"
                                    aria-label="エレベーター基本情報にコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="comment-help-elevator_basic" class="sr-only">
                            Enterキーまたは投稿ボタンでコメントを追加できます
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="elevator_basic"
                         role="log"
                         aria-label="エレベーター基本情報のコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 保守情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="elevator_maintenance">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-tools text-info me-2"></i>保守情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="elevator_maintenance" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="elevator_maintenance">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($elevatorEquipment->maintenance_info['company']) ? 'empty-field' : '' }}">
                        <span class="detail-label">保守会社</span>
                        <span class="detail-value">{{ $elevatorEquipment->maintenance_info['company'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($elevatorEquipment->maintenance_info['contract_type']) ? 'empty-field' : '' }}">
                        <span class="detail-label">保守契約種別</span>
                        <span class="detail-value">{{ $elevatorEquipment->maintenance_info['contract_type'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($elevatorEquipment->maintenance_info['last_inspection_date']) ? 'empty-field' : '' }}">
                        <span class="detail-label">最終点検日</span>
                        <span class="detail-value">{{ $elevatorEquipment->maintenance_info['last_inspection_date'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($elevatorEquipment->maintenance_info['next_inspection_date']) ? 'empty-field' : '' }}">
                        <span class="detail-label">次回点検予定日</span>
                        <span class="detail-value">{{ $elevatorEquipment->maintenance_info['next_inspection_date'] ?? '未設定' }}</span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="elevator_maintenance">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="elevator_maintenance">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="elevator_maintenance">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="elevator_maintenance">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 安全情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="elevator_safety">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-shield-alt text-success me-2"></i>安全情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="elevator_safety" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="elevator_safety">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($elevatorEquipment->safety_info['inspection_agency']) ? 'empty-field' : '' }}">
                        <span class="detail-label">検査機関</span>
                        <span class="detail-value">{{ $elevatorEquipment->safety_info['inspection_agency'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($elevatorEquipment->safety_info['certificate_number']) ? 'empty-field' : '' }}">
                        <span class="detail-label">検査証番号</span>
                        <span class="detail-value">{{ $elevatorEquipment->safety_info['certificate_number'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($elevatorEquipment->safety_info['certificate_expiry']) ? 'empty-field' : '' }}">
                        <span class="detail-label">検査有効期限</span>
                        <span class="detail-value">{{ $elevatorEquipment->safety_info['certificate_expiry'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($elevatorEquipment->safety_info['safety_devices']) ? 'empty-field' : '' }}">
                        <span class="detail-label">安全装置</span>
                        <span class="detail-value">{{ $elevatorEquipment->safety_info['safety_devices'] ?? '未設定' }}</span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="elevator_safety">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="elevator_safety">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="elevator_safety">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="elevator_safety">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 備考カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="elevator_notes">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-sticky-note text-warning me-2"></i>備考
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="elevator_notes" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="elevator_notes">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($elevatorEquipment->notes) ? 'empty-field' : '' }}">
                        <span class="detail-label">備考</span>
                        <span class="detail-value">
                            @if(!empty($elevatorEquipment->notes))
                                <div class="border rounded p-2 bg-light">{!! nl2br(e($elevatorEquipment->notes)) !!}</div>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="elevator_notes">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="elevator_notes">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="elevator_notes">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="elevator_notes">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>