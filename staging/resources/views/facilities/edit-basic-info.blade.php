@extends('layouts.app')

@section('title', '施設基本情報編集')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">施設基本情報編集</h3>
                </div>
                
                <form action="{{ route('facilities.update-basic-info', $facility) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div class="row">
                            <!-- 基本情報 -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">基本情報</h5>
                                
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">会社名 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('company_name') is-invalid @enderror" 
                                           id="company_name" name="company_name" 
                                           value="{{ old('company_name', $facility->company_name) }}" required>
                                    @error('company_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="office_code" class="form-label">事業所コード <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('office_code') is-invalid @enderror" 
                                           id="office_code" name="office_code" 
                                           value="{{ old('office_code', $facility->office_code) }}" required>
                                    @error('office_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="designation_number" class="form-label">指定番号</label>
                                    <input type="text" class="form-control @error('designation_number') is-invalid @enderror" 
                                           id="designation_number" name="designation_number" 
                                           value="{{ old('designation_number', $facility->designation_number) }}">
                                    @error('designation_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="facility_name" class="form-label">施設名 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('facility_name') is-invalid @enderror" 
                                           id="facility_name" name="facility_name" 
                                           value="{{ old('facility_name', $facility->facility_name) }}" required>
                                    @error('facility_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- 住所・連絡先情報 -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">住所・連絡先</h5>
                                
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">郵便番号</label>
                                    <input type="text" class="form-control @error('postal_code') is-invalid @enderror" 
                                           id="postal_code" name="postal_code" 
                                           value="{{ old('postal_code', $facility->postal_code) }}"
                                           placeholder="例: 100-0001">
                                    @error('postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">住所</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" 
                                              id="address" name="address" rows="2">{{ old('address', $facility->address) }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="building_name" class="form-label">住所（建物名）</label>
                                    <input type="text" class="form-control @error('building_name') is-invalid @enderror" 
                                           id="building_name" name="building_name" 
                                           value="{{ old('building_name', $facility->building_name) }}">
                                    @error('building_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone_number" class="form-label">電話番号</label>
                                    <input type="text" class="form-control @error('phone_number') is-invalid @enderror" 
                                           id="phone_number" name="phone_number" 
                                           value="{{ old('phone_number', $facility->phone_number) }}">
                                    @error('phone_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fax_number" class="form-label">FAX番号</label>
                                    <input type="text" class="form-control @error('fax_number') is-invalid @enderror" 
                                           id="fax_number" name="fax_number" 
                                           value="{{ old('fax_number', $facility->fax_number) }}">
                                    @error('fax_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="toll_free_number" class="form-label">フリーダイヤル</label>
                                    <input type="text" class="form-control @error('toll_free_number') is-invalid @enderror" 
                                           id="toll_free_number" name="toll_free_number" 
                                           value="{{ old('toll_free_number', $facility->toll_free_number) }}">
                                    @error('toll_free_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">メールアドレス</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" 
                                           value="{{ old('email', $facility->email) }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="website_url" class="form-label">URL</label>
                                    <input type="url" class="form-control @error('website_url') is-invalid @enderror" 
                                           id="website_url" name="website_url" 
                                           value="{{ old('website_url', $facility->website_url) }}">
                                    @error('website_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="row">
                            <!-- 開設・建物情報 -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">開設・建物情報</h5>
                                
                                <div class="mb-3">
                                    <label for="opening_date" class="form-label">開設日</label>
                                    <input type="date" class="form-control @error('opening_date') is-invalid @enderror" 
                                           id="opening_date" name="opening_date" 
                                           value="{{ old('opening_date', $facility->opening_date?->format('Y-m-d')) }}">
                                    @error('opening_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="years_in_operation" class="form-label">開設年数</label>
                                    <input type="number" class="form-control @error('years_in_operation') is-invalid @enderror" 
                                           id="years_in_operation" name="years_in_operation" 
                                           value="{{ old('years_in_operation', $facility->years_in_operation) }}" min="0">
                                    @error('years_in_operation')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
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
                                    @error('building_structure')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="building_floors" class="form-label">建物階数</label>
                                    <input type="number" class="form-control @error('building_floors') is-invalid @enderror" 
                                           id="building_floors" name="building_floors" 
                                           value="{{ old('building_floors', $facility->building_floors) }}" min="1">
                                    @error('building_floors')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- 施設情報 -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">施設情報</h5>
                                
                                <div class="mb-3">
                                    <label for="paid_rooms_count" class="form-label">居室数（有料）</label>
                                    <input type="number" class="form-control @error('paid_rooms_count') is-invalid @enderror" 
                                           id="paid_rooms_count" name="paid_rooms_count" 
                                           value="{{ old('paid_rooms_count', $facility->paid_rooms_count) }}" min="0">
                                    @error('paid_rooms_count')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="ss_rooms_count" class="form-label">内SS数</label>
                                    <input type="number" class="form-control @error('ss_rooms_count') is-invalid @enderror" 
                                           id="ss_rooms_count" name="ss_rooms_count" 
                                           value="{{ old('ss_rooms_count', $facility->ss_rooms_count) }}" min="0">
                                    @error('ss_rooms_count')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="capacity" class="form-label">定員数</label>
                                    <input type="number" class="form-control @error('capacity') is-invalid @enderror" 
                                           id="capacity" name="capacity" 
                                           value="{{ old('capacity', $facility->capacity) }}" min="1">
                                    @error('capacity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- サービスの種類・指定更新情報 - 独立セクション -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">サービスの種類・指定更新情報</h5>
                                <small class="text-muted d-block mb-3">最大10件まで登録できます。入力されている行のみ保存されます。</small>
                                
                                <div id="services-container">
                                    @php
                                        $existingServices = old('services', $facility->services ?? collect());
                                        $maxServices = 10;
                                    @endphp
                                    
                                    @for($i = 0; $i < $maxServices; $i++)
                                        @php
                                            $service = $existingServices->get($i);
                                            $hasData = $service && !empty($service->service_type);
                                        @endphp
                                        <div class="service-row mb-3 p-3 border rounded {{ !$hasData && $i >= 3 ? 'd-none' : '' }}" data-index="{{ $i }}">
                                            <div class="row align-items-end">
                                                <div class="col-md-5">
                                                    <label for="service_type_{{ $i }}" class="form-label">サービス名</label>
                                                    <input type="text" 
                                                           class="form-control @error('services.'.$i.'.service_type') is-invalid @enderror" 
                                                           id="service_type_{{ $i }}" 
                                                           name="services[{{ $i }}][service_type]" 
                                                           value="{{ old('services.'.$i.'.service_type', $service ? $service->service_type : '') }}"
                                                           placeholder="例: 介護付有料老人ホーム">
                                                    @error('services.'.$i.'.service_type')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">有効期限</label>
                                                    <div class="row g-2">
                                                        <div class="col-5">
                                                            <input type="date" 
                                                                   class="form-control @error('services.'.$i.'.renewal_start_date') is-invalid @enderror" 
                                                                   id="renewal_start_{{ $i }}" 
                                                                   name="services[{{ $i }}][renewal_start_date]" 
                                                                   value="{{ old('services.'.$i.'.renewal_start_date', $service && $service->renewal_start_date ? $service->renewal_start_date->format('Y-m-d') : '') }}"
                                                                   placeholder="開始日">
                                                            @error('services.'.$i.'.renewal_start_date')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-2 text-center d-flex align-items-center justify-content-center">
                                                            <span class="text-muted">〜</span>
                                                        </div>
                                                        <div class="col-5">
                                                            <input type="date" 
                                                                   class="form-control @error('services.'.$i.'.renewal_end_date') is-invalid @enderror" 
                                                                   id="renewal_end_{{ $i }}" 
                                                                   name="services[{{ $i }}][renewal_end_date]" 
                                                                   value="{{ old('services.'.$i.'.renewal_end_date', $service && $service->renewal_end_date ? $service->renewal_end_date->format('Y-m-d') : '') }}"
                                                                   placeholder="終了日">
                                                            @error('services.'.$i.'.renewal_end_date')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
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
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-service-btn">
                                        <i class="fas fa-plus"></i> サービス行を追加
                                    </button>
                                    <small class="text-muted ms-2">
                                        <span id="service-count">0</span>/10 件
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('facilities.show', $facility) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> 戻る
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 保存
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 開設日が変更されたら開設年数を自動計算
    const openingDateInput = document.getElementById('opening_date');
    const yearsInOperationInput = document.getElementById('years_in_operation');
    
    openingDateInput.addEventListener('change', function() {
        if (this.value) {
            const openingDate = new Date(this.value);
            const today = new Date();
            const years = Math.floor((today - openingDate) / (365.25 * 24 * 60 * 60 * 1000));
            yearsInOperationInput.value = Math.max(0, years);
        }
    });

    // サービス行の管理
    const addServiceBtn = document.getElementById('add-service-btn');
    const serviceCountSpan = document.getElementById('service-count');
    
    function updateServiceCount() {
        const visibleRows = document.querySelectorAll('.service-row:not(.d-none)').length;
        serviceCountSpan.textContent = visibleRows;
        
        // 10行に達したら追加ボタンを無効化
        addServiceBtn.disabled = visibleRows >= 10;
    }
    
    function showNextServiceRow() {
        const hiddenRows = document.querySelectorAll('.service-row.d-none');
        if (hiddenRows.length > 0) {
            hiddenRows[0].classList.remove('d-none');
            updateServiceCount();
        }
    }
    
    // サービス行追加ボタン
    addServiceBtn.addEventListener('click', function() {
        showNextServiceRow();
    });
    
    // 初期表示時のカウント更新
    updateServiceCount();
    
    // 入力値の変更を監視してカウントを更新
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[name*="service_type"]')) {
            updateServiceCount();
        }
    });
});

// サービス行をクリアする関数
function clearServiceRow(index) {
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
        
        // カウント更新
        const serviceCountSpan = document.getElementById('service-count');
        const addServiceBtn = document.getElementById('add-service-btn');
        const visibleRows = document.querySelectorAll('.service-row:not(.d-none)').length;
        serviceCountSpan.textContent = visibleRows;
        addServiceBtn.disabled = false;
    }
}
</script>
@endsection