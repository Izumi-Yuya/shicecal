@extends('layouts.app')

@section('title', 'ユーザー詳細 - ' . $user->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">ユーザー詳細</h1>
                <div>
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> 編集
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> 一覧に戻る
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Basic Information -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">基本情報</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">ユーザーID</label>
                                        <div class="fw-bold">{{ $user->id }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">名前</label>
                                        <div class="fw-bold">{{ $user->name }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">メールアドレス</label>
                                        <div class="fw-bold">{{ $user->email }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">ロール</label>
                                        <div>
                                            <span class="badge bg-info fs-6">{{ $user->role_display_name }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">部門</label>
                                        <div class="fw-bold">{{ $user->department ?? '-' }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">ステータス</label>
                                        <div>
                                            @if($user->is_active)
                                                <span class="badge bg-success fs-6">有効</span>
                                            @else
                                                <span class="badge bg-danger fs-6">無効</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">登録日</label>
                                        <div class="fw-bold">{{ $user->created_at->format('Y年m月d日 H:i') }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">最終更新</label>
                                        <div class="fw-bold">{{ $user->updated_at->format('Y年m月d日 H:i') }}</div>
                                    </div>
                                </div>
                            </div>

                            @if($user->access_scope)
                                <div class="mt-4">
                                    <label class="form-label text-muted">閲覧権限範囲</label>
                                    <div class="bg-light p-3 rounded">
                                        <pre class="mb-0">{{ json_encode($user->access_scope, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">統計情報</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="border-end">
                                        <div class="h4 text-primary mb-1">{{ $user->createdFacilities->count() }}</div>
                                        <small class="text-muted">作成施設数</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="h4 text-success mb-1">{{ $user->updatedFacilities->count() }}</div>
                                    <small class="text-muted">更新施設数</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="border-end">
                                        <div class="h4 text-info mb-1">{{ $user->approvedFacilities->count() }}</div>
                                        <small class="text-muted">承認施設数</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="h4 text-warning mb-1">{{ $user->uploadedFiles->count() }}</div>
                                    <small class="text-muted">アップロードファイル数</small>
                                </div>
                                <div class="col-6">
                                    <div class="border-end">
                                        <div class="h4 text-secondary mb-1">{{ $user->postedComments->count() }}</div>
                                        <small class="text-muted">投稿コメント数</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="h4 text-dark mb-1">{{ $user->assignedComments->count() }}</div>
                                    <small class="text-muted">担当コメント数</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            @if($user->activityLogs->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">最近の活動履歴</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>日時</th>
                                        <th>操作</th>
                                        <th>対象</th>
                                        <th>詳細</th>
                                        <th>IPアドレス</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($user->activityLogs as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('m/d H:i') }}</td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $log->action }}</span>
                                            </td>
                                            <td>{{ $log->target_type }}</td>
                                            <td>{{ Str::limit($log->description, 50) }}</td>
                                            <td><small class="text-muted">{{ $log->ip_address }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <small class="text-muted">最新10件を表示</small>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection