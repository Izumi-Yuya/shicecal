@if($comments->count() > 0)
    @foreach($comments as $comment)
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="card-title">
                            <a href="{{ route('facilities.show', $comment->facility) }}">
                                {{ $comment->facility->facility_name }}
                            </a>
                            @if($comment->field_name)
                                <span class="badge bg-secondary ms-2">{{ $comment->field_name }}</span>
                            @endif
                        </h6>
                        <p class="card-text">{{ $comment->content }}</p>
                        <small class="text-muted">
                            投稿者: {{ $comment->poster->name }} | 
                            投稿日時: {{ $comment->created_at->format('Y年m月d日 H:i') }}
                            @if($comment->resolved_at)
                                | 解決日時: {{ $comment->resolved_at->format('Y年m月d日 H:i') }}
                            @endif
                        </small>
                    </div>
                    
                    <div class="ms-3 d-flex align-items-center">
                        <span class="badge me-2
                            @if($comment->status === 'pending') bg-warning
                            @elseif($comment->status === 'in_progress') bg-info
                            @else bg-success
                            @endif">
                            @if($comment->status === 'pending') 未対応
                            @elseif($comment->status === 'in_progress') 対応中
                            @else 対応済
                            @endif
                        </span>
                        
                        @if($showActions)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    ステータス変更
                                </button>
                                <ul class="dropdown-menu">
                                    @if($comment->status !== 'pending')
                                        <li>
                                            <form action="{{ route('comments.update-status', $comment) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" class="dropdown-item">未対応に戻す</button>
                                            </form>
                                        </li>
                                    @endif
                                    @if($comment->status !== 'in_progress')
                                        <li>
                                            <form action="{{ route('comments.update-status', $comment) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="in_progress">
                                                <button type="submit" class="dropdown-item">対応中にする</button>
                                            </form>
                                        </li>
                                    @endif
                                    @if($comment->status !== 'resolved')
                                        <li>
                                            <form action="{{ route('comments.update-status', $comment) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="resolved">
                                                <button type="submit" class="dropdown-item">対応済みにする</button>
                                            </form>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="text-center py-4">
        <p class="text-muted">該当するコメントはありません。</p>
    </div>
@endif