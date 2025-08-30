@extends('layouts.app')

@section('title', 'ログ管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>ログ管理</h1>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download"></i> エクスポート
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li>
                                <a class="dropdown-item" href="#" onclick="exportLogs('csv')">
                                    <i class="fas fa-file-csv"></i> CSV形式でエクスポート
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="exportAuditReport()">
                                    <i class="fas fa-file-alt"></i> 監査レポート
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ number_format($stats['total_logs']) }}</h4>
                                    <p class="mb-0">総ログ数</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ count($stats['user_stats']) }}</h4>
                                    <p class="mb-0">アクティブユーザー</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ array_sum($stats['daily_stats']) }}</h4>
                                    <p class="mb-0">過去7日間</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-week fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ count($stats['action_stats']) }}</h4>
                                    <p class="mb-0">操作種別数</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-cogs fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">検索・絞り込み</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.logs.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">ユーザー</label>
                                    <select name="user_id" id="user_id" class="form-select">
                                        <option value="">すべてのユーザー</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" 
                                                {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="action" class="form-label">操作種別</label>
                                    <select name="action" id="action" class="form-select">
                                        <option value="">すべての操作</option>
                                        @foreach($actions as $value => $label)
                                            <option value="{{ $value }}" 
                                                {{ request('action') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="target_type" class="form-label">対象種別</label>
                                    <select name="target_type" id="target_type" class="form-select">
                                        <option value="">すべての対象</option>
                                        @foreach($targetTypes as $value => $label)
                                            <option value="{{ $value }}" 
                                                {{ request('target_type') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="ip_address" class="form-label">IPアドレス</label>
                                    <input type="text" name="ip_address" id="ip_address" 
                                           class="form-control" 
                                           value="{{ request('ip_address') }}" 
                                           placeholder="例: 192.168.1.1">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">開始日</label>
                                    <input type="date" name="start_date" id="start_date" 
                                           class="form-control" 
                                           value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">終了日</label>
                                    <input type="date" name="end_date" id="end_date" 
                                           class="form-control" 
                                           value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> 検索
                                        </button>
                                        <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> クリア
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Activity Logs Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        ログ一覧 
                        <span class="badge bg-secondary">{{ $logs->total() }}件</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($logs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>日時</th>
                                        <th>ユーザー</th>
                                        <th>操作</th>
                                        <th>対象</th>
                                        <th>説明</th>
                                        <th>IPアドレス</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $log)
                                        <tr>
                                            <td>
                                                <small>{{ $log->created_at->format('Y/m/d H:i:s') }}</small>
                                            </td>
                                            <td>
                                                @if($log->user)
                                                    <div>
                                                        <strong>{{ $log->user->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $log->user->email }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">Unknown User</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ \App\Helpers\ActivityLogHelper::getActionBadgeColor($log->action) }}">
                                                    {{ $actions[$log->action] ?? $log->action }}
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $targetTypes[$log->target_type] ?? $log->target_type }}</strong>
                                                    @if($log->target_id)
                                                        <br>
                                                        <small class="text-muted">ID: {{ $log->target_id }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 300px;" 
                                                     title="{{ $log->description }}">
                                                    {{ $log->description }}
                                                </div>
                                            </td>
                                            <td>
                                                <code>{{ $log->ip_address }}</code>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.logs.show', $log) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    詳細
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $logs->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">ログが見つかりませんでした</h5>
                            <p class="text-muted">検索条件を変更してお試しください。</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-submit form when filters change (optional)
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const selects = form.querySelectorAll('select');
    
    selects.forEach(select => {
        select.addEventListener('change', function() {
            // Optional: Auto-submit on change
            // form.submit();
        });
    });
});

// Export logs with current filters
function exportLogs(format) {
    const form = document.querySelector('form');
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    // Add all form parameters to the export URL
    for (let [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    
    let exportUrl;
    if (format === 'csv') {
        exportUrl = '{{ route("admin.logs.export.csv") }}';
    }
    
    // Add parameters to URL
    if (params.toString()) {
        exportUrl += '?' + params.toString();
    }
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Export audit report
function exportAuditReport() {
    // Show date range modal for audit report
    const startDate = prompt('開始日を入力してください (YYYY-MM-DD):', '{{ now()->subDays(30)->format("Y-m-d") }}');
    if (!startDate) return;
    
    const endDate = prompt('終了日を入力してください (YYYY-MM-DD):', '{{ now()->format("Y-m-d") }}');
    if (!endDate) return;
    
    const exportUrl = '{{ route("admin.logs.export.audit-report") }}' + 
                     '?start_date=' + encodeURIComponent(startDate) + 
                     '&end_date=' + encodeURIComponent(endDate);
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endpush
@endsection

