@extends('layouts.app')

@section('title', 'ユーザー管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-users me-2"></i>
                    ユーザー管理
                </h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="fas fa-plus"></i> 新規ユーザー登録
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userActionsDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> 操作
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportUsers()">
                                <i class="fas fa-download"></i> ユーザー一覧エクスポート
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="bulkRoleUpdate()">
                                <i class="fas fa-users-cog"></i> 一括ロール変更
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h4>{{ $stats['total_users'] ?? 0 }}</h4>
                            <p class="mb-0">総ユーザー数</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h4>{{ $stats['active_users'] ?? 0 }}</h4>
                            <p class="mb-0">アクティブ</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h4>{{ $stats['admin_users'] ?? 0 }}</h4>
                            <p class="mb-0">管理者</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h4>{{ $stats['editor_users'] ?? 0 }}</h4>
                            <p class="mb-0">編集者</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-secondary text-white">
                        <div class="card-body text-center">
                            <h4>{{ $stats['viewer_users'] ?? 0 }}</h4>
                            <p class="mb-0">閲覧者</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h4>{{ $stats['inactive_users'] ?? 0 }}</h4>
                            <p class="mb-0">無効</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">検索・絞り込み</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.users.index') }}" id="userSearchForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="search" class="form-label">メールアドレス・名前</label>
                                    <input type="text" name="search" id="search" 
                                           class="form-control" 
                                           value="{{ request('search') }}" 
                                           placeholder="検索キーワード">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="role" class="form-label">ロール</label>
                                    <select name="role" id="role" class="form-select">
                                        <option value="">すべてのロール</option>
                                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>管理者</option>
                                        <option value="editor" {{ request('role') == 'editor' ? 'selected' : '' }}>編集者</option>
                                        <option value="primary_responder" {{ request('role') == 'primary_responder' ? 'selected' : '' }}>主担当者</option>
                                        <option value="approver" {{ request('role') == 'approver' ? 'selected' : '' }}>承認者</option>
                                        <option value="viewer" {{ request('role') == 'viewer' ? 'selected' : '' }}>閲覧者</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="status" class="form-label">ステータス</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">すべて</option>
                                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>アクティブ</option>
                                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>無効</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="department" class="form-label">部門</label>
                                    <input type="text" name="department" id="department" 
                                           class="form-control" 
                                           value="{{ request('department') }}" 
                                           placeholder="部門名">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> 検索
                                        </button>
                                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> クリア
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        ユーザー一覧 
                        <span class="badge bg-secondary">{{ $users->total() ?? 0 }}件</span>
                    </h5>
                    <div class="d-flex gap-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">全選択</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($users) && $users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="selectAllHeader" class="form-check-input">
                                        </th>
                                        <th>ユーザー情報</th>
                                        <th>ロール</th>
                                        <th>部門</th>
                                        <th>権限範囲</th>
                                        <th>ステータス</th>
                                        <th>最終ログイン</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input user-checkbox" 
                                                       value="{{ $user->id }}">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle me-2">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <strong>{{ $user->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $user->email }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ getRoleBadgeColor($user->role) }}">
                                                    {{ getRoleDisplayName($user->role) }}
                                                </span>
                                            </td>
                                            <td>{{ $user->department ?? '-' }}</td>
                                            <td>
                                                @if($user->access_scope)
                                                    <small class="text-muted">
                                                        {{ getAccessScopeDisplay($user->access_scope) }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}">
                                                    {{ $user->is_active ? 'アクティブ' : '無効' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($user->last_login_at)
                                                    <small>{{ $user->last_login_at->format('Y/m/d H:i') }}</small>
                                                @else
                                                    <span class="text-muted">未ログイン</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editUser({{ $user->id }})">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info" 
                                                            onclick="viewUserDetails({{ $user->id }})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    @if($user->id !== auth()->id())
                                                        <button class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}" 
                                                                onclick="toggleUserStatus({{ $user->id }})">
                                                            <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }}"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if(isset($users) && method_exists($users, 'links'))
                            <div class="d-flex justify-content-center">
                                {{ $users->appends(request()->query())->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">ユーザーが見つかりませんでした。</h5>
                            <p class="text-muted">検索条件を変更するか、新しいユーザーを登録してください。</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新規ユーザー登録</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createUserForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_name" class="form-label">名前 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="create_email" name="email" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_password" class="form-label">パスワード <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="create_password" name="password" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_role" class="form-label">ロール <span class="text-danger">*</span></label>
                                <select class="form-select" id="create_role" name="role" required>
                                    <option value="">ロールを選択</option>
                                    <option value="admin">管理者</option>
                                    <option value="editor">編集者</option>
                                    <option value="primary_responder">主担当者</option>
                                    <option value="approver">承認者</option>
                                    <option value="viewer">閲覧者</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_department" class="form-label">部門</label>
                                <input type="text" class="form-control" id="create_department" name="department">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_is_active" class="form-label">ステータス</label>
                                <select class="form-select" id="create_is_active" name="is_active">
                                    <option value="1">アクティブ</option>
                                    <option value="0">無効</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="accessScopeSection" style="display: none;">
                        <label class="form-label">閲覧権限範囲</label>
                        <div class="border p-3 rounded">
                            <div class="mb-2">
                                <label for="scope_type" class="form-label">権限タイプ</label>
                                <select class="form-select" id="scope_type" name="scope_type">
                                    <option value="all">全施設</option>
                                    <option value="department">部門限定</option>
                                    <option value="region">地区限定</option>
                                    <option value="facility">施設限定</option>
                                </select>
                            </div>
                            <div id="scopeDetails" style="display: none;">
                                <textarea class="form-control" id="scope_details" name="scope_details" 
                                          placeholder="権限範囲の詳細を入力してください"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 登録
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
</style>
@endpush

@push('scripts')
<script>
// Role-based access scope visibility
document.getElementById('create_role').addEventListener('change', function() {
    const role = this.value;
    const accessScopeSection = document.getElementById('accessScopeSection');
    
    if (role === 'viewer' || role === 'primary_responder') {
        accessScopeSection.style.display = 'block';
    } else {
        accessScopeSection.style.display = 'none';
    }
});

// Scope type change handler
document.getElementById('scope_type').addEventListener('change', function() {
    const scopeDetails = document.getElementById('scopeDetails');
    
    if (this.value !== 'all') {
        scopeDetails.style.display = 'block';
    } else {
        scopeDetails.style.display = 'none';
    }
});

// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Create user form submission
document.getElementById('createUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('{{ route("admin.users.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('エラーが発生しました：' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('エラーが発生しました');
    });
});

// User management functions
function editUser(userId) {
    // Implementation for edit user modal
    console.log('Edit user:', userId);
}

function viewUserDetails(userId) {
    // Implementation for view user details modal
    console.log('View user details:', userId);
}

function toggleUserStatus(userId) {
    if (confirm('ユーザーのステータスを変更しますか？')) {
        fetch(`/admin/users/${userId}/toggle-status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('エラーが発生しました');
            }
        });
    }
}

function exportUsers() {
    const form = document.getElementById('userSearchForm');
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    
    const exportUrl = '{{ route("admin.users.export") }}' + '?' + params.toString();
    
    const link = document.createElement('a');
    link.href = exportUrl;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function bulkRoleUpdate() {
    const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
    
    if (selectedUsers.length === 0) {
        alert('ユーザーを選択してください');
        return;
    }
    
    const newRole = prompt('新しいロールを選択してください:\nadmin, editor, primary_responder, approver, viewer');
    
    if (newRole && ['admin', 'editor', 'primary_responder', 'approver', 'viewer'].includes(newRole)) {
        if (confirm(`選択した${selectedUsers.length}人のユーザーのロールを${newRole}に変更しますか？`)) {
            fetch('{{ route("admin.users.bulk-update-role") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_ids: selectedUsers,
                    role: newRole
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('エラーが発生しました');
                }
            });
        }
    }
}

// Helper functions for role display (these would typically come from backend)
function getRoleBadgeColor(role) {
    const colors = {
        'admin': 'danger',
        'editor': 'primary',
        'primary_responder': 'warning',
        'approver': 'info',
        'viewer': 'secondary'
    };
    return colors[role] || 'secondary';
}

function getRoleDisplayName(role) {
    const names = {
        'admin': '管理者',
        'editor': '編集者',
        'primary_responder': '主担当者',
        'approver': '承認者',
        'viewer': '閲覧者'
    };
    return names[role] || role;
}

function getAccessScopeDisplay(scope) {
    if (!scope) return '-';
    // This would be implemented based on the actual scope structure
    return '権限設定済み';
}
</script>
@endpush
@endsection