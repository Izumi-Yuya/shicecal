@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>担当コメント</h4>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- ステータス別タブ -->
                    <ul class="nav nav-tabs mb-3" id="statusTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                                未対応 <span class="badge bg-warning ms-1">{{ $comments->where('status', 'pending')->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="in-progress-tab" data-bs-toggle="tab" data-bs-target="#in-progress" type="button" role="tab">
                                対応中 <span class="badge bg-info ms-1">{{ $comments->where('status', 'in_progress')->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="resolved-tab" data-bs-toggle="tab" data-bs-target="#resolved" type="button" role="tab">
                                対応済 <span class="badge bg-success ms-1">{{ $comments->where('status', 'resolved')->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="statusTabsContent">
                        <!-- 未対応コメント -->
                        <div class="tab-pane fade show active" id="pending" role="tabpanel">
                            @include('comments.comment-list', ['comments' => $comments->where('status', 'pending'), 'showActions' => true])
                        </div>

                        <!-- 対応中コメント -->
                        <div class="tab-pane fade" id="in-progress" role="tabpanel">
                            @include('comments.comment-list', ['comments' => $comments->where('status', 'in_progress'), 'showActions' => true])
                        </div>

                        <!-- 対応済コメント -->
                        <div class="tab-pane fade" id="resolved" role="tabpanel">
                            @include('comments.comment-list', ['comments' => $comments->where('status', 'resolved'), 'showActions' => false])
                        </div>
                    </div>

                    @if($comments->count() === 0)
                        <div class="text-center py-4">
                            <p class="text-muted">担当するコメントはありません。</p>
                        </div>
                    @endif

                    <!-- ページネーション -->
                    @if($comments->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $comments->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection