@php
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
            'title' => '基本情報編集',
            'active' => true
        ]
    ];
@endphp

<x-facility.edit-layout
    title="基本情報編集 - {{ $facility->facility_name }}"
    :facility="$facility"
    :breadcrumbs="$breadcrumbs"
    :back-route="route('facilities.show', $facility)"
    :form-action="route('facilities.update-basic-info', $facility)"
    form-method="PUT"
    form-id="basicInfoForm"
>
    <!-- 基本情報セクション -->
    <x-form.section title="基本情報" icon="fas fa-info-circle" icon-color="primary"
                    :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('basic_info', 'basic_info')">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="company_name" class="form-label required">会社名</label>
                <input type="text" class="form-control @error('company_name') is-invalid @enderror" 
                       id="company_name" name="company_name" 
                       value="{{ old('company_name', $facility->company_name) }}" required>
                <x-form.field-error field="company_name" />
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="office_code" class="form-label required">事業所コード</label>
                <input type="text" class="form-control @error('office_code') is-invalid @enderror" 
                       id="office_code" name="office_code" 
                       value="{{ old('office_code', $facility->office_code) }}" required>
                <x-form.field-error field="office_code" />
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="designation_number" class="form-label">指定番号</label>
                <input type="text" class="form-control @error('designation_number') is-invalid @enderror" 
                       id="designation_number" name="designation_number" 
                       value="{{ old('designation_number', $facility->designation_number) }}">
                <x-form.field-error field="designation_number" />
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="facility_name" class="form-label required">施設名</label>
                <input type="text" class="form-control @error('facility_name') is-invalid @enderror" 
                       id="facility_name" name="facility_name" 
                       value="{{ old('facility_name', $facility->facility_name) }}" required>
                <x-form.field-error field="facility_name" />
            </div>
        </div>
    </x-form.section>

    <!-- 住所・連絡先情報セクション -->
    <x-form.section title="住所・連絡先" icon="fas fa-map-marker-alt" icon-color="success"
                    :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('contact_info', 'basic_info')">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="postal_code" class="form-label">郵便番号</label>
                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" 
                       id="postal_code" name="postal_code" 
                       value="{{ old('postal_code', $facility->postal_code) }}"
                       placeholder="例: 100-0001">
                <x-form.field-error field="postal_code" />
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="building_name" class="form-label">住所（建物名）</label>
                <input type="text" class="form-control @error('building_name') is-invalid @enderror" 
                       id="building_name" name="building_name" 
                       value="{{ old('building_name', $facility->building_name) }}">
                <x-form.field-error field="building_name" />
            </div>
        </div>
        
        <div class="row">
            <div class="col-12 mb-3">
                <label for="address" class="form-label">住所</label>
                <textarea class="form-control @error('address') is-invalid @enderror" 
                          id="address" name="address" rows="2">{{ old('address', $facility->address) }}</textarea>
                <x-form.field-error field="address" />
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="phone_number" class="form-label">電話番号</label>
                <input type="text" class="form-control @error('phone_number') is-invalid @enderror" 
                       id="phone_number" name="phone_number" 
                       value="{{ old('phone_number', $facility->phone_number) }}">
                <x-form.field-error field="phone_number" />
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="fax_number" class="form-label">FAX番号</label>
                <input type="text" class="form-control @error('fax_number') is-invalid @enderror" 
                       id="fax_number" name="fax_number" 
                       value="{{ old('fax_number', $facility->fax_number) }}">
                <x-form.field-error field="fax_number" />
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="toll_free_number" class="form-label">フリーダイヤル</label>
                <input type="text" class="form-control @error('toll_free_number') is-invalid @enderror" 
                       id="toll_free_number" name="toll_free_number" 
                       value="{{ old('toll_free_number', $facility->toll_free_number) }}">
                <x-form.field-error field="toll_free_number" />
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">メールアドレス</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                       id="email" name="email" 
                       value="{{ old('email', $facility->email) }}">
                <x-form.field-error field="email" />
            </div>
        </div>
        
        <div class="row">
            <div class="col-12 mb-3">
                <label for="website_url" class="form-label">URL</label>
                <input type="url" class="form-control @error('website_url') is-invalid @enderror" 
                       id="website_url" name="website_url" 
                       value="{{ old('website_url', $facility->website_url) }}">
                <x-form.field-error field="website_url" />
            </div>
        </div>
    </x-form.section>

    <!-- 開設・建物情報セクション -->
    <x-form.section title="開設・建物情報" icon="fas fa-building" icon-color="info"
                    :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('building_info', 'basic_info')">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="opening_date" class="form-label">開設日</label>
                <input type="date" class="form-control @error('opening_date') is-invalid @enderror" 
                       id="opening_date" name="opening_date" 
                       value="{{ old('opening_date', $facility->opening_date?->format('Y-m-d')) }}">
                <x-form.field-error field="opening_date" />
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="years_in_operation" class="form-label">
                    開設年数
                    <span class="auto-calc-indicator" title="開設日から自動計算されます">
                        <i class="fas fa-calculator text-info"></i>
                        <small class="text-muted">自動計算</small>
                    </span>
                </label>
                <input type="number" class="form-control auto-calc-field @error('years_in_operation') is-invalid @enderror" 
                       id="years_in_operation" name="years_in_operation" 
                       value="{{ old('years_in_operation', $facility->years_in_operation) }}" min="0"
                       readonly title="このフィールドは開設日から自動計算されます">
                <x-form.field-error field="years_in_operation" />
                <small class="form-text text-muted">
                    <i class="fas fa-info-circle"></i> 開設日を入力すると自動で計算されます
                </small>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="building_structure" class="form-label">建物構造</label>
                <select class="form-select @error('building_structure') is-invalid @enderror" 
                        id="building_structure" name="building_structure">
                    <option value="">選択してください</option>
                    <option value="鉄筋コンクリート造" {{ old('building_structure', $facility->building_structure) == '鉄筋コンクリート造' ? 'selected' : '' }}>鉄筋コンクリート造</option>
                    <option value="鉄骨造" {{ old('building_structure', $facility->building_structure) == '鉄骨造' ? 'selected' : '' }}>鉄骨造</option>
                    <option value="木造" {{ old('building_structure', $facility->building_structure) == '木造' ? 'selected' : '' }}>木造</option>
                    <option value="鉄骨鉄筋コンクリート造" {{ old('building_structure', $facility->building_structure) == '鉄骨鉄筋コンクリート造' ? 'selected' : '' }}>鉄骨鉄筋コンクリート造</option>
                    <option value="その他" {{ old('building_structure', $facility->building_structure) == 'その他' ? 'selected' : '' }}>その他</option>
                </select>
                <x-form.field-error field="building_structure" />
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="building_floors" class="form-label">建物階数</label>
                <input type="number" class="form-control @error('building_floors') is-invalid @enderror" 
                       id="building_floors" name="building_floors" 
                       value="{{ old('building_floors', $facility->building_floors) }}" min="1">
                <x-form.field-error field="building_floors" />
            </div>
        </div>
    </x-form.section>

    <!-- 施設情報セクション -->
    <x-form.section title="施設情報" icon="fas fa-home" icon-color="warning"
                    :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('facility_info', 'basic_info')">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="paid_rooms_count" class="form-label">居室数（有料）</label>
                <input type="number" class="form-control @error('paid_rooms_count') is-invalid @enderror" 
                       id="paid_rooms_count" name="paid_rooms_count" 
                       value="{{ old('paid_rooms_count', $facility->paid_rooms_count) }}" min="0">
                <x-form.field-error field="paid_rooms_count" />
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="ss_rooms_count" class="form-label">内SS数</label>
                <input type="number" class="form-control @error('ss_rooms_count') is-invalid @enderror" 
                       id="ss_rooms_count" name="ss_rooms_count" 
                       value="{{ old('ss_rooms_count', $facility->ss_rooms_count) }}" min="0">
                <x-form.field-error field="ss_rooms_count" />
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="capacity" class="form-label">定員数</label>
                <input type="number" class="form-control @error('capacity') is-invalid @enderror" 
                       id="capacity" name="capacity" 
                       value="{{ old('capacity', $facility->capacity) }}" min="1">
                <x-form.field-error field="capacity" />
            </div>
        </div>
    </x-form.section>

    <!-- サービスの種類・指定更新情報セクション -->
    <x-form.section title="サービスの種類・指定更新情報" icon="fas fa-cogs" icon-color="dark"
                    :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('services', 'basic_info')">
        <div class="mb-3">
            <small class="text-muted d-block mb-3">最大10件まで登録できます。入力されている行のみ保存されます。</small>
            
            <div id="services-container">
                @php
                    $existingServices = old('services', $facility->services ?? collect());
                    // Ensure it's a collection for consistent access
                    if (is_array($existingServices)) {
                        $existingServices = collect($existingServices);
                    }
                    $maxServices = 10;
                @endphp
                
                @for($i = 0; $i < $maxServices; $i++)
                    @php
                        $service = $existingServices->get($i);
                        $hasData = $service && (is_object($service) ? !empty($service->service_type) : !empty($service['service_type'] ?? ''));
                    @endphp
                    <div class="service-row mb-3 p-3 border rounded {{ !$hasData && $i >= 3 ? 'd-none' : '' }}" data-index="{{ $i }}">
                        <div class="row align-items-end">
                            <div class="col-md-5">
                                <label for="service_type_{{ $i }}" class="form-label">サービス名</label>
                                <input type="text" 
                                       class="form-control @error('services.'.$i.'.service_type') is-invalid @enderror" 
                                       id="service_type_{{ $i }}" 
                                       name="services[{{ $i }}][service_type]" 
                                       value="{{ old('services.'.$i.'.service_type', $service ? (is_object($service) ? $service->service_type : ($service['service_type'] ?? '')) : '') }}"
                                       placeholder="例: 介護付有料老人ホーム">
                                <x-form.field-error field="services.{{ $i }}.service_type" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">有効期限</label>
                                <div class="row g-2">
                                    <div class="col-5">
                                        <input type="date" 
                                               class="form-control @error('services.'.$i.'.renewal_start_date') is-invalid @enderror" 
                                               id="renewal_start_{{ $i }}" 
                                               name="services[{{ $i }}][renewal_start_date]" 
                                               value="{{ old('services.'.$i.'.renewal_start_date', $service ? (is_object($service) && $service->renewal_start_date ? $service->renewal_start_date->format('Y-m-d') : (is_array($service) && isset($service['renewal_start_date']) ? $service['renewal_start_date'] : '')) : '') }}"
                                               placeholder="開始日">
                                        <x-form.field-error field="services.{{ $i }}.renewal_start_date" />
                                    </div>
                                    <div class="col-2 text-center d-flex align-items-center justify-content-center">
                                        <span class="text-muted">〜</span>
                                    </div>
                                    <div class="col-5">
                                        <input type="date" 
                                               class="form-control @error('services.'.$i.'.renewal_end_date') is-invalid @enderror" 
                                               id="renewal_end_{{ $i }}" 
                                               name="services[{{ $i }}][renewal_end_date]" 
                                               value="{{ old('services.'.$i.'.renewal_end_date', $service ? (is_object($service) && $service->renewal_end_date ? $service->renewal_end_date->format('Y-m-d') : (is_array($service) && isset($service['renewal_end_date']) ? $service['renewal_end_date'] : '')) : '') }}"
                                               placeholder="終了日">
                                        <x-form.field-error field="services.{{ $i }}.renewal_end_date" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-service" 
                                        onclick="clearServiceRow({{ $i }})" title="クリア">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
            
            <div class="mt-2">
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-service-btn" onclick="testAddRow()">
                    <i class="fas fa-plus"></i> サービス行を追加
                </button>

                <small class="text-muted ms-2">
                    <span id="service-count">0</span>/10 件
                </small>
            </div>
        </div>
    </x-form.section>

</x-facility.edit-layout>

<script>
// グローバル関数として定義（デバッグ用）
function testAddRow() {
    console.log('=== テスト追加ボタンがクリックされました ===');
    
    const hiddenRows = document.querySelectorAll('.service-row.d-none');
    const visibleRows = document.querySelectorAll('.service-row:not(.d-none)');
    
    console.log('非表示行数:', hiddenRows.length);
    console.log('表示行数:', visibleRows.length);
    console.log('非表示行:', hiddenRows);
    
    if (hiddenRows.length > 0) {
        const newRow = hiddenRows[0];
        console.log('表示する行:', newRow);
        newRow.classList.remove('d-none');
        console.log('d-noneクラスを削除しました');
        
        // フォーカス設定
        const firstInput = newRow.querySelector('input[name*="service_type"]');
        if (firstInput) {
            setTimeout(() => {
                firstInput.focus();
                console.log('フォーカスを設定しました');
            }, 100);
        }
        
        // カウント更新
        updateServiceCountGlobal();
    } else {
        console.log('追加できる行がありません');
    }
}

function updateServiceCountGlobal() {
    const serviceCountSpan = document.getElementById('service-count');
    const addServiceBtn = document.getElementById('add-service-btn');
    
    const filledRows = document.querySelectorAll('.service-row input[name*="service_type"]');
    let filledCount = 0;
    
    filledRows.forEach(input => {
        if (input.value.trim() !== '') {
            filledCount++;
        }
    });
    
    const visibleRows = document.querySelectorAll('.service-row:not(.d-none)').length;
    
    if (serviceCountSpan) {
        serviceCountSpan.textContent = filledCount;
    }
    
    if (addServiceBtn) {
        addServiceBtn.disabled = visibleRows >= 10;
    }
    
    console.log('カウント更新:', filledCount, '/', visibleRows);
}

// サービス行をクリアする関数
function clearServiceRow(index) {
    console.log('サービス行をクリア:', index);
    
    const row = document.querySelector(`.service-row[data-index="${index}"]`);
    if (row) {
        // 入力値をクリア
        row.querySelectorAll('input').forEach(input => {
            input.value = '';
        });
        
        // 最初の3行以外は非表示にする
        if (index >= 3) {
            row.classList.add('d-none');
        }
        
        updateServiceCountGlobal();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== 基本情報編集ページ - 初期化開始 ===');
    
    // 要素の存在確認
    const addServiceBtn = document.getElementById('add-service-btn');
    const serviceCountSpan = document.getElementById('service-count');
    const allServiceRows = document.querySelectorAll('.service-row');
    const hiddenRows = document.querySelectorAll('.service-row.d-none');
    const visibleRows = document.querySelectorAll('.service-row:not(.d-none)');
    
    console.log('追加ボタン:', addServiceBtn);
    console.log('カウント表示:', serviceCountSpan);
    console.log('全サービス行数:', allServiceRows.length);
    console.log('非表示行数:', hiddenRows.length);
    console.log('表示行数:', visibleRows.length);
    
    // 開設年数自動計算
    const openingDateInput = document.getElementById('opening_date');
    const yearsInOperationInput = document.getElementById('years_in_operation');
    
    if (openingDateInput && yearsInOperationInput) {
        openingDateInput.addEventListener('change', function() {
            if (this.value) {
                // 計算中のアニメーション開始
                yearsInOperationInput.classList.add('calculating');
                
                // 少し遅延を入れて計算感を演出
                setTimeout(() => {
                    const openingDate = new Date(this.value);
                    const today = new Date();
                    const years = Math.floor((today - openingDate) / (365.25 * 24 * 60 * 60 * 1000));
                    yearsInOperationInput.value = Math.max(0, years);
                    
                    // アニメーション終了
                    yearsInOperationInput.classList.remove('calculating');
                    
                    // 成功のフィードバック
                    yearsInOperationInput.style.borderColor = '#198754';
                    setTimeout(() => {
                        yearsInOperationInput.style.borderColor = '#0dcaf0';
                    }, 1000);
                }, 300);
            } else {
                // 開設日が空の場合は年数もクリア
                yearsInOperationInput.value = '';
            }
        });
        
        // 初期値がある場合の自動計算
        if (openingDateInput.value) {
            const openingDate = new Date(openingDateInput.value);
            const today = new Date();
            const years = Math.floor((today - openingDate) / (365.25 * 24 * 60 * 60 * 1000));
            yearsInOperationInput.value = Math.max(0, years);
        }
        
        console.log('開設年数自動計算を設定しました');
    }
    
    // サービス行追加ボタンのイベントリスナー
    if (addServiceBtn) {
        // シンプルにイベントリスナーを追加
        addServiceBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('=== メインの追加ボタンがクリックされました ===');
            testAddRow(); // テスト関数を呼び出し
        });
        
        console.log('サービス行追加ボタンのイベントリスナーを設定しました');
    } else {
        console.error('サービス行追加ボタンが見つかりません');
    }
    
    // 入力値の変更を監視
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[name*="service_type"]')) {
            updateServiceCountGlobal();
        }
    });
    
    // 初期カウント更新
    updateServiceCountGlobal();
    
    console.log('=== 初期化完了 ===');
});

// ページ読み込み完了後にも再確認
window.addEventListener('load', function() {
    console.log('=== ページ読み込み完了 - 再確認 ===');
    
    const addServiceBtn = document.getElementById('add-service-btn');
    if (addServiceBtn) {
        console.log('追加ボタンが存在します');
        console.log('ボタンのクラス:', addServiceBtn.className);
        console.log('ボタンが無効化されているか:', addServiceBtn.disabled);
    } else {
        console.error('追加ボタンが見つかりません');
    }
    
    const allRows = document.querySelectorAll('.service-row');
    console.log('全サービス行:', allRows.length);
    allRows.forEach((row, index) => {
        console.log(`行${index}:`, row.classList.contains('d-none') ? '非表示' : '表示');
    });
});
</script>