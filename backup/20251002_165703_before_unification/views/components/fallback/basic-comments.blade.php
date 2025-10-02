{{-- Fallback basic comments component --}}
@props([
    'section' => null,
    'facilityId' => null
])

<div class="comments-fallback">
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        コメントシステムの読み込みに問題が発生しました。
    </div>
    
    @if($section)
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-comments"></i>
                    {{ $section }} - コメント
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">
                    コメント機能は現在利用できません。システム管理者にお問い合わせください。
                </p>
            </div>
        </div>
    @endif
</div>

<style>
.comments-fallback .alert {
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.comments-fallback .card {
    border: 1px solid #dee2e6;
}

.comments-fallback .card-header {
    border-bottom: 1px solid #dee2e6;
}
</style>