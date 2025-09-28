@php
    $waterLifeline = $facility->getLifelineEquipmentByCategory('water');
    $waterEquipment = $waterLifeline?->waterEquipment;
    $basicInfo = $waterEquipment->basic_info ?? [];
    $filterInfo = $basicInfo['filter_info'] ?? [];
    $tankInfo = $basicInfo['tank_info'] ?? [];
    $pumpInfo = $basicInfo['pump_info'] ?? [];
    $septicTankInfo = $basicInfo['septic_tank_info'] ?? [];
    $legionellaInfo = $basicInfo['legionella_info'] ?? [];
    
    // 加圧ポンプデータの正規化（複数台対応）
    $pumps = [];
    if (isset($pumpInfo['pumps']) && is_array($pumpInfo['pumps'])) {
        $pumps = $pumpInfo['pumps'];
    } elseif (!empty($pumpInfo['manufacturer']) || !empty($pumpInfo['model_year']) || !empty($pumpInfo['update_date'])) {
        // 単一データを配列形式に変換
        $pumps = [$pumpInfo];
    }
    
    // レジオネラ検査データの正規化（複数設備対応）
    $legionellaInspections = [];
    if (isset($legionellaInfo['inspections']) && is_array($legionellaInfo['inspections'])) {
        $legionellaInspections = $legionellaInfo['inspections'];
    } elseif (!empty($legionellaInfo['inspection_date']) || !empty($legionellaInfo['first_result']) || !empty($legionellaInfo['second_result'])) {
        // 単一データを配列形式に変換
        $legionellaInspections = [$legionellaInfo];
    }
    
    $breadcrumbs = [
        [
            'title' => '施設一覧',
            'route' => 'facilities.index',
            'active' => false
        ],
        [
            'title' => $facility->facility_name,
            'route' => 'facilities.show',
            'params' => [$facility],
            'active' => false
        ],
        [
            'title' => '水設備編集',
            'active' => true
        ]
    ];
@endphp

<x-facility.edit-layout
    title="水設備編集"
    :facility="$facility"
    :breadcrumbs="$breadcrumbs"
    :back-route="route('facilities.show', $facility) . '#water'"
    :form-action="route('facilities.lifeline-equipment.update', [$facility, 'water'])"
    form-method="PUT"
    form-id="waterEquipmentForm"
