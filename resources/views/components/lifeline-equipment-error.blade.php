@props([
    'hasError' => false,
    'errorMessage' => 'データの読み込み中にエラーが発生しました。'
])

@if($hasError)
<div class="alert alert-danger" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    {{ $errorMessage }}
</div>
@endif