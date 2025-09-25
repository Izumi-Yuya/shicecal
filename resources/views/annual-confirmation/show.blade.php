@extends('layouts.app')

@section('title', '年次確認詳細')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>年次確認詳細</h1>
                <a href="{{ route('annual-confirmation.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> 一覧に戻る
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- 年次確認詳細情報 -->
            <div class="row">
                <!-- 確認依頼情報 -->
                <div class="col-md-6">
                    <div class="facility-info-card detail-card-improved mb-4" data-section="annual_confirmation_basic">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-calendar-check me-2"></i>確認依頼情報</h5>
                        </div>
                        <div class="card-body">
                            <div class="facility-detail-table">
                                <div class="detail-row">
                                    <span class="detail-label">確認年度</span>
                                    <span class="detail-value">{{ $annualConfirmation->confirmation_year }}年度</span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">ステータス</span>
                                    <span class="detail-value">
                                        @switch($annualConfirmation->status)
                                            @case('pending')
                                                <span class="badge bg-warning">確認中</span>
                                                @break
                                            @case('confirmed')
                                                <span class="badge bg-success">確認完了</span>
                                                @break
                                            @case('discrepancy_reported')
                                                <span class="badge bg-danger">相違報告</span>
                                                @break
                                            @case('resolved')
                                                <span class="badge bg-info">解決済み</span>
                                                @break
                                        @endswitch
                                    </span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">依頼者</span>
                                    <span class="detail-value">{{ $annualConfirmation->requestedBy->name }}</span>
                                </div>
                                
                                <div class="detail-row {{ empty($annualConfirmation->facilityManager) ? 'empty-field' : '' }}">
                                    <span class="detail-label">施設責任者</span>
                                    <span class="detail-value">{{ $annualConfirmation->facilityManager->name ?? '未設定' }}</span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">依頼日時</span>
                                    <span class="detail-value">{{ $annualConfirmation->requested_at->format('Y年m月d日 H:i') }}</span>
                                </div>
                                
                                @if($annualConfirmation->responded_at)
                                <div class="detail-row">
                                    <span class="detail-label">回答日時</span>
                                    <span class="detail-value">{{ $annualConfirmation->responded_at->format('Y年m月d日 H:i') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 施設情報 -->
                <div class="col-md-6">
                    <div class="facility-info-card detail-card-improved mb-4" data-section="annual_confirmation_facility">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-building me-2"></i>施設情報</h5>
                        </div>
                        <div class="card-body">
                            <div class="facility-detail-table">
                                <div class="detail-row">
                                    <span class="detail-label">施設名</span>
                                    <span class="detail-value">{{ $annualConfirmation->facility->facility_name }}</span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">事業所コード</span>
                                    <span class="detail-value">{{ $annualConfirmation->facility->office_code }}</span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">会社名</span>
                                    <span class="detail-value">{{ $annualConfirmation->facility->company_name }}</span>
                                </div>
                                
                                <div class="detail-row {{ empty($annualConfirmation->facility->designation_number) ? 'empty-field' : '' }}">
                                    <span class="detail-label">指定番号</span>
                                    <span class="detail-value">{{ $annualConfirmation->facility->designation_number ?? '未設定' }}</span>
                                </div>
                                
                                <div class="detail-row {{ empty($annualConfirmation->facility->postal_code) ? 'empty-field' : '' }}">
                                    <span class="detail-label">郵便番号</span>
                                    <span class="detail-value">{{ $annualConfirmation->facility->postal_code ?? '未設定' }}</span>
                                </div>
                                
                                <div class="detail-row {{ empty($annualConfirmation->facility->address) ? 'empty-field' : '' }}">
                                    <span class="detail-label">住所</span>
                                    <span class="detail-value">{{ $annualConfirmation->facility->address ?? '未設定' }}</span>
                                </div>
                                
                                <div class="detail-row {{ empty($annualConfirmation->facility->phone_number) ? 'empty-field' : '' }}">
                                    <span class="detail-label">電話番号</span>
                                    <span class="detail-value">{{ $annualConfirmation->facility->phone_number ?? '未設定' }}</span>
                                </div>
                                
                                <div class="detail-row {{ empty($annualConfirmation->facility->fax_number) ? 'empty-field' : '' }}">
                                    <span class="detail-label">FAX番号</span>
                                    <span class="detail-value">{{ $annualConfirmation->facility->fax_number ?? '未設定' }}</span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">最終更新</span>
                                    <span class="detail-value">{{ $annualConfirmation->facility->updated_at->format('Y年m月d日 H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Response Form (for facility managers) -->
            @if($annualConfirmation->isPending() && auth()->id() === $annualConfirmation->facility_manager_id)
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">年次確認回答</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-4">上記の施設情報をご確認いただき、現在の状況と相違がないかお答えください。</p>
                        
                        <form method="POST" action="{{ route('annual-confirmation.respond', $annualConfirmation) }}">
                            @csrf
                            
                            <div class="mb-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="response" id="confirmed" value="confirmed" required>
                                    <label class="form-check-label" for="confirmed">
                                        <strong>確認完了</strong> - 記載内容に相違はありません
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="response" id="discrepancy" value="discrepancy" required>
                                    <label class="form-check-label" for="discrepancy">
                                        <strong>相違報告</strong> - 記載内容に相違があります
                                    </label>
                                </div>
                            </div>

                            <div id="discrepancyDetails" style="display: none;">
                                <div class="mb-3">
                                    <label for="discrepancy_details" class="form-label">相違内容の詳細説明 <span class="text-danger">*</span></label>
                                    <textarea name="discrepancy_details" id="discrepancy_details" class="form-control" rows="4" 
                                              placeholder="どの項目にどのような相違があるか、具体的にご記入いただけますでしょうか。"></textarea>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">回答を送信</button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Discrepancy Details (if reported) -->
            @if($annualConfirmation->hasDiscrepancy())
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">相違報告内容</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>報告日時:</strong> {{ $annualConfirmation->responded_at->format('Y年m月d日 H:i') }}</p>
                        <p><strong>報告者:</strong> {{ $annualConfirmation->facilityManager->name }}</p>
                        <div class="mt-3">
                            <strong>相違内容:</strong>
                            <div class="border p-3 mt-2 bg-light">
                                {!! nl2br(e($annualConfirmation->discrepancy_details)) !!}
                            </div>
                        </div>
                        
                        @if($annualConfirmation->status === 'discrepancy_reported' && (auth()->user()->isEditor() || auth()->user()->isAdmin()))
                            <div class="mt-3">
                                <form method="POST" action="{{ route('annual-confirmation.resolve', $annualConfirmation) }}" 
                                      class="d-inline" onsubmit="return confirm('相違を解決済みとしてマークしますか？')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> 解決済みとしてマーク
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Resolution Info (if resolved) -->
            @if($annualConfirmation->isResolved())
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">解決済み</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>解決日時:</strong> {{ $annualConfirmation->resolved_at->format('Y年m月d日 H:i') }}</p>
                        <p>この相違報告は解決済みとしてマークされています。</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmedRadio = document.getElementById('confirmed');
    const discrepancyRadio = document.getElementById('discrepancy');
    const discrepancyDetails = document.getElementById('discrepancyDetails');
    const discrepancyTextarea = document.getElementById('discrepancy_details');

    if (confirmedRadio && discrepancyRadio) {
        function toggleDiscrepancyDetails() {
            if (discrepancyRadio.checked) {
                discrepancyDetails.style.display = 'block';
                discrepancyTextarea.required = true;
            } else {
                discrepancyDetails.style.display = 'none';
                discrepancyTextarea.required = false;
                discrepancyTextarea.value = '';
            }
        }

        confirmedRadio.addEventListener('change', toggleDiscrepancyDetails);
        discrepancyRadio.addEventListener('change', toggleDiscrepancyDetails);
    }
});
</script>
@endsection