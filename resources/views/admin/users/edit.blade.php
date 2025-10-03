@extends('layouts.app')

@section('title', 'ユーザー編集')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">ユーザー編集</h1>
                <div>
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary me-2">
                        <i class="fas fa-eye me-1"></i>詳細
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>戻る
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">ユーザー情報編集</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        この機能は現在開発中です。ユーザーID: {{ $user }}
                    </div>
                    
                    <!-- Placeholder form -->
                    <form>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">名前</label>
                                    <input type="text" class="form-control" id="name" name="name" value="サンプルユーザー" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">メールアドレス</label>
                                    <input type="email" class="form-control" id="email" name="email" value="sample@example.com" disabled>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">役割</label>
                                    <select class="form-select" id="role" name="role" disabled>
                                        <option value="admin" selected>管理者</option>
                                        <option value="editor">編集者</option>
                                        <option value="viewer">閲覧者</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">ステータス</label>
                                    <select class="form-select" id="status" name="status" disabled>
                                        <option value="active" selected>有効</option>
                                        <option value="inactive">無効</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="reset_password" name="reset_password" disabled>
                                <label class="form-check-label" for="reset_password">
                                    次回ログイン時にパスワード変更を要求する
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" disabled>キャンセル</button>
                            <button type="button" class="btn btn-primary" disabled>更新</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection