@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>コメントステータス管理</h4>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- ステータス概要 -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h2>{{ $statusCounts['pending'] ?? 0 }}</h2>
                                    <p class="mb-0">未対応</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h2>{{ $statusCounts['in_progress'] ?? 0 }}</h2>
                                    <p class="mb-0">対応中</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h2>{{ $statusCounts['resolved'] ?? 0 }}</h2>
                                    <p class="mb-0">対応済</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h2>{{ array_sum($statusCounts) }}</h2>
                                    <p class="mb-0">合計</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 進捗可視化 -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5>進捗状況</h5>
                            @php
                                $total = array_sum($statusCounts);
                                $resolvedPercentage = $total > 0 ? round(($statusCounts['resolved'] / $total) * 100, 1) : 0;
                                $inProgressPercentage = $total > 0 ? round(($statusCounts['in_progress'] / $total) * 100, 1) : 0;
                                $pendingPercentage = $total > 0 ? round(($statusCounts['pending'] / $total) * 100, 1) : 0;
                            @endphp
                            
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: {{ $resolvedPercentage }}%" 
                                     aria-valuenow="{{ $resolvedPercentage }}" aria-valuemin="0" aria-valuemax="100">
                                    対応済 {{ $resolvedPercentage }}%
                                </div>
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: {{ $inProgressPercentage }}%" 
                                     aria-valuenow="{{ $inProgressPercentage }}" aria-valuemin="0" aria-valuemax="100">
                                    対応中 {{ $inProgressPercentage }}%
                                </div>
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: {{ $pendingPercentage }}%" 
                                     aria-valuenow="{{ $pendingPercentage }}" aria-valuemin="0" aria-valuemax="100">
                                    未対応 {{ $pendingPercentage }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- フィルター -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('comments.status-dashboard') }}" class="row g-3">
                                <div class="col-md-3">
                                    <label for="status" class="form-label">ステータス</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">すべて</option>
                                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>未対応</option>
                                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>対応中</option>
                                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>対応済</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="facility_name" class="form-label">施設名</label>
                                    <input type="text" name="facility_name" id="facility_name" class="form-control" 
                                           value="{{ request('facility_name') }}" placeholder="施設名で検索">
                                </div>
                                <div class="col-md-3">
                                    <label for="assignee" class="form-label">担当者</label>
                                    <select name="assignee" id="assignee" class="form-select">
                                        <option value="">すべて</option>
                                        @foreach($assignees as $assignee)
                                            <option value="{{ $assignee->id }}" {{ request('assignee') == $assignee->id ? 'selected' : '' }}>
                                                {{ $assignee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">検索</button>
                                    <a href="{{ route('comments.status-dashboard') }}" class="btn btn-secondary">リセット</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- コメント一覧 -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>施設名</th>
                                    <th>対象項目</th>
                                    <th>コメント</th>
                                    <th>投稿者</th>
                                    <th>担当者</th>
                                    <th>ステータス</th>
                                    <th>投稿日時</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($comments as $comment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('facilities.show', $comment->facility) }}">
                                                {{ $comment->facility->facility_name }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($comment->field_name)
                                                <span class="badge bg-secondary">{{ $comment->field_name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="{{ $comment->content }}">
                                                {{ $comment->content }}
                                            </div>
                                        </td>
                                        <td>{{ $comment->poster->name }}</td>
                                        <td>{{ $comment->assignee->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($comment->status === 'pending') bg-warning
                                                @elseif($comment->status === 'in_progress') bg-info
                                                @else bg-success
                                                @endif">
                                                @if($comment->status === 'pending') 未対応
                                                @elseif($comment->status === 'in_progress') 対応中
                                                @else 対応済
                                                @endif
                                            </span>
                                        </td>
                                        <td>{{ $comment->created_at->format('Y/m/d H:i') }}</td>
                                        <td>
                                            @if(auth()->user()->isPrimaryResponder() || auth()->user()->isAdmin() || auth()->id() === $comment->assigned_to)
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        変更
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        @if($comment->status !== 'pending')
                                                            <li>
                                                                <form action="{{ route('comments.update-status', $comment) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="pending">
                                                                    <button type="submit" class="dropdown-item">未対応に戻す</button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                        @if($comment->status !== 'in_progress')
                                                            <li>
                                                                <form action="{{ route('comments.update-status', $comment) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="in_progress">
                                                                    <button type="submit" class="dropdown-item">対応中にする</button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                        @if($comment->status !== 'resolved')
                                                            <li>
                                                                <form action="{{ route('comments.update-status', $comment) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="resolved">
                                                                    <button type="submit" class="dropdown-item">対応済みにする</button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">該当するコメントはありません。</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- ページネーション -->
                    @if($comments->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $comments->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection