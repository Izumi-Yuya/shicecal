@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>通知一覧</h4>
                    @if($notifications->where('is_read', false)->count() > 0)
                        <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">すべて既読にする</button>
                        </form>
                    @endif
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($notifications->count() > 0)
                        @foreach($notifications as $notification)
                            <div class="card mb-3 {{ $notification->isRead() ? '' : 'border-primary' }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title d-flex align-items-center">
                                                {{ $notification->title }}
                                                @if(!$notification->isRead())
                                                    <span class="badge bg-primary ms-2">新着</span>
                                                @endif
                                            </h6>
                                            <p class="card-text">{{ $notification->message }}</p>
                                            <small class="text-muted">
                                                {{ $notification->created_at->format('Y年m月d日 H:i') }}
                                                @if($notification->isRead() && $notification->read_at)
                                                    | 既読: {{ $notification->read_at->format('Y年m月d日 H:i') }}
                                                @endif
                                            </small>
                                        </div>
                                        
                                        <div class="ms-3">
                                            @if(!$notification->isRead())
                                                <button class="btn btn-sm btn-outline-primary mark-as-read" 
                                                        data-notification-id="{{ $notification->id }}">
                                                    既読にする
                                                </button>
                                            @endif
                                            
                                            @if($notification->data && isset($notification->data['facility_id']))
                                                <a href="{{ route('facilities.show', $notification->data['facility_id']) }}" 
                                                   class="btn btn-sm btn-outline-secondary ms-1">
                                                    施設を見る
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- ページネーション -->
                        <div class="d-flex justify-content-center">
                            {{ $notifications->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">通知はありません。</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark as read functionality
    document.querySelectorAll('.mark-as-read').forEach(button => {
        button.addEventListener('click', function() {
            const notificationId = this.dataset.notificationId;
            
            fetch(`/notifications/${notificationId}/mark-as-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
});
</script>
@endpush