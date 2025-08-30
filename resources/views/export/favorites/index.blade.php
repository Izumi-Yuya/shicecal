@extends('layouts.app')

@section('title', 'お気に入り管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-star me-2 text-warning"></i>
                    お気に入り管理
                </h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFavoriteModal">
                        <i class="fas fa-plus"></i> 新規お気に入り
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="favoritesActionsDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> 操作
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportFavorites()">
                                <i class="fas fa-download"></i> お気に入り一覧エクスポート
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="importFavorites()">
                                <i class="fas fa-upload"></i> お気に入りインポート
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="bulkDeleteFavorites()">
                                <i class="fas fa-trash"></i> 一括削除
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card bg-primary text-white">
                        <div class="card-body text-center">
                            <h4>{{ $stats['total_favorites'] ?? 0 }}</h4>
                            <p class="mb-0">総お気に入り数</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-success text-white">
                        <div class="card-body text-center">
                            <h4>{{ $stats['csv_favorites'] ?? 0 }}</h4>
                            <p class="mb-0">CSV出力</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-danger text-white">
                        <div class="card-body text-center">
                            <h4>{{ $stats['pdf_favorites'] ?? 0 }}</h4>
                            <p class="mb-0">PDF出力</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-info text-white">
                        <div class="card-body text-center">
                            <h4>{{ $stats['recent_used'] ?? 0 }}</h4>
                            <p class="mb-0">最近使用</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <ul class="nav nav-tabs mb-4" id="favoritesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                        <i class="fas fa-list me-1"></i>すべて
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="csv-tab" data-bs-toggle="tab" data-bs-target="#csv" type="button" role="tab">
                        <i class="fas fa-file-csv me-1"></i>CSV出力
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pdf-tab" data-bs-toggle="tab" data-bs-target="#pdf" type="button" role="tab">
                        <i class="fas fa-file-pdf me-1"></i>PDF出力
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="recent-tab" data-bs-toggle="tab" data-bs-target="#recent" type="button" role="tab">
                        <i class="fas fa-clock me-1"></i>最近使用
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="favoritesTabContent">
                <!-- All Favorites -->
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    <div class="card admin-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">すべてのお気に入り</h5>
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control form-control-sm" placeholder="検索..." id="searchFavorites" style="width: 200px;">
                                <select class="form-select form-select-sm" id="sortFavorites" style="width: 150px;">
                                    <option value="name">名前順</option>
                                    <option value="created_at">作成日順</option>
                                    <option value="last_used">使用日順</option>
                                    <option value="type">種別順</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="favoritesGrid" class="row">
                                <!-- Favorites will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CSV Favorites -->
                <div class="tab-pane fade" id="csv" role="tabpanel">
                    <div class="card admin-card">
                        <div class="card-header">
                            <h5 class="mb-0">CSV出力お気に入り</h5>
                        </div>
                        <div class="card-body">
                            <div id="csvFavoritesGrid" class="row">
                                <!-- CSV favorites will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PDF Favorites -->
                <div class="tab-pane fade" id="pdf" role="tabpanel">
                    <div class="card admin-card">
                        <div class="card-header">
                            <h5 class="mb-0">PDF出力お気に入り</h5>
                        </div>
                        <div class="card-body">
                            <div id="pdfFavoritesGrid" class="row">
                                <!-- PDF favorites will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Favorites -->
                <div class="tab-pane fade" id="recent" role="tabpanel">
                    <div class="card admin-card">
                        <div class="card-header">
                            <h5 class="mb-0">最近使用したお気に入り</h5>
                        </div>
                        <div class="card-body">
                            <div id="recentFavoritesGrid" class="row">
                                <!-- Recent favorites will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Favorite Modal -->
<div class="modal fade" id="createFavoriteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">お気に入り作成</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="favoriteForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="favorite_name" class="form-label">お気に入り名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="favorite_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="favorite_type" class="form-label">種別 <span class="text-danger">*</span></label>
                                <select class="form-select" id="favorite_type" name="type" required>
                                    <option value="">選択してください</option>
                                    <option value="csv">CSV出力</option>
                                    <option value="pdf">PDF出力</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="favorite_description" class="form-label">説明</label>
                        <textarea class="form-control" id="favorite_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">施設選択</label>
                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="selectAllModalFacilities()">全選択</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllModalFacilities()">全解除</button>
                            </div>
                            <div id="modalFacilitiesList">
                                <!-- Facilities will be loaded here -->
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="csvFieldsSection" style="display: none;">
                        <label class="form-label">出力項目（CSV）</label>
                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="selectAllModalFields()">全選択</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllModalFields()">全解除</button>
                            </div>
                            <div id="modalFieldsList" class="row">
                                <!-- Fields will be loaded here -->
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="pdfOptionsSection" style="display: none;">
                        <label class="form-label">PDF設定</label>
                        <div class="border rounded p-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pdf_secure" name="pdf_secure" value="1">
                                <label class="form-check-label" for="pdf_secure">
                                    セキュアPDF（パスワード保護）
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pdf_watermark" name="pdf_watermark" value="1">
                                <label class="form-check-label" for="pdf_watermark">
                                    透かし文字を追加
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Favorite Detail Modal -->
<div class="modal fade" id="favoriteDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">お気に入り詳細</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="favoriteDetailContent">
                <!-- Detail content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                <button type="button" class="btn btn-primary" id="executeFavoriteBtn">
                    <i class="fas fa-play"></i> 実行
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.favorite-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}

.favorite-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.favorite-type-badge {
    position: absolute;
    top: 10px;
    right: 10px;
}

.favorite-stats {
    font-size: 0.8rem;
    color: #6c757d;
}

.favorite-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.favorite-card:hover .favorite-actions {
    opacity: 1;
}

.last-used-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}

.last-used-indicator.recent {
    background-color: #28a745;
}

.last-used-indicator.old {
    background-color: #dc3545;
}

.last-used-indicator.medium {
    background-color: #ffc107;
}
</style>
@endpush

@push('scripts')
<script>
class FavoritesManager {
    constructor() {
        this.currentTab = 'all';
        this.favorites = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadFavorites();
    }

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                this.currentTab = e.target.getAttribute('data-bs-target').replace('#', '');
                this.renderFavorites();
            });
        });

        // Search and sort
        document.getElementById('searchFavorites').addEventListener('input', 
            this.debounce(() => this.renderFavorites(), 300));
        document.getElementById('sortFavorites').addEventListener('change', 
            () => this.renderFavorites());

        // Favorite type change
        document.getElementById('favorite_type').addEventListener('change', (e) => {
            const csvSection = document.getElementById('csvFieldsSection');
            const pdfSection = document.getElementById('pdfOptionsSection');
            
            if (e.target.value === 'csv') {
                csvSection.style.display = 'block';
                pdfSection.style.display = 'none';
            } else if (e.target.value === 'pdf') {
                csvSection.style.display = 'none';
                pdfSection.style.display = 'block';
            } else {
                csvSection.style.display = 'none';
                pdfSection.style.display = 'none';
            }
        });

        // Form submission
        document.getElementById('favoriteForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveFavorite();
        });
    }

    async loadFavorites() {
        try {
            const response = await fetch('/export/favorites/api');
            const data = await response.json();
            
            if (data.success) {
                this.favorites = data.data;
                this.renderFavorites();
            }
        } catch (error) {
            console.error('Failed to load favorites:', error);
        }
    }

    renderFavorites() {
        const searchTerm = document.getElementById('searchFavorites').value.toLowerCase();
        const sortBy = document.getElementById('sortFavorites').value;
        
        let filteredFavorites = this.favorites.filter(favorite => {
            const matchesSearch = favorite.name.toLowerCase().includes(searchTerm) ||
                                favorite.description?.toLowerCase().includes(searchTerm);
            
            switch (this.currentTab) {
                case 'csv':
                    return matchesSearch && favorite.type === 'csv';
                case 'pdf':
                    return matchesSearch && favorite.type === 'pdf';
                case 'recent':
                    return matchesSearch && this.isRecentlyUsed(favorite);
                default:
                    return matchesSearch;
            }
        });

        // Sort favorites
        filteredFavorites.sort((a, b) => {
            switch (sortBy) {
                case 'name':
                    return a.name.localeCompare(b.name);
                case 'created_at':
                    return new Date(b.created_at) - new Date(a.created_at);
                case 'last_used':
                    return new Date(b.last_used_at || 0) - new Date(a.last_used_at || 0);
                case 'type':
                    return a.type.localeCompare(b.type);
                default:
                    return 0;
            }
        });

        const containerId = this.currentTab === 'all' ? 'favoritesGrid' : 
                           this.currentTab === 'csv' ? 'csvFavoritesGrid' :
                           this.currentTab === 'pdf' ? 'pdfFavoritesGrid' : 'recentFavoritesGrid';
        
        const container = document.getElementById(containerId);
        container.innerHTML = this.generateFavoritesHTML(filteredFavorites);
    }

    generateFavoritesHTML(favorites) {
        if (favorites.length === 0) {
            return `
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-star fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">お気に入りがありません</h5>
                        <p class="text-muted">新しいお気に入りを作成してください。</p>
                    </div>
                </div>
            `;
        }

        return favorites.map(favorite => `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card favorite-card h-100" onclick="showFavoriteDetail(${favorite.id})">
                    <div class="card-body position-relative">
                        <span class="badge favorite-type-badge bg-${favorite.type === 'csv' ? 'success' : 'danger'}">
                            ${favorite.type.toUpperCase()}
                        </span>
                        
                        <h6 class="card-title">${favorite.name}</h6>
                        
                        ${favorite.description ? `<p class="card-text text-muted small">${favorite.description}</p>` : ''}
                        
                        <div class="favorite-stats mb-2">
                            <div class="d-flex justify-content-between">
                                <span>
                                    <i class="fas fa-building"></i> ${favorite.facility_count || 0}施設
                                </span>
                                <span>
                                    ${this.getLastUsedIndicator(favorite)}
                                    ${this.formatLastUsed(favorite.last_used_at)}
                                </span>
                            </div>
                            ${favorite.type === 'csv' ? `
                                <div class="mt-1">
                                    <i class="fas fa-list"></i> ${favorite.field_count || 0}項目
                                </div>
                            ` : ''}
                        </div>
                        
                        <div class="favorite-actions">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-sm btn-primary" 
                                        onclick="event.stopPropagation(); executeFavorite(${favorite.id})">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                        onclick="event.stopPropagation(); editFavorite(${favorite.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="event.stopPropagation(); deleteFavorite(${favorite.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    getLastUsedIndicator(favorite) {
        if (!favorite.last_used_at) return '<span class="last-used-indicator old"></span>';
        
        const daysSinceUsed = Math.floor((Date.now() - new Date(favorite.last_used_at)) / (1000 * 60 * 60 * 24));
        
        if (daysSinceUsed <= 7) return '<span class="last-used-indicator recent"></span>';
        if (daysSinceUsed <= 30) return '<span class="last-used-indicator medium"></span>';
        return '<span class="last-used-indicator old"></span>';
    }

    formatLastUsed(lastUsedAt) {
        if (!lastUsedAt) return '未使用';
        
        const date = new Date(lastUsedAt);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) return '今日';
        if (diffDays === 1) return '昨日';
        if (diffDays <= 7) return `${diffDays}日前`;
        if (diffDays <= 30) return `${Math.floor(diffDays / 7)}週間前`;
        return date.toLocaleDateString();
    }

    isRecentlyUsed(favorite) {
        if (!favorite.last_used_at) return false;
        const daysSinceUsed = Math.floor((Date.now() - new Date(favorite.last_used_at)) / (1000 * 60 * 60 * 24));
        return daysSinceUsed <= 30;
    }

    async saveFavorite() {
        const formData = new FormData(document.getElementById('favoriteForm'));
        
        try {
            const response = await fetch('/export/favorites', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('createFavoriteModal'));
                modal.hide();
                this.loadFavorites();
                this.showAlert('お気に入りが保存されました', 'success');
            } else {
                this.showAlert(data.message || 'エラーが発生しました', 'danger');
            }
        } catch (error) {
            console.error('Save favorite error:', error);
            this.showAlert('保存中にエラーが発生しました', 'danger');
        }
    }

    showAlert(message, type) {
        // Implementation for showing alerts
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Global functions
function showFavoriteDetail(id) {
    // Implementation for showing favorite details
    console.log('Show favorite detail:', id);
}

function executeFavorite(id) {
    // Implementation for executing favorite
    console.log('Execute favorite:', id);
}

function editFavorite(id) {
    // Implementation for editing favorite
    console.log('Edit favorite:', id);
}

function deleteFavorite(id) {
    if (confirm('このお気に入りを削除しますか？')) {
        // Implementation for deleting favorite
        console.log('Delete favorite:', id);
    }
}

function selectAllModalFacilities() {
    document.querySelectorAll('#modalFacilitiesList input[type="checkbox"]').forEach(cb => cb.checked = true);
}

function deselectAllModalFacilities() {
    document.querySelectorAll('#modalFacilitiesList input[type="checkbox"]').forEach(cb => cb.checked = false);
}

function selectAllModalFields() {
    document.querySelectorAll('#modalFieldsList input[type="checkbox"]').forEach(cb => cb.checked = true);
}

function deselectAllModalFields() {
    document.querySelectorAll('#modalFieldsList input[type="checkbox"]').forEach(cb => cb.checked = false);
}

function exportFavorites() {
    // Implementation for exporting favorites
    console.log('Export favorites');
}

function importFavorites() {
    // Implementation for importing favorites
    console.log('Import favorites');
}

function bulkDeleteFavorites() {
    // Implementation for bulk delete
    console.log('Bulk delete favorites');
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.favoritesManager = new FavoritesManager();
});
</script>
@endpush
@endsection