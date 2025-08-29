@extends('layouts.app')

@section('title', 'ユーザー編集 - ' . $user->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">ユーザー編集</h1>
                <div>
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-info">
                        <i class="fas fa-eye"></i> 詳細表示
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> 一覧に戻る
                    </a>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">ユーザー情報編集</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                                @csrf
                                @method('PUT')
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="{{ old('name', $user->name) }}" placeholder="山田 太郎">
                                            <div class="form-text">ユーザーの表示名を入力してください</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="{{ old('email', $user->email) }}" placeholder="user@example.com">
                                            <div class="form-text">ログイン時に使用するメールアドレス</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">パスワード</label>
                                            <input type="password" class="form-control" id="password" name="password">
                                            <div class="form-text">変更する場合のみ入力してください（8文字以上を推奨）</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="role" class="form-label">ロール <span class="text-danger">*</span></label>
                                            <select class="form-select" id="role" name="role">
                                                <option value="">ロールを選択してください</option>
                                                @foreach($roles as $roleValue => $roleLabel)
                                                    <option value="{{ $roleValue }}" 
                                                            {{ old('role', $user->role) === $roleValue ? 'selected' : '' }}>
                                                        {{ $roleLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">ユーザーの権限レベルを設定</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="department" class="form-label">部門</label>
                                    <input type="text" class="form-control" id="department" name="department" 
                                           value="{{ old('department', $user->department) }}" placeholder="総務部">
                                    <div class="form-text">所属部門名（任意）</div>
                                </div>

                                <div class="mb-3">
                                    <label for="access_scope" class="form-label">閲覧権限範囲</label>
                                    <textarea class="form-control" id="access_scope" name="access_scope" rows="4" 
                                              placeholder='{"regions": ["東京", "大阪"], "departments": ["営業部"]}'>{{ old('access_scope', $user->access_scope ? json_encode($user->access_scope, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                                    <div class="form-text">
                                        JSON形式で閲覧権限範囲を設定（地区担当・部門責任者の場合）<br>
                                        例: {"regions": ["東京", "大阪"], "departments": ["営業部"]}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            アカウントを有効にする
                                        </label>
                                        <div class="form-text">チェックを外すとユーザーはログインできません</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary">
                                        キャンセル
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 更新
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    @if($user->is_active)
                        <div class="card mt-4 border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5 class="card-title mb-0">危険な操作</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">
                                    このユーザーを削除すると、ログインできなくなります。この操作は元に戻すことができません。
                                </p>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" 
                                      onsubmit="return confirm('本当にこのユーザーを削除してもよろしいですか？この操作は元に戻せません。')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> ユーザーを削除
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection