<div class="row">
    <!-- 基本情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="hvac_basic">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="hvac_basic" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="空調・照明基本情報のコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-hvac_basic">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="hvac_basic" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($hvacLightingEquipment->basic_info['hvac_contractor']) ? 'empty-field' : '' }}">
                        <span class="detail-label">空調業者</span>
                        <span class="detail-value">{{ $hvacLightingEquipment->basic_info['hvac_contractor'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($hvacLightingEquipment->basic_info['maintenance_company']) ? 'empty-field' : '' }}">
                        <span class="detail-label">保守管理業者</span>
                        <span class="detail-value">{{ $hvacLightingEquipment->basic_info['maintenance_company'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($hvacLightingEquipment->basic_info['last_inspection_date']) ? 'empty-field' : '' }}">
                        <span class="detail-label">前回点検日</span>
                        <span class="detail-value">
                            @if(!empty($hvacLightingEquipment->basic_info['last_inspection_date']))
                                {{ \Carbon\Carbon::parse($hvacLightingEquipment->basic_info['last_inspection_date'])->format('Y年m月d日') }}
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($hvacLightingEquipment->basic_info['next_inspection_date']) ? 'empty-field' : '' }}">
                        <span class="detail-label">次回点検予定日</span>
                        <span class="detail-value">
                            @if(!empty($hvacLightingEquipment->basic_info['next_inspection_date']))
                                {{ \Carbon\Carbon::parse($hvacLightingEquipment->basic_info['next_inspection_date'])->format('Y年m月d日') }}
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($hvacLightingEquipment->basic_info['system_type']) ? 'empty-field' : '' }}">
                        <span class="detail-label">空調システム種別</span>
                        <span class="detail-value">{{ $hvacLightingEquipment->basic_info['system_type'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($hvacLightingEquipment->basic_info['lighting_type']) ? 'empty-field' : '' }}">
                        <span class="detail-label">照明種別</span>
                        <span class="detail-value">{{ $hvacLightingEquipment->basic_info['lighting_type'] ?? '未設定' }}</span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="hvac_basic" 
                     id="comment-section-hvac_basic"
                     role="region"
                     aria-label="空調・照明基本情報のコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-hvac_basic" class="sr-only">空調・照明基本情報にコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-hvac_basic"
                                   placeholder="コメントを入力..." 
                                   data-section="hvac_basic"
                                   aria-describedby="comment-help-hvac_basic">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="hvac_basic"
                                    aria-label="空調・照明基本情報にコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="comment-help-hvac_basic" class="sr-only">
                            Enterキーまたは投稿ボタンでコメントを追加できます
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="hvac_basic"
                         role="log"
                         aria-label="空調・照明基本情報のコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 備考カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="hvac_notes">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-sticky-note text-warning me-2"></i>備考
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="hvac_notes" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="hvac_notes">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($hvacLightingEquipment->notes) ? 'empty-field' : '' }}">
                        <span class="detail-label">備考</span>
                        <span class="detail-value">
                            @if(!empty($hvacLightingEquipment->notes))
                                <div class="border rounded p-2 bg-light">{!! nl2br(e($hvacLightingEquipment->notes)) !!}</div>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="hvac_notes">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="hvac_notes">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="hvac_notes">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="hvac_notes">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 設備詳細カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="hvac_equipment">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-cog text-info me-2"></i>設備詳細
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="hvac_equipment" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="hvac_equipment">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row empty-field">
                        <span class="detail-label">設備仕様</span>
                        <span class="detail-value">
                            <div class="text-muted text-center py-4">
                                <i class="fas fa-tools fa-2x mb-3"></i>
                                <p>空調・照明設備の詳細仕様は開発中です</p>
                            </div>
                        </span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="hvac_equipment">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="hvac_equipment">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="hvac_equipment">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="hvac_equipment">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- メンテナンス履歴カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="hvac_maintenance">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line text-success me-2"></i>メンテナンス履歴
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="hvac_maintenance" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="hvac_maintenance">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row empty-field">
                        <span class="detail-label">履歴</span>
                        <span class="detail-value">
                            <div class="text-muted text-center py-4">
                                <i class="fas fa-history fa-2x mb-3"></i>
                                <p>詳細仕様は開発中です</p>
                            </div>
                        </span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="hvac_maintenance">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="hvac_maintenance">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="hvac_maintenance">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="hvac_maintenance">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>