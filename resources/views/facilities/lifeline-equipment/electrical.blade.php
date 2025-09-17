<!-- 電気設備カード群 -->
<div class="row electrical-equipment-cards">
    <!-- 基本情報カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="electrical_basic">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="electrical_basic" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="基本情報のコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-electrical_basic">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="electrical_basic" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                @php
                    $electricalLifeline = $facility->getLifelineEquipmentByCategory('electrical');
                    $electricalEquipment = $electricalLifeline?->electricalEquipment;
                    $basicInfo = $electricalEquipment->basic_info ?? [];
                    $canEdit = auth()->user()->canEditFacility($facility->id);
                @endphp
                
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($basicInfo['electrical_contractor']) ? 'empty-field' : '' }}">
                        <span class="detail-label">電気契約会社</span>
                        <span class="detail-value">{{ $basicInfo['electrical_contractor'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($basicInfo['safety_management_company']) ? 'empty-field' : '' }}">
                        <span class="detail-label">保安管理業者</span>
                        <span class="detail-value">{{ $basicInfo['safety_management_company'] ?? '未設定' }}</span>
                    </div>
                    <div class="detail-row {{ empty($basicInfo['maintenance_inspection_date']) ? 'empty-field' : '' }}">
                        <span class="detail-label">電気保守点検実施日</span>
                        <span class="detail-value">
                            @if(!empty($basicInfo['maintenance_inspection_date']))
                                {{ \Carbon\Carbon::parse($basicInfo['maintenance_inspection_date'])->format('Y年m月d日') }}
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    <div class="detail-row {{ empty($basicInfo['inspection_report_pdf']) ? 'empty-field' : '' }}">
                        <span class="detail-label">点検実施報告書</span>
                        <span class="detail-value">
                            @if(!empty($basicInfo['inspection_report_pdf']))
                                <a href="#" class="text-decoration-none" aria-label="点検実施報告書PDFを開く">
                                    <i class="fas fa-file-pdf me-1 text-danger" aria-hidden="true"></i>{{ $basicInfo['inspection_report_pdf'] }}
                                </a>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                </div>

                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="electrical_basic" 
                     id="comment-section-electrical_basic"
                     role="region"
                     aria-label="基本情報のコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-electrical_basic" class="sr-only">基本情報にコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-electrical_basic"
                                   placeholder="コメントを入力..." 
                                   data-section="electrical_basic"
                                   aria-describedby="comment-help-electrical_basic">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="electrical_basic"
                                    aria-label="基本情報にコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="comment-help-electrical_basic" class="sr-only">
                            Enterキーまたは投稿ボタンでコメントを追加できます
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="electrical_basic"
                         role="log"
                         aria-label="基本情報のコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PASカード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="electrical_pas">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-shield-alt text-primary me-2"></i>PAS
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="electrical_pas" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="PASのコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-electrical_pas">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="electrical_pas" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                @php
                    $pasInfo = $electricalEquipment->pas_info ?? [];
                @endphp
                
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($pasInfo['availability']) ? 'empty-field' : '' }}">
                        <span class="detail-label">有無</span>
                        <span class="detail-value">
                            @if(!empty($pasInfo['availability']))
                                <span class="badge {{ $pasInfo['availability'] === '有' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $pasInfo['availability'] }}
                                </span>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    @if(!empty($pasInfo['availability']) && $pasInfo['availability'] === '有')
                        <div class="detail-row {{ empty($pasInfo['details']) ? 'empty-field' : '' }}">
                            <span class="detail-label">詳細</span>
                            <span class="detail-value">{{ $pasInfo['details'] ?? '未設定' }}</span>
                        </div>
                        <div class="detail-row {{ empty($pasInfo['update_date']) ? 'empty-field' : '' }}">
                            <span class="detail-label">更新年月日</span>
                            <span class="detail-value">
                                @if(!empty($pasInfo['update_date']))
                                    {{ \Carbon\Carbon::parse($pasInfo['update_date'])->format('Y年n月j日') }}
                                @else
                                    未設定
                                @endif
                            </span>
                        </div>
                    @endif
                </div>

                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="electrical_pas" 
                     id="comment-section-electrical_pas"
                     role="region"
                     aria-label="PASのコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-electrical_pas" class="sr-only">PASにコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-electrical_pas"
                                   placeholder="コメントを入力..." 
                                   data-section="electrical_pas"
                                   aria-describedby="comment-help-electrical_pas">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="electrical_pas"
                                    aria-label="PASにコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="comment-help-electrical_pas" class="sr-only">
                            Enterキーまたは投稿ボタンでコメントを追加できます
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="electrical_pas"
                         role="log"
                         aria-label="PASのコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- キュービクルカード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="electrical_cubicle">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-cube text-primary me-2"></i>キュービクル
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="electrical_cubicle" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="キュービクルのコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-electrical_cubicle">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="electrical_cubicle" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                @php
                    $cubicleInfo = $electricalEquipment->cubicle_info ?? [];
                    $equipmentList = $cubicleInfo['equipment_list'] ?? [];
                @endphp
                
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($cubicleInfo['availability']) ? 'empty-field' : '' }}">
                        <span class="detail-label">有無</span>
                        <span class="detail-value">
                            @if(!empty($cubicleInfo['availability']))
                                <span class="badge {{ $cubicleInfo['availability'] === '有' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $cubicleInfo['availability'] }}
                                </span>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    @if(!empty($cubicleInfo['availability']) && $cubicleInfo['availability'] === '有')
                        <div class="detail-row {{ empty($cubicleInfo['details']) ? 'empty-field' : '' }}">
                            <span class="detail-label">詳細</span>
                            <span class="detail-value">{{ $cubicleInfo['details'] ?? '未設定' }}</span>
                        </div>
                        @if(!empty($equipmentList) && is_array($equipmentList))
                            <div class="detail-row">
                                <span class="detail-label">設備情報</span>
                                <div class="detail-value">
                                    <div class="equipment-list">
                                        @foreach($equipmentList as $index => $equipment)
                                            <div class="equipment-item mb-2 p-2 border rounded">
                                                <div class="row">
                                                    <div class="col-sm-3">
                                                        <small class="text-muted">設備番号:</small><br>
                                                        <strong>{{ $equipment['equipment_number'] ?? '未設定' }}</strong>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <small class="text-muted">メーカー:</small><br>
                                                        {{ $equipment['manufacturer'] ?? '未設定' }}
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <small class="text-muted">年式:</small><br>
                                                        {{ $equipment['model_year'] ?? '未設定' }}
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <small class="text-muted">更新年月日:</small><br>
                                                        @if(!empty($equipment['update_date']))
                                                            {{ \Carbon\Carbon::parse($equipment['update_date'])->format('Y/m/d') }}
                                                        @else
                                                            未設定
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="detail-row empty-field">
                                <span class="detail-label">設備情報</span>
                                <span class="detail-value">未設定</span>
                            </div>
                        @endif
                    @endif
                </div>
                
                <!-- Edit Mode -->
                @if($canEdit)
                <div class="edit-mode d-none">
                    <form class="equipment-form" data-section="electrical_cubicle" data-card="cubicle">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cubicle_availability" class="form-label">有無 <span class="text-danger">*</span></label>
                                <select class="form-select" 
                                        id="cubicle_availability" 
                                        name="cubicle_info[availability]"
                                        aria-describedby="cubicle_availability_help"
                                        data-conditional-trigger="cubicle_details">
                                    <option value="">選択してください</option>
                                    <option value="有" {{ ($cubicleInfo['availability'] ?? '') === '有' ? 'selected' : '' }}>有</option>
                                    <option value="無" {{ ($cubicleInfo['availability'] ?? '') === '無' ? 'selected' : '' }}>無</option>
                                </select>
                                <div id="cubicle_availability_help" class="form-text">
                                    キュービクル（高圧受電設備）の有無を選択してください
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <!-- 詳細情報（「有」選択時のみ表示） -->
                        <div class="conditional-fields" 
                             data-conditional-target="cubicle_details" 
                             style="display: {{ ($cubicleInfo['availability'] ?? '') === '有' ? 'block' : 'none' }};">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="cubicle_details" class="form-label">詳細</label>
                                    <textarea class="form-control" 
                                              id="cubicle_details" 
                                              name="cubicle_info[details]"
                                              rows="3"
                                              maxlength="1000"
                                              placeholder="キュービクルの詳細情報を入力してください"
                                              aria-describedby="cubicle_details_help">{{ $cubicleInfo['details'] ?? '' }}</textarea>
                                    <div id="cubicle_details_help" class="form-text">
                                        キュービクルの仕様、設置場所、容量などの詳細情報を入力してください（最大1000文字）
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            
                            <!-- 設備リスト -->
                            <div class="equipment-list-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">設備情報</h6>
                                    <button type="button" class="btn btn-outline-primary btn-sm add-equipment-btn" 
                                            data-equipment-type="cubicle"
                                            aria-label="キュービクル設備を追加">
                                        <i class="fas fa-plus me-1" aria-hidden="true"></i>設備追加
                                    </button>
                                </div>
                                
                                <div class="equipment-list" data-equipment-type="cubicle">
                                    @if(!empty($equipmentList) && is_array($equipmentList))
                                        @foreach($equipmentList as $index => $equipment)
                                            <div class="equipment-item border rounded p-3 mb-3" data-equipment-index="{{ $index }}">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0">設備 {{ $index + 1 }}</h6>
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-equipment-btn"
                                                            aria-label="この設備を削除">
                                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">設備番号</label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               name="cubicle_info[equipment_list][{{ $index }}][equipment_number]"
                                                               value="{{ $equipment['equipment_number'] ?? '' }}"
                                                               maxlength="50"
                                                               placeholder="例: CB-001">
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">メーカー</label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               name="cubicle_info[equipment_list][{{ $index }}][manufacturer]"
                                                               value="{{ $equipment['manufacturer'] ?? '' }}"
                                                               maxlength="100"
                                                               placeholder="例: 三菱電機">
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">年式</label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               name="cubicle_info[equipment_list][{{ $index }}][model_year]"
                                                               value="{{ $equipment['model_year'] ?? '' }}"
                                                               maxlength="10"
                                                               placeholder="例: 2020">
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">更新年月日</label>
                                                        <input type="date" 
                                                               class="form-control" 
                                                               name="cubicle_info[equipment_list][{{ $index }}][update_date]"
                                                               value="{{ $equipment['update_date'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                
                                <!-- 設備が0件の場合のメッセージ -->
                                <div class="no-equipment-message text-muted text-center py-3" 
                                     style="display: {{ empty($equipmentList) ? 'block' : 'none' }};">
                                    設備情報が登録されていません。「設備追加」ボタンから追加してください。
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions mt-3">
                            <button type="submit" class="btn btn-success btn-sm me-2 save-card-btn" 
                                    data-card="cubicle" 
                                    data-section="electrical_cubicle"
                                    aria-label="キュービクル情報を保存">
                                <i class="fas fa-save me-1" aria-hidden="true"></i>保存
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm cancel-edit-btn" 
                                    data-card="cubicle" 
                                    data-section="electrical_cubicle"
                                    aria-label="編集をキャンセル">
                                <i class="fas fa-times me-1" aria-hidden="true"></i>キャンセル
                            </button>
                        </div>
                        
                        <!-- Loading indicator -->
                        <div class="loading-indicator d-none mt-2">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                                <span>保存中...</span>
                            </div>
                        </div>
                    </form>
                </div>
                @endif
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="electrical_cubicle" 
                     id="comment-section-electrical_cubicle"
                     role="region"
                     aria-label="キュービクルのコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-electrical_cubicle" class="sr-only">キュービクルにコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-electrical_cubicle"
                                   placeholder="コメントを入力..." 
                                   data-section="electrical_cubicle">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="electrical_cubicle"
                                    aria-label="キュービクルにコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="electrical_cubicle"
                         role="log"
                         aria-label="キュービクルのコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 非常用発電機カード -->
    <div class="col-lg-6 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="electrical_generator">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-cog text-primary me-2"></i>非常用発電機
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="electrical_generator" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="非常用発電機のコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-electrical_generator">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="electrical_generator" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                @php
                    $generatorInfo = $electricalEquipment->generator_info ?? [];
                    $equipmentList = $generatorInfo['equipment_list'] ?? [];
                @endphp
                
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($generatorInfo['availability']) ? 'empty-field' : '' }}">
                        <span class="detail-label">有無</span>
                        <span class="detail-value">
                            @if(!empty($generatorInfo['availability']))
                                <span class="badge {{ $generatorInfo['availability'] === '有' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $generatorInfo['availability'] }}
                                </span>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                    @if(!empty($generatorInfo['availability']) && $generatorInfo['availability'] === '有')
                        <div class="detail-row {{ empty($generatorInfo['availability_details']) ? 'empty-field' : '' }}">
                            <span class="detail-label">詳細</span>
                            <span class="detail-value">{{ $generatorInfo['availability_details'] ?? '未設定' }}</span>
                        </div>
                        @if(!empty($equipmentList) && is_array($equipmentList))
                            <div class="detail-row">
                                <span class="detail-label">設備情報</span>
                                <div class="detail-value">
                                    <div class="equipment-list">
                                        @foreach($equipmentList as $index => $equipment)
                                            <div class="equipment-item mb-2 p-2 border rounded">
                                                <div class="row">
                                                    <div class="col-sm-3">
                                                        <small class="text-muted">設備番号:</small><br>
                                                        <strong>{{ $equipment['equipment_number'] ?? '未設定' }}</strong>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <small class="text-muted">メーカー:</small><br>
                                                        {{ $equipment['manufacturer'] ?? '未設定' }}
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <small class="text-muted">年式:</small><br>
                                                        {{ $equipment['model_year'] ?? '未設定' }}
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <small class="text-muted">更新年月日:</small><br>
                                                        @if(!empty($equipment['update_date']))
                                                            {{ \Carbon\Carbon::parse($equipment['update_date'])->format('Y/m/d') }}
                                                        @else
                                                            未設定
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="detail-row empty-field">
                                <span class="detail-label">設備情報</span>
                                <span class="detail-value">未設定</span>
                            </div>
                        @endif
                    @endif
                </div>
                
                <!-- Edit Mode -->
                @if($canEdit)
                <div class="edit-mode d-none">
                    <form class="equipment-form" data-section="electrical_generator" data-card="generator">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="generator_availability" class="form-label">有無 <span class="text-danger">*</span></label>
                                <select class="form-select" 
                                        id="generator_availability" 
                                        name="generator_info[availability]"
                                        aria-describedby="generator_availability_help"
                                        data-conditional-trigger="generator_details">
                                    <option value="">選択してください</option>
                                    <option value="有" {{ ($generatorInfo['availability'] ?? '') === '有' ? 'selected' : '' }}>有</option>
                                    <option value="無" {{ ($generatorInfo['availability'] ?? '') === '無' ? 'selected' : '' }}>無</option>
                                </select>
                                <div id="generator_availability_help" class="form-text">
                                    非常用発電機の有無を選択してください
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <!-- 詳細情報（「有」選択時のみ表示） -->
                        <div class="conditional-fields" 
                             data-conditional-target="generator_details" 
                             style="display: {{ ($generatorInfo['availability'] ?? '') === '有' ? 'block' : 'none' }};">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="generator_availability_details" class="form-label">詳細</label>
                                    <textarea class="form-control" 
                                              id="generator_availability_details" 
                                              name="generator_info[availability_details]"
                                              rows="3"
                                              maxlength="1000"
                                              placeholder="非常用発電機の詳細情報を入力してください"
                                              aria-describedby="generator_availability_details_help">{{ $generatorInfo['availability_details'] ?? '' }}</textarea>
                                    <div id="generator_availability_details_help" class="form-text">
                                        非常用発電機の仕様、設置場所、容量などの詳細情報を入力してください（最大1000文字）
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            
                            <!-- 設備リスト -->
                            <div class="equipment-list-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">設備情報</h6>
                                    <button type="button" class="btn btn-outline-primary btn-sm add-equipment-btn" 
                                            data-equipment-type="generator"
                                            aria-label="非常用発電機設備を追加">
                                        <i class="fas fa-plus me-1" aria-hidden="true"></i>設備追加
                                    </button>
                                </div>
                                
                                <div class="equipment-list" data-equipment-type="generator">
                                    @if(!empty($equipmentList) && is_array($equipmentList))
                                        @foreach($equipmentList as $index => $equipment)
                                            <div class="equipment-item border rounded p-3 mb-3" data-equipment-index="{{ $index }}">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0">設備 {{ $index + 1 }}</h6>
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-equipment-btn"
                                                            aria-label="この設備を削除">
                                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">設備番号</label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               name="generator_info[equipment_list][{{ $index }}][equipment_number]"
                                                               value="{{ $equipment['equipment_number'] ?? '' }}"
                                                               maxlength="50"
                                                               placeholder="例: GEN-001">
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">メーカー</label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               name="generator_info[equipment_list][{{ $index }}][manufacturer]"
                                                               value="{{ $equipment['manufacturer'] ?? '' }}"
                                                               maxlength="100"
                                                               placeholder="例: ヤンマー">
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">年式</label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               name="generator_info[equipment_list][{{ $index }}][model_year]"
                                                               value="{{ $equipment['model_year'] ?? '' }}"
                                                               maxlength="10"
                                                               placeholder="例: 2021">
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">更新年月日</label>
                                                        <input type="date" 
                                                               class="form-control" 
                                                               name="generator_info[equipment_list][{{ $index }}][update_date]"
                                                               value="{{ $equipment['update_date'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                
                                <!-- 設備が0件の場合のメッセージ -->
                                <div class="no-equipment-message text-muted text-center py-3" 
                                     style="display: {{ empty($equipmentList) ? 'block' : 'none' }};">
                                    設備情報が登録されていません。「設備追加」ボタンから追加してください。
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions mt-3">
                            <button type="submit" class="btn btn-success btn-sm me-2 save-card-btn" 
                                    data-card="generator" 
                                    data-section="electrical_generator"
                                    aria-label="非常用発電機情報を保存">
                                <i class="fas fa-save me-1" aria-hidden="true"></i>保存
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm cancel-edit-btn" 
                                    data-card="generator" 
                                    data-section="electrical_generator"
                                    aria-label="編集をキャンセル">
                                <i class="fas fa-times me-1" aria-hidden="true"></i>キャンセル
                            </button>
                        </div>
                        
                        <!-- Loading indicator -->
                        <div class="loading-indicator d-none mt-2">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                                <span>保存中...</span>
                            </div>
                        </div>
                    </form>
                </div>
                @endif
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="electrical_generator" 
                     id="comment-section-electrical_generator"
                     role="region"
                     aria-label="非常用発電機のコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-electrical_generator" class="sr-only">非常用発電機にコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-electrical_generator"
                                   placeholder="コメントを入力..." 
                                   data-section="electrical_generator">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="electrical_generator"
                                    aria-label="非常用発電機にコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="electrical_generator"
                         role="log"
                         aria-label="非常用発電機のコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 備考カード -->
    <div class="col-12 mb-4">
        <div class="card facility-info-card detail-card-improved h-100" data-section="electrical_notes">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-sticky-note text-primary me-2"></i>備考
                </h5>
                <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                        data-section="electrical_notes" 
                        data-bs-toggle="tooltip" 
                        title="コメントを表示/非表示"
                        aria-label="備考のコメントを表示または非表示にする"
                        aria-expanded="false"
                        aria-controls="comment-section-electrical_notes">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <span class="comment-count" data-section="electrical_notes" aria-label="コメント数">0</span>
                </button>
            </div>
            <div class="card-body">
                <div class="facility-detail-table">
                    <div class="detail-row {{ empty($electricalEquipment?->notes) ? 'empty-field' : '' }}">
                        <span class="detail-label">備考</span>
                        <span class="detail-value">
                            @if(!empty($electricalEquipment?->notes))
                                <div class="border rounded p-2 bg-light">{!! nl2br(e($electricalEquipment->notes)) !!}</div>
                            @else
                                未設定
                            @endif
                        </span>
                    </div>
                </div>
                
                <!-- Edit Mode -->
                @if($canEdit)
                <div class="edit-mode d-none">
                    <form class="equipment-form" data-section="electrical_notes" data-card="notes">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="electrical_notes" class="form-label">備考</label>
                                <textarea class="form-control" 
                                          id="electrical_notes" 
                                          name="notes"
                                          rows="6"
                                          maxlength="2000"
                                          placeholder="電気設備に関する特記事項、注意点、その他の情報を入力してください"
                                          aria-describedby="electrical_notes_help">{{ $electricalEquipment?->notes ?? '' }}</textarea>
                                <div id="electrical_notes_help" class="form-text">
                                    電気設備に関する特記事項、メンテナンス履歴、注意点などを記録してください（最大2000文字）
                                </div>
                                <div class="character-count text-muted small mt-1">
                                    <span class="current-count">{{ mb_strlen($electricalEquipment?->notes ?? '') }}</span> / 2000 文字
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="form-actions mt-3">
                            <button type="submit" class="btn btn-success btn-sm me-2 save-card-btn" 
                                    data-card="notes" 
                                    data-section="electrical_notes"
                                    aria-label="備考を保存">
                                <i class="fas fa-save me-1" aria-hidden="true"></i>保存
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm cancel-edit-btn" 
                                    data-card="notes" 
                                    data-section="electrical_notes"
                                    aria-label="編集をキャンセル">
                                <i class="fas fa-times me-1" aria-hidden="true"></i>キャンセル
                            </button>
                        </div>
                        
                        <!-- Loading indicator -->
                        <div class="loading-indicator d-none mt-2">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                                <span>保存中...</span>
                            </div>
                        </div>
                    </form>
                </div>
                @endif
                
                <!-- コメントセクション -->
                <div class="comment-section mt-3 d-none" 
                     data-section="electrical_notes" 
                     id="comment-section-electrical_notes"
                     role="region"
                     aria-label="備考のコメント">
                    <hr>
                    <div class="comment-form mb-3">
                        <div class="input-group">
                            <label for="comment-input-electrical_notes" class="sr-only">備考にコメントを追加</label>
                            <input type="text" 
                                   class="form-control comment-input" 
                                   id="comment-input-electrical_notes"
                                   placeholder="コメントを入力..." 
                                   data-section="electrical_notes">
                            <button class="btn btn-primary comment-submit" 
                                    data-section="electrical_notes"
                                    aria-label="備考にコメントを投稿">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comment-list" 
                         data-section="electrical_notes"
                         role="log"
                         aria-label="備考のコメント一覧"
                         aria-live="polite">
                        <!-- コメントがここに動的に追加されます -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>