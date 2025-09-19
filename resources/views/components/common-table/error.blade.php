@props([
    'message' => 'テーブルの表示中にエラーが発生しました',
    'errors' => [],
    'showDetails' => false,
    'errorId' => null
])

<div class="alert alert-danger" role="alert">
    <div class="d-flex align-items-center">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>エラー</strong>
    </div>
    
    <p class="mb-2 mt-2">{{ $message }}</p>
    
    @if(!empty($errors))
        <div class="mt-3">
            <h6>詳細:</h6>
            <ul class="mb-0">
                @foreach($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    @if($showDetails && $errorId)
        <div class="mt-3">
            <small class="text-muted">
                エラーID: {{ $errorId }}
            </small>
        </div>
    @endif
    
    <div class="mt-3">
        <small class="text-muted">
            この問題が続く場合は、システム管理者にお問い合わせください。
        </small>
    </div>
</div>