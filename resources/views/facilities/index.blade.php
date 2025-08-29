@extends('layouts.app')

@section('title', '施設一覧')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">施設一覧</h1>
                <a href="{{ route('facilities.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 新規登録
                </a>
            </div>

            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">検索・絞り込み</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('facilities.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="search_name" class="form-label">施設名</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search_name" 
                                   name="search_name" 
                                   value="{{ request('search_name') }}"
                                   placeholder="施設名で検索">
                        </div>
                        <div class="col-md-4">
                            <label for="search_office_code" class="form-label">事業所コード</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search_office_code" 
                                   name="search_office_code" 
                                   value="{{ request('search_office_code') }}"
                                   placeholder="事業所コードで検索">
                        </div>
                        <div class="col-md-4">
                            <label for="search_address" class="form-label">都道府県・住所</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search_address" 
                                   name="search_address" 
                                   value="{{ request('search_address') }}"
                                   placeholder="都道府県・住所で検索">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-search"></i> 検索
                            </button>
                            <a href="{{ route('facilities.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> クリア
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Facilities Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">施設一覧 ({{ $facilities->total() }}件)</h5>
                </div>
                <div class="card-body p-0">
                    @if($facilities->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>事業所コード</th>
                                        <th>施設名</th>
                                        <th>会社名</th>
                                        <th>都道府県</th>
                                        <th>ステータス</th>
                                        <th>最終更新日</th>
                                        <th class="text-center">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($facilities as $facility)
                                        <tr>
                                            <td>
                                                <code>{{ $facility->office_code }}</code>
                                            </td>
                                            <td>
                                                <strong>{{ $facility->facility_name }}</strong>
                                                @if($facility->designation_number)
                                                    <br>
                                                    <small class="text-muted">指定番号: {{ $facility->designation_number }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $facility->company_name }}</td>
                                            <td>
                                                @if($facility->address)
                                                    {{ Str::limit($facility->address, 20) }}
                                                @else
                                                    <span class="text-muted">未設定</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($facility->status === 'approved')
                                                    <span class="badge bg-success">{{ $facility->status_display_name }}</span>
                                                @elseif($facility->status === 'pending_approval')
                                                    <span class="badge bg-warning">{{ $facility->status_display_name }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $facility->status_display_name }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $facility->updated_at->format('Y/m/d H:i') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('facilities.show', $facility) }}" 
                                                       class="btn btn-outline-primary" 
                                                       title="詳細表示">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('facilities.edit', $facility) }}" 
                                                       class="btn btn-outline-secondary" 
                                                       title="編集">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($facilities->hasPages())
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted">
                                        {{ $facilities->firstItem() }}〜{{ $facilities->lastItem() }}件 / 全{{ $facilities->total() }}件
                                    </div>
                                    {{ $facilities->appends(request()->query())->links() }}
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">施設が見つかりませんでした</h5>
                            <p class="text-muted">検索条件を変更するか、新しい施設を登録してください。</p>
                            <a href="{{ route('facilities.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> 新規登録
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table th {
        border-top: none;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
    }
    
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .btn-group-sm > .btn {
            padding: 0.125rem 0.25rem;
        }
    }
</style>
@endpush