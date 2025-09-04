@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- 固定ヘッダーカード -->
            <div class="facility-header-card card mb-3 sticky-top">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="facility-icon me-3">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 facility-name">{{ $facility->facility_name }}</h5>
                                    <div class="facility-meta">
                                        <span class="badge bg-primary me-2">{{ $facility->office_code }}</span>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            更新日時: {{ $facility->updated_at->format('Y年m月d日 H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="facility-actions">
                                @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                                    <a href="{{ route('facilities.edit-basic-info', $facility) }}" class="btn btn-primary btn-sm me-2">
                                        <i class="fas fa-edit"></i> 編集
                                    </a>
                                @endif
                                <a href="{{ route('facilities.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> 一覧に戻る
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- アラート表示エリア -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- タブナビゲーション -->
            <div class="facility-detail-container">
                <div class="tab-navigation mb-4">
                    <ul class="nav nav-tabs" id="facilityTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab" aria-controls="basic-info" aria-selected="true">
                                <i class="fas fa-info-circle me-2"></i>基本
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="land-tab" data-bs-toggle="tab" data-bs-target="#land-info" type="button" role="tab" aria-controls="land-info" aria-selected="false">
                                <i class="fas fa-map me-2"></i>土地
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="tab-content" id="facilityTabContent">
                    <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-tab">
                        @include('facilities.partials.basic-info', ['facility' => $facility])
                    </div>
                    <div class="tab-pane fade" id="land-info" role="tabpanel" aria-labelledby="land-tab">
                        @include('facilities.partials.land-info', ['facility' => $facility, 'landInfo' => $landInfo ?? null])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* タブナビゲーションのスタイル */
.facility-detail-container .nav-tabs {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 0;
}

.facility-detail-container .nav-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    background: none;
    color: #6c757d;
    font-weight: 500;
    padding: 1rem 1.5rem;
    margin-bottom: -2px;
    transition: all 0.3s ease;
}

.facility-detail-container .nav-tabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.facility-detail-container .nav-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    background-color: transparent;
}

.facility-detail-container .tab-content {
    padding-top: 2rem;
}

/* レスポンシブデザイン */
@media (max-width: 768px) {
    .facility-detail-container .nav-tabs .nav-link {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
    
    .facility-detail-container .nav-tabs .nav-link i {
        display: none;
    }
    
    .facility-detail-container .tab-content {
        padding-top: 1rem;
    }
}

@media (max-width: 576px) {
    .facility-detail-container .nav-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .facility-detail-container .nav-tabs .nav-item {
        flex-shrink: 0;
    }
    
    .facility-detail-container .nav-tabs .nav-link {
        white-space: nowrap;
        padding: 0.75rem;
    }
}

/* タブコンテンツのアニメーション */
.facility-detail-container .tab-pane {
    opacity: 0;
    transform: translateY(10px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.facility-detail-container .tab-pane.show.active {
    opacity: 1;
    transform: translateY(0);
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const facilityId = {{ $facility->id }};
    
    // コメント機能の初期化
    initCommentSystem();
    
    // タブ切り替え時のイベント処理
    const tabTriggerList = [].slice.call(document.querySelectorAll('#facilityTabs button[data-bs-toggle="tab"]'));
    tabTriggerList.forEach(function (tabTrigger) {
        tabTrigger.addEventListener('shown.bs.tab', function (event) {
            // タブが表示された時の処理
            const targetTab = event.target.getAttribute('data-bs-target');
            
            // 土地タブが表示された時の処理
            if (targetTab === '#land-info') {
                // 土地情報のコメント数を更新
                updateLandInfoCommentCounts();
            }
        });
    });
    
    function initCommentSystem() {
        // コメントトグルボタンのイベントリスナー
        document.querySelectorAll('.comment-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const section = this.dataset.section;
                toggleCommentSection(section);
            });
        });
        
        // コメント送信ボタンのイベントリスナー
        document.querySelectorAll('.comment-submit').forEach(button => {
            button.addEventListener('click', function() {
                const section = this.dataset.section;
                submitComment(section);
            });
        });
        
        // Enterキーでコメント送信
        document.querySelectorAll('.comment-input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const section = this.dataset.section;
                    submitComment(section);
                }
            });
        });
        
        // 初期コメント数を読み込み
        loadAllCommentCounts();
    }
    
    function toggleCommentSection(section) {
        const commentSection = document.querySelector(`.comment-section[data-section="${section}"]`);
        const isVisible = !commentSection.classList.contains('d-none');
        
        if (isVisible) {
            commentSection.classList.add('d-none');
        } else {
            commentSection.classList.remove('d-none');
            loadComments(section);
        }
    }
    
    function loadComments(section) {
        const commentList = document.querySelector(`.comment-list[data-section="${section}"]`);
        
        fetch(`/facilities/${facilityId}/comments/${section}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayComments(section, data.comments);
                }
            })
            .catch(error => {
                console.error('コメントの読み込みに失敗しました:', error);
                showToast('コメントの読み込みに失敗しました。', 'error');
            });
    }
    
    function displayComments(section, comments) {
        const commentList = document.querySelector(`.comment-list[data-section="${section}"]`);
        
        if (comments.length === 0) {
            commentList.innerHTML = '<div class="text-muted text-center py-2"><small>コメントはありません</small></div>';
            return;
        }
        
        const commentsHtml = comments.map(comment => `
            <div class="comment-item mb-2 p-2 border rounded bg-light" data-comment-id="${comment.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="comment-text">${escapeHtml(comment.comment)}</div>
                        <small class="text-muted">
                            <i class="fas fa-user me-1"></i>${escapeHtml(comment.user_name)}
                            <i class="fas fa-clock ms-2 me-1"></i>${comment.created_at}
                        </small>
                    </div>
                    ${comment.can_delete ? `
                        <button class="btn btn-outline-danger btn-sm ms-2 comment-delete" 
                                data-comment-id="${comment.id}" 
                                data-section="${section}"
                                title="削除">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
        
        commentList.innerHTML = commentsHtml;
        
        // 削除ボタンのイベントリスナーを追加
        commentList.querySelectorAll('.comment-delete').forEach(button => {
            button.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                const section = this.dataset.section;
                deleteComment(commentId, section);
            });
        });
    }
    
    function submitComment(section) {
        const input = document.querySelector(`.comment-input[data-section="${section}"]`);
        const comment = input.value.trim();
        
        if (!comment) {
            showToast('コメントを入力してください。', 'warning');
            return;
        }
        
        const submitButton = document.querySelector(`.comment-submit[data-section="${section}"]`);
        submitButton.disabled = true;
        
        fetch(`/facilities/${facilityId}/comments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                section: section,
                comment: comment
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                loadComments(section);
                updateCommentCount(section);
                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'コメントの投稿に失敗しました。', 'error');
            }
        })
        .catch(error => {
            console.error('コメントの投稿に失敗しました:', error);
            showToast('コメントの投稿に失敗しました。', 'error');
        })
        .finally(() => {
            submitButton.disabled = false;
        });
    }
    
    function deleteComment(commentId, section) {
        if (!confirm('このコメントを削除しますか？')) {
            return;
        }
        
        fetch(`/facilities/${facilityId}/comments/${commentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadComments(section);
                updateCommentCount(section);
                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'コメントの削除に失敗しました。', 'error');
            }
        })
        .catch(error => {
            console.error('コメントの削除に失敗しました:', error);
            showToast('コメントの削除に失敗しました。', 'error');
        });
    }
    
    function loadAllCommentCounts() {
        const sections = ['basic_info', 'contact_info', 'building_info', 'facility_info', 'services'];
        
        sections.forEach(section => {
            updateCommentCount(section);
        });
    }
    
    function updateLandInfoCommentCounts() {
        const landSections = ['land_basic', 'land_financial', 'land_management', 'land_owner', 'land_notes'];
        
        landSections.forEach(section => {
            updateCommentCount(section);
        });
    }
    
    function updateCommentCount(section) {
        fetch(`/facilities/${facilityId}/comments/${section}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const countElement = document.querySelector(`.comment-count[data-section="${section}"]`);
                    if (countElement) {
                        countElement.textContent = data.comments.length;
                        
                        // コメント数に応じてボタンのスタイルを変更
                        const toggleButton = countElement.closest('.comment-toggle');
                        if (data.comments.length > 0) {
                            toggleButton.classList.remove('btn-outline-secondary', 'btn-outline-light');
                            toggleButton.classList.add('btn-warning');
                        } else {
                            toggleButton.classList.remove('btn-warning');
                            if (toggleButton.closest('.card-section-header')) {
                                toggleButton.classList.add('btn-outline-light');
                            } else {
                                toggleButton.classList.add('btn-outline-secondary');
                            }
                        }
                    }
                }
            })
            .catch(error => {
                console.error('コメント数の取得に失敗しました:', error);
            });
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showToast(message, type = 'info') {
        // 既存のtoast機能を使用
        if (window.ShiseCal && window.ShiseCal.utils && window.ShiseCal.utils.showToast) {
            window.ShiseCal.utils.showToast(message, type);
        } else {
            alert(message);
        }
    }
});
</script>
@endpush