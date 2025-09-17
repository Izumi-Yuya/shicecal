@props([
    'title' => '入力エラーがあります',
    'icon' => 'fas fa-exclamation-triangle',
    'dismissible' => true,
    'showList' => true
])

@if ($errors->any())
    <div class="alert alert-danger {{ $dismissible ? 'alert-dismissible' : '' }} fade show mb-4" 
         role="alert" 
         aria-labelledby="error-heading"
         aria-describedby="error-list">
        <div class="d-flex align-items-start">
            <i class="{{ $icon }} me-2 mt-1" aria-hidden="true"></i>
            <div class="flex-grow-1">
                <h6 class="alert-heading mb-2" id="error-heading">{{ $title }}</h6>
                @if ($showList)
                    <ul class="mb-0" id="error-list" role="list">
                        @foreach ($errors->all() as $error)
                            <li role="listitem">{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        @if ($dismissible)
            <button type="button" 
                    class="btn-close" 
                    data-bs-dismiss="alert" 
                    aria-label="エラーメッセージを閉じる"
                    title="エラーメッセージを閉じる"></button>
        @endif
    </div>
@endif