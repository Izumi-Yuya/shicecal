{{-- Table Comment Section Component --}}
@props([
    'section' => 'default',
    'displayName' => 'コメント',
    'facility' => null,
    'comments' => null
])

@php
    // Ensure we have a facility
    if (!$facility) {
        return;
    }
    
    // Get comments if not provided
    if ($comments === null) {
        $comments = $facility->comments()
            ->where('section', $section)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    $commentCount = $comments->count();
    $sectionId = 'comment-section-' . $section;
    $formId = 'comment-form-' . $section;
@endphp

<div class="table-comment-section" data-section="{{ $section }}">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-comments me-2"></i>{{ $displayName }}のコメント
                    <span class="badge bg-secondary ms-2">{{ $commentCount }}</span>
                </h6>
                <div class="comment-actions">
                    <button class="btn btn-sm btn-outline-primary comment-form-toggle" 
                            data-section="{{ $section }}"
                            data-bs-toggle="tooltip" 
                            title="新しいコメントを追加">
                        <i class="fas fa-plus me-1"></i>追加
                    </button>

                </div>
            </div>
        </div>
        
        <div class="card-body">
            {{-- Comment Form --}}
            <div class="comment-form d-none mb-4" id="{{ $formId }}">
                <form class="comment-submit-form" data-section="{{ $section }}" data-facility-id="{{ $facility->id }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="comment-content-{{ $section }}" class="form-label">コメント内容</label>
                                <textarea class="form-control comment-content" 
                                          id="comment-content-{{ $section }}"
                                          name="comment" 
                                          rows="3" 
                                          placeholder="コメントを入力してください..."
                                          required></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="comment-priority-{{ $section }}" class="form-label">優先度</label>
                                <select class="form-select comment-priority" 
                                        id="comment-priority-{{ $section }}"
                                        name="priority">
                                    <option value="normal">通常</option>
                                    <option value="high">高</option>
                                    <option value="urgent">緊急</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane me-1"></i>投稿
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm comment-form-cancel" data-section="{{ $section }}">
                            <i class="fas fa-times me-1"></i>キャンセル
                        </button>
                    </div>
                </form>
            </div>

            {{-- Comments List --}}
            <div class="comments-list" data-section="{{ $section }}">
                @if($commentCount > 0)
                    @foreach($comments as $comment)
                        <div class="comment-item mb-3 p-3 border rounded" 
                             data-comment-id="{{ $comment->id }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="comment-header d-flex align-items-center mb-2">
                                        <div class="avatar-circle me-2">
                                            {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $comment->user->name }}</strong>
                                            <div class="comment-badges mt-1">
                                                <span class="badge bg-primary">
                                                    コメント
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="comment-content mb-2">
                                        <p class="mb-0">{{ $comment->comment }}</p>
                                    </div>
                                    
                                    <div class="comment-meta">
                                        <small class="text-muted d-flex align-items-center gap-3">
                                            <span>
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $comment->created_at->format('Y年m月d日 H:i') }}
                                            </span>
                                        </small>
                                    </div>
                                    

                                </div>
                                
                                {{-- Comment Actions --}}
                                <div class="comment-actions ms-3">
                                    @if(auth()->user()->isAdmin() || auth()->id() === $comment->user_id)
                                        <button class="btn btn-sm btn-outline-danger comment-delete" 
                                                data-comment-id="{{ $comment->id }}"
                                                data-bs-toggle="tooltip"
                                                title="コメントを削除">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="no-comments text-center py-4">
                        <i class="fas fa-comments fa-2x text-muted mb-3"></i>
                        <h6 class="text-muted">まだコメントはありません</h6>
                        <p class="text-muted mb-3">{{ $displayName }}に関するコメントを投稿してください。</p>
                        <button class="btn btn-primary btn-sm comment-form-toggle" data-section="{{ $section }}">
                            <i class="fas fa-plus me-1"></i>最初のコメントを追加
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const section = '{{ $section }}';
    
    // Comment form toggle
    document.querySelectorAll(`[data-section="${section}"].comment-form-toggle`).forEach(button => {
        button.addEventListener('click', function() {
            const form = document.getElementById('{{ $formId }}');
            const isHidden = form.classList.contains('d-none');
            
            if (isHidden) {
                form.classList.remove('d-none');
                form.querySelector('.comment-content').focus();
            } else {
                form.classList.add('d-none');
            }
        });
    });
    
    // Comment form cancel
    document.querySelectorAll(`[data-section="${section}"].comment-form-cancel`).forEach(button => {
        button.addEventListener('click', function() {
            const form = document.getElementById('{{ $formId }}');
            form.classList.add('d-none');
            form.querySelector('form').reset();
        });
    });
    
    // Comment form submission
    document.querySelectorAll(`[data-section="${section}"].comment-submit-form`).forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('section', section);
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>投稿中...';
            submitBtn.disabled = true;
            
            fetch(`/facilities/${this.dataset.facilityId}/comments`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show new comment
                    location.reload();
                } else {
                    alert('コメントの投稿に失敗しました: ' + (data.message || '不明なエラー'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('エラーが発生しました');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    });
    
    // Comment deletion
    document.querySelectorAll('.comment-delete').forEach(button => {
        button.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            
            if (confirm('このコメントを削除しますか？')) {
                fetch(`/facilities/{{ $facility->id }}/comments/${commentId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('コメントの削除に失敗しました: ' + (data.message || '不明なエラー'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('エラーが発生しました');
                });
            }
        });
    });
});
</script>
@endpush

@push('styles')
<style>
.table-comment-section .card {
    border: 1px solid #e3e6f0;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.table-comment-section .card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

.comment-item {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.comment-item {
    border-left: 4px solid #007bff;
    background-color: #f8f9fa;
}

.comment-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: #5a5c69;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.comment-badges .badge {
    font-size: 0.7rem;
}

.comment-content {
    line-height: 1.6;
}

.comment-meta {
    border-top: 1px solid #f1f3f4;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
}

.comment-progress .progress {
    border-radius: 2px;
}

.comment-form {
    background-color: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
}

.comment-form.show {
    animation: slideDown 0.3s ease-out;
}

.no-comments {
    color: #6c757d;
}

.comment-actions .dropdown-toggle {
    border: none;
    background: transparent;
}

.comment-actions .dropdown-toggle:hover {
    background-color: #f8f9fa;
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

/* Responsive adjustments */
@media (max-width: 768px) {
    .comment-header {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .comment-badges {
        margin-top: 0.25rem;
    }
    
    .comment-meta {
        flex-direction: column;
        gap: 0.25rem !important;
    }
    
    .comment-actions {
        margin-left: 0 !important;
        margin-top: 0.5rem;
    }
}
</style>
@endpush