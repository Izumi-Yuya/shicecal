@extends('layouts.app')

@section('title', 'セキュリティ設定')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">セキュリティ設定</h1>
                <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>戻る
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">セキュリティ設定</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        この機能は現在開発中です。実装予定の機能です。
                    </div>
                    
                    <!-- Placeholder form -->
                    <form>
                        <div class="mb-4">
                            <h6>パスワードポリシー</h6>
                            <div class="mb-3">
                                <label for="min_password_length" class="form-label">最小パスワード長</label>
                                <input type="number" class="form-control" id="min_password_length" name="min_password_length" value="8" disabled>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="require_uppercase" name="require_uppercase" checked disabled>
                                <label class="form-check-label" for="require_uppercase">
                                    大文字を必須とする
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="require_numbers" name="require_numbers" checked disabled>
                                <label class="form-check-label" for="require_numbers">
                                    数字を必須とする
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="require_symbols" name="require_symbols" disabled>
                                <label class="form-check-label" for="require_symbols">
                                    記号を必須とする
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6>セッション設定</h6>
                            <div class="mb-3">
                                <label for="session_timeout" class="form-label">セッションタイムアウト（分）</label>
                                <input type="number" class="form-control" id="session_timeout" name="session_timeout" value="120" disabled>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6>ログイン試行制限</h6>
                            <div class="mb-3">
                                <label for="max_login_attempts" class="form-label">最大試行回数</label>
                                <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" value="5" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="lockout_duration" class="form-label">ロックアウト時間（分）</label>
                                <input type="number" class="form-control" id="lockout_duration" name="lockout_duration" value="15" disabled>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-primary" disabled>保存</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection