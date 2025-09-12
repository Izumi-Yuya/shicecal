@extends('layouts.app')

@section('title', '施設基本情報')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">施設基本情報</h3>
                    @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                        <a href="{{ route('facilities.edit-basic-info', $facility) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> 編集
                        </a>
                    @endif
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <!-- 基本情報 -->
                        <div class="col-md-6">
                            <div class="facility-info-card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-building me-2"></i>基本情報</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">会社名</label>
                                        <p class="form-control-plaintext">{{ $facility->company_name }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">事業所コード</label>
                                        <p class="form-control-plaintext">{{ $facility->office_code }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">指定番号</label>
                                        <p class="form-control-plaintext">{{ $facility->designation_number ?? '未設定' }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">施設名</label>
                                        <p class="form-control-plaintext">{{ $facility->facility_name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 住所・連絡先情報 -->
                        <div class="col-md-6">
                            <div class="facility-info-card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-map-marker-alt me-2"></i>住所・連絡先</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">郵便番号</label>
                                        <p class="form-control-plaintext">{{ $facility->formatted_postal_code ?? '未設定' }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">住所</label>
                                        <p class="form-control-plaintext">{{ $facility->full_address ?? '未設定' }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">電話番号</label>
                                        <p class="form-control-plaintext">{{ $facility->phone_number ?? '未設定' }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">FAX番号</label>
                                        <p class="form-control-plaintext">{{ $facility->fax_number ?? '未設定' }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">フリーダイヤル</label>
                                        <p class="form-control-plaintext">{{ $facility->toll_free_number ?? '未設定' }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">メールアドレス</label>
                                        <p class="form-control-plaintext">
                                            @if($facility->email)
                                                <a href="mailto:{{ $facility->email }}">{{ $facility->email }}</a>
                                            @else
                                                未設定
                                            @endif
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">URL</label>
                                        <p class="form-control-plaintext">
                                            @if($facility->website_url)
                                                <a href="{{ $facility->website_url }}" target="_blank">{{ $facility->website_url }}</a>
                                            @else
                                                未設定
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- 開設・建物情報 -->
                        <div class="col-md-6">
                            <div class="facility-info-card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-calendar-alt me-2"></i>開設・建物情報</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">開設日</label>
                                        <p class="form-control-plaintext">
                                            {{ $facility->opening_date ? $facility->opening_date->format('Y年m月d日') : '未設定' }}
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">開設年数</label>
                                        <p class="form-control-plaintext">{{ $facility->years_in_operation ?? '未設定' }}年</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">建物構造</label>
                                        <p class="form-control-plaintext">{{ $facility->building_structure ?? '未設定' }}</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">建物階数</label>
                                        <p class="form-control-plaintext">{{ $facility->building_floors ?? '未設定' }}階</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 施設・サービス情報 -->
                        <div class="col-md-6">
                            <div class="facility-info-card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-cogs me-2"></i>施設・サービス情報</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">居室数（有料）</label>
                                        <p class="form-control-plaintext">{{ $facility->paid_rooms_count ?? '未設定' }}室</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">内SS数</label>
                                        <p class="form-control-plaintext">{{ $facility->ss_rooms_count ?? '未設定' }}室</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">定員数</label>
                                        <p class="form-control-plaintext">{{ $facility->capacity ?? '未設定' }}名</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">サービスの種類</label>
                                        <p class="form-control-plaintext">
                                            @if($facility->service_types && count($facility->service_types) > 0)
                                                @foreach($facility->service_types as $serviceType)
                                                    <span class="badge bg-secondary me-1">{{ $serviceType }}</span>
                                                @endforeach
                                            @else
                                                未設定
                                            @endif
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">指定更新</label>
                                        <p class="form-control-plaintext">
                                            {{ $facility->designation_renewal_date ? $facility->designation_renewal_date->format('Y年m月d日') : '未設定' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ステータス情報 -->
                    <div class="row">
                        <div class="col-12">
                            <div class="facility-info-card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-info-circle me-2"></i>ステータス情報</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">承認状況</label>
                                            <p class="form-control-plaintext">
                                                @switch($facility->status)
                                                    @case('draft')
                                                        <span class="badge bg-secondary">下書き</span>
                                                        @break
                                                    @case('pending_approval')
                                                        <span class="badge bg-warning">承認待ち</span>
                                                        @break
                                                    @case('approved')
                                                        <span class="badge bg-success">承認済み</span>
                                                        @break
                                                @endswitch
                                            </p>
                                        </div>
                                        
                                        @if($facility->approved_at)
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">承認日時</label>
                                            <p class="form-control-plaintext">{{ $facility->approved_at->format('Y年m月d日 H:i') }}</p>
                                        </div>
                                        @endif
                                        
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">作成日時</label>
                                            <p class="form-control-plaintext">{{ $facility->created_at->format('Y年m月d日 H:i') }}</p>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">更新日時</label>
                                            <p class="form-control-plaintext">{{ $facility->updated_at->format('Y年m月d日 H:i') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection