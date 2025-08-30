<div class="mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>
            <i class="fas fa-comments me-2"></i>
            コメント
            <span class="badge bg-secondary">{{ $facility->comments->count() }}</span>
        </h5>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" onclick="toggleCommentForm()">
                <i class="fas fa-plus"></i> コメント追加
            </button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="commentOptionsDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-cog"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="filterComments('all')">
                        <i class="fas fa-list"></i> すべて表示
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="filterComments('pending')">
                        <i class="fas fa-clock text-warning"></i> 未対応のみ
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="filterComments('in_progress')">
                        <i class="fas fa-spinner text-info"></i> 対応中のみ
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="filterComments('resolved')">
                        <i class="fas fa-check text-success"></i> 対応済みのみ
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="exportComments()">
                        <i class="fas fa-download"></i> コメント出力
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- コメント投稿フォーム -->
    <div class="card mb-3 admin-card" id="commentForm" style="display: none;">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
                <i class="fas fa-edit me-2"></i>
                新しいコメントを投稿
            </h6>
        </div>
        <div class="card-body">
            <form action="{{ route('comments.store') }}" method="POST" id="commentSubmitForm" class="ajax-form">
                @csrf
                <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="field_name" class="form-label">対象項目（任意）</label>
                            <select name="field_name" id="field_name" class="form-select">
                                <option value="">-- 項目を選択 --</option>
                                <option value="company_name">会社名</option>
                                <option value="office_code">事業所コード</option>
                                <option value="designation_number">指定番号</option>
                                <option value="facility_name">施設名</option>
                                <option value="postal_code">郵便番号</option>
                                <option value="address">住所</option>
                                <option value="phone_number">電話番号</option>
                                <option value="fax_number">FAX番号</option>
                                <option value="general">全般</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="priority" class="form-label">優先度</label>
                            <select name="priority" id="priority" class="form-select">
                                <option value="normal">通常</option>
                                <option value="high">高</option>
                                <option value="urgent">緊急</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="content" class="form-label">コメント内容 <span class="text-danger">*</span></label>
                    <textarea name="content" id="content" class="form-control" rows="4" required 
                              placeholder="コメントを入力してください..."></textarea>
                    <div class="form-text">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            このコメントは一次対応者に自動通知されます
                        </small>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> コメントを投稿
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleCommentForm()">
                        <i class="fas fa-times"></i> キャンセル
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="previewComment()">
                        <i class="fas fa-eye"></i> プレビュー
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 既存コメント一覧 -->
    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-list me-2"></i>
                コメント一覧
            </h6>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="commentSort" style="width: auto;">
                    <option value="newest">新しい順</option>
                    <option value="oldest">古い順</option>
                    <option value="priority">優先度順</option>
                    <option value="status">ステータス順</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            @if($facility->comments->count() > 0)
                <div id="commentsList">
                @foreach($facility->comments->sortByDesc('created_at') as $comment)
                    <div class="comment-item border rounded p-3 mb-3 {{ $comment->status }}" data-status="{{ $comment->status }}" data-priority="{{ $comment->priority ?? 'normal' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar-circle me-2 sm">
                                        {{ strtoupper(substr($comment->poster->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <strong>{{ $comment->poster->name }}</strong>
                                        <div class="d-flex gap-1 mt-1">
                                            @if($comment->field_name)
                                                <span class="badge bg-secondary">{{ $comment->field_name }}</span>
                                            @endif
                                            <span class="badge 
                                                @if($comment->status === 'pending') bg-warning
                                                @elseif($comment->status === 'in_progress') bg-info
                                                @else bg-success
                                                @endif">
                                                @if($comment->status === 'pending') 未対応
                                                @elseif($comment->status === 'in_progress') 対応中
                                                @else 対応済
                                                @endif
                                            </span>
                                            @if(isset($comment->priority) && $comment->priority !== 'normal')
                                                <span class="badge bg-{{ $comment->priority === 'urgent' ? 'danger' : 'warning' }}">
                                                    {{ $comment->priority === 'urgent' ? '緊急' : '高' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="comment-content mb-2">
                                    <p class="mb-0">{{ $comment->content }}</p>
                                </div>
                                <div class="comment-meta">
                                    <small class="text-muted d-flex align-items-center gap-3">
                                        <span>
                                            <i class="fas fa-clock"></i>
                                            {{ $comment->created_at->format('Y年m月d日 H:i') }}
                                        </span>
                                        @if($comment->assignee)
                                            <span>
                                                <i class="fas fa-user"></i>
                                                担当: {{ $comment->assignee->name }}
                                            </span>
                                        @endif
                                        @if($comment->resolved_at)
                                            <span>
                                                <i class="fas fa-check-circle text-success"></i>
                                                解決: {{ $comment->resolved_at->format('m/d H:i') }}
                                            </span>
                                        @endif
                                    </small>
                                </div>
                                
                                <!-- ステータス進捗表示 -->
                                <div class="mt-3">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar 
                                            @if($comment->status === 'pending') bg-warning
                                            @elseif($comment->status === 'in_progress') bg-info
                                            @else bg-success
                                            @endif" 
                                            role="progressbar" 
                                            style="width: 
                                                @if($comment->status === 'pending') 33%
                                                @elseif($comment->status === 'in_progress') 66%
                                                @else 100%
                                                @endif">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted">未対応</small>
                                        <small class="text-muted">対応中</small>
                                        <small class="text-muted">対応済</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- アクションボタン -->
                            <div class="ms-3">
                                <div class="btn-group" role="group">
                                    @if(auth()->user()->isPrimaryResponder() || auth()->user()->isAdmin() || auth()->id() === $comment->assigned_to)
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                @if($comment->status !== 'pending')
                                                    <li>
                                                        <button class="dropdown-item" onclick="updateCommentStatus({{ $comment->id }}, 'pending')">
                                                            <i class="fas fa-clock text-warning"></i> 未対応に戻す
                                                        </button>
                                                    </li>
                                                @endif
                                                @if($comment->status !== 'in_progress')
                                                    <li>
                                                        <button class="dropdown-item" onclick="updateCommentStatus({{ $comment->id }}, 'in_progress')">
                                                            <i class="fas fa-spinner text-info"></i> 対応中にする
                                                        </button>
                                                    </li>
                                                @endif
                                                @if($comment->status !== 'resolved')
                                                    <li>
                                                        <button class="dropdown-item" onclick="updateCommentStatus({{ $comment->id }}, 'resolved')">
                                                            <i class="fas fa-check text-success"></i> 対応済みにする
                                                        </button>
                                                    </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item" onclick="assignComment({{ $comment->id }})">
                                                        <i class="fas fa-user-plus"></i> 担当者変更
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    @endif
                                    <button class="btn btn-sm btn-outline-secondary" onclick="replyToComment({{ $comment->id }})">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">まだコメントはありません</h6>
                    <p class="text-muted">最初のコメントを投稿してください。</p>
                    <button class="btn btn-primary" onclick="toggleCommentForm()">
                        <i class="fas fa-plus"></i> コメントを追加
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
.comment-item {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.comment-item.pending {
    border-left-color: #ffc107;
    background-color: #fff8e1;
}

.comment-item.in_progress {
    border-left-color: #17a2b8;
    background-color: #e1f5fe;
}

.comment-item.resolved {
    border-left-color: #28a745;
    background-color: #e8f5e8;
}

.comment-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.comment-content {
    line-height: 1.6;
}

.comment-meta {
    border-top: 1px solid #f1f3f4;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
}

#commentForm {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.priority-urgent {
    border-left-color: #dc3545 !important;
}

.priority-high {
    border-left-color: #fd7e14 !important;
}
</style>
@endpush

@push('scripts')
<script>
// Comment form toggle
function toggleCommentForm() {
    const form = document.getElementById('commentForm');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        document.getElementById('content').focus();
    } else {
        form.style.display = 'none';
    }
}

// Filter comments by status
function filterComments(status) {
    const comments = document.querySelectorAll('.comment-item');
    comments.forEach(comment => {
        if (status === 'all' || comment.dataset.status === status) {
            comment.style.display = 'block';
        } else {
            comment.style.display = 'none';
        }
    });
}

// Sort comments
document.getElementById('commentSort').addEventListener('change', function() {
    const container = document.getElementById('commentsList');
    const comments = Array.from(container.children);
    
    comments.sort((a, b) => {
        switch (this.value) {
            case 'newest':
                return new Date(b.dataset.created) - new Date(a.dataset.created);
            case 'oldest':
                return new Date(a.dataset.created) - new Date(b.dataset.created);
            case 'priority':
                const priorityOrder = { urgent: 3, high: 2, normal: 1 };
                return priorityOrder[b.dataset.priority] - priorityOrder[a.dataset.priority];
            case 'status':
                const statusOrder = { pending: 3, in_progress: 2, resolved: 1 };
                return statusOrder[b.dataset.status] - statusOrder[a.dataset.status];
            default:
                return 0;
        }
    });
    
    comments.forEach(comment => container.appendChild(comment));
});

// Update comment status via AJAX
function updateCommentStatus(commentId, status) {
    fetch(`/comments/${commentId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to show updated status
        } else {
            alert('ステータスの更新に失敗しました');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('エラーが発生しました');
    });
}

// Reply to comment
function replyToComment(commentId) {
    toggleCommentForm();
    const content = document.getElementById('content');
    content.value = `@コメント${commentId}への返信:\n\n`;
    content.focus();
    content.setSelectionRange(content.value.length, content.value.length);
}

// Assign comment to user
function assignComment(commentId) {
    // This would open a modal to select assignee
    console.log('Assign comment:', commentId);
}

// Preview comment
function previewComment() {
    const content = document.getElementById('content').value;
    const fieldName = document.getElementById('field_name').value;
    const priority = document.getElementById('priority').value;
    
    if (!content.trim()) {
        alert('コメント内容を入力してください');
        return;
    }
    
    // Show preview modal or inline preview
    alert(`プレビュー:\n対象: ${fieldName || '全般'}\n優先度: ${priority}\n内容: ${content}`);
}

// Export comments
function exportComments() {
    const facilityId = document.querySelector('input[name="facility_id"]').value;
    window.open(`/facilities/${facilityId}/comments/export`, '_blank');
}

// Auto-save draft (optional)
let draftTimer;
document.getElementById('content').addEventListener('input', function() {
    clearTimeout(draftTimer);
    draftTimer = setTimeout(() => {
        const draft = {
            content: this.value,
            field_name: document.getElementById('field_name').value,
            priority: document.getElementById('priority').value
        };
        localStorage.setItem('comment_draft', JSON.stringify(draft));
    }, 1000);
});

// Load draft on page load
document.addEventListener('DOMContentLoaded', function() {
    const draft = localStorage.getItem('comment_draft');
    if (draft) {
        const data = JSON.parse(draft);
        if (data.content) {
            document.getElementById('content').value = data.content;
            document.getElementById('field_name').value = data.field_name || '';
            document.getElementById('priority').value = data.priority || 'normal';
        }
    }
});

// Clear draft after successful submission
document.getElementById('commentSubmitForm').addEventListener('submit', function() {
    localStorage.removeItem('comment_draft');
});
</script>
@endpush