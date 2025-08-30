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

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">施設情報</label>
                            <div class="p-3 bg-light rounded">
                                <div class="fw-bold">{{ $maintenanceHistory->facility->facility_name }}</div>
                                <div class="text-muted">事業所コード: {{ $maintenanceHistory->facility->office_code }}</div>
                                <div class="text-muted">{{ $maintenanceHistory->facility->address }}</div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">修繕対応日</label>
                            <div class="p-3 bg-light rounded">
                                <div class="h5 mb-0">{{ $maintenanceHistory->maintenance_date->format('Y年m月d日') }}</div>
                                <div class="text-muted">{{ $maintenanceHistory->maintenance_date->format('l') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">修繕内容</label>
                        <div class="p-3 bg-light rounded">
                            <div style="white-space: pre-wrap;">{{ $maintenanceHistory->content }}</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">費用</label>
                            <div class="p-3 bg-light rounded">
                                @if($maintenanceHistory->cost)
                                    <div class="h5 mb-0 text-primary">¥{{ number_format($maintenanceHistory->cost) }}</div>
                                @else
                                    <div class="text-muted">費用情報なし</div>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">業者名</label>
                            <div class="p-3 bg-light rounded">
                                @if($maintenanceHistory->contractor)
                                    <div>{{ $maintenanceHistory->contractor }}</div>
                                @else
                                    <div class="text-muted">業者情報なし</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">登録者</label>
                            <div class="p-3 bg-light rounded">
                                <div>{{ $maintenanceHistory->creator->name }}</div>
                                <div class="text-muted">{{ $maintenanceHistory->creator->email }}</div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">登録日時</label>
                            <div class="p-3 bg-light rounded">
                                <div>{{ $maintenanceHistory->created_at->format('Y年m月d日 H:i') }}</div>
                                @if($maintenanceHistory->updated_at != $maintenanceHistory->created_at)
                                    <div class="text-muted">更新: {{ $maintenanceHistory->updated_at->format('Y年m月d日 H:i') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

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