>
    <!-- 基本情報セクション -->
    <x-form.section title="基本情報" icon="fas fa-info-circle" icon-color="primary">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="water_contractor" class="form-label">水道契約会社</label>
                <input type="text" class="form-control @error('basic_info.water_contractor') is-invalid @enderror" 
                       id="water_contractor" name="basic_info[water_contractor]" 
                       value="{{ old('basic_info.water_contractor', $basicInfo['water_contractor'] ?? '') }}">
                <x-form.field-error field="basic_info.water_contractor" />
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="tank_cleaning_company" class="form-label">受水槽清掃業者</label>
                <input type="text" class="form-control @error('basic_info.tank_cleaning_company') is-invalid @enderror" 
                       id="tank_cleaning_company" name="basic_info[tank_cleaning_company]" 
                       value="{{ old('basic_info.tank_cleaning_company', $basicInfo['tank_cleaning_company'] ?? '') }}">
                <x-form.field-error field="basic_info.tank_cleaning_company" />
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="tank_cleaning_date" class="form-label">受水槽清掃実施日</label>
                <input type="date" class="form-control @error('basic_info.tank_cleaning_date') is-invalid @enderror" 
                       id="tank_cleaning_date" name="basic_info[tank_cleaning_date]" 
                       value="{{ old('basic_info.tank_cleaning_date', $basicInfo['tank_cleaning_date'] ?? '') }}">
                <x-form.field-error field="basic_info.tank_cleaning_date" />
            </div>
            
            <div class="col-md-6 mb-3">
                @php
                    $tankCleaningFileData = null;
                    if (!empty($basicInfo['tank_cleaning']['tank_cleaning_report_pdf'])) {
                        $tankCleaningFileData = [
                            'filename' => $basicInfo['tank_cleaning']['tank_cleaning_report_pdf'],
                            'download_url' => route('facilities.lifeline-equipment.download-file', [$facility, 'water', 'tank_cleaning_report']),
                            'exists' => true
                        ];
                    }
                @endphp
                
                <x-file-upload 
                    name="tank_cleaning_report_file"
                    label="受水槽清掃報告書"
                    fileType="pdf"
                    :currentFile="$tankCleaningFileData"
                    :required="false"
                    :showRemoveOption="true"
                    removeFieldName="remove_tank_cleaning_report"
                />
            </div>
        </div>
    </x-form.section>

    <!-- ろ過器セクション -->
    <x-form.section title="ろ過器" icon="fas fa-filter" icon-color="info">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="bath_system" class="form-label">浴槽循環方式</label>
                <select class="form-select @error('basic_info.filter_info.bath_system') is-invalid @enderror" 
                        id="bath_system" name="basic_info[filter_info][bath_system]">
                    <option value="">選択してください</option>
                    <option value="循環式" {{ old('basic_info.filter_info.bath_system', $filterInfo['bath_system'] ?? '') === '循環式' ? 'selected' : '' }}>循環式</option>
                    <option value="掛け流し式" {{ old('basic_info.filter_info.bath_system', $filterInfo['bath_system'] ?? '') === '掛け流し式' ? 'selected' : '' }}>掛け流し式</option>
                </select>
                <x-form.field-error field="basic_info.filter_info.bath_system" />
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="filter_availability" class="form-label">設置の有無</label>
                <select class="form-select @error('basic_info.filter_info.availability') is-invalid @enderror" 
                        id="filter_availability" name="basic_info[filter_info][availability]">
                    <option value="">選択してください</option>
                    <option value="有" {{ old('basic_info.filter_info.availability', $filterInfo['availability'] ?? '') === '有' ? 'selected' : '' }}>有</option>
                    <option value="無" {{ old('basic_info.filter_info.availability', $filterInfo['availability'] ?? '') === '無' ? 'selected' : '' }}>無</option>
                </select>
                <x-form.field-error field="basic_info.filter_info.availability" />
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="filter_manufacturer" class="form-label">メーカー</label>
                <input type="text" class="form-control @error('basic_info.filter_info.manufacturer') is-invalid @enderror" 
                       id="filter_manufacturer" name="basic_info[filter_info][manufacturer]" 
                       value="{{ old('basic_info.filter_info.manufacturer', $filterInfo['manufacturer'] ?? '') }}">
                <x-form.field-error field="basic_info.filter_info.manufacturer" />
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="filter_model_year" class="form-label">年式</label>
                <input type="number" class="form-control @error('basic_info.filter_info.model_year') is-invalid @enderror" 
                       id="filter_model_year" name="basic_info[filter_info][model_year]" 
                       value="{{ old('basic_info.filter_info.model_year', $filterInfo['model_year'] ?? '') }}"
                       min="1900" max="{{ date('Y') + 5 }}">
                <x-form.field-error field="basic_info.filter_info.model_year" />
            </div>
        </div>
    </x-form.section>

    <!-- 受水槽セクション -->
    <x-form.section title="受水槽" icon="fas fa-water" icon-color="info">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="tank_availability" class="form-label">設置の有無</label>
                <select class="form-select @error('basic_info.tank_info.availability') is-invalid @enderror" 
                        id="tank_availability" name="basic_info[tank_info][availability]">
                    <option value="">選択してください</option>
                    <option value="有" {{ old('basic_info.tank_info.availability', $tankInfo['availability'] ?? '') === '有' ? 'selected' : '' }}>有</option>
                    <option value="無" {{ old('basic_info.tank_info.availability', $tankInfo['availability'] ?? '') === '無' ? 'selected' : '' }}>無</option>
                </select>
                <x-form.field-error field="basic_info.tank_info.availability" />
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="tank_manufacturer" class="form-label">メーカー</label>
                <input type="text" class="form-control @error('basic_info.tank_info.manufacturer') is-invalid @enderror" 
                       id="tank_manufacturer" name="basic_info[tank_info][manufacturer]" 
                       value="{{ old('basic_info.tank_info.manufacturer', $tankInfo['manufacturer'] ?? '') }}">
                <x-form.field-error field="basic_info.tank_info.manufacturer" />
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="tank_model_year" class="form-label">年式</label>
                <input type="number" class="form-control @error('basic_info.tank_info.model_year') is-invalid @enderror" 
                       id="tank_model_year" name="basic_info[tank_info][model_year]" 
                       value="{{ old('basic_info.tank_info.model_year', $tankInfo['model_year'] ?? '') }}"
                       min="1900" max="{{ date('Y') + 5 }}">
                <x-form.field-error field="basic_info.tank_info.model_year" />
            </div>
        </div>
    </x-form.section>

    <!-- 加圧ポンプセクション -->
    <x-form.section title="加圧ポンプ" icon="fas fa-cogs" icon-color="success">
        <div class="equipment-section-header d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">加圧ポンプ設備</h6>
            <button type="button" class="btn btn-outline-primary btn-sm add-pump-btn">
                <i class="fas fa-plus"></i> 設備を追加
            </button>
        </div>
        
        <div id="pump-equipment-list" class="equipment-list">
            @if(empty($pumps))
                <div class="no-equipment-message">
                    加圧ポンプが登録されていません。「設備を追加」ボタンをクリックして追加してください。
                </div>
            @else
                @foreach($pumps as $index => $pump)
                    <div class="equipment-item pump-equipment-item mb-3" data-index="{{ $index }}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                <span class="equipment-number badge bg-primary me-2">{{ $index + 1 }}</span>
                                加圧ポンプ {{ $index + 1 }}
                            </h6>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-pump-btn">
                                <i class="fas fa-trash"></i> 削除
                            </button>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">メーカー</label>
                                <input type="text" class="form-control" 
                                       name="basic_info[pump_info][pumps][{{ $index }}][manufacturer]" 
                                       value="{{ old('basic_info.pump_info.pumps.' . $index . '.manufacturer', $pump['manufacturer'] ?? '') }}"
                                       placeholder="メーカー名を入力してください">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">年式</label>
                                <input type="number" class="form-control" 
                                       name="basic_info[pump_info][pumps][{{ $index }}][model_year]" 
                                       value="{{ old('basic_info.pump_info.pumps.' . $index . '.model_year', $pump['model_year'] ?? '') }}"
                                       min="1900" max="{{ date('Y') + 5 }}" 
                                       placeholder="年式を入力">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">更新年月日</label>
                                <input type="date" class="form-control" 
                                       name="basic_info[pump_info][pumps][{{ $index }}][update_date]" 
                                       value="{{ old('basic_info.pump_info.pumps.' . $index . '.update_date', $pump['update_date'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </x-form.section>

    <!-- 浄化槽セクション -->
    <x-form.section title="浄化槽" icon="fas fa-recycle" icon-color="success">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="septic_tank_availability" class="form-label">設置の有無</label>
                <select class="form-select @error('basic_info.septic_tank_info.availability') is-invalid @enderror" 
                        id="septic_tank_availability" name="basic_info[septic_tank_info][availability]">
                    <option value="">選択してください</option>
                    <option value="有" {{ old('basic_info.septic_tank_info.availability', $septicTankInfo['availability'] ?? '') === '有' ? 'selected' : '' }}>有</option>
                    <option value="無" {{ old('basic_info.septic_tank_info.availability', $septicTankInfo['availability'] ?? '') === '無' ? 'selected' : '' }}>無</option>
                </select>
                <x-form.field-error field="basic_info.septic_tank_info.availability" />
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="septic_tank_manufacturer" class="form-label">メーカー</label>
                <input type="text" class="form-control @error('basic_info.septic_tank_info.manufacturer') is-invalid @enderror" 
                       id="septic_tank_manufacturer" name="basic_info[septic_tank_info][manufacturer]" 
                       value="{{ old('basic_info.septic_tank_info.manufacturer', $septicTankInfo['manufacturer'] ?? '') }}">
                <x-form.field-error field="basic_info.septic_tank_info.manufacturer" />
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="septic_tank_model_year" class="form-label">年式</label>
                <input type="number" class="form-control @error('basic_info.septic_tank_info.model_year') is-invalid @enderror" 
                       id="septic_tank_model_year" name="basic_info[septic_tank_info][model_year]" 
                       value="{{ old('basic_info.septic_tank_info.model_year', $septicTankInfo['model_year'] ?? '') }}"
                       min="1900" max="{{ date('Y') + 5 }}">
                <x-form.field-error field="basic_info.septic_tank_info.model_year" />
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="septic_tank_inspection_company" class="form-label">点検・清掃業者</label>
                <input type="text" class="form-control @error('basic_info.septic_tank_info.inspection_company') is-invalid @enderror" 
                       id="septic_tank_inspection_company" name="basic_info[septic_tank_info][inspection_company]" 
                       value="{{ old('basic_info.septic_tank_info.inspection_company', $septicTankInfo['inspection_company'] ?? '') }}">
                <x-form.field-error field="basic_info.septic_tank_info.inspection_company" />
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="septic_tank_inspection_date" class="form-label">点検・清掃実施日</label>
                <input type="date" class="form-control @error('basic_info.septic_tank_info.inspection_date') is-invalid @enderror" 
                       id="septic_tank_inspection_date" name="basic_info[septic_tank_info][inspection_date]" 
                       value="{{ old('basic_info.septic_tank_info.inspection_date', $septicTankInfo['inspection_date'] ?? '') }}">
                <x-form.field-error field="basic_info.septic_tank_info.inspection_date" />
            </div>
            
            <div class="col-md-4 mb-3">
                @php
                    $septicTankFileData = null;
                    if (!empty($septicTankInfo['inspection']['inspection_report_pdf'])) {
                        $septicTankFileData = [
                            'filename' => $septicTankInfo['inspection']['inspection_report_pdf'],
                            'download_url' => route('facilities.lifeline-equipment.download-file', [$facility, 'water', 'septic_tank_inspection_report']),
                            'exists' => true
                        ];
                    }
                @endphp
                
                <x-file-upload 
                    name="septic_tank_inspection_report_file"
                    label="点検・清掃報告書"
                    fileType="pdf"
                    :currentFile="$septicTankFileData"
                    :required="false"
                    :showRemoveOption="true"
                    removeFieldName="remove_septic_tank_inspection_report"
                />
            </div>
        </div>
    </x-form.section>

    <!-- レジオネラ検査セクション -->
    <x-form.section title="レジオネラ検査" icon="fas fa-microscope" icon-color="warning">
        <div class="equipment-section-header d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">レジオネラ検査設備</h6>
            <button type="button" class="btn btn-outline-warning btn-sm add-legionella-btn">
                <i class="fas fa-plus"></i> 検査を追加
            </button>
        </div>
        
        <div id="legionella-equipment-list" class="equipment-list">
            @if(empty($legionellaInspections))
                <div class="no-legionella-message">
                    レジオネラ検査が登録されていません。「検査を追加」ボタンをクリックして追加してください。
                </div>
            @else
                @foreach($legionellaInspections as $index => $inspection)
                    <div class="equipment-item legionella-equipment-item mb-3" data-index="{{ $index }}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                <span class="equipment-number badge bg-warning me-2">{{ $index + 1 }}</span>
                                レジオネラ検査 {{ $index + 1 }}
                            </h6>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-legionella-btn">
                                <i class="fas fa-trash"></i> 削除
                            </button>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">実施日</label>
                                <input type="date" class="form-control" 
                                       name="basic_info[legionella_info][inspections][{{ $index }}][inspection_date]" 
                                       value="{{ old('basic_info.legionella_info.inspections.' . $index . '.inspection_date', $inspection['inspection_date'] ?? '') }}">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                @php
                                    $legionellaFileData = null;
                                    if (!empty($inspection['report']['report_pdf'])) {
                                        $legionellaFileData = [
                                            'filename' => $inspection['report']['report_pdf'],
                                            'download_url' => route('facilities.lifeline-equipment.download-file', [$facility, 'water', 'legionella_report_' . $index]),
                                            'exists' => true
                                        ];
                                    }
                                @endphp
                                
                                <x-file-upload 
                                    name="legionella_report_file_{{ $index }}"
                                    label="検査結果報告書"
                                    fileType="pdf"
                                    :currentFile="$legionellaFileData"
                                    :required="false"
                                    :showRemoveOption="true"
                                    removeFieldName="remove_legionella_report_{{ $index }}"
                                />
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">検査結果（初回）</label>
                                <select class="form-select" 
                                        name="basic_info[legionella_info][inspections][{{ $index }}][first_result]">
                                    <option value="">選択してください</option>
                                    <option value="陰性" {{ old('basic_info.legionella_info.inspections.' . $index . '.first_result', $inspection['first_result'] ?? '') === '陰性' ? 'selected' : '' }}>陰性</option>
                                    <option value="陽性" {{ old('basic_info.legionella_info.inspections.' . $index . '.first_result', $inspection['first_result'] ?? '') === '陽性' ? 'selected' : '' }}>陽性</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">数値（陽性の場合）</label>
                                <input type="text" class="form-control" 
                                       name="basic_info[legionella_info][inspections][{{ $index }}][first_value]" 
                                       value="{{ old('basic_info.legionella_info.inspections.' . $index . '.first_value', $inspection['first_value'] ?? '') }}"
                                       placeholder="陽性の場合の数値を入力">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">検査結果（2回目）</label>
                                <select class="form-select" 
                                        name="basic_info[legionella_info][inspections][{{ $index }}][second_result]">
                                    <option value="">選択してください</option>
                                    <option value="陰性" {{ old('basic_info.legionella_info.inspections.' . $index . '.second_result', $inspection['second_result'] ?? '') === '陰性' ? 'selected' : '' }}>陰性</option>
                                    <option value="陽性" {{ old('basic_info.legionella_info.inspections.' . $index . '.second_result', $inspection['second_result'] ?? '') === '陽性' ? 'selected' : '' }}>陽性</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">数値（陽性の場合）</label>
                                <input type="text" class="form-control" 
                                       name="basic_info[legionella_info][inspections][{{ $index }}][second_value]" 
                                       value="{{ old('basic_info.legionella_info.inspections.' . $index . '.second_value', $inspection['second_value'] ?? '') }}"
                                       placeholder="陽性の場合の数値を入力">
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </x-form.section>

    <!-- 備考セクション -->
    <x-form.section title="備考" icon="fas fa-sticky-note" icon-color="warning">
        <div class="row">
            <div class="col-12 mb-3">
                <label for="notes" class="form-label">備考</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" 
                          id="notes" name="notes" rows="4"
                          placeholder="水設備に関する追加情報や特記事項があれば記入してください">{{ old('notes', $waterEquipment->notes ?? '') }}</textarea>
                <x-form.field-error field="notes" />
            </div>
        </div>
    </x-form.section>
