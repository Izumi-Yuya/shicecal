@extends('layouts.app')

@section('title', 'アクティビティログ')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">アクティビティログ</h1>
                <a href="{{ route('admin.logs.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>戻る
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">ユーザーアクティビティログ</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        この機能は現在開発中です。実装予定の機能です。
                    </div>
                    
                    <!-- Placeholder filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="user_filter" class="form-label">ユーザー</label>
                            <select class="form-select" id="user_filter" disabled>
                                <option value="">すべてのユーザー</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="action_filter" class="form-label">アクション</label>
                            <select class="form-select" id="action_filter" disabled>
                                <option value="">すべてのアクション</option>
                                <option value="login">ログイン</option>
                                <option value="logout">ログアウト</option>
                                <option value="create">作成</option>
                                <option value="update">更新</option>
                                <option value="delete">削除</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">開始日</label>
                            <input type="date" class="form-control" id="date_from" disabled>
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">終了日</label>
                            <input type="date" class="form-control" id="date_to" disabled>
                        </div>
                    </div>
                    
                    <!-- Placeholder table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>日時</th>
                                    <th>ユーザー</th>
                                    <th>アクション</th>
                                    <th>対象</th>
                                    <th>IPアドレス</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        データがありません
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection