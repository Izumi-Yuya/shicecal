<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - @yield('title', 'Dashboard')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
    <div id="app">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="fas fa-building me-2"></i>
                    {{ config('app.name', 'Shise-Cal') }}
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('facilities.index') }}">
                                    <i class="fas fa-building me-1"></i>施設一覧
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="exportDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-download me-1"></i>出力
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('pdf.export.index') }}">
                                        <i class="fas fa-file-pdf me-1"></i>PDF出力
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('csv.export.index') }}">
                                        <i class="fas fa-file-csv me-1"></i>CSV出力
                                    </a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="commentDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-comments me-1"></i>コメント
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('comments.my-comments') }}">
                                        <i class="fas fa-user-edit me-1"></i>マイコメント
                                    </a></li>
                                    @if(auth()->user()->isPrimaryResponder() || auth()->user()->isAdmin())
                                        <li><a class="dropdown-item" href="{{ route('comments.assigned') }}">
                                            <i class="fas fa-tasks me-1"></i>担当コメント
                                        </a></li>
                                    @endif
                                </ul>
                            </li>
                        @endauth
                    </ul>
                    
                    <ul class="navbar-nav">
                        @auth
                            <!-- Notification Bell -->
                            <li class="nav-item dropdown">
                                <a class="nav-link position-relative" href="{{ route('notifications.index') }}" id="notificationBell">
                                    <i class="fas fa-bell"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                          id="unread-count" style="display: none;">
                                        0
                                    </span>
                                </a>
                            </li>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-1"></i>
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('notifications.index') }}">
                                        <i class="fas fa-bell me-1"></i>通知
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-1"></i>ログアウト
                                    </a></li>
                                </ul>
                                <form id="logout-form" action="#" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link" href="#">ログイン</a>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-4">
            @yield('content')
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    @auth
    <script>
        // Update notification count
        function updateNotificationCount() {
            fetch('{{ route("notifications.unread-count") }}')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('unread-count');
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error fetching notification count:', error));
        }

        // Update count on page load
        document.addEventListener('DOMContentLoaded', updateNotificationCount);
        
        // Update count every 30 seconds
        setInterval(updateNotificationCount, 30000);
    </script>
    @endauth
    
    @stack('scripts')
</body>
</html>