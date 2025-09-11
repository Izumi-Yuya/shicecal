@props([
    'cancelRoute' => null,
    'cancelText' => 'キャンセル',
    'submitText' => '保存',
    'submitIcon' => 'fas fa-save',
    'class' => 'd-flex justify-content-between align-items-center mb-4'
])

<div class="form-actions {{ $class }}" role="group" aria-label="フォームアクション">
    <div>
        @if($cancelRoute)
            <a href="{{ $cancelRoute }}" 
               class="btn btn-outline-secondary"
               role="button"
               aria-label="変更をキャンセルして前のページに戻る">
                <i class="fas fa-times me-2" aria-hidden="true"></i>{{ $cancelText }}
            </a>
        @endif
    </div>
    
    <div class="d-flex gap-2">
        @isset($additional)
            {{ $additional }}
        @endisset
        
        <button type="submit" 
                class="btn btn-primary"
                aria-label="フォームの内容を保存する"
                aria-describedby="submit-help">
            <i class="{{ $submitIcon }} me-2" aria-hidden="true"></i>{{ $submitText }}
        </button>
        <div id="submit-help" class="sr-only">
            Ctrl+Sキーでも保存できます
        </div>
    </div>
</div>