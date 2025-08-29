<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - @yield('title', 'ホーム')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUa6c+yl9+TGUS79eP1l/5sLWppSFS+fDpSbiDVXUo+/xJr7+/EGVQtvlWRn" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .main-content {
            min-height: calc(100vh - 120px);
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-top: auto;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .content-wrapper {
            padding: 20px;
        }
    </style>

    @stack('styles')
</head>
<body>
    <div id="app">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="bi bi-building"></i>
                    施設管理システム
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        @auth
                            <!-- 施設管理 -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="facilityDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-building"></i>
                                    施設管理
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="facilityDropdown">
                                    <li><a class="dropdown-item" href="{{ route('facilities.index') }}">
                                        <i class="bi bi-list-ul"></i> 施設一覧
                                    </a></li>
                                    @if(auth()->user()->hasRole(['admin', 'editor']))
                                        <li><a class="dropdown-item" href="{{ route('facilities.create') }}">
                                            <i class="bi bi-plus-circle"></i> 施設登録
                                        </a></li>
                                    @endif
                                    <li><a class="dropdown-item" href="{{ route('maintenance.index') }}">
                                        <i class="bi bi-tools"></i> 修繕履歴
                                    </a></li>
                                </ul>
                            </li>

                            <!-- 出力機能 -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="exportDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-download"></i>
                                    出力
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                    <li><a class="dropdown-item" href="{{ route('export.csv') }}">
                                        <i class="bi bi-filetype-csv"></i> CSV出力
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('export.pdf') }}">
                                        <i class="bi bi-filetype-pdf"></i> PDF出力
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('export.favorites') }}">
                                        <i class="bi bi-star"></i> お気に入り管理
                                    </a></li>
                                </ul>
                            </li>

                            <!-- コメント・通知 -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="commentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-chat-dots"></i>
                                    コメント
                                    @php
                                        $unreadCount = auth()->user()->assignedComments()->where('status', 'pending')->count();
                                    @endphp
                                    @if($unreadCount > 0)
                                        <span class="badge bg-danger rounded-pill ms-1">{{ $unreadCount }}</span>
                                    @endif
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="commentDropdown">
                                    <li><a class="dropdown-item" href="{{ route('comments.index') }}">
                                        <i class="bi bi-chat-left-text"></i> コメント一覧
                                    </a></li>
                                    @if(auth()->user()->hasRole(['editor', 'primary_responder']))
                                        <li><a class="dropdown-item" href="{{ route('comments.assigned') }}">
                                            <i class="bi bi-person-check"></i> 担当コメント
                                            @if($unreadCount > 0)
                                                <span class="badge bg-danger rounded-pill ms-1">{{ $unreadCount }}</span>
                                            @endif
                                        </a></li>
                                    @endif
                                    <li><a class="dropdown-item" href="{{ route('my-page') }}">
                                        <i class="bi bi-person-circle"></i> マイページ
                                    </a></li>
                                </ul>
                            </li>

                            <!-- 承認機能 -->
                            @if(auth()->user()->hasRole(['admin', 'approver']))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('approvals.index') }}">
                                        <i class="bi bi-check-circle"></i>
                                        承認待ち
                                        @php
                                            $pendingCount = \App\Models\Facility::where('status', 'pending_approval')->count();
                                        @endphp
                                        @if($pendingCount > 0)
                                            <span class="badge bg-warning rounded-pill ms-1">{{ $pendingCount }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endif

                            <!-- 年次確認 -->
                            @if(auth()->user()->hasRole(['admin', 'editor']))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('annual-check.index') }}">
                                        <i class="bi bi-calendar-check"></i>
                                        年次確認
                                    </a>
                                </li>
                            @endif

                            <!-- 管理機能 -->
                            @if(auth()->user()->hasRole('admin'))
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-gear"></i>
                                        管理
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                        <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">
                                            <i class="bi bi-people"></i> ユーザー管理
                                        </a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.system.settings') }}">
                                            <i class="bi bi-sliders"></i> システム設定
                                        </a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.logs.index') }}">
                                            <i class="bi bi-journal-text"></i> ログ管理
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                            <i class="bi bi-speedometer2"></i> 管理ダッシュボード
                                        </a></li>
                                    </ul>
                                </li>
                            @endif
                        @endauth
                    </ul>
                    
                    <ul class="navbar-nav">
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    ログイン
                                </a>
                            </li>
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle"></i>
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="{{ route('profile.show') }}">プロフィール</a></li>
                                    <li><a class="dropdown-item" href="{{ route('my-page') }}">マイページ</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-box-arrow-right"></i>
                                                ログアウト
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid">
            <div class="row">
                @hasSection('sidebar')
                    <div class="col-md-3 col-lg-2 sidebar">
                        @yield('sidebar')
                    </div>
                    <div class="col-md-9 col-lg-10 content-wrapper">
                @else
                    <div class="col-12 content-wrapper">
                @endif
                
                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>エラーが発生しました：</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Page Header -->
                @hasSection('header')
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">@yield('header')</h1>
                        @hasSection('header-actions')
                            <div>
                                @yield('header-actions')
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Main Content -->
                <main class="main-content">
                    @yield('content')
                </main>
                
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer mt-auto">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0 text-muted">
                            &copy; {{ date('Y') }} 施設管理システム. All rights reserved.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-0 text-muted">
                            Version 1.0.0
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-danger)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Confirm delete actions
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-delete') || e.target.closest('.btn-delete')) {
                if (!confirm('本当に削除しますか？この操作は取り消せません。')) {
                    e.preventDefault();
                }
            }
        });
    </script>

    @stack('scripts')
</body>
</html>