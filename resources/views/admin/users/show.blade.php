@extends('layouts.app')

@section('title', 'ユーザー詳細')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">ユーザー詳細</h1>
                <div>
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-1"></i>編集
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>戻る
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">ユーザー情報</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        この機能は現在開発中です。ユーザーID: {{ $user }}
                    </div>
                    
                    <!-- Placeholder user details -->
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 120px;">ID:</th>
                                    <td>{{ $user }}</td>
                                </tr>
                                <tr>
                                    <th>名前:</th>
                                    <td>サンプルユーザー</td>
                                </tr>
                                <tr>
                                    <th>メール:</th>
                                    <td>sample@example.com</td>
                                </tr>
                                <tr>
                                    <th>役割:</th>
                                    <td><span class="badge bg-primary">管理者</span></td>
                                </tr>
                                <tr>
                                    <th>作成日:</th>
                                    <td>2024-01-01 00:00:00</td>
                                </tr>
                                <tr>
                                    <th>最終ログイン:</th>
                                    <td>2024-01-01 00:00:00</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>権限</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>施設管理</li>
                                <li><i class="fas fa-check text-success me-2"></i>ユーザー管理</li>
                                <li><i class="fas fa-check text-success me-2"></i>システム設定</li>
                                <li><i class="fas fa-check text-success me-2"></i>ログ閲覧</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection