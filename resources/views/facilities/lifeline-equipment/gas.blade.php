@php
    $gasLifeline = $facility->getLifelineEquipmentByCategory('gas');
    $gasEquipment = $gasLifeline?->gasEquipment;
    $canEdit = auth()->user()->canEditFacility($facility->id);
@endphp

<div class="row">
    {{-- Basic Information Card --}}
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="gas_basic">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="gas_basic" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="ガス基本情報のコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-gas_basic">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="gas_basic" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($gasEquipment?->basic_info['gas_supplier']) ? 'empty-field' : '' }}">
                        <span class="detail-label">ガス供給会社</span>
                        <span class="detail-value">{{ $gasEquipment?->basic_info['gas_supplier'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($gasEquipment?->basic_info['safety_management_company']) ? 'empty-field' : '' }}">
                        <span class="detail-label">保安管理業者</span>
                        <span class="detail-value">{{ $gasEquipment?->basic_info['safety_management_company'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($gasEquipment?->basic_info['maintenance_inspection_date']) ? 'empty-field' : '' }}">
                        <span class="detail-label">ガス保守点検実施日</span>
                        <span class="detail-value">
                            @if(!empty($gasEquipment?->basic_info['maintenance_inspection_date']))
                                {{ \Carbon\Carbon::parse($gasEquipment->basic_info['maintenance_inspection_date'])->format('Y年m月d日') }}
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($gasEquipment?->basic_info['inspection_report_pdf']) ? 'empty-field' : '' }}">
                        <span class="detail-label">点検実施報告書</span>
                        <span class="detail-value">
                            @if(!empty($gasEquipment?->basic_info['inspection_report_pdf']))
                                <a href="#" class="text-decoration-none" aria-label="点検実施報告書PDFを開く">
                                    <i class="fas fa-file-pdf me-1 text-danger" aria-hidden="true"></i>{{ $gasEquipment->basic_info['inspection_report_pdf'] }}
                                </a>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="gas_basic" 
                     id="comment-section-gas_basic"
                     role="region"
                     aria-label="ガス基本情報のコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-gas_basic" class="sr-only">ガス基本情報にコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-gas_basic"
                                   placeholder="コメントを入力..." 
                                   data-section="gas_basic"
                                   aria-describedby="comment-help-gas_basic">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="gas_basic"
                                    aria-label="ガス基本情報にコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="comment-help-gas_basic" class="sr-only">
                            Enterキーまたは投稿ボタンでコメントを追加できます
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="gas_basic"
                         role="log"
                         aria-label="ガス基本情報のコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Notes Card --}}
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="gas_notes">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-sticky-note text-warning me-2"></i>備考
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="gas_notes" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="ガス備考のコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-gas_notes">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="gas_notes" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($gasEquipment?->notes) ? 'empty-field' : '' }}">
                        <span class="detail-label">備考</span>
                        <span class="detail-value">
                            @if(!empty($gasEquipment?->notes))
                                <div class="border rounded p-2 bg-light">{!! nl2br(e($gasEquipment->notes)) !!}</div>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="gas_notes" 
                     id="comment-section-gas_notes"
                     role="region"
                     aria-label="ガス備考のコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-gas_notes" class="sr-only">ガス備考にコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-gas_notes"
                                   placeholder="コメントを入力..." 
                                   data-section="gas_notes"
                                   aria-describedby="comment-help-gas_notes">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="gas_notes"
                                    aria-label="ガス備考にコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="comment-help-gas_notes" class="sr-only">
                            Enterキーまたは投稿ボタンでコメントを追加できます
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="gas_notes"
                         role="log"
                         aria-label="ガス備考のコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Equipment Details Card --}}
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="gas_equipment">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-cog text-info me-2"></i>設備詳細
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="gas_equipment" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="gas_equipment">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row empty-field">
                        <span class="detail-label">設備仕様</span>
                        <span class="detail-value">
                            <div class="text-muted text-center py-4">
                                <i class="fas fa-tools fa-2x mb-3"></i>
                                <p>詳細仕様は開発中です</p>
                            </div>
                        </span>
                    </div>
                </div>
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" data-section="gas_equipment">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="gas_equipment">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="gas_equipment">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="gas_equipment">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Maintenance History Card --}}
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="gas_maintenance">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line text-success me-2"></i>メンテナンス履歴
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="gas_maintenance" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示">
                    <i class="fas fa-comment"></i>
                    <span class="comment-count" data-section="gas_maintenance">0</span>
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
                <div class="comment-section mt-3 d-none" data-section="gas_maintenance">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control comment-input" 
                                   placeholder="コメントを入力..." 
                                   data-section="gas_maintenance">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="gas_maintenance">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" data-section="gas_maintenance">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>