</x-facility.edit-layout>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // フォーム送信時の処理
    const form = document.getElementById('waterEquipmentForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // 送信前の確認
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
            }
        });
    }

    // 少し遅延を追加してすべての要素が確実に読み込まれるようにする
    setTimeout(function() {
        initializePumpEquipment();
        initializeLegionellaEquipment();
    }, 100);
});

function initializePumpEquipment() {
    // 加圧ポンプ設備の動的追加・削除機能
    let pumpIndex = {{ count($pumps) }};
    const pumpList = document.getElementById('pump-equipment-list');
    const addPumpBtn = document.querySelector('.add-pump-btn');
    const noEquipmentMessage = document.querySelector('.no-equipment-message');
    
    console.log('Initializing pump equipment');
    console.log('Pump list:', pumpList);
    console.log('Add pump button:', addPumpBtn);
    console.log('No equipment message:', noEquipmentMessage);

    // 設備追加ボタンのイベント
    if (addPumpBtn) {
        console.log('Add pump button found, adding event listener');
        addPumpBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Add pump button clicked');
            addPumpEquipment();
        });
    } else {
        console.error('Add pump button not found');
        // 代替手段：すべてのボタンを検索
        const allButtons = document.querySelectorAll('button');
        console.log('All buttons found:', allButtons.length);
        allButtons.forEach((btn, index) => {
            console.log(`Button ${index}:`, btn.className, btn.textContent.trim());
        });
        
        // クラス名で再検索
        const altAddBtn = document.querySelector('button.add-pump-btn');
        if (altAddBtn) {
            console.log('Alternative add pump button found');
            altAddBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Alternative add pump button clicked');
                addPumpEquipment();
            });
        }
    }

    // 設備追加関数
    function addPumpEquipment() {
        console.log('Adding pump equipment, current index:', pumpIndex);
        
        // メッセージを非表示
        if (noEquipmentMessage) {
            noEquipmentMessage.style.display = 'none';
        }

        const pumpItem = document.createElement('div');
        pumpItem.className = 'equipment-item pump-equipment-item mb-3 adding';
        pumpItem.setAttribute('data-index', pumpIndex);
        
        pumpItem.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">
                    <span class="equipment-number badge bg-primary me-2">${pumpIndex + 1}</span>
                    加圧ポンプ ${pumpIndex + 1}
                </h6>
                <button type="button" class="btn btn-outline-danger btn-sm remove-pump-btn">
                    <i class="fas fa-trash"></i> 削除
                </button>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">メーカー</label>
                    <input type="text" class="form-control" 
                           name="basic_info[pump_info][pumps][${pumpIndex}][manufacturer]" 
                           placeholder="メーカー名を入力">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">年式</label>
                    <input type="number" class="form-control" 
                           name="basic_info[pump_info][pumps][${pumpIndex}][model_year]" 
                           min="1900" max="${new Date().getFullYear() + 5}" 
                           placeholder="年式を入力">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">更新年月日</label>
                    <input type="date" class="form-control" 
                           name="basic_info[pump_info][pumps][${pumpIndex}][update_date]">
                </div>
            </div>
        `;

        if (pumpList) {
            pumpList.appendChild(pumpItem);
        } else {
            console.error('Pump list container not found');
            return;
        }
        
        // アニメーション
        setTimeout(() => {
            pumpItem.classList.remove('adding');
        }, 10);

        // 削除ボタンのイベント追加
        const removeBtn = pumpItem.querySelector('.remove-pump-btn');
        removeBtn.addEventListener('click', function() {
            removePumpEquipment(pumpItem);
        });

        pumpIndex++;
        updatePumpNumbers();
    }

    // 設備削除関数
    function removePumpEquipment(item) {
        item.classList.add('removing');
        
        setTimeout(() => {
            item.remove();
            updatePumpNumbers();
            
            // 設備がすべて削除された場合はメッセージを表示
            const remainingItems = pumpList.querySelectorAll('.pump-equipment-item');
            if (remainingItems.length === 0 && noEquipmentMessage) {
                noEquipmentMessage.style.display = 'block';
            }
        }, 300);
    }

    // 番号更新関数
    function updatePumpNumbers() {
        const items = pumpList.querySelectorAll('.pump-equipment-item');
        items.forEach((item, index) => {
            const numberBadge = item.querySelector('.equipment-number');
            const title = item.querySelector('h6');
            
            if (numberBadge) {
                numberBadge.textContent = index + 1;
            }
            
            if (title) {
                const titleText = title.querySelector('span:not(.equipment-number)');
                if (titleText) {
                    titleText.textContent = `加圧ポンプ ${index + 1}`;
                } else {
                    // タイトルテキストが見つからない場合は作成
                    const newTitle = title.cloneNode(false);
                    newTitle.innerHTML = `
                        <span class="equipment-number badge bg-primary me-2">${index + 1}</span>
                        加圧ポンプ ${index + 1}
                    `;
                    title.parentNode.replaceChild(newTitle, title);
                }
            }

            // name属性も更新
            const inputs = item.querySelectorAll('input');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.includes('pumps[')) {
                    const newName = name.replace(/pumps\[\d+\]/, `pumps[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
    }

    // 既存の削除ボタンにイベント追加
    document.querySelectorAll('.remove-pump-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const item = this.closest('.pump-equipment-item');
            removePumpEquipment(item);
        });
    });
}

