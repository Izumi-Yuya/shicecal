@extends('layouts.app')

@section('title', 'ユーザー管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">ユーザー管理</h1>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 新規ユーザー登録
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="email" class="form-label">メールアドレス</label>
                            <input type="text" class="form-control" id="email" name="email" 
                                   value="{{ request('email') }}" placeholder="メールアドレスで検索">
                        </div>
                        <div class="col-md-3">
                            <label for="role" class="form-label">ロール</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">すべてのロール</option>
                                @foreach($roles as $roleValue => $roleLabel)
                                    <option value="{{ $roleValue }}" 
                                            {{ request('role') === $roleValue ? 'selected' : '' }}>
                                        {{ $roleLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">ステータス</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">すべてのステータス</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>
                                    有効
                                </option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>
                                    無効
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-search"></i> 検索
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> クリア
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    @if($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>名前</th>
                                        <th>メールアドレス</th>
                                        <th>ロール</th>
                                        <th>部門</th>
                                        <th>ステータス</th>
                                        <th>最終ログイン</th>
                                        <th>登録日</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>{{ $user->id }}</td>
                                            <td>
                                                <strong>{{ $user->name }}</strong>
                                            </td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ $user->role_display_name }}</span>
                                            </td>
                                            <td>{{ $user->department ?? '-' }}</td>
                                            <td>
                                                @if($user->is_active)
                                                    <span class="badge bg-success">有効</span>
                                                @else
                                                    <span class="badge bg-danger">無効</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $user->last_login_at ? $user->last_login_at->format('Y/m/d H:i') : '-' }}
                                            </td>
                                            <td>{{ $user->created_at->format('Y/m/d') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.users.show', $user) }}" 
                                                       class="btn btn-sm btn-outline-info" title="詳細">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                                       class="btn btn-sm btn-outline-primary" title="編集">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @if($user->is_active)
                                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" 
                                                              class="d-inline" 
                                                              onsubmit="return confirm('このユーザーを削除してもよろしいですか？')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="削除">
                                                                <i class="fas fa-trash"></i>
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
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                <small class="text-muted">
                                    {{ $users->firstItem() }} - {{ $users->lastItem() }} / {{ $users->total() }} 件
                                </small>
                            </div>
                            <div>
                                {{ $users->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">ユーザーが見つかりません</h5>
                            <p class="text-muted">検索条件を変更するか、新しいユーザーを作成してください。</p>
                            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> 新規ユーザー登録
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection