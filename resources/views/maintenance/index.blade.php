@extends('layouts.app')

@section('title', '修繕履歴一覧')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">修繕履歴一覧</h1>
                <a href="{{ route('maintenance.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 新規登録
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">検索条件</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                                お気に入り
                            </button>
                            <ul class="dropdown-menu" id="favorites-dropdown">
                                @if($searchFavorites->count() > 0)
                                    @foreach($searchFavorites as $favorite)
                                        <li>
                                            <a class="dropdown-item load-favorite" href="#" 
                                                data-favorite-id="{{ $favorite->id }}">
                                                {{ $favorite->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                    <li><hr class="dropdown-divider"></li>
                                @endif
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" 
                                        data-bs-target="#saveFavoriteModal">
                                        <i class="fas fa-save"></i> 現在の条件を保存
                                    </a>
                                </li>
                                @if($searchFavorites->count() > 0)
                                    <li>
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" 
                                            data-bs-target="#manageFavoritesModal">
                                            <i class="fas fa-cog"></i> お気に入り管理
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('maintenance.index') }}" class="row g-3" id="search-form">
                        <div class="col-md-3">
                            <label for="facility_id" class="form-label">施設</label>
                            <select name="facility_id" id="facility_id" class="form-select">
                                <option value="">すべての施設</option>
                                @foreach($facilities as $facility)
                                    <option value="{{ $facility->id }}" 
                                        {{ request('facility_id') == $facility->id ? 'selected' : '' }}>
                                        {{ $facility->office_code }} - {{ $facility->facility_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">開始日</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" 
                                value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">終了日</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" 
                                value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">内容検索</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                placeholder="修繕内容で検索" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary me-2">検索</button>
                            <a href="{{ route('maintenance.index') }}" class="btn btn-outline-secondary">クリア</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Maintenance Histories Table -->
            <div class="card">
                <div class="card-body">
                    @if($maintenanceHistories->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>修繕日</th>
                                        <th>施設名</th>
                                        <th>修繕内容</th>
                                        <th>費用</th>
                                        <th>業者</th>
                                        <th>登録者</th>
                                        <th>登録日</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($maintenanceHistories as $history)
                                        <tr>
                                            <td>{{ $history->maintenance_date->format('Y/m/d') }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $history->facility->facility_name }}</div>
                                                <small class="text-muted">{{ $history->facility->office_code }}</small>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 300px;" 
                                                    title="{{ $history->content }}">
                                                    {{ $history->content }}
                                                </div>
                                            </td>
                                            <td>
                                                @if($history->cost)
                                                    ¥{{ number_format($history->cost) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $history->contractor ?? '-' }}</td>
                                            <td>{{ $history->creator->name }}</td>
                                            <td>{{ $history->created_at->format('Y/m/d') }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('maintenance.show', $history) }}" 
                                                        class="btn btn-outline-primary">詳細</a>
                                                    <a href="{{ route('maintenance.edit', $history) }}" 
                                                        class="btn btn-outline-secondary">編集</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $maintenanceHistories->withQueryString()->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">修繕履歴がありません</h5>
                            <p class="text-muted">条件を変更して再度検索してください。</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Save Favorite Modal -->
<div class="modal fade" id="saveFavoriteModal" tabindex="-1" aria-labelledby="saveFavoriteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveFavoriteModalLabel">検索条件を保存</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="save-favorite-form">
                    <div class="mb-3">
                        <label for="favorite-name" class="form-label">お気に入り名</label>
                        <input type="text" class="form-control" id="favorite-name" name="name" required>
                    </div>
                    <div class="alert alert-info">
                        <small>現在の検索条件が保存されます。</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="save-favorite-btn">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- Manage Favorites Modal -->
<div class="modal fade" id="manageFavoritesModal" tabindex="-1" aria-labelledby="manageFavoritesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageFavoritesModalLabel">お気に入り管理</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="favorites-list">
                    <!-- Favorites will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load favorite functionality
    document.querySelectorAll('.load-favorite').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const favoriteId = this.dataset.favoriteId;
            loadFavorite(favoriteId);
        });
    });

    // Save favorite functionality
    document.getElementById('save-favorite-btn').addEventListener('click', function() {
        saveFavorite();
    });

    // Manage favorites modal
    document.getElementById('manageFavoritesModal').addEventListener('show.bs.modal', function() {
        loadFavoritesList();
    });

    function loadFavorite(favoriteId) {
        fetch(`{{ route('maintenance.search-favorites.show', '') }}/${favoriteId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const favorite = data.favorite;
                    
                    // Set form values
                    document.getElementById('facility_id').value = favorite.facility_id || '';
                    document.getElementById('start_date').value = favorite.start_date || '';
                    document.getElementById('end_date').value = favorite.end_date || '';
                    document.getElementById('search').value = favorite.search_content || '';
                    
                    // Submit the form to apply the search
                    document.getElementById('search-form').submit();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('エラーが発生しました。');
            });
    }

    function saveFavorite() {
        const name = document.getElementById('favorite-name').value;
        if (!name.trim()) {
            alert('お気に入り名を入力してください。');
            return;
        }

        const formData = {
            name: name,
            facility_id: document.getElementById('facility_id').value || null,
            start_date: document.getElementById('start_date').value || null,
            end_date: document.getElementById('end_date').value || null,
            search_content: document.getElementById('search').value || null,
        };

        fetch('{{ route('maintenance.search-favorites.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                document.getElementById('favorite-name').value = '';
                bootstrap.Modal.getInstance(document.getElementById('saveFavoriteModal')).hide();
                location.reload(); // Reload to update the favorites dropdown
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました。');
        });
    }

    function loadFavoritesList() {
        fetch('{{ route('maintenance.search-favorites.index') }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const favoritesList = document.getElementById('favorites-list');
                    
                    if (data.favorites.length === 0) {
                        favoritesList.innerHTML = '<p class="text-muted text-center">保存されたお気に入りはありません。</p>';
                        return;
                    }

                    let html = '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>名前</th><th>施設</th><th>期間</th><th>内容検索</th><th>操作</th></tr></thead><tbody>';
                    
                    data.favorites.forEach(function(favorite) {
                        const facilityName = favorite.facility ? `${favorite.facility.office_code} - ${favorite.facility.facility_name}` : 'すべての施設';
                        const dateRange = favorite.start_date || favorite.end_date ? 
                            `${favorite.start_date || ''} ～ ${favorite.end_date || ''}` : '-';
                        const searchContent = favorite.search_content || '-';
                        
                        html += `
                            <tr>
                                <td>${favorite.name}</td>
                                <td>${facilityName}</td>
                                <td>${dateRange}</td>
                                <td>${searchContent}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="loadFavorite(${favorite.id})">適用</button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteFavorite(${favorite.id})">削除</button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += '</tbody></table></div>';
                    favoritesList.innerHTML = html;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('favorites-list').innerHTML = '<p class="text-danger text-center">エラーが発生しました。</p>';
            });
    }

    window.deleteFavorite = function(favoriteId) {
        if (!confirm('このお気に入りを削除しますか？')) {
            return;
        }

        fetch(`{{ route('maintenance.search-favorites.destroy', '') }}/${favoriteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                loadFavoritesList(); // Reload the list
                location.reload(); // Reload to update the favorites dropdown
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました。');
        });
    };
});
</script>
@endpush