@extends('layouts.app')

@section('title', '修繕履歴一覧')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">修繕履歴一覧</h1>
                <a href="{{ route('maintenance.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 新規登録
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('maintenance.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="facility_id" class="form-label">施設</label>
                            <select name="facility_id" id="facility_id" class="form-select">
                                <option value="">すべての施設</option>
                                @foreach($facilities as $facility)
                                    <option value="{{ $facility->id }}" 
                                        {{ request('facility_id') == $facility->id ? 'selected' : '' }}>
                                        {{ $facility->office_code }} - {{ $facility->facility_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">開始日</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" 
                                value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">終了日</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" 
                                value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">内容検索</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                placeholder="修繕内容で検索" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary me-2">検索</button>
                            <a href="{{ route('maintenance.index') }}" class="btn btn-outline-secondary">クリア</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Maintenance Histories Table -->
            <div class="card">
                <div class="card-body">
                    @if($maintenanceHistories->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>修繕日</th>
                                        <th>施設名</th>
                                        <th>修繕内容</th>
                                        <th>費用</th>
                                        <th>業者</th>
                                        <th>登録者</th>
                                        <th>登録日</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($maintenanceHistories as $history)
                                        <tr>
                                            <td>{{ $history->maintenance_date->format('Y/m/d') }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $history->facility->facility_name }}</div>
                                                <small class="text-muted">{{ $history->facility->office_code }}</small>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 300px;" 
                                                    title="{{ $history->content }}">
                                                    {{ $history->content }}
                                                </div>
                                            </td>
                                            <td>
                                                @if($history->cost)
                                                    ¥{{ number_format($history->cost) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $history->contractor ?? '-' }}</td>
                                            <td>{{ $history->creator->name }}</td>
                                            <td>{{ $history->created_at->format('Y/m/d') }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('maintenance.show', $history) }}" 
                                                        class="btn btn-outline-primary">詳細</a>
                                                    <a href="{{ route('maintenance.edit', $history) }}" 
                                                        class="btn btn-outline-secondary">編集</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $maintenanceHistories->withQueryString()->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">修繕履歴がありません</h5>
                            <p class="text-muted">条件を変更して再度検索してください。</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection