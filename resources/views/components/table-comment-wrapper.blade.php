{{-- Table Comment Wrapper Component --}}
@props([
    'tableId' => null,
    'config' => [],
    'data' => [],
    'section' => null,
    'facility' => null,
    'commentEnabled' => true,
    'responsive' => true
])

@php
    // Generate unique table ID if not provided
    $tableId = $tableId ?? 'table-' . uniqid();
    
    // Comment section configuration
    $commentSection = $section ?? 'default';
    $commentDisplayName = $config['comment_display_name'] ?? ucfirst($commentSection);
    
    // Get existing comments for this section
    $sectionComments = collect();
    if ($facility && $commentEnabled) {
        $sectionComments = $facility->comments()
            ->where('section', $commentSection)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    $commentCount = $sectionComments->count();
@endphp

<div class="table-comment-wrapper" data-table-id="{{ $tableId }}" data-section="{{ $commentSection }}">
    {{-- Table Header with Comment Controls --}}
    @if($commentEnabled)
        <div class="table-header mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-table text-primary me-2"></i>
                    {{ $commentDisplayName }}
                </h5>
                <div class="table-comment-controls">
                    <button class="btn btn-outline-primary btn-sm comment-toggle" 
                            data-section="{{ $commentSection }}" 
                            data-bs-toggle="tooltip" 
                            title="コメントを表示/非表示"
                            aria-label="{{ $commentDisplayName }}のコメントを表示または非表示にする">
                        <i class="fas fa-comment me-1" aria-hidden="true"></i>
                        コメント
                        <span class="badge bg-primary ms-1 comment-count" 
                              data-section="{{ $commentSection }}" 
                              aria-label="コメント数">{{ $commentCount }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Universal Table Component --}}
    <x-universal-table 
        :table-id="$tableId"
        :config="$config"
        :data="$data"
        :section="$section"
        :facility="$facility"
        :comment-enabled="false"
        :responsive="$responsive"
    />

    {{-- Comment Section --}}
    @if($commentEnabled)
        <div class="comment-section mt-4 {{ $commentCount > 0 ? '' : 'd-none' }}" 
             data-section="{{ $commentSection }}" 
             id="comment-section-{{ $commentSection }}">
            <x-table-comment-section 
                :section="$commentSection"
                :display-name="$commentDisplayName"
                :facility="$facility"
                :comments="$sectionComments"
            />
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize comment toggle functionality
    const commentToggle = document.querySelector('[data-section="{{ $commentSection }}"].comment-toggle');
    const commentSection = document.getElementById('comment-section-{{ $commentSection }}');
    
    if (commentToggle && commentSection) {
        commentToggle.addEventListener('click', function() {
            const isHidden = commentSection.classList.contains('d-none');
            
            if (isHidden) {
                commentSection.classList.remove('d-none');
                commentSection.style.display = 'block';
                
                // Add animation
                commentSection.style.opacity = '0';
                commentSection.style.transform = 'translateY(-10px)';
                
                requestAnimationFrame(() => {
                    commentSection.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    commentSection.style.opacity = '1';
                    commentSection.style.transform = 'translateY(0)';
                });
                
                // Update button state
                this.classList.add('active');
                this.setAttribute('aria-expanded', 'true');
            } else {
                commentSection.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                commentSection.style.opacity = '0';
                commentSection.style.transform = 'translateY(-10px)';
                
                setTimeout(() => {
                    commentSection.classList.add('d-none');
                    commentSection.style.display = 'none';
                }, 300);
                
                // Update button state
                this.classList.remove('active');
                this.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Initialize tooltip
        if (typeof bootstrap !== 'undefined') {
            new bootstrap.Tooltip(commentToggle);
        }
    }
});

// Update comment count when comments are added/removed
function updateCommentCount(section, count) {
    const countBadge = document.querySelector(`[data-section="${section}"].comment-count`);
    if (countBadge) {
        countBadge.textContent = count;
        countBadge.setAttribute('aria-label', `コメント数: ${count}`);
    }
}

// Show comment section when new comment is added
function showCommentSection(section) {
    const commentSection = document.getElementById(`comment-section-${section}`);
    const commentToggle = document.querySelector(`[data-section="${section}"].comment-toggle`);
    
    if (commentSection && commentSection.classList.contains('d-none')) {
        commentSection.classList.remove('d-none');
        commentSection.style.display = 'block';
        
        if (commentToggle) {
            commentToggle.classList.add('active');
            commentToggle.setAttribute('aria-expanded', 'true');
        }
    }
}

// Global functions for comment integration
window.TableCommentWrapper = {
    updateCommentCount,
    showCommentSection
};
</script>
@endpush

@push('styles')
<style>
.table-comment-wrapper {
    margin-bottom: 2rem;
}

.table-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.75rem;
}

.table-comment-controls .comment-toggle {
    transition: all 0.2s ease;
    border-radius: 20px;
}

.table-comment-controls .comment-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table-comment-controls .comment-toggle.active {
    background-color: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

.table-comment-controls .comment-count {
    font-size: 0.75rem;
    min-width: 1.5rem;
    height: 1.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.comment-section {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
}

/* Animation for comment section */
.comment-section.show {
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

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start !important;
    }
    
    .table-comment-controls {
        width: 100%;
        display: flex;
        justify-content: flex-end;
    }
}
</style>
@endpush