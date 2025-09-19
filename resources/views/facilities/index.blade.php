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
                    <form method="GET" action="{{ route('facilities.index') }}" id="search-form">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="section" class="form-label">部門</label>
                                <select class="form-select" id="section" name="section">
                                    <option value="">すべての部門</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section }}" 
                                                {{ request('section') == $section ? 'selected' : '' }}>
                                            {{ $section }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="prefecture" class="form-label">都道府県</label>
                                <select class="form-select" id="prefecture" name="prefecture">
                                    <option value="">すべての都道府県</option>
                                    @foreach($prefectures as $prefecture)
                                        <option value="{{ $prefecture }}" 
                                                {{ request('prefecture') == $prefecture ? 'selected' : '' }}>
                                            {{ $prefecture }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="keyword" class="form-label">キーワード検索</label>
                                <input type="text" class="form-control" id="keyword" name="keyword" 
                                       placeholder="施設名、会社名、事業所コード、住所で検索..." 
                                       value="{{ request('keyword') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="btn-group w-100" role="group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>検索
                                    </button>
                                    <a href="{{ route('facilities.index') }}" class="btn btn-outline-secondary">
                                        検索クリア
                                    </a>
                                </div>
                            </div>
                        </div>
                        @if(request()->hasAny(['section', 'prefecture', 'keyword']))
                            <div class="row g-3 mt-2">
                                <div class="col-12">
                                    <small class="text-muted">
                                        <i class="fas fa-filter me-1"></i>
                                        {{ $facilities->count() }}件の施設が見つかりました
                                        @if(request('section'))
                                            <span class="badge bg-primary ms-1">{{ request('section') }}</span>
                                        @endif
                                        @if(request('prefecture'))
                                            <span class="badge bg-info ms-1">{{ request('prefecture') }}</span>
                                        @endif
                                        @if(request('keyword'))
                                            <span class="badge bg-success ms-1">"{{ request('keyword') }}"</span>
                                        @endif
                                    </small>
                                </div>
                            </div>
                        @endif
                    </form>
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
                                        <th class="text-center">事業所コード</th>
                                        <th>施設名</th>
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
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination - Disabled --}}
                        {{--
                        @if($facilities->hasPages())
                            <div class="d-flex justify-content-center py-3">
                                {{ $facilities->links() }}
                            </div>
                        @endif
                        --}}
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
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filters change
    const filterElements = ['section', 'prefecture'];
    
    filterElements.forEach(function(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.addEventListener('change', function() {
                document.getElementById('search-form').submit();
            });
        }
    });

    // Submit form on Enter key in keyword field
    const keywordField = document.getElementById('keyword');
    if (keywordField) {
        keywordField.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('search-form').submit();
            }
        });
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
@endsection