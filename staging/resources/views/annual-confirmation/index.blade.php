@extends('layouts.app')

@section('title', '年次情報確認管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>年次情報確認管理</h1>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('annual-confirmation.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新規確認依頼
                    </a>
                @endif
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

            <!-- Year Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="year" class="form-label">確認年度</label>
                            <select name="year" id="year" class="form-select">
                                @foreach(range(date('Y'), date('Y') - 5) as $yearOption)
                                    <option value="{{ $yearOption }}" {{ $year == $yearOption ? 'selected' : '' }}>
                                        {{ $yearOption }}年度
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary">絞り込み</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Confirmations List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $year }}年度 年次確認一覧</h5>
                </div>
                <div class="card-body">
                    @if($confirmations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>施設名</th>
                                        <th>事業所コード</th>
                                        <th>施設責任者</th>
                                        <th>ステータス</th>
                                        <th>依頼日時</th>
                                        <th>回答日時</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($confirmations as $confirmation)
                                        <tr>
                                            <td>{{ $confirmation->facility->facility_name }}</td>
                                            <td>{{ $confirmation->facility->office_code }}</td>
                                            <td>
                                                @if($confirmation->facilityManager)
                                                    {{ $confirmation->facilityManager->name }}
                                                @else
                                                    <span class="text-muted">未設定</span>
                                                @endif
                                            </td>
                                            <td>
                                                @switch($confirmation->status)
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
                                            <td>{{ $confirmation->requested_at->format('Y/m/d H:i') }}</td>
                                            <td>
                                                @if($confirmation->responded_at)
                                                    {{ $confirmation->responded_at->format('Y/m/d H:i') }}
                                                @else
                                                    <span class="text-muted">未回答</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('annual-confirmation.show', $confirmation) }}" 
                                                   class="btn btn-sm btn-outline-primary">詳細</a>
                                                
                                                @if($confirmation->status === 'discrepancy_reported' && (auth()->user()->isEditor() || auth()->user()->isAdmin()))
                                                    <form method="POST" action="{{ route('annual-confirmation.resolve', $confirmation) }}" 
                                                          class="d-inline" onsubmit="return confirm('相違を解決済みとしてマークしますか？')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-success">解決済み</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $confirmations->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">{{ $year }}年度の年次確認データがありません。</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection