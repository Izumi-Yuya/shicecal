@extends('layouts.app')

@section('title', '担当コメント')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">担当コメント</h3>
                </div>
                <div class="card-body">
                    @if($comments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>施設名</th>
                                        <th>投稿者</th>
                                        <th>コメント内容</th>
                                        <th>ステータス</th>
                                        <th>投稿日時</th>
                                        <th>操作</th>
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
                                        <td>{{ $comment->poster->name }}</td>
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
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if($comment->status !== 'resolved')
                                                <form method="POST" action="{{ route('comments.update-status', $comment) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="resolved">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> 解決
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
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
                            <p class="text-muted">担当コメントがありません。</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection