@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>マイコメント</h4>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

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
                                                @endif
                                            </small>
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
                            {{ $comments->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">まだコメントを投稿していません。</p>
                            <a href="{{ route('facilities.index') }}" class="btn btn-primary">施設一覧を見る</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection