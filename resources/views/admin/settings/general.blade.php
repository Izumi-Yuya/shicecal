@extends('layouts.app')

@section('title', '一般設定')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">一般設定</h1>
                <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>戻る
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">システム一般設定</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        この機能は現在開発中です。実装予定の機能です。
                    </div>
                    
                    <!-- Placeholder form -->
                    <form>
                        <div class="mb-3">
                            <label for="app_name" class="form-label">アプリケーション名</label>
                            <input type="text" class="form-control" id="app_name" name="app_name" value="Shise-Cal" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="app_description" class="form-label">アプリケーション説明</label>
                            <textarea class="form-control" id="app_description" name="app_description" rows="3" disabled>施設管理システム</textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="timezone" class="form-label">タイムゾーン</label>
                            <select class="form-select" id="timezone" name="timezone" disabled>
                                <option value="Asia/Tokyo" selected>Asia/Tokyo</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="language" class="form-label">言語</label>
                            <select class="form-select" id="language" name="language" disabled>
                                <option value="ja" selected>日本語</option>
                                <option value="en">English</option>
                            </select>
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