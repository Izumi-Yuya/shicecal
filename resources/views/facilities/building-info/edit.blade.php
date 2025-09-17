@extends('layouts.app')

@section('title', '建物情報編集 - ' . $facility->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">建物情報編集</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}">施設一覧</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('facilities.show', $facility) }}">{{ $facility->name }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">建物情報編集</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>戻る
                    </a>
                </div>
            </div>

            <form action="{{ route('facilities.building-info.update', $facility) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- 所有区分 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-home me-2"></i>所有区分
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ownership_type" class="form-label">所有任意項目 <span class="text-danger">*</span></label>
                                    <select class="form-select @error('ownership_type') is-invalid @enderror" 
                                            id="ownership_type" name="ownership_type" required>
                                        <option value="">選択してください</option>
                                        <option value="自社" {{ old('ownership_type', $buildingInfo->ownership_type ?? '') === '自社' ? 'selected' : '' }}>自社</option>
                                        <option value="賃借" {{ old('ownership_type', $buildingInfo->ownership_type ?? '') === '賃借' ? 'selected' : '' }}>賃借</option>
                                        <option value="賃貸" {{ old('ownership_type', $buildingInfo->ownership_type ?? '') === '賃貸' ? 'selected' : '' }}>賃貸</option>
                                    </select>
                                    @error('ownership_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">建物の所有形態を選択してください（50全角・半角文字まで）</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 建築面積・延床面積 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-ruler-combined me-2"></i>建築面積・延床面積
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="building_area_sqm" class="form-label">建築面積（㎡）</label>
                                    <input type="number" step="0.01" class="form-control @error('building_area_sqm') is-invalid @enderror" 
                                           id="building_area_sqm" name="building_area_sqm" 
                                           value="{{ old('building_area_sqm', $buildingInfo->building_area_sqm ?? '') }}">
                                    @error('building_area_sqm')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="building_area_tsubo" class="form-label">建築面積（坪数）</label>
                                    <input type="number" step="0.01" class="form-control @error('building_area_tsubo') is-invalid @enderror" 
                                           id="building_area_tsubo" name="building_area_tsubo" 
                                           value="{{ old('building_area_tsubo', $buildingInfo->building_area_tsubo ?? '') }}">
                                    @error('building_area_tsubo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_floor_area_sqm" class="form-label">延床面積（㎡）</label>
                                    <input type="number" step="0.01" class="form-control @error('total_floor_area_sqm') is-invalid @enderror" 
                                           id="total_floor_area_sqm" name="total_floor_area_sqm" 
                                           value="{{ old('total_floor_area_sqm', $buildingInfo->total_floor_area_sqm ?? '') }}">
                                    @error('total_floor_area_sqm')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_floor_area_tsubo" class="form-label">延床面積（坪数）</label>
                                    <input type="number" step="0.01" class="form-control @error('total_floor_area_tsubo') is-invalid @enderror" 
                                           id="total_floor_area_tsubo" name="total_floor_area_tsubo" 
                                           value="{{ old('total_floor_area_tsubo', $buildingInfo->total_floor_area_tsubo ?? '') }}">
                                    @error('total_floor_area_tsubo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 建築費用・賃料 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-yen-sign me-2"></i>建築費用・賃料
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="construction_cost" class="form-label">本体価格（建築費用）</label>
                                    <div class="input-group">
                                        <span class="input-group-text">¥</span>
                                        <input type="number" class="form-control @error('construction_cost') is-invalid @enderror" 
                                               id="construction_cost" name="construction_cost" 
                                               value="{{ old('construction_cost', $buildingInfo->construction_cost ?? '') }}">
                                    </div>
                                    @error('construction_cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="construction_cooperation_fee" class="form-label">建設協力金</label>
                                    <div class="input-group">
                                        <span class="input-group-text">¥</span>
                                        <input type="number" class="form-control @error('construction_cooperation_fee') is-invalid @enderror" 
                                               id="construction_cooperation_fee" name="construction_cooperation_fee" 
                                               value="{{ old('construction_cooperation_fee', $buildingInfo->construction_cooperation_fee ?? '') }}">
                                    </div>
                                    @error('construction_cooperation_fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="monthly_rent" class="form-label">家賃（月）</label>
                                    <div class="input-group">
                                        <span class="input-group-text">¥</span>
                                        <input type="number" class="form-control @error('monthly_rent') is-invalid @enderror" 
                                               id="monthly_rent" name="monthly_rent" 
                                               value="{{ old('monthly_rent', $buildingInfo->monthly_rent ?? '') }}">
                                    </div>
                                    @error('monthly_rent')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 契約情報 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-file-contract me-2"></i>契約情報
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contract_start_date" class="form-label">契約開始日</label>
                                    <input type="date" class="form-control @error('contract_start_date') is-invalid @enderror" 
                                           id="contract_start_date" name="contract_start_date" 
                                           value="{{ old('contract_start_date', $buildingInfo?->contract_start_date?->format('Y-m-d') ?? '') }}">
                                    @error('contract_start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contract_end_date" class="form-label">契約終了日</label>
                                    <input type="date" class="form-control @error('contract_end_date') is-invalid @enderror" 
                                           id="contract_end_date" name="contract_end_date" 
                                           value="{{ old('contract_end_date', $buildingInfo?->contract_end_date?->format('Y-m-d') ?? '') }}">
                                    @error('contract_end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="auto_renewal" class="form-label">自動更新</label>
                                    <select class="form-select @error('auto_renewal') is-invalid @enderror" 
                                            id="auto_renewal" name="auto_renewal">
                                        <option value="">選択してください</option>
                                        <option value="1" {{ old('auto_renewal', $buildingInfo->auto_renewal ?? '') === '1' || old('auto_renewal', $buildingInfo->auto_renewal ?? '') === 1 ? 'selected' : '' }}>あり</option>
                                        <option value="0" {{ old('auto_renewal', $buildingInfo->auto_renewal ?? '') === '0' || old('auto_renewal', $buildingInfo->auto_renewal ?? '') === 0 ? 'selected' : '' }}>なし</option>
                                    </select>
                                    @error('auto_renewal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 管理会社情報 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-building me-2"></i>管理会社情報
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="management_company_name" class="form-label">会社名</label>
                                    <input type="text" class="form-control @error('management_company_name') is-invalid @enderror" 
                                           id="management_company_name" name="management_company_name" 
                                           value="{{ old('management_company_name', $buildingInfo->management_company_name ?? '') }}">
                                    @error('management_company_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="management_company_postal_code" class="form-label">郵便番号</label>
                                    <input type="text" class="form-control @error('management_company_postal_code') is-invalid @enderror" 
                                           id="management_company_postal_code" name="management_company_postal_code" 
                                           value="{{ old('management_company_postal_code', $buildingInfo->management_company_postal_code ?? '') }}"
                                           placeholder="123-4567">
                                    @error('management_company_postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="management_company_address" class="form-label">住所</label>
                                    <input type="text" class="form-control @error('management_company_address') is-invalid @enderror" 
                                           id="management_company_address" name="management_company_address" 
                                           value="{{ old('management_company_address', $buildingInfo->management_company_address ?? '') }}">
                                    @error('management_company_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="management_company_building_name" class="form-label">建物名</label>
                                    <input type="text" class="form-control @error('management_company_building_name') is-invalid @enderror" 
                                           id="management_company_building_name" name="management_company_building_name" 
                                           value="{{ old('management_company_building_name', $buildingInfo->management_company_building_name ?? '') }}">
                                    @error('management_company_building_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="management_company_phone" class="form-label">電話番号</label>
                                    <input type="tel" class="form-control @error('management_company_phone') is-invalid @enderror" 
                                           id="management_company_phone" name="management_company_phone" 
                                           value="{{ old('management_company_phone', $buildingInfo->management_company_phone ?? '') }}">
                                    @error('management_company_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="management_company_fax" class="form-label">FAX番号</label>
                                    <input type="tel" class="form-control @error('management_company_fax') is-invalid @enderror" 
                                           id="management_company_fax" name="management_company_fax" 
                                           value="{{ old('management_company_fax', $buildingInfo->management_company_fax ?? '') }}">
                                    @error('management_company_fax')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="management_company_email" class="form-label">メールアドレス</label>
                                    <input type="email" class="form-control @error('management_company_email') is-invalid @enderror" 
                                           id="management_company_email" name="management_company_email" 
                                           value="{{ old('management_company_email', $buildingInfo->management_company_email ?? '') }}">
                                    @error('management_company_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="management_company_url" class="form-label">URL</label>
                                    <input type="url" class="form-control @error('management_company_url') is-invalid @enderror" 
                                           id="management_company_url" name="management_company_url" 
                                           value="{{ old('management_company_url', $buildingInfo->management_company_url ?? '') }}">
                                    @error('management_company_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- オーナー情報 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user-tie me-2"></i>オーナー情報
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="owner_name" class="form-label">氏名・会社名</label>
                                    <input type="text" class="form-control @error('owner_name') is-invalid @enderror" 
                                           id="owner_name" name="owner_name" 
                                           value="{{ old('owner_name', $buildingInfo->owner_name ?? '') }}">
                                    @error('owner_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="owner_postal_code" class="form-label">郵便番号</label>
                                    <input type="text" class="form-control @error('owner_postal_code') is-invalid @enderror" 
                                           id="owner_postal_code" name="owner_postal_code" 
                                           value="{{ old('owner_postal_code', $buildingInfo->owner_postal_code ?? '') }}"
                                           placeholder="123-4567">
                                    @error('owner_postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="owner_address" class="form-label">住所</label>
                                    <input type="text" class="form-control @error('owner_address') is-invalid @enderror" 
                                           id="owner_address" name="owner_address" 
                                           value="{{ old('owner_address', $buildingInfo->owner_address ?? '') }}">
                                    @error('owner_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="owner_building_name" class="form-label">建物名</label>
                                    <input type="text" class="form-control @error('owner_building_name') is-invalid @enderror" 
                                           id="owner_building_name" name="owner_building_name" 
                                           value="{{ old('owner_building_name', $buildingInfo->owner_building_name ?? '') }}">
                                    @error('owner_building_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="owner_phone" class="form-label">電話番号</label>
                                    <input type="tel" class="form-control @error('owner_phone') is-invalid @enderror" 
                                           id="owner_phone" name="owner_phone" 
                                           value="{{ old('owner_phone', $buildingInfo->owner_phone ?? '') }}">
                                    @error('owner_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="owner_fax" class="form-label">FAX番号</label>
                                    <input type="tel" class="form-control @error('owner_fax') is-invalid @enderror" 
                                           id="owner_fax" name="owner_fax" 
                                           value="{{ old('owner_fax', $buildingInfo->owner_fax ?? '') }}">
                                    @error('owner_fax')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="owner_email" class="form-label">メールアドレス</label>
                                    <input type="email" class="form-control @error('owner_email') is-invalid @enderror" 
                                           id="owner_email" name="owner_email" 
                                           value="{{ old('owner_email', $buildingInfo->owner_email ?? '') }}">
                                    @error('owner_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="owner_url" class="form-label">URL</label>
                                    <input type="url" class="form-control @error('owner_url') is-invalid @enderror" 
                                           id="owner_url" name="owner_url" 
                                           value="{{ old('owner_url', $buildingInfo->owner_url ?? '') }}">
                                    @error('owner_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 施工会社・建築情報 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-hard-hat me-2"></i>施工会社・建築情報
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="construction_company_name" class="form-label">施工会社名</label>
                                    <input type="text" class="form-control @error('construction_company_name') is-invalid @enderror" 
                                           id="construction_company_name" name="construction_company_name" 
                                           value="{{ old('construction_company_name', $buildingInfo->construction_company_name ?? '') }}">
                                    @error('construction_company_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="construction_company_phone" class="form-label">施工会社電話番号</label>
                                    <input type="tel" class="form-control @error('construction_company_phone') is-invalid @enderror" 
                                           id="construction_company_phone" name="construction_company_phone" 
                                           value="{{ old('construction_company_phone', $buildingInfo->construction_company_phone ?? '') }}">
                                    @error('construction_company_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="completion_date" class="form-label">竣工日</label>
                                    <input type="date" class="form-control @error('completion_date') is-invalid @enderror" 
                                           id="completion_date" name="completion_date" 
                                           value="{{ old('completion_date', $buildingInfo?->completion_date?->format('Y-m-d') ?? '') }}">
                                    @error('completion_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="useful_life" class="form-label">耐用年数</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('useful_life') is-invalid @enderror" 
                                               id="useful_life" name="useful_life" 
                                               value="{{ old('useful_life', $buildingInfo->useful_life ?? '') }}">
                                        <span class="input-group-text">年</span>
                                    </div>
                                    @error('useful_life')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="construction_company_notes" class="form-label">施工会社備考</label>
                                    <textarea class="form-control @error('construction_company_notes') is-invalid @enderror" 
                                              id="construction_company_notes" name="construction_company_notes" 
                                              rows="3">{{ old('construction_company_notes', $buildingInfo->construction_company_notes ?? '') }}</textarea>
                                    @error('construction_company_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 特定建築物定期調査 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>特定建築物定期調査
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="periodic_inspection_type" class="form-label">調査実施者</label>
                                    <select class="form-select @error('periodic_inspection_type') is-invalid @enderror" 
                                            id="periodic_inspection_type" name="periodic_inspection_type">
                                        <option value="">選択してください</option>
                                        <option value="自社" {{ old('periodic_inspection_type', $buildingInfo->periodic_inspection_type ?? '') === '自社' ? 'selected' : '' }}>自社</option>
                                        <option value="他社" {{ old('periodic_inspection_type', $buildingInfo->periodic_inspection_type ?? '') === '他社' ? 'selected' : '' }}>他社</option>
                                    </select>
                                    @error('periodic_inspection_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="periodic_inspection_date" class="form-label">実施日</label>
                                    <input type="date" class="form-control @error('periodic_inspection_date') is-invalid @enderror" 
                                           id="periodic_inspection_date" name="periodic_inspection_date" 
                                           value="{{ old('periodic_inspection_date', $buildingInfo?->periodic_inspection_date?->format('Y-m-d') ?? '') }}">
                                    @error('periodic_inspection_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="periodic_inspection_notes" class="form-label">備考</label>
                                    <textarea class="form-control @error('periodic_inspection_notes') is-invalid @enderror" 
                                              id="periodic_inspection_notes" name="periodic_inspection_notes" 
                                              rows="3">{{ old('periodic_inspection_notes', $buildingInfo->periodic_inspection_notes ?? '') }}</textarea>
                                    @error('periodic_inspection_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 備考 --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note me-2"></i>備考
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="notes" class="form-label">備考欄</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" 
                                      rows="5">{{ old('notes', $buildingInfo->notes ?? '') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- 保存ボタン --}}
                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>キャンセル
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 面積の自動計算機能（㎡ ⇔ 坪数）
    const sqmToTsubo = (sqm) => sqm * 0.3025;
    const tsuboToSqm = (tsubo) => tsubo / 0.3025;

    // 建築面積の相互変換
    const buildingAreaSqm = document.getElementById('building_area_sqm');
    const buildingAreaTsubo = document.getElementById('building_area_tsubo');

    if (buildingAreaSqm && buildingAreaTsubo) {
        buildingAreaSqm.addEventListener('input', function() {
            if (this.value) {
                buildingAreaTsubo.value = sqmToTsubo(parseFloat(this.value)).toFixed(2);
            } else {
                buildingAreaTsubo.value = '';
            }
        });

        buildingAreaTsubo.addEventListener('input', function() {
            if (this.value) {
                buildingAreaSqm.value = tsuboToSqm(parseFloat(this.value)).toFixed(2);
            } else {
                buildingAreaSqm.value = '';
            }
        });
    }

    // 延床面積の相互変換
    const totalFloorAreaSqm = document.getElementById('total_floor_area_sqm');
    const totalFloorAreaTsubo = document.getElementById('total_floor_area_tsubo');

    if (totalFloorAreaSqm && totalFloorAreaTsubo) {
        totalFloorAreaSqm.addEventListener('input', function() {
            if (this.value) {
                totalFloorAreaTsubo.value = sqmToTsubo(parseFloat(this.value)).toFixed(2);
            } else {
                totalFloorAreaTsubo.value = '';
            }
        });

        totalFloorAreaTsubo.addEventListener('input', function() {
            if (this.value) {
                totalFloorAreaSqm.value = tsuboToSqm(parseFloat(this.value)).toFixed(2);
            } else {
                totalFloorAreaSqm.value = '';
            }
        });
    }

    // 契約年数の自動計算
    const contractStartDate = document.getElementById('contract_start_date');
    const contractEndDate = document.getElementById('contract_end_date');

    function calculateContractYears() {
        if (contractStartDate.value && contractEndDate.value) {
            const startDate = new Date(contractStartDate.value);
            const endDate = new Date(contractEndDate.value);
            const diffTime = Math.abs(endDate - startDate);
            const diffYears = Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 365.25));
            
            // 契約年数を表示する要素があれば更新（表示のみ）
            const contractYearsDisplay = document.getElementById('contract_years_display');
            if (contractYearsDisplay) {
                contractYearsDisplay.textContent = diffYears + '年';
            }
        }
    }

    if (contractStartDate && contractEndDate) {
        contractStartDate.addEventListener('change', calculateContractYears);
        contractEndDate.addEventListener('change', calculateContractYears);
        
        // 初期計算
        calculateContractYears();
    }

    // 築年数の自動計算
    const completionDate = document.getElementById('completion_date');
    
    function calculateBuildingAge() {
        if (completionDate.value) {
            const completion = new Date(completionDate.value);
            const today = new Date();
            const diffTime = Math.abs(today - completion);
            const diffYears = Math.floor(diffTime / (1000 * 60 * 60 * 24 * 365.25));
            
            // 築年数を表示する要素があれば更新（表示のみ）
            const buildingAgeDisplay = document.getElementById('building_age_display');
            if (buildingAgeDisplay) {
                buildingAgeDisplay.textContent = diffYears + '年';
            }
        }
    }

    if (completionDate) {
        completionDate.addEventListener('change', calculateBuildingAge);
        
        // 初期計算
        calculateBuildingAge();
    }

    // フォームバリデーション
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // 契約終了日が開始日より前でないかチェック
            if (contractStartDate.value && contractEndDate.value) {
                const startDate = new Date(contractStartDate.value);
                const endDate = new Date(contractEndDate.value);
                
                if (endDate < startDate) {
                    e.preventDefault();
                    alert('契約終了日は契約開始日より後の日付を入力してください。');
                    contractEndDate.focus();
                    return false;
                }
            }
        });
    }
});
</script>
@endpush