function initializeLegionellaEquipment() {
    // レジオネラ検査設備の動的追加・削除機能
    let legionellaIndex = {{ count($legionellaInspections) }};
    const legionellaList = document.getElementById('legionella-equipment-list');
    const addLegionellaBtn = document.querySelector('.add-legionella-btn');
    const noLegionellaMessage = document.querySelector('.no-legionella-message');
    
    console.log('Initializing legionella equipment');
    console.log('Legionella list:', legionellaList);
    console.log('Add legionella button:', addLegionellaBtn);

    // 設備追加ボタンのイベント
    if (addLegionellaBtn) {
        addLegionellaBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addLegionellaEquipment();
        });
    }

    // 設備追加関数
    function addLegionellaEquipment() {
        console.log('Adding legionella equipment, current index:', legionellaIndex);
        
        // メッセージを非表示
        if (noLegionellaMessage) {
            noLegionellaMessage.style.display = 'none';
        }

        const legionellaItem = document.createElement('div');
        legionellaItem.className = 'equipment-item legionella-equipment-item mb-3 adding';
        legionellaItem.setAttribute('data-index', legionellaIndex);
        
        legionellaItem.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">
                    <span class="equipment-number badge bg-warning me-2">${legionellaIndex + 1}</span>
                    レジオネラ検査 ${legionellaIndex + 1}
                </h6>
                <button type="button" class="btn btn-outline-danger btn-sm remove-legionella-btn">
                    <i class="fas fa-trash"></i> 削除
                </button>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">実施日</label>
                    <input type="date" class="form-control" 
                           name="basic_info[legionella_info][inspections][${legionellaIndex}][inspection_date]">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">検査結果報告書</label>
                    <input type="file" class="form-control" 
                           name="legionella_report_file_${legionellaIndex}" 
                           accept=".pdf">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" 
                               name="remove_legionella_report_${legionellaIndex}" 
                               value="1" id="remove_legionella_report_${legionellaIndex}">
                        <label class="form-check-label" for="remove_legionella_report_${legionellaIndex}">
                            既存ファイルを削除
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">検査結果（初回）</label>
                    <select class="form-select" 
                            name="basic_info[legionella_info][inspections][${legionellaIndex}][first_result]">
                        <option value="">選択してください</option>
                        <option value="陰性">陰性</option>
                        <option value="陽性">陽性</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">数値（陽性の場合）</label>
                    <input type="text" class="form-control" 
                           name="basic_info[legionella_info][inspections][${legionellaIndex}][first_value]" 
                           placeholder="陽性の場合の数値を入力">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">検査結果（2回目）</label>
                    <select class="form-select" 
                            name="basic_info[legionella_info][inspections][${legionellaIndex}][second_result]">
                        <option value="">選択してください</option>
                        <option value="陰性">陰性</option>
                        <option value="陽性">陽性</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">数値（陽性の場合）</label>
                    <input type="text" class="form-control" 
                           name="basic_info[legionella_info][inspections][${legionellaIndex}][second_value]" 
                           placeholder="陽性の場合の数値を入力">
                </div>
            </div>
        `;

        if (legionellaList) {
            legionellaList.appendChild(legionellaItem);
        } else {
            console.error('Legionella list container not found');
            return;
        }
        
        // アニメーション
        setTimeout(() => {
            legionellaItem.classList.remove('adding');
        }, 10);

        // 削除ボタンのイベント追加
        const removeBtn = legionellaItem.querySelector('.remove-legionella-btn');
        removeBtn.addEventListener('click', function() {
            removeLegionellaEquipment(legionellaItem);
        });

        legionellaIndex++;
        updateLegionellaNumbers();
    }

    // 設備削除関数
    function removeLegionellaEquipment(item) {
        item.classList.add('removing');
        
        setTimeout(() => {
            item.remove();
            updateLegionellaNumbers();
            
            // 設備がすべて削除された場合はメッセージを表示
            const remainingItems = legionellaList.querySelectorAll('.legionella-equipment-item');
            if (remainingItems.length === 0 && noLegionellaMessage) {
                noLegionellaMessage.style.display = 'block';
            }
        }, 300);
    }

    // 番号更新関数
    function updateLegionellaNumbers() {
        const items = legionellaList.querySelectorAll('.legionella-equipment-item');
        items.forEach((item, index) => {
            const numberBadge = item.querySelector('.equipment-number');
            const title = item.querySelector('h6');
            
            if (numberBadge) {
                numberBadge.textContent = index + 1;
            }
            
            if (title) {
                const titleText = title.querySelector('span:not(.equipment-number)');
                if (titleText) {
                    titleText.textContent = `レジオネラ検査 ${index + 1}`;
                } else {
                    // タイトルテキストが見つからない場合は作成
                    const newTitle = title.cloneNode(false);
                    newTitle.innerHTML = `
                        <span class="equipment-number badge bg-warning me-2">${index + 1}</span>
                        レジオネラ検査 ${index + 1}
                    `;
                    title.parentNode.replaceChild(newTitle, title);
                }
            }

            // name属性も更新
            const inputs = item.querySelectorAll('input, select');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.includes('inspections[')) {
                    const newName = name.replace(/inspections\[\d+\]/, `inspections[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
    }

    // 既存の削除ボタンにイベント追加
    document.querySelectorAll('.remove-legionella-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const item = this.closest('.legionella-equipment-item');
            removeLegionellaEquipment(item);
        });
    });
}
</script>