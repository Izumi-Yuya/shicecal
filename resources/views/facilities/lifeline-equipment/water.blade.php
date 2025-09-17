@php
    $waterLifeline = $facility->getLifelineEquipmentByCategory('water');
    $waterEquipment = $waterLifeline?->waterEquipment;
    $basicInfo = $waterEquipment->basic_info ?? [];
    $canEdit = auth()->user()->canEditFacility($facility->id);
@endphp

<div class="row">
    <!-- 基本情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="water_basic">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="water_basic" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="水道基本情報のコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-water_basic">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="water_basic" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($basicInfo['water_contractor']) ? 'empty-field' : '' }}">
                        <span class="detail-label">水道契約会社</span>
                        <span class="detail-value">{{ $basicInfo['water_contractor'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($basicInfo['maintenance_company']) ? 'empty-field' : '' }}">
                        <span class="detail-label">水道保守点検業者</span>
                        <span class="detail-value">{{ $basicInfo['maintenance_company'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($basicInfo['maintenance_date']) ? 'empty-field' : '' }}">
                        <span class="detail-label">水道保守点検実施日</span>
                        <span class="detail-value">
                            @if(!empty($basicInfo['maintenance_date']))
                                {{ \Carbon\Carbon::parse($basicInfo['maintenance_date'])->format('Y年m月d日') }}
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($basicInfo['inspection_report']) ? 'empty-field' : '' }}">
                        <span class="detail-label">点検実施報告書</span>
                        <span class="detail-value">
                            @if(!empty($basicInfo['inspection_report']))
                                <a href="#" class="text-decoration-none" aria-label="点検実施報告書PDFを開く">
                                    <i class="fas fa-file-pdf me-1 text-danger" aria-hidden="true"></i>{{ $basicInfo['inspection_report'] }}
                                </a>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="water_basic" 
                     id="comment-section-water_basic"
                     role="region"
                     aria-label="水道基本情報のコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-water_basic" class="sr-only">水道基本情報にコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-water_basic"
                                   placeholder="コメントを入力..." 
                                   data-section="water_basic"
                                   aria-describedby="comment-help-water_basic">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="water_basic"
                                    aria-label="水道基本情報にコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="comment-help-water_basic" class="sr-only">
                            Enterキーまたは投稿ボタンでコメントを追加できます
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="water_basic"
                         role="log"
                         aria-label="水道基本情報のコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 備考カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="water_notes">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-sticky-note text-warning me-2"></i>備考
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="water_notes" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="水道備考のコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-water_notes">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="water_notes" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($waterEquipment->notes) ? 'empty-field' : '' }}">
                        <span class="detail-label">備考</span>
                        <span class="detail-value">
                            @if(!empty($waterEquipment->notes))
                                <div class="border rounded p-2 bg-light">{!! nl2br(e($waterEquipment->notes)) !!}</div>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="water_notes" 
                     id="comment-section-water_notes"
                     role="region"
                     aria-label="水道備考のコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-water_notes" class="sr-only">水道備考にコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-water_notes"
                                   placeholder="コメントを入力..." 
                                   data-section="water_notes"
                                   aria-describedby="comment-help-water_notes">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="water_notes"
                                    aria-label="水道備考にコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="comment-help-water_notes" class="sr-only">
                            Enterキーまたは投稿ボタンでコメントを追加できます
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="water_notes"
                         role="log"
                         aria-label="水道備考のコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 設備詳細カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="water_equipment">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-cog text-info me-2"></i>設備詳細
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="water_equipment" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="water_equipment">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row empty-field">
                        <span class="detail-label">設備仕様</span>
                        <span class="detail-value">
                            <div class="text-muted text-center py-4">
                                <i class="fas fa-tools fa-2x mb-3"></i>
                                <p>給水設備、排水設備、浄化設備などの詳細情報管理機能を準備中です</p>
                            </div>
                        </span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="water_equipment">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="water_equipment">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="water_equipment">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="water_equipment">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- メンテナンス履歴カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="water_maintenance">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line text-success me-2"></i>メンテナンス履歴
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="water_maintenance" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="water_maintenance">0</span>
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
                <div class="comment-section mt-3 d-none" data-section="water_maintenance">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="water_maintenance">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="water_maintenance">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="water_maintenance">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>