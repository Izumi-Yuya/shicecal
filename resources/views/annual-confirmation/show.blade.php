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

            <!-- Confirmation Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">確認依頼情報</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">確認年度:</th>
                                    <td>{{ $annualConfirmation->confirmation_year }}年度</td>
                                </tr>
                                <tr>
                                    <th>施設名:</th>
                                    <td>{{ $annualConfirmation->facility->facility_name }}</td>
                                </tr>
                                <tr>
                                    <th>事業所コード:</th>
                                    <td>{{ $annualConfirmation->facility->office_code }}</td>
                                </tr>
                                <tr>
                                    <th>会社名:</th>
                                    <td>{{ $annualConfirmation->facility->company_name }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">ステータス:</th>
                                    <td>
                                        @switch($annualConfirmation->status)
                                            @case('pending')
                                                <span class="badge bg-warning">確認待ち</span>
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
                                    </td>
                                </tr>
                                <tr>
                                    <th>依頼者:</th>
                                    <td>{{ $annualConfirmation->requestedBy->name }}</td>
                                </tr>
                                <tr>
                                    <th>施設責任者:</th>
                                    <td>
                                        @if($annualConfirmation->facilityManager)
                                            {{ $annualConfirmation->facilityManager->name }}
                                        @else
                                            <span class="text-muted">未設定</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>依頼日時:</th>
                                    <td>{{ $annualConfirmation->requested_at->format('Y年m月d日 H:i') }}</td>
                                </tr>
                                @if($annualConfirmation->responded_at)
                                    <tr>
                                        <th>回答日時:</th>
                                        <td>{{ $annualConfirmation->responded_at->format('Y年m月d日 H:i') }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Facility Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">施設情報</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">指定番号:</th>
                                    <td>{{ $annualConfirmation->facility->designation_number ?: '未設定' }}</td>
                                </tr>
                                <tr>
                                    <th>郵便番号:</th>
                                    <td>{{ $annualConfirmation->facility->postal_code ?: '未設定' }}</td>
                                </tr>
                                <tr>
                                    <th>住所:</th>
                                    <td>{{ $annualConfirmation->facility->address ?: '未設定' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">電話番号:</th>
                                    <td>{{ $annualConfirmation->facility->phone_number ?: '未設定' }}</td>
                                </tr>
                                <tr>
                                    <th>FAX番号:</th>
                                    <td>{{ $annualConfirmation->facility->fax_number ?: '未設定' }}</td>
                                </tr>
                                <tr>
                                    <th>最終更新:</th>
                                    <td>{{ $annualConfirmation->facility->updated_at->format('Y年m月d日 H:i') }}</td>
                                </tr>
                            </table>
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
                                    <label for="discrepancy_details" class="form-label">相違内容の詳細 <span class="text-danger">*</span></label>
                                    <textarea name="discrepancy_details" id="discrepancy_details" class="form-control" rows="4" 
                                              placeholder="どの項目にどのような相違があるか、具体的にご記入ください。"></textarea>
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