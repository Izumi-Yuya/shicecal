@extends('layouts.app')

@section('title', 'マイコメント')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">マイコメント</h3>
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

                    <!-- Comments List -->
                    @if($comments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>施設名</th>
                                        <th>コメント内容</th>
                                        <th>ステータス</th>
                                        <th>担当者</th>
                                        <th>投稿日時</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($comments as $comment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('facilities.show', $comment->facility) }}">
                                                {{ $comment->facility->facility_name }}
                                            </a>
                                        </td>
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
                                        <td>{{ $comment->assignee->name ?? '未割当' }}</td>
                                        <td>{{ $comment->created_at->format('Y/m/d H:i') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $comments->links() }}
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
@endsection