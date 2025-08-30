@extends('layouts.app')

@section('title', '通知一覧')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-bell me-2"></i>
                    通知一覧
                    @if($notifications->where('is_read', false)->count() > 0)
                        <span class="badge bg-danger">{{ $notifications->where('is_read', false)->count() }}</span>
                    @endif
                </h1>
                <div class="d-flex gap-2">
                    @if($notifications->where('is_read', false)->count() > 0)
                        <button class="btn btn-primary" onclick="markAllAsRead()">
                            <i class="fas fa-check-double"></i> すべて既読
                        </button>
                    @endif
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="notificationOptionsDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> オプション
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="filterNotifications('all')">
                                <i class="fas fa-list"></i> すべて表示
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterNotifications('unread')">
                                <i class="fas fa-envelope"></i> 未読のみ
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterNotifications('read')">
                                <i class="fas fa-envelope-open"></i> 既読のみ
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="exportNotifications()">
                                <i class="fas fa-download"></i> 通知履歴出力
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="showNotificationSettings()">
                                <i class="fas fa-cog"></i> 通知設定
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
                            <h4>{{ $notifications->count() }}</h4>
                            <p class="mb-0">総通知数</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-danger text-white">
                        <div class="card-body text-center">
                            <h4>{{ $notifications->where('is_read', false)->count() }}</h4>
                            <p class="mb-0">未読</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-success text-white">
                        <div class="card-body text-center">
                            <h4>{{ $notifications->where('is_read', true)->count() }}</h4>
                            <p class="mb-0">既読</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-info text-white">
                        <div class="card-body text-center">
                            <h4>{{ $notifications->where('created_at', '>=', now()->subDays(7))->count() }}</h4>
                            <p class="mb-0">過去7日間</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <ul class="nav nav-tabs mb-4" id="notificationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                        <i class="fas fa-list me-1"></i>すべて
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="unread-tab" data-bs-toggle="tab" data-bs-target="#unread" type="button" role="tab">
                        <i class="fas fa-envelope me-1"></i>未読 
                        <span class="badge bg-danger">{{ $notifications->where('is_read', false)->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab">
                        <i class="fas fa-comments me-1"></i>コメント
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals" type="button" role="tab">
                        <i class="fas fa-check-circle me-1"></i>承認
                    </button>
                </li>
            </ul>

            <div class="card admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">通知リスト</h5>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" placeholder="検索..." id="searchNotifications" style="width: 200px;">
                        <select class="form-select form-select-sm" id="sortNotifications" style="width: 150px;">
                            <option value="newest">新しい順</option>
                            <option value="oldest">古い順</option>
                            <option value="unread_first">未読優先</option>
                            <option value="type">種別順</option>
                        </select>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($notifications->count() > 0)
                        <div id="notificationsList">
                        @foreach($notifications as $notification)
                            <div class="notification-item {{ $notification->isRead() ? 'read' : 'unread' }} mb-3" 
                                 data-id="{{ $notification->id }}" 
                                 data-type="{{ $notification->type ?? 'general' }}"
                                 data-read="{{ $notification->isRead() ? 'true' : 'false' }}">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon me-3">
                                        @switch($notification->type ?? 'general')
                                            @case('comment')
                                                <i class="fas fa-comment text-primary"></i>
                                                @break
                                            @case('approval')
                                                <i class="fas fa-check-circle text-success"></i>
                                                @break
                                            @case('annual_confirmation')
                                                <i class="fas fa-calendar-check text-warning"></i>
                                                @break
                                            @default
                                                <i class="fas fa-bell text-info"></i>
                                        @endswitch
                                    </div>
                                    
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="notification-content">
                                                <h6 class="notification-title mb-1">
                                                    {{ $notification->title }}
                                                    @if(!$notification->isRead())
                                                        <span class="badge bg-danger ms-2">新着</span>
                                                    @endif
                                                </h6>
                                                <p class="notification-message mb-2">{{ $notification->message }}</p>
                                                <div class="notification-meta">
                                                    <small class="text-muted d-flex align-items-center gap-3">
                                                        <span>
                                                            <i class="fas fa-clock"></i>
                                                            {{ $notification->created_at->format('Y年m月d日 H:i') }}
                                                        </span>
                                                        @if($notification->isRead() && $notification->read_at)
                                                            <span>
                                                                <i class="fas fa-check text-success"></i>
                                                                既読: {{ $notification->read_at->format('m/d H:i') }}
                                                            </span>
                                                        @endif
                                                        @if($notification->data && isset($notification->data['facility_name']))
                                                            <span>
                                                                <i class="fas fa-building"></i>
                                                                {{ $notification->data['facility_name'] }}
                                                            </span>
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="notification-actions ms-3">
                                                <div class="btn-group" role="group">
                                                    @if(!$notification->isRead())
                                                        <button class="btn btn-sm btn-outline-primary mark-as-read" 
                                                                data-notification-id="{{ $notification->id }}">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    @endif
                                                    
                                                    @if($notification->data && isset($notification->data['facility_id']))
                                                        <a href="{{ route('facilities.show', $notification->data['facility_id']) }}" 
                                                           class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    @endif
                                                    
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteNotification({{ $notification->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </div>

                        <!-- ページネーション -->
                        @if($notifications->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $notifications->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">通知はありません</h5>
                            <p class="text-muted">新しい通知が届くとここに表示されます。</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Settings Modal -->
<div class="modal fade" id="notificationSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">通知設定</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="notificationSettingsForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">メール通知</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="email_comments" name="email_comments" checked>
                            <label class="form-check-label" for="email_comments">
                                コメント投稿時
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="email_approvals" name="email_approvals" checked>
                            <label class="form-check-label" for="email_approvals">
                                承認依頼時
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="email_annual" name="email_annual" checked>
                            <label class="form-check-label" for="email_annual">
                                年次確認依頼時
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notification_frequency" class="form-label">通知頻度</label>
                        <select class="form-select" id="notification_frequency" name="notification_frequency">
                            <option value="immediate">即座</option>
                            <option value="hourly">1時間ごと</option>
                            <option value="daily">1日1回</option>
                            <option value="weekly">週1回</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="saveNotificationSettings()">
                    <i class="fas fa-save"></i> 保存
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.notification-item {
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    position: relative;
}

.notification-item.unread {
    background-color: #f8f9ff;
    border-left: 4px solid #007bff;
}

.notification-item.read {
    background-color: #f8f9fa;
    opacity: 0.8;
}

.notification-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.notification-title {
    font-weight: 600;
    color: #495057;
}

.notification-message {
    color: #6c757d;
    line-height: 1.5;
}

.notification-meta {
    border-top: 1px solid #f1f3f4;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
}

.notification-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.notification-item:hover .notification-actions {
    opacity: 1;
}

.nav-tabs .nav-link .badge {
    font-size: 0.7rem;
}

@media (max-width: 768px) {
    .notification-item {
        padding: 0.75rem;
    }
    
    .notification-actions {
        opacity: 1;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
class NotificationManager {
    constructor() {
        this.notifications = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadNotifications();
        this.setupRealTimeUpdates();
    }

    setupEventListeners() {
        // Mark as read functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('.mark-as-read') || e.target.closest('.mark-as-read')) {
                const button = e.target.closest('.mark-as-read');
                const notificationId = button.dataset.notificationId;
                this.markAsRead(notificationId);
            }
        });

        // Search functionality
        document.getElementById('searchNotifications').addEventListener('input', 
            this.debounce(() => this.filterNotifications(), 300));

        // Sort functionality
        document.getElementById('sortNotifications').addEventListener('change', 
            () => this.sortNotifications());

        // Tab switching
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                const target = e.target.getAttribute('data-bs-target').replace('#', '');
                this.filterByTab(target);
            });
        });
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/notifications/${notificationId}/mark-as-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();
            
            if (data.success) {
                const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.classList.remove('unread');
                    notificationElement.classList.add('read');
                    notificationElement.dataset.read = 'true';
                    
                    // Remove the mark as read button
                    const button = notificationElement.querySelector('.mark-as-read');
                    if (button) {
                        button.remove();
                    }
                    
                    // Update badge counts
                    this.updateBadgeCounts();
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();
            
            if (data.success) {
                location.reload();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    filterNotifications() {
        const searchTerm = document.getElementById('searchNotifications').value.toLowerCase();
        const notifications = document.querySelectorAll('.notification-item');
        
        notifications.forEach(notification => {
            const title = notification.querySelector('.notification-title').textContent.toLowerCase();
            const message = notification.querySelector('.notification-message').textContent.toLowerCase();
            
            if (title.includes(searchTerm) || message.includes(searchTerm)) {
                notification.style.display = 'block';
            } else {
                notification.style.display = 'none';
            }
        });
    }

    sortNotifications() {
        const sortBy = document.getElementById('sortNotifications').value;
        const container = document.getElementById('notificationsList');
        const notifications = Array.from(container.children);
        
        notifications.sort((a, b) => {
            switch (sortBy) {
                case 'newest':
                    return new Date(b.dataset.created) - new Date(a.dataset.created);
                case 'oldest':
                    return new Date(a.dataset.created) - new Date(b.dataset.created);
                case 'unread_first':
                    if (a.dataset.read === 'false' && b.dataset.read === 'true') return -1;
                    if (a.dataset.read === 'true' && b.dataset.read === 'false') return 1;
                    return new Date(b.dataset.created) - new Date(a.dataset.created);
                case 'type':
                    return a.dataset.type.localeCompare(b.dataset.type);
                default:
                    return 0;
            }
        });
        
        notifications.forEach(notification => container.appendChild(notification));
    }

    filterByTab(tab) {
        const notifications = document.querySelectorAll('.notification-item');
        
        notifications.forEach(notification => {
            switch (tab) {
                case 'unread':
                    notification.style.display = notification.dataset.read === 'false' ? 'block' : 'none';
                    break;
                case 'comments':
                    notification.style.display = notification.dataset.type === 'comment' ? 'block' : 'none';
                    break;
                case 'approvals':
                    notification.style.display = notification.dataset.type === 'approval' ? 'block' : 'none';
                    break;
                default:
                    notification.style.display = 'block';
            }
        });
    }

    updateBadgeCounts() {
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        
        // Update header badge
        const headerBadge = document.querySelector('h1 .badge');
        if (headerBadge) {
            if (unreadCount > 0) {
                headerBadge.textContent = unreadCount;
                headerBadge.style.display = 'inline';
            } else {
                headerBadge.style.display = 'none';
            }
        }
        
        // Update tab badge
        const tabBadge = document.querySelector('#unread-tab .badge');
        if (tabBadge) {
            tabBadge.textContent = unreadCount;
        }
        
        // Update stats card
        const statsCard = document.querySelector('.stats-card.bg-danger h4');
        if (statsCard) {
            statsCard.textContent = unreadCount;
        }
    }

    setupRealTimeUpdates() {
        // Poll for new notifications every 30 seconds
        setInterval(() => {
            this.checkForNewNotifications();
        }, 30000);
    }

    async checkForNewNotifications() {
        try {
            const response = await fetch('/notifications/recent');
            const data = await response.json();
            
            if (data.success && data.new_count > 0) {
                // Show toast notification for new notifications
                this.showToast(`${data.new_count}件の新しい通知があります`, 'info');
            }
        } catch (error) {
            console.error('Error checking for new notifications:', error);
        }
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
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

    loadNotifications() {
        // Load notifications data if needed
        this.updateBadgeCounts();
    }
}

// Global functions
function markAllAsRead() {
    window.notificationManager.markAllAsRead();
}

function deleteNotification(id) {
    if (confirm('この通知を削除しますか？')) {
        fetch(`/notifications/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`[data-id="${id}"]`).remove();
                window.notificationManager.updateBadgeCounts();
            }
        });
    }
}

function exportNotifications() {
    window.open('/notifications/export', '_blank');
}

function showNotificationSettings() {
    const modal = new bootstrap.Modal(document.getElementById('notificationSettingsModal'));
    modal.show();
}

function saveNotificationSettings() {
    const form = document.getElementById('notificationSettingsForm');
    const formData = new FormData(form);
    
    fetch('/notifications/settings', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('notificationSettingsModal'));
            modal.hide();
            window.notificationManager.showToast('通知設定が保存されました', 'success');
        }
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.notificationManager = new NotificationManager();
});
</script>
@endpush