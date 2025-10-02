{{-- Fallback error message component --}}
@props([
    'errorType' => 'component',
    'componentName' => 'unknown',
    'errorId' => null
])

@php
    $errorId = $errorId ?? uniqid('error_');
@endphp

<div class="error-fallback alert alert-danger" role="alert">
    <div class="d-flex align-items-center">
        <i class="fas fa-exclamation-circle me-2"></i>
        <div class="flex-grow-1">
            <h6 class="alert-heading mb-1">コンポーネントエラー</h6>
            <p class="mb-1">
                @switch($errorType)
                    @case('component')
                        コンポーネント「{{ $componentName }}」の読み込みに失敗しました。
                        @break
                    @case('config')
                        テーブル設定の読み込みに失敗しました。
                        @break
                    @case('render')
                        テーブルの描画に失敗しました。
                        @break
                    @default
                        システムエラーが発生しました。
                @endswitch
            </p>
            <small class="text-muted">
                エラーID: {{ $errorId }} | 
                時刻: {{ now()->format('Y-m-d H:i:s') }}
            </small>
        </div>
    </div>
    
    @if(config('app.debug'))
        <hr>
        <details>
            <summary class="text-muted">デバッグ情報</summary>
            <div class="mt-2">
                <strong>エラータイプ:</strong> {{ $errorType }}<br>
                <strong>コンポーネント名:</strong> {{ $componentName }}<br>
                <strong>タイムスタンプ:</strong> {{ now()->toISOString() }}
            </div>
        </details>
    @endif
</div>

<style>
.error-fallback {
    border-left: 4px solid #dc3545;
}

.error-fallback .alert-heading {
    color: #721c24;
}

.error-fallback details summary {
    cursor: pointer;
    font-size: 0.875rem;
}

.error-fallback details[open] summary {
    margin-bottom: 0.5rem;
}
</style>