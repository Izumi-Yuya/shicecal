@extends('layouts.app')

@section('title', 'ホーム')

@section('header', 'ダッシュボード')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-house"></i>
                    施設管理システムへようこそ
                </h5>
                <p class="card-text">
                    このシステムでは、施設情報の管理、承認フロー、ファイル管理、レポート出力などの機能を提供しています。
                </p>
                
                @auth
                    <div class="row mt-4">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">施設数</h6>
                                            <h4 class="mb-0">{{ $facilityCount ?? 0 }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-building fs-2"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if(auth()->user()->hasRole(['admin', 'approver']))
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">承認待ち</h6>
                                                <h4 class="mb-0">{{ $pendingApprovals ?? 0 }}</h4>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="bi bi-clock fs-2"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">未読コメント</h6>
                                            <h4 class="mb-0">{{ $unreadComments ?? 0 }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-chat fs-2"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">今月の修繕</h6>
                                            <h4 class="mb-0">{{ $monthlyMaintenance ?? 0 }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-tools fs-2"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</div>

@auth
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-activity"></i>
                        最近の活動
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($recentActivities) && count($recentActivities) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentActivities as $activity)
                                <div class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">{{ $activity->description }}</div>
                                        <small class="text-muted">{{ $activity->created_at->format('Y/m/d H:i') }}</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">{{ $activity->action }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">最近の活動はありません。</p>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-link-45deg"></i>
                        クイックアクション
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('facilities.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-list-ul"></i>
                            施設一覧を見る
                        </a>
                        
                        @if(auth()->user()->hasRole(['admin', 'editor']))
                            <a href="{{ route('facilities.create') }}" class="btn btn-outline-success">
                                <i class="bi bi-plus-circle"></i>
                                新しい施設を登録
                            </a>
                        @endif
                        
                        <a href="{{ route('export.csv') }}" class="btn btn-outline-info">
                            <i class="bi bi-download"></i>
                            CSV出力
                        </a>
                        
                        @if(auth()->user()->hasRole(['admin', 'approver']))
                            <a href="{{ route('approvals.index') }}" class="btn btn-outline-warning">
                                <i class="bi bi-check-circle"></i>
                                承認待ち一覧
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="row mt-4">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">ログインが必要です</h5>
                    <p class="card-text">システムを利用するにはログインしてください。</p>
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right"></i>
                        ログイン
                    </a>
                </div>
            </div>
        </div>
    </div>
@endauth
@endsection