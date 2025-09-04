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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS (for admin users) -->
    @if(auth()->check() && auth()->user()->isAdmin())
        <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    @endif
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Admin CSS -->
    @if(auth()->check() && auth()->user()->isAdmin())
        @vite('resources/css/admin.css')
    @endif
    
    @stack('styles')
</head>
<body>
    <div id="app">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top figma-header">
            <div class="figma-header-container">
                <!-- Left side: Logo (prominent) -->
                <div class="d-flex align-items-center header-left">
                    <a class="navbar-brand d-flex align-items-center me-4" href="{{ url('/') }}">
                        <img src="{{ asset('images/shicecal-logo.png') }}" 
                             alt="Shise-Cal Logo" 
                             class="navbar-logo me-3"
                             onerror="this.style.display='none';">
                        <span class="navbar-brand-text">{{ config('app.name', 'Shise-Cal') }}</span>
                    </a>
                    <button class="btn btn-outline-light" type="button" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <!-- Right side: Navigation -->
                <div class="d-flex align-items-center header-right">
                    @auth
                        <!-- Notification Bell -->
                        <a class="nav-link position-relative me-2" href="{{ route('notifications.index') }}" id="notificationBell">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                  id="unread-count" style="display: none;">
                                0
                            </span>
                        </a>
                        
                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-2"></i>
                                <span>{{ Auth::user()->name }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('my-page.index') }}">
                                    <i class="fas fa-home me-2"></i>マイページ
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('notifications.index') }}">
                                    <i class="fas fa-bell me-2"></i>通知
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt me-2"></i>ログアウト
                                </a></li>
                            </ul>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    @else
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            ログイン
                        </a>
                    @endauth
                    </div>
                </div>
            </div>
        </nav>

        <div class="d-flex">
            <!-- Sidebar -->
            @auth
            <nav id="sidebar" class="sidebar bg-light border-end">
                <div class="sidebar-header p-3 border-bottom">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-building me-2"></i>
                        メニュー
                    </h5>
                </div>
                
                <div class="sidebar-content">
                    <ul class="nav flex-column">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('facilities.index') ? 'active' : '' }}" 
                               href="{{ route('facilities.index') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                ダッシュボード
                            </a>
                        </li>
                        
                        <!-- Facilities Section -->
                        <li class="nav-item">
                            <div class="nav-section-header">
                                <i class="fas fa-building me-2"></i>
                                施設管理
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('facilities.*') ? 'active' : '' }}" 
                               href="{{ route('facilities.index') }}">
                                <i class="fas fa-list me-2"></i>
                                施設一覧
                            </a>
                        </li>
                        @if(auth()->user()->canEdit())
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('facilities.create') }}">
                                <i class="fas fa-plus me-2"></i>
                                施設登録
                            </a>
                        </li>
                        @endif
                        
                        <!-- Maintenance Section -->
                        <li class="nav-item">
                            <div class="nav-section-header">
                                <i class="fas fa-tools me-2"></i>
                                修繕管理
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('maintenance.*') ? 'active' : '' }}" 
                               href="{{ route('maintenance.index') }}">
                                <i class="fas fa-history me-2"></i>
                                修繕履歴
                            </a>
                        </li>
                        @if(auth()->user()->canEdit())
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('maintenance.create') }}">
                                <i class="fas fa-plus me-2"></i>
                                修繕記録追加
                            </a>
                        </li>
                        @endif
                        
                        <!-- Export Section -->
                        <li class="nav-item">
                            <div class="nav-section-header">
                                <i class="fas fa-download me-2"></i>
                                出力機能
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('pdf.export.*') ? 'active' : '' }}" 
                               href="{{ route('pdf.export.index') }}">
                                <i class="fas fa-file-pdf me-2"></i>
                                PDF出力
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('csv.export.*') ? 'active' : '' }}" 
                               href="{{ route('csv.export.index') }}">
                                <i class="fas fa-file-csv me-2"></i>
                                CSV出力
                            </a>
                        </li>
                        
                        <!-- Comments Section -->
                        <li class="nav-item">
                            <div class="nav-section-header">
                                <i class="fas fa-comments me-2"></i>
                                コメント管理
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('comments.my-comments') ? 'active' : '' }}" 
                               href="{{ route('comments.my-comments') }}">
                                <i class="fas fa-user-edit me-2"></i>
                                マイコメント
                            </a>
                        </li>
                        @if(auth()->user()->isPrimaryResponder() || auth()->user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('comments.assigned') ? 'active' : '' }}" 
                               href="{{ route('comments.assigned') }}">
                                <i class="fas fa-tasks me-2"></i>
                                担当コメント
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('comments.status-dashboard') ? 'active' : '' }}" 
                               href="{{ route('comments.status-dashboard') }}">
                                <i class="fas fa-chart-bar me-2"></i>
                                ステータス管理
                            </a>
                        </li>
                        @endif
                        
                        <!-- Annual Confirmation Section -->
                        <li class="nav-item">
                            <div class="nav-section-header">
                                <i class="fas fa-calendar-check me-2"></i>
                                年次確認
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('annual-confirmation.*') ? 'active' : '' }}" 
                               href="{{ route('annual-confirmation.index') }}">
                                <i class="fas fa-clipboard-check me-2"></i>
                                年次確認一覧
                            </a>
                        </li>
                        @if(auth()->user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('annual-confirmation.create') }}">
                                <i class="fas fa-plus me-2"></i>
                                確認依頼作成
                            </a>
                        </li>
                        @endif
                        
                        <!-- Admin Section -->
                        @if(auth()->user()->isAdmin())
                        <li class="nav-item">
                            <div class="nav-section-header">
                                <i class="fas fa-cogs me-2"></i>
                                システム管理
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" 
                               href="{{ route('admin.users.index') }}">
                                <i class="fas fa-users me-2"></i>
                                ユーザー管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" 
                               href="{{ route('admin.settings.index') }}">
                                <i class="fas fa-cog me-2"></i>
                                システム設定
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}" 
                               href="{{ route('admin.logs.index') }}">
                                <i class="fas fa-list-alt me-2"></i>
                                ログ管理
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </nav>
            @endauth

            <!-- Main Content -->
            <main class="main-content flex-grow-1">
                <div class="container-fluid py-4">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery (required for admin.js) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS (for admin users) -->
    @if(auth()->check() && auth()->user()->isAdmin())
        <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
        @vite('resources/js/admin.js')
    @endif
    
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