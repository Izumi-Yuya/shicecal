<div class="mt-4">
    <h5>コメント</h5>
    
    <!-- コメント投稿フォーム -->
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">新しいコメントを投稿</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('comments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                
                <div class="mb-3">
                    <label for="field_name" class="form-label">対象項目（任意）</label>
                    <select name="field_name" id="field_name" class="form-select">
                        <option value="">-- 項目を選択 --</option>
                        <option value="company_name">会社名</option>
                        <option value="office_code">事業所コード</option>
                        <option value="designation_number">指定番号</option>
                        <option value="facility_name">施設名</option>
                        <option value="postal_code">郵便番号</option>
                        <option value="address">住所</option>
                        <option value="phone_number">電話番号</option>
                        <option value="fax_number">FAX番号</option>
                        <option value="general">全般</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="content" class="form-label">コメント内容 <span class="text-danger">*</span></label>
                    <textarea name="content" id="content" class="form-control" rows="4" required placeholder="コメントを入力してください..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">コメントを投稿</button>
            </form>
        </div>
    </div>

    <!-- 既存コメント一覧 -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">コメント一覧</h6>
        </div>
        <div class="card-body">
            @if($facility->comments->count() > 0)
                @foreach($facility->comments->sortByDesc('created_at') as $comment)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-2">
                                    <strong>{{ $comment->poster->name }}</strong>
                                    @if($comment->field_name)
                                        <span class="badge bg-secondary ms-2">{{ $comment->field_name }}</span>
                                    @endif
                                    <span class="badge ms-2 
                                        @if($comment->status === 'pending') bg-warning
                                        @elseif($comment->status === 'in_progress') bg-info
                                        @else bg-success
                                        @endif">
                                        @if($comment->status === 'pending') 未対応
                                        @elseif($comment->status === 'in_progress') 対応中
                                        @else 対応済
                                        @endif
                                    </span>
                                </div>
                                <p class="mb-2">{{ $comment->content }}</p>
                                <small class="text-muted">
                                    投稿日時: {{ $comment->created_at->format('Y年m月d日 H:i') }}
                                    @if($comment->assignee)
                                        | 担当者: {{ $comment->assignee->name }}
                                    @endif
                                    @if($comment->resolved_at)
                                        | 解決日時: {{ $comment->resolved_at->format('Y年m月d日 H:i') }}
                                    @endif
                                </small>
                                
                                <!-- ステータス進捗表示 -->
                                <div class="mt-2">
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar 
                                            @if($comment->status === 'pending') bg-warning
                                            @elseif($comment->status === 'in_progress') bg-info
                                            @else bg-success
                                            @endif" 
                                            role="progressbar" 
                                            style="width: 
                                                @if($comment->status === 'pending') 33%
                                                @elseif($comment->status === 'in_progress') 66%
                                                @else 100%
                                                @endif">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted">未対応</small>
                                        <small class="text-muted">対応中</small>
                                        <small class="text-muted">対応済</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ステータス更新ボタン（担当者または管理者のみ） -->
                            @if(auth()->user()->isPrimaryResponder() || auth()->user()->isAdmin() || auth()->id() === $comment->assigned_to)
                                <div class="ms-3">
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
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-muted">まだコメントはありません。</p>
            @endif
        </div>
    </div>
</div>