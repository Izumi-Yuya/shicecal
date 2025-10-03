@extends('layouts.app')

@section('title', 'システムログ')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">システムログ</h1>
                <a href="{{ route('admin.logs.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>戻る
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">システムエラーログ</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        この機能は現在開発中です。実装予定の機能です。
                    </div>
                    
                    <!-- Placeholder filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="level_filter" class="form-label">ログレベル</label>
                            <select class="form-select" id="level_filter" disabled>
                                <option value="">すべてのレベル</option>
                                <option value="emergency">Emergency</option>
                                <option value="alert">Alert</option>
                                <option value="critical">Critical</option>
                                <option value="error">Error</option>
                                <option value="warning">Warning</option>
                                <option value="notice">Notice</option>
                                <option value="info">Info</option>
                                <option value="debug">Debug</option>
                            </select>
                        <