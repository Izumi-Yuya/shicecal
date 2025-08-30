@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>マイコメント</h4>
                    <a href="{{ route('my-page.index') }}" class="btn btn-secondary btn-sm">マイページに戻る</a>
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
                                    <h3>{{ $statusCounts['pending'] }}</h3>
                                    <p class="mb-0">未対応</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $statusCounts['in_progress'] }}</h3>
                                    <p class="mb-0">対応中</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $statusCounts['resolved'] }}</h3>
                                    <p class="mb-0">対応済</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3>{{ array_sum($statusCounts) }}</h3>
                                    <p class="mb-0">合計</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- フィルター -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('my-page.my-comments') }}" class="row g-3">
                                <div class="col-md-4">
                                    <label for="status" class="form-label">ステータス</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">すべて</option>
                                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>未対応</option>
                                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>対応中</option>
                                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>対応済</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="facility_name" class="form-label">施設名</label>
                                    <input type="text" name="facility_name" id="facility_name" class="form-control" 
                                           value="{{ request('facility_name') }}" placeholder="施設名で検索">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">検索</button>
                                    <a href="{{ route('my-page.my-comments') }}" class="btn btn-secondary">リセット</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    @if($comments->count() > 0)
                        @foreach($comments as $comment)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title">
                                                <a href="{{ route('facilities.show', $comment->facility) }}">
                                                    {{ $comment->facility->facility_name }}
                                                </a>
                                                @if($comment->field_name)
                                                    <span class="badge bg-secondary ms-2">{{ $comment->field_name }}</span>
                                                @endif
                                            </h6>
                                            <p class="card-text">{{ $comment->content }}</p>
                                            <small class="text-muted">
                                                投稿日時: {{ $comment->created_at->format('Y年m月d日 H:i') }}
                                                @if($comment->assignee)
                                                    | 担当者: {{ $comment->assignee->name }}
                                                @endif
                                                @if($comment->resolved_at)
                                                    | 解決日時: {{ $comment->resolved_at->format('Y年m月d日 H:i') }}
                                                    | 対応時間: {{ $comment->created_at->diffInHours($comment->resolved_at) }}時間
                                                @endif
                                            </small>
                                            
                                            <!-- 対応状況の進捗表示 -->
                                            <div class="mt-2">
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar 
                                                        @if($comment->status === 'pending') bg-warning
                                                        @elseif($comment->status === 'in_progress') bg-info
                                                        @else bg-success
                                                        @endif" 
                                                        role="progressbar" 
                                                        style="width: 
                                                            @if($comment->status === 'pending') 33%
                                                            @elseif($comment->status === 'in_progress') 66%
                                                            @else 100%
                                                            @endif">
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between mt-1">
                                                    <small class="text-muted">投稿</small>
                                                    <small class="text-muted">対応中</small>
                                                    <small class="text-muted">完了</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ms-3">
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- ページネーション -->
                        <div class="d-flex justify-content-center">
                            {{ $comments->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">該当するコメントはありません。</p>
                            <a href="{{ route('facilities.index') }}" class="btn btn-primary">施設一覧を見る</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection