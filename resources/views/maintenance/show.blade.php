@extends('layouts.app')

@section('title', '修繕履歴詳細')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">修繕履歴詳細</h1>
                <div class="btn-group">
                    <a href="{{ route('maintenance.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> 一覧に戻る
                    </a>
                    <a href="{{ route('maintenance.edit', $maintenanceHistory) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit"></i> 編集
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- 修繕履歴詳細情報 -->
            <div class="row">
                <!-- 基本情報 -->
                <div class="col-md-6">
                    <div class="facility-info-card detail-card-improved mb-4" data-section="maintenance_basic">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-tools me-2"></i>基本情報</h5>
                        </div>
                        <div class="card-body">
                            <div class="facility-detail-table">
                                <div class="detail-row">
                                    <span class="detail-label">施設名</span>
                                    <span class="detail-value">{{ $maintenanceHistory->facility->facility_name }}</span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">事業所コード</span>
                                    <span class="detail-value">{{ $maintenanceHistory->facility->office_code }}</span>
                                </div>
                                
                                <div class="detail-row {{ empty($maintenanceHistory->facility->address) ? 'empty-field' : '' }}">
                                    <span class="detail-label">住所</span>
                                    <span class="detail-value">{{ $maintenanceHistory->facility->address ?? '未設定' }}</span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">修繕対応日</span>
                                    <span class="detail-value">{{ $maintenanceHistory->maintenance_date->format('Y年m月d日') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 費用・業者情報 -->
                <div class="col-md-6">
                    <div class="facility-info-card detail-card-improved mb-4" data-section="maintenance_financial">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-yen-sign me-2"></i>費用・業者情報</h5>
                        </div>
                        <div class="card-body">
                            <div class="facility-detail-table">
                                <div class="detail-row {{ empty($maintenanceHistory->cost) ? 'empty-field' : '' }}">
                                    <span class="detail-label">費用</span>
                                    <span class="detail-value">
                                        @if($maintenanceHistory->cost)
                                            ¥{{ number_format($maintenanceHistory->cost) }}
                                        @else
                                            未設定
                                        @endif
                                    </span>
                                </div>
                                
                                <div class="detail-row {{ empty($maintenanceHistory->contractor) ? 'empty-field' : '' }}">
                                    <span class="detail-label">業者名</span>
                                    <span class="detail-value">{{ $maintenanceHistory->contractor ?? '未設定' }}</span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">登録者</span>
                                    <span class="detail-value">{{ $maintenanceHistory->creator->name }}</span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">登録日時</span>
                                    <span class="detail-value">
                                        {{ $maintenanceHistory->created_at->format('Y年m月d日 H:i') }}
                                        @if($maintenanceHistory->updated_at != $maintenanceHistory->created_at)
                                            <br><small class="text-muted">更新: {{ $maintenanceHistory->updated_at->format('Y年m月d日 H:i') }}</small>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 修繕内容 -->
            <div class="row">
                <div class="col-12">
                    <div class="facility-info-card detail-card-improved mb-4" data-section="maintenance_content">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-clipboard-list me-2"></i>修繕内容</h5>
                        </div>
                        <div class="card-body">
                            <div class="facility-detail-table">
                                <div class="detail-row">
                                    <span class="detail-label">内容</span>
                                    <span class="detail-value" style="white-space: pre-wrap;">{{ $maintenanceHistory->content }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash"></i> 削除
                        </button>
                        <a href="{{ route('maintenance.edit', $maintenanceHistory) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> 編集
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">削除確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>この修繕履歴を削除してもよろしいですか？</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    削除した履歴は復元できません。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <form method="POST" action="{{ route('maintenance.destroy', $maintenanceHistory) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">削除</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection