@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2>マイページ</h2>
            <p class="text-muted">{{ auth()->user()->name }}さんのダッシュボード</p>
        </div>
    </div>

    <div class="row">
        <!-- 投稿コメント概要 -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">投稿したコメント</h5>
                    <a href="{{ route('my-page.my-comments') }}" class="btn btn-sm btn-outline-primary">すべて見る</a>
                </div>
                <div class="card-body">
                    <!-- ステータス概要 -->
                    <div class="row mb-3">
                        <div class="col-4 text-center">
                            <div class="text-warning">
                                <i class="fas fa-clock fa-2x"></i>
                                <div class="mt-1">
                                    <strong>{{ $commentStatusCounts['pending'] }}</strong>
                                    <br><small>未対応</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="text-info">
                                <i class="fas fa-cog fa-2x"></i>
                                <div class="mt-1">
                                    <strong>{{ $commentStatusCounts['in_progress'] }}</strong>
                                    <br><small>対応中</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="text-success">
                                <i class="fas fa-check-circle fa-2x"></i>
                                <div class="mt-1">
                                    <strong>{{ $commentStatusCounts['resolved'] }}</strong>
                                    <br><small>対応済</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 最近のコメント -->
                    @if($myComments->count() > 0)
                        <h6>最近のコメント</h6>
                        @foreach($myComments->take(3) as $comment)
                            <div class="border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <small class="text-muted">{{ $comment->facility->facility_name }}</small>
                                        <p class="mb-1 text-truncate" style="max-width: 200px;">{{ $comment->content }}</p>
                                        <small class="text-muted">{{ $comment->created_at->format('m/d H:i') }}</small>
                                    </div>
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
                        @endforeach
                    @else
                        <p class="text-muted text-center py-3">まだコメントを投稿していません。</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- 通知 -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        通知
                        @if($unreadNotificationCount > 0)
                            <span class="badge bg-danger ms-2">{{ $unreadNotificationCount }}</span>
                        @endif
                    </h5>
                    <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">すべて見る</a>
                </div>
                <div class="card-body">
                    @if($recentNotifications->count() > 0)
                        @foreach($recentNotifications as $notification)
                            <div class="border-bottom pb-2 mb-2 {{ $notification->isRead() ? '' : 'bg-light p-2 rounded' }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ $notification->title }}</h6>
                                        <p class="mb-1 small">{{ Str::limit($notification->message, 80) }}</p>
                                        <small class="text-muted">{{ $notification->created_at->format('m/d H:i') }}</small>
                                    </div>
                                    @if(!$notification->isRead())
                                        <span class="badge bg-primary">新着</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center py-3">通知はありません。</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(auth()->user()->isPrimaryResponder() || auth()->user()->isAdmin())
        <div class="row">
            <!-- 担当コメント概要 -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">担当しているコメント</h5>
                        <a href="{{ route('comments.assigned') }}" class="btn btn-sm btn-outline-primary">すべて見る</a>
                    </div>
                    <div class="card-body">
                        <!-- ステータス概要 -->
                        <div class="row mb-3">
                            <div class="col-4 text-center">
                                <div class="text-warning">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    <div class="mt-1">
                                        <strong>{{ $assignedStatusCounts['pending'] ?? 0 }}</strong>
                                        <br><small>未対応</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="text-info">
                                    <i class="fas fa-tasks fa-2x"></i>
                                    <div class="mt-1">
                                        <strong>{{ $assignedStatusCounts['in_progress'] ?? 0 }}</strong>
                                        <br><small>対応中</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="text-success">
                                    <i class="fas fa-check-double fa-2x"></i>
                                    <div class="mt-1">
                                        <strong>{{ $assignedStatusCounts['resolved'] ?? 0 }}</strong>
                                        <br><small>対応済</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 最近の担当コメント -->
                        @if($assignedComments->count() > 0)
                            <h6>最近の担当コメント</h6>
                            @foreach($assignedComments->take(3) as $comment)
                                <div class="border-bottom pb-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <small class="text-muted">{{ $comment->facility->facility_name }} - {{ $comment->poster->name }}</small>
                                            <p class="mb-1 text-truncate" style="max-width: 300px;">{{ $comment->content }}</p>
                                            <small class="text-muted">{{ $comment->created_at->format('m/d H:i') }}</small>
                                        </div>
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
                            @endforeach
                        @else
                            <p class="text-muted text-center py-3">担当するコメントはありません。</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- クイックアクション -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">クイックアクション</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('facilities.index') }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-building me-2"></i>施設一覧
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('my-page.my-comments') }}" class="btn btn-outline-info w-100">
                                <i class="fas fa-comments me-2"></i>マイコメント
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('notifications.index') }}" class="btn btn-outline-warning w-100">
                                <i class="fas fa-bell me-2"></i>通知一覧
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('my-page.activity-summary') }}" class="btn btn-outline-success w-100">
                                <i class="fas fa-chart-line me-2"></i>活動サマリー
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection