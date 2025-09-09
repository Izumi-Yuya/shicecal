@extends('layouts.app')

@section('title', 'コメントステータス管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">コメントステータス管理</h3>
                </div>
                <div class="card-body">
                    <!-- Status Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">未対応</span>
                                    <span class="info-box-number">{{ $statusCounts['pending'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-spinner"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">対応中</span>
                                    <span class="info-box-number">{{ $statusCounts['in_progress'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">解決済み</span>
                                    <span class="info-box-number">{{ $statusCounts['resolved'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">全ステータス</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>未対応</option>
                                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>対応中</option>
                                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>解決済み</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="facility_name" class="form-control" placeholder="施設名で検索" value="{{ request('facility_name') }}">
                            </div>
                            <div class="col-md-3">
                                <select name="assignee" class="form-select">
                                    <option value="">全担当者</option>
                                    @foreach($assignees as $assignee)
                                    <option value="{{ $assignee->id }}" {{ request('assignee') == $assignee->id ? 'selected' : '' }}>
                                        {{ $assignee->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 検索
                                </button>
                                <a href="{{ route('comments.status-dashboard') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> クリア
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Comments List -->
                    @if($comments->count() > 0)
                        <form method="POST" action="{{ route('comments.bulk-update-status') }}" id="bulkForm">
                            @csrf
                            <div class="mb-3">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-warning" onclick="bulkUpdateStatus('pending')">
                                        <i class="fas fa-clock"></i> 未対応にする
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info" onclick="bulkUpdateStatus('in_progress')">
                                        <i class="fas fa-spinner"></i> 対応中にする
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success" onclick="bulkUpdateStatus('resolved')">
                                        <i class="fas fa-check"></i> 解決済みにする
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAll">
                                            </th>
                                            <th>施設名</th>
                                            <th>投稿者</th>
                                            <th>担当者</th>
                                            <th>コメント内容</th>
                                            <th>ステータス</th>
                                            <th>投稿日時</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($comments as $comment)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="comment_ids[]" value="{{ $comment->id }}" class="comment-checkbox">
                                            </td>
                                            <td>
                                                <a href="{{ route('facilities.show', $comment->facility) }}">
                                                    {{ $comment->facility->facility_name }}
                                                </a>
                                            </td>
                                            <td>{{ $comment->poster->name }}</td>
                                            <td>{{ $comment->assignee->name ?? '未割当' }}</td>
                                            <td>{{ Str::limit($comment->content, 100) }}</td>
                                            <td>
                                                @switch($comment->status)
                                                    @case('pending')
                                                        <span class="badge bg-warning">未対応</span>
                                                        @break
                                                    @case('in_progress')
                                                        <span class="badge bg-info">対応中</span>
                                                        @break
                                                    @case('resolved')
                                                        <span class="badge bg-success">解決済み</span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td>{{ $comment->created_at->format('Y/m/d H:i') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $comments->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">コメントがありません。</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const commentCheckboxes = document.querySelectorAll('.comment-checkbox');

    selectAllCheckbox.addEventListener('change', function() {
        commentCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Update select all checkbox when individual checkboxes change
    commentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.comment-checkbox:checked').length;
            selectAllCheckbox.checked = checkedCount === commentCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < commentCheckboxes.length;
        });
    });
});

function bulkUpdateStatus(status) {
    const checkedBoxes = document.querySelectorAll('.comment-checkbox:checked');
    
    if (checkedBoxes.length === 0) {
        alert('コメントを選択してください。');
        return;
    }

    const statusText = {
        'pending': '未対応',
        'in_progress': '対応中',
        'resolved': '解決済み'
    };

    if (confirm(`選択した${checkedBoxes.length}件のコメントを「${statusText[status]}」に変更しますか？`)) {
        const form = document.getElementById('bulkForm');
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        form.appendChild(statusInput);
        form.submit();
    }
}
</script>
@endpush
@endsection