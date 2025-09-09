@extends('layouts.app')

@section('title', '施設一覧')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="jp-title mb-1">
                        <i class="fas fa-building text-primary me-2"></i>施設一覧
                    </h2>
                    <p class="text-muted mb-0">登録されている施設の一覧を表示しています</p>
                </div>
                @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                    <a href="{{ route('facilities.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>新規登録
                    </a>
                @endif
            </div>

            <!-- Search and Filter Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">検索</label>
                            <input type="text" class="form-control" id="search" placeholder="施設名、会社名、住所で検索..." 
                                   data-search="tbody tr">
                        </div>
                        <div class="col-md-3">
                            <label for="company-filter" class="form-label">会社名</label>
                            <select class="form-select" id="company-filter">
                                <option value="">すべて</option>
                                @foreach($facilities->pluck('company_name')->unique()->sort() as $company)
                                    <option value="{{ $company }}">{{ $company }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort-by" class="form-label">並び順</label>
                            <select class="form-select" id="sort-by">
                                <option value="updated_at">更新日順</option>
                                <option value="facility_name">施設名順</option>
                                <option value="company_name">会社名順</option>
                                <option value="office_code">事業所コード順</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                                <i class="fas fa-times me-1"></i>クリア
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Facilities Table -->
            <div class="card">
                <div class="card-body p-0">
                    @if (session('success'))
                        <div class="alert alert-success mx-3 mt-3">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    @if($facilities->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th data-sort="office_code" class="text-center">
                                            事業所コード
                                            <i class="fas fa-sort ms-1 text-muted"></i>
                                        </th>
                                        <th data-sort="facility_name">
                                            施設名
                                            <i class="fas fa-sort ms-1 text-muted"></i>
                                        </th>
                                        <th data-sort="company_name">
                                            会社名
                                            <i class="fas fa-sort ms-1 text-muted"></i>
                                        </th>
                                        <th>住所</th>
                                        <th data-sort="updated_at" class="text-center">
                                            最終更新日
                                            <i class="fas fa-sort ms-1 text-muted"></i>
                                        </th>
                                        <th class="text-center">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($facilities as $facility)
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark">{{ $facility->office_code }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('facilities.show', $facility) }}" 
                                                   class="text-decoration-none fw-semibold">
                                                    {{ $facility->facility_name }}
                                                </a>
                                            </td>
                                            <td>{{ $facility->company_name }}</td>
                                            <td class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                {{ Str::limit($facility->address, 40) }}
                                            </td>
                                            <td class="text-center">
                                                <small class="text-muted">
                                                    {{ $facility->updated_at->format('Y年m月d日') }}
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('facilities.show', $facility) }}" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       data-bs-toggle="tooltip" title="詳細を表示">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                                                        <a href="{{ route('facilities.edit', $facility) }}" 
                                                           class="btn btn-sm btn-outline-secondary"
                                                           data-bs-toggle="tooltip" title="編集">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($facilities->hasPages())
                            <div class="d-flex justify-content-center py-3">
                                {{ $facilities->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-building fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-3">施設が登録されていません</h5>
                            <p class="text-muted mb-4">まだ施設が登録されていません。最初の施設を登録してください。</p>
                            @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                                <a href="{{ route('facilities.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>最初の施設を登録する
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function clearFilters() {
    document.getElementById('search').value = '';
    document.getElementById('company-filter').value = '';
    document.getElementById('sort-by').value = 'updated_at';
    
    // Show all rows
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => row.style.display = '');
}

// Company filter functionality
document.getElementById('company-filter').addEventListener('change', function() {
    const selectedCompany = this.value;
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const companyCell = row.cells[2].textContent.trim();
        const shouldShow = selectedCompany === '' || companyCell === selectedCompany;
        row.style.display = shouldShow ? '' : 'none';
    });
});
</script>
@endpush
@endsection