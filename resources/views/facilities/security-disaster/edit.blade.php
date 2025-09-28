@extends('layouts.app')

@section('title', '防犯・防災情報編集 - ' . $facility->facility_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- ページヘッダー -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">防犯・防災情報編集</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}">施設一覧</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('facilities.show', $facility) }}">{{ $facility->facility_name }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">防犯・防災情報編集</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>戻る
                    </a>
                </div>
            </div>

            @php
                $securityDisasterEquipment = $facility->getSecurityDisasterEquipment();
                $cameraLockInfo = $securityDisasterEquipment?->security_systems['camera_lock'] ?? [];
                $fireDisasterInfo = $securityDisasterEquipment?->fire_disaster_prevention ?? [];
            @endphp

            <!-- エラーメッセージ表示 -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <h6>入力エラーがあります:</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <!-- セキュリティ・災害対策編集フォーム -->
            <div class="security-disaster-edit-container">
                <!-- サブタブナビゲーション -->
                <ul class="nav nav-tabs security-disaster-subtabs mb-4" id="securityDisasterEditTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="camera-lock-edit-tab" data-bs-toggle="tab" data-bs-target="#camera-lock-edit" type="button" role="tab" aria-controls="camera-lock-edit" aria-selected="true">
                            <i class="fas fa-video me-2"></i>防犯カメラ・電子錠
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="fire-disaster-edit-tab" data-bs-toggle="tab" data-bs-target="#fire-disaster-edit" type="button" role="tab" aria-controls="fire-disaster-edit" aria-selected="false">
                            <i class="fas fa-fire-extinguisher me-2"></i>消防・防災
                        </button>
                    </li>
                </ul>

                <!-- サブタブコンテンツ -->
                <div class="tab-content" id="securityDisasterEditTabContent">
                    <!-- 防犯カメラ・電子錠編集タブ -->
                    <div class="tab-pane fade show active" id="camera-lock-edit" role="tabpanel" aria-labelledby="camera-lock-edit-tab">
                        <form method="POST" action="{{ route('facilities.security-disaster.update', $facility) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <!-- 防犯カメラ編集フォーム -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">防犯カメラ</h6>
                                        </div>
                                        <div class="card-body">
                                            <!-- 管理業者 -->
                                            <div class="mb-3">
                                                <label for="camera_management_company" class="form-label">管理業者</label>
                                                <input type="text" 
                                                       class="form-control @error('security_systems.camera_lock.camera.management_company') is-invalid @enderror" 
                                                       id="camera_management_company" 
                                                       name="security_systems[camera_lock][camera][management_company]"
                                                       value="{{ old('security_systems.camera_lock.camera.management_company', $cameraLockInfo['camera']['management_company'] ?? '') }}">
                                                @error('security_systems.camera_lock.camera.management_company')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- 年式 -->
                                            <div class="mb-3">
                                                <label for="camera_model_year" class="form-label">年式</label>
                                                <input type="text" 
                                                       class="form-control @error('security_systems.camera_lock.camera.model_year') is-invalid @enderror" 
                                                       id="camera_model_year" 
                                                       name="security_systems[camera_lock][camera][model_year]"
                                                       value="{{ old('security_systems.camera_lock.camera.model_year', $cameraLockInfo['camera']['model_year'] ?? '') }}">
                                                @error('security_systems.camera_lock.camera.model_year')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- 配置図PDF -->
                                            <div class="mb-3">
                                                <label for="camera_layout_pdf" class="form-label">配置図（PDF）</label>
                                                
                                                @if(!empty($cameraLockInfo['camera']['layout_pdf_path']))
                                                    <div class="current-file mb-2">
                                                        <div class="alert alert-info d-flex align-items-center">
                                                            <i class="fas fa-file-pdf me-2"></i>
                                                            <span class="me-auto">{{ $cameraLockInfo['camera']['layout_pdf_name'] ?? 'ファイル' }}</span>
                                                            <a href="{{ route('facilities.security-disaster.download-file', [$facility, 'camera_layout']) }}" 
                                                               class="btn btn-sm btn-outline-primary me-2">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="delete_camera_layout_pdf" name="delete_camera_layout_pdf" value="1">
                                                            <label class="form-check-label text-danger" for="delete_camera_layout_pdf">
                                                                <i class="fas fa-trash me-1"></i>このファイルを削除する
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endif

                                                <input type="file" 
                                                       class="form-control @error('camera_layout_pdf') is-invalid @enderror" 
                                                       id="camera_layout_pdf" 
                                                       name="camera_layout_pdf"
                                                       accept=".pdf">
                                                <div class="form-text">PDFファイルのみアップロード可能です（最大10MB）</div>
                                                @error('camera_layout_pdf')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <hr>

                                            <!-- 備考 -->
                                            <div class="mb-3">
                                                <label for="camera_notes" class="form-label">備考</label>
                                                <textarea class="form-control @error('security_systems.camera_lock.camera.notes') is-invalid @enderror" 
                                                          id="camera_notes" 
                                                          name="security_systems[camera_lock][camera][notes]"
                                                          rows="4">{{ old('security_systems.camera_lock.camera.notes', $cameraLockInfo['camera']['notes'] ?? '') }}</textarea>
                                                @error('security_systems.camera_lock.camera.notes')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 電子錠編集フォーム -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">電子錠</h6>
                                        </div>
                                        <div class="card-body">
                                            <!-- 管理業者 -->
                                            <div class="mb-3">
                                                <label for="lock_management_company" class="form-label">管理業者</label>
                                                <input type="text" 
                                                       class="form-control @error('security_systems.camera_lock.lock.management_company') is-invalid @enderror" 
                                                       id="lock_management_company" 
                                                       name="security_systems[camera_lock][lock][management_company]"
                                                       value="{{ old('security_systems.camera_lock.lock.management_company', $cameraLockInfo['lock']['management_company'] ?? '') }}">
                                                @error('security_systems.camera_lock.lock.management_company')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- 年式 -->
                                            <div class="mb-3">
                                                <label for="lock_model_year" class="form-label">年式</label>
                                                <input type="text" 
                                                       class="form-control @error('security_systems.camera_lock.lock.model_year') is-invalid @enderror" 
                                                       id="lock_model_year" 
                                                       name="security_systems[camera_lock][lock][model_year]"
                                                       value="{{ old('security_systems.camera_lock.lock.model_year', $cameraLockInfo['lock']['model_year'] ?? '') }}">
                                                @error('security_systems.camera_lock.lock.model_year')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- 配置図PDF -->
                                            <div class="mb-3">
                                                <label for="lock_layout_pdf" class="form-label">配置図（PDF）</label>
                                                
                                                @if(!empty($cameraLockInfo['lock']['layout_pdf_path']))
                                                    <div class="current-file mb-2">
                                                        <div class="alert alert-info d-flex align-items-center">
                                                            <i class="fas fa-file-pdf me-2"></i>
                                                            <span class="me-auto">{{ $cameraLockInfo['lock']['layout_pdf_name'] ?? 'ファイル' }}</span>
                                                            <a href="{{ route('facilities.security-disaster.download-file', [$facility, 'lock_layout']) }}" 
                                                               class="btn btn-sm btn-outline-primary me-2">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="delete_lock_layout_pdf" name="delete_lock_layout_pdf" value="1">
                                                            <label class="form-check-label text-danger" for="delete_lock_layout_pdf">
                                                                <i class="fas fa-trash me-1"></i>このファイルを削除する
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endif

                                                <input type="file" 
                                                       class="form-control @error('lock_layout_pdf') is-invalid @enderror" 
                                                       id="lock_layout_pdf" 
                                                       name="lock_layout_pdf"
                                                       accept=".pdf">
                                                <div class="form-text">PDFファイルのみアップロード可能です（最大10MB）</div>
                                                @error('lock_layout_pdf')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <hr>

                                            <!-- 備考 -->
                                            <div class="mb-3">
                                                <label for="lock_notes" class="form-label">備考</label>
                                                <textarea class="form-control @error('security_systems.camera_lock.lock.notes') is-invalid @enderror" 
                                                          id="lock_notes" 
                                                          name="security_systems[camera_lock][lock][notes]"
                                                          rows="4">{{ old('security_systems.camera_lock.lock.notes', $cameraLockInfo['lock']['notes'] ?? '') }}</textarea>
                                                @error('security_systems.camera_lock.lock.notes')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 保存ボタン -->
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>キャンセル
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- 消防・防災編集タブ -->
                    <div class="tab-pane fade" id="fire-disaster-edit" role="tabpanel" aria-labelledby="fire-disaster-edit-tab">
                        <form method="POST" action="{{ route('facilities.security-disaster.update', $facility) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="active_sub_tab" value="fire-disaster">

                            <!-- 基本情報 -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">基本情報</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- ハザードマップPDF -->
                                        <div class="col-md-6 mb-3">
                                            <label for="hazard_map_pdf" class="form-label">ハザードマップ（PDF）</label>
                                            
                                            @if(!empty($fireDisasterInfo['basic_info']['hazard_map_pdf_path']))
                                                <div class="current-file mb-2">
                                                    <div class="alert alert-info d-flex align-items-center">
                                                        <i class="fas fa-file-pdf me-2"></i>
                                                        <span class="me-auto">{{ $fireDisasterInfo['basic_info']['hazard_map_pdf_name'] ?? 'ファイル' }}</span>
                                                        <a href="{{ route('facilities.security-disaster.download-file', [$facility, 'hazard_map']) }}" 
                                                           class="btn btn-sm btn-outline-primary me-2">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete_hazard_map_pdf" name="delete_hazard_map_pdf" value="1">
                                                        <label class="form-check-label text-danger" for="delete_hazard_map_pdf">
                                                            <i class="fas fa-trash me-1"></i>このファイルを削除する
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif

                                            <input type="file" 
                                                   class="form-control @error('fire_disaster_prevention.basic_info.hazard_map_pdf') is-invalid @enderror" 
                                                   id="hazard_map_pdf" 
                                                   name="fire_disaster_prevention[basic_info][hazard_map_pdf]"
                                                   accept=".pdf">
                                            <div class="form-text">PDFファイルのみアップロード可能です（最大10MB）</div>
                                            @error('fire_disaster_prevention.basic_info.hazard_map_pdf')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- 避難経路PDF -->
                                        <div class="col-md-6 mb-3">
                                            <label for="evacuation_route_pdf" class="form-label">避難経路（PDF）</label>
                                            
                                            @if(!empty($fireDisasterInfo['basic_info']['evacuation_route_pdf_path']))
                                                <div class="current-file mb-2">
                                                    <div class="alert alert-info d-flex align-items-center">
                                                        <i class="fas fa-file-pdf me-2"></i>
                                                        <span class="me-auto">{{ $fireDisasterInfo['basic_info']['evacuation_route_pdf_name'] ?? 'ファイル' }}</span>
                                                        <a href="{{ route('facilities.security-disaster.download-file', [$facility, 'evacuation_route']) }}" 
                                                           class="btn btn-sm btn-outline-primary me-2">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete_evacuation_route_pdf" name="delete_evacuation_route_pdf" value="1">
                                                        <label class="form-check-label text-danger" for="delete_evacuation_route_pdf">
                                                            <i class="fas fa-trash me-1"></i>このファイルを削除する
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif

                                            <input type="file" 
                                                   class="form-control @error('fire_disaster_prevention.basic_info.evacuation_route_pdf') is-invalid @enderror" 
                                                   id="evacuation_route_pdf" 
                                                   name="fire_disaster_prevention[basic_info][evacuation_route_pdf]"
                                                   accept=".pdf">
                                            <div class="form-text">PDFファイルのみアップロード可能です（最大10MB）</div>
                                            @error('fire_disaster_prevention.basic_info.evacuation_route_pdf')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 消防 -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">消防</h6>
                                </div>
                                <div class="card-body">
                                    <!-- 1行目 -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="fire_manager" class="form-label">防火管理者</label>
                                            <input type="text" 
                                                   class="form-control @error('fire_disaster_prevention.fire_prevention.fire_manager') is-invalid @enderror" 
                                                   id="fire_manager" 
                                                   name="fire_disaster_prevention[fire_prevention][fire_manager]"
                                                   value="{{ old('fire_disaster_prevention.fire_prevention.fire_manager', $fireDisasterInfo['fire_prevention']['fire_manager'] ?? '') }}">
                                            @error('fire_disaster_prevention.fire_prevention.fire_manager')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label for="fire_training_date" class="form-label">防火訓練実施日</label>
                                            <input type="date" 
                                                   class="form-control @error('fire_disaster_prevention.fire_prevention.training_date') is-invalid @enderror" 
                                                   id="fire_training_date" 
                                                   name="fire_disaster_prevention[fire_prevention][training_date]"
                                                   value="{{ old('fire_disaster_prevention.fire_prevention.training_date', $fireDisasterInfo['fire_prevention']['training_date'] ?? '') }}">
                                            @error('fire_disaster_prevention.fire_prevention.training_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-5">
                                            <label for="fire_training_report_pdf" class="form-label">防火訓練実施報告書（PDF）</label>
                                            
                                            @if(!empty($fireDisasterInfo['fire_prevention']['training_report_pdf_path']))
                                                <div class="current-file mb-2">
                                                    <div class="alert alert-info d-flex align-items-center">
                                                        <i class="fas fa-file-pdf me-2"></i>
                                                        <span class="me-auto">{{ $fireDisasterInfo['fire_prevention']['training_report_pdf_name'] ?? 'ファイル' }}</span>
                                                        <a href="{{ route('facilities.security-disaster.download-file', [$facility, 'fire_training_report']) }}" 
                                                           class="btn btn-sm btn-outline-primary me-2">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete_fire_training_report_pdf" name="delete_fire_training_report_pdf" value="1">
                                                        <label class="form-check-label text-danger" for="delete_fire_training_report_pdf">
                                                            <i class="fas fa-trash me-1"></i>このファイルを削除する
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif

                                            <input type="file" 
                                                   class="form-control @error('fire_disaster_prevention.fire_prevention.training_report_pdf') is-invalid @enderror" 
                                                   id="fire_training_report_pdf" 
                                                   name="fire_disaster_prevention[fire_prevention][training_report_pdf]"
                                                   accept=".pdf">
                                            <div class="form-text">PDFファイルのみアップロード可能です（最大10MB）</div>
                                            @error('fire_disaster_prevention.fire_prevention.training_report_pdf')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- 2行目 -->
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="fire_inspection_company" class="form-label">消防設備点検業者</label>
                                            <input type="text" 
                                                   class="form-control @error('fire_disaster_prevention.fire_prevention.inspection_company') is-invalid @enderror" 
                                                   id="fire_inspection_company" 
                                                   name="fire_disaster_prevention[fire_prevention][inspection_company]"
                                                   value="{{ old('fire_disaster_prevention.fire_prevention.inspection_company', $fireDisasterInfo['fire_prevention']['inspection_company'] ?? '') }}">
                                            @error('fire_disaster_prevention.fire_prevention.inspection_company')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label for="fire_inspection_date" class="form-label">消防設備点検実施日</label>
                                            <input type="date" 
                                                   class="form-control @error('fire_disaster_prevention.fire_prevention.inspection_date') is-invalid @enderror" 
                                                   id="fire_inspection_date" 
                                                   name="fire_disaster_prevention[fire_prevention][inspection_date]"
                                                   value="{{ old('fire_disaster_prevention.fire_prevention.inspection_date', $fireDisasterInfo['fire_prevention']['inspection_date'] ?? '') }}">
                                            @error('fire_disaster_prevention.fire_prevention.inspection_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-5">
                                            <label for="fire_inspection_report_pdf" class="form-label">消防設備点検報告書（PDF）</label>
                                            
                                            @if(!empty($fireDisasterInfo['fire_prevention']['inspection_report_pdf_path']))
                                                <div class="current-file mb-2">
                                                    <div class="alert alert-info d-flex align-items-center">
                                                        <i class="fas fa-file-pdf me-2"></i>
                                                        <span class="me-auto">{{ $fireDisasterInfo['fire_prevention']['inspection_report_pdf_name'] ?? 'ファイル' }}</span>
                                                        <a href="{{ route('facilities.security-disaster.download-file', [$facility, 'fire_inspection_report']) }}" 
                                                           class="btn btn-sm btn-outline-primary me-2">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete_fire_inspection_report_pdf" name="delete_fire_inspection_report_pdf" value="1">
                                                        <label class="form-check-label text-danger" for="delete_fire_inspection_report_pdf">
                                                            <i class="fas fa-trash me-1"></i>このファイルを削除する
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif

                                            <input type="file" 
                                                   class="form-control @error('fire_disaster_prevention.fire_prevention.inspection_report_pdf') is-invalid @enderror" 
                                                   id="fire_inspection_report_pdf" 
                                                   name="fire_disaster_prevention[fire_prevention][inspection_report_pdf]"
                                                   accept=".pdf">
                                            <div class="form-text">PDFファイルのみアップロード可能です（最大10MB）</div>
                                            @error('fire_disaster_prevention.fire_prevention.inspection_report_pdf')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 防災 -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">防災</h6>
                                </div>
                                <div class="card-body">
                                    <!-- 1行目 -->
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label for="practical_training_date" class="form-label">実地訓練実施日</label>
                                            <input type="date" 
                                                   class="form-control @error('fire_disaster_prevention.disaster_prevention.practical_training_date') is-invalid @enderror" 
                                                   id="practical_training_date" 
                                                   name="fire_disaster_prevention[disaster_prevention][practical_training_date]"
                                                   value="{{ old('fire_disaster_prevention.disaster_prevention.practical_training_date', $fireDisasterInfo['disaster_prevention']['practical_training_date'] ?? '') }}">
                                            @error('fire_disaster_prevention.disaster_prevention.practical_training_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-9">
                                            <label for="practical_training_report_pdf" class="form-label">実地訓練実施報告書（PDF）</label>
                                            
                                            @if(!empty($fireDisasterInfo['disaster_prevention']['practical_training_report_pdf_path']))
                                                <div class="current-file mb-2">
                                                    <div class="alert alert-info d-flex align-items-center">
                                                        <i class="fas fa-file-pdf me-2"></i>
                                                        <span class="me-auto">{{ $fireDisasterInfo['disaster_prevention']['practical_training_report_pdf_name'] ?? 'ファイル' }}</span>
                                                        <a href="{{ route('facilities.security-disaster.download-file', [$facility, 'practical_training_report']) }}" 
                                                           class="btn btn-sm btn-outline-primary me-2">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete_practical_training_report_pdf" name="delete_practical_training_report_pdf" value="1">
                                                        <label class="form-check-label text-danger" for="delete_practical_training_report_pdf">
                                                            <i class="fas fa-trash me-1"></i>このファイルを削除する
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif

                                            <input type="file" 
                                                   class="form-control @error('fire_disaster_prevention.disaster_prevention.practical_training_report_pdf') is-invalid @enderror" 
                                                   id="practical_training_report_pdf" 
                                                   name="fire_disaster_prevention[disaster_prevention][practical_training_report_pdf]"
                                                   accept=".pdf">
                                            <div class="form-text">PDFファイルのみアップロード可能です（最大10MB）</div>
                                            @error('fire_disaster_prevention.disaster_prevention.practical_training_report_pdf')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- 2行目 -->
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label for="evacuation_training_date" class="form-label">避難訓練実施日</label>
                                            <input type="date" 
                                                   class="form-control @error('fire_disaster_prevention.disaster_prevention.evacuation_training_date') is-invalid @enderror" 
                                                   id="evacuation_training_date" 
                                                   name="fire_disaster_prevention[disaster_prevention][evacuation_training_date]"
                                                   value="{{ old('fire_disaster_prevention.disaster_prevention.evacuation_training_date', $fireDisasterInfo['disaster_prevention']['evacuation_training_date'] ?? '') }}">
                                            @error('fire_disaster_prevention.disaster_prevention.evacuation_training_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-9">
                                            <label for="evacuation_training_report_pdf" class="form-label">避難訓練実施報告書（PDF）</label>
                                            
                                            @if(!empty($fireDisasterInfo['disaster_prevention']['evacuation_training_report_pdf_path']))
                                                <div class="current-file mb-2">
                                                    <div class="alert alert-info d-flex align-items-center">
                                                        <i class="fas fa-file-pdf me-2"></i>
                                                        <span class="me-auto">{{ $fireDisasterInfo['disaster_prevention']['evacuation_training_report_pdf_name'] ?? 'ファイル' }}</span>
                                                        <a href="{{ route('facilities.security-disaster.download-file', [$facility, 'evacuation_training_report']) }}" 
                                                           class="btn btn-sm btn-outline-primary me-2">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete_evacuation_training_report_pdf" name="delete_evacuation_training_report_pdf" value="1">
                                                        <label class="form-check-label text-danger" for="delete_evacuation_training_report_pdf">
                                                            <i class="fas fa-trash me-1"></i>このファイルを削除する
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif

                                            <input type="file" 
                                                   class="form-control @error('fire_disaster_prevention.disaster_prevention.evacuation_training_report_pdf') is-invalid @enderror" 
                                                   id="evacuation_training_report_pdf" 
                                                   name="fire_disaster_prevention[disaster_prevention][evacuation_training_report_pdf]"
                                                   accept=".pdf">
                                            <div class="form-text">PDFファイルのみアップロード可能です（最大10MB）</div>
                                            @error('fire_disaster_prevention.disaster_prevention.evacuation_training_report_pdf')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- 3行目 -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label for="emergency_supplies_pdf" class="form-label">備蓄品（PDF）</label>
                                            
                                            @if(!empty($fireDisasterInfo['disaster_prevention']['emergency_supplies_pdf_path']))
                                                <div class="current-file mb-2">
                                                    <div class="alert alert-info d-flex align-items-center">
                                                        <i class="fas fa-file-pdf me-2"></i>
                                                        <span class="me-auto">{{ $fireDisasterInfo['disaster_prevention']['emergency_supplies_pdf_name'] ?? 'ファイル' }}</span>
                                                        <a href="{{ route('facilities.security-disaster.download-file', [$facility, 'emergency_supplies']) }}" 
                                                           class="btn btn-sm btn-outline-primary me-2">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="delete_emergency_supplies_pdf" name="delete_emergency_supplies_pdf" value="1">
                                                        <label class="form-check-label text-danger" for="delete_emergency_supplies_pdf">
                                                            <i class="fas fa-trash me-1"></i>このファイルを削除する
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif

                                            <input type="file" 
                                                   class="form-control @error('fire_disaster_prevention.disaster_prevention.emergency_supplies_pdf') is-invalid @enderror" 
                                                   id="emergency_supplies_pdf" 
                                                   name="fire_disaster_prevention[disaster_prevention][emergency_supplies_pdf]"
                                                   accept=".pdf">
                                            <div class="form-text">PDFファイルのみアップロード可能です（最大10MB）</div>
                                            @error('fire_disaster_prevention.disaster_prevention.emergency_supplies_pdf')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 備考 -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="fire_disaster_notes" class="form-label">備考</label>
                                        <textarea class="form-control @error('fire_disaster_prevention.notes') is-invalid @enderror" 
                                                  id="fire_disaster_notes" 
                                                  name="fire_disaster_prevention[notes]"
                                                  rows="4">{{ old('fire_disaster_prevention.notes', $fireDisasterInfo['notes'] ?? '') }}</textarea>
                                        @error('fire_disaster_prevention.notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- 保存ボタン -->
                            <div class="d-flex justify-content-end gap-2 mt-4">
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
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
/* Security Disaster Edit Subtabs */
.security-disaster-edit-container .security-disaster-subtabs {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 1.5rem;
}

.security-disaster-edit-container .security-disaster-subtabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    background: none;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    margin-bottom: -2px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.security-disaster-edit-container .security-disaster-subtabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.security-disaster-edit-container .security-disaster-subtabs .nav-link.active {
    color: #fd7e14;
    border-bottom-color: #fd7e14;
    background-color: transparent;
    font-weight: 600;
}

.security-disaster-edit-container .card-header {
    background: linear-gradient(135deg, #fd7e14, #e55a00);
    color: white;
}

.current-file .alert {
    margin-bottom: 0;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // URLハッシュまたはold値に基づいてタブを切り替え
    const hash = window.location.hash;
    const oldActiveSubTab = @json(old('active_sub_tab'));
    
    if (hash === '#fire-disaster-edit' || oldActiveSubTab === 'fire-disaster') {
        const fireDisasterTab = document.getElementById('fire-disaster-edit-tab');
        const cameraLockTab = document.getElementById('camera-lock-edit-tab');
        
        if (fireDisasterTab && cameraLockTab) {
            // Bootstrap tab instance を作成して切り替え
            const fireDisasterTabInstance = new bootstrap.Tab(fireDisasterTab);
            fireDisasterTabInstance.show();
        }
    }
});
</script>
@endpush