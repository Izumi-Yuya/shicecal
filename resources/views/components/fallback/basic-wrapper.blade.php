{{-- Fallback basic wrapper component --}}
@props([
    'section' => null,
    'commentEnabled' => false
])

<div class="table-wrapper-fallback">
    <div class="alert alert-warning mb-3">
        <i class="fas fa-exclamation-triangle"></i>
        テーブルラッパーコンポーネントの読み込みに問題が発生しました。
    </div>
    
    <div class="table-content">
        {{ $slot }}
    </div>
    
    @if($commentEnabled && $section)
        <div class="mt-3">
            <div class="alert alert-info">
                <i class="fas fa-comment"></i>
                コメント機能は現在利用できません。
            </div>
        </div>
    @endif
</div>

<style>
.table-wrapper-fallback {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    background-color: #f8f9fa;
}

.table-wrapper-fallback .alert {
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.table-wrapper-fallback .table-content {
    background-color: white;
    border-radius: 0.25rem;
    padding: 1rem;
}
</style>