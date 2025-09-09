@extends('layouts.app')

@section('title', 'ログ詳細')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>ログ詳細</h1>
                <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> ログ一覧に戻る
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        ログID: {{ $activityLog->id }}
                        <span class="badge bg-{{ \App\Helpers\ActivityLogHelper::getActionBadgeColor($activityLog->action) }} ms-2">
                            {{ $activityLog->action }}
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">基本情報</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">日時:</th>
                                    <td>{{ $activityLog->created_at->format('Y年m月d日 H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>ユーザー:</th>
                                    <td>
                                        @if($activityLog->user)
                                            <div>
                                                <strong>{{ $activityLog->user->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $activityLog->user->email }}</small>
                                                <br>
                                                <span class="badge bg-info">{{ $activityLog->user->role }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted">Unknown User (ID: {{ $activityLog->user_id }})</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>操作種別:</th>
                                    <td>
                                        <span class="badge bg-{{ \App\Helpers\ActivityLogHelper::getActionBadgeColor($activityLog->action) }}">
                                            {{ \App\Helpers\ActivityLogHelper::getActionName($activityLog->action) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>対象種別:</th>
                                    <td>
                                        <strong>{{ $activityLog->target_type }}</strong>
                                        @if($activityLog->target_id)
                                            <br>
                                            <small class="text-muted">対象ID: {{ $activityLog->target_id }}</small>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">技術情報</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">IPアドレス:</th>
                                    <td><code>{{ $activityLog->ip_address }}</code></td>
                                </tr>
                                <tr>
                                    <th>ユーザーエージェント:</th>
                                    <td>
                                        <div class="text-break">
                                            <small>{{ $activityLog->user_agent }}</small>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>ログID:</th>
                                    <td><code>{{ $activityLog->id }}</code></td>
                                </tr>
                                <tr>
                                    <th>記録日時:</th>
                                    <td>
                                        <small class="text-muted">
                                            {{ $activityLog->created_at->format('Y-m-d H:i:s T') }}
                                        </small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted">操作内容</h6>
                            <div class="alert alert-light">
                                <p class="mb-0">{{ $activityLog->description }}</p>
                            </div>
                        </div>
                    </div>

                    @if($activityLog->target_id && $activityLog->target_type)
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-muted">対象情報</h6>
                                <div class="alert alert-info">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>対象種別:</strong> {{ $activityLog->target_type }}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>対象ID:</strong> {{ $activityLog->target_id }}
                                        </div>
                                    </div>
                                    @if($activityLog->target_type === 'facility' && $activityLog->target_id)
                                        <div class="mt-2">
                                            <a href="{{ route('facilities.show', $activityLog->target_id) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                施設詳細を表示
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Related Logs -->
                    @if($activityLog->user)
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-muted">同一ユーザーの最近のログ</h6>
                                @php
                                    $recentLogs = \App\Models\ActivityLog::where('user_id', $activityLog->user_id)
                                        ->where('id', '!=', $activityLog->id)
                                        ->orderBy('created_at', 'desc')
                                        ->limit(5)
                                        ->get();
                                @endphp
                                
                                @if($recentLogs->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>日時</th>
                                                    <th>操作</th>
                                                    <th>対象</th>
                                                    <th>説明</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recentLogs as $log)
                                                    <tr>
                                                        <td>
                                                            <small>{{ $log->created_at->format('m/d H:i') }}</small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-{{ \App\Helpers\ActivityLogHelper::getActionBadgeColor($log->action) }} badge-sm">
                                                                {{ \App\Helpers\ActivityLogHelper::getActionName($log->action) }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $log->target_type }}</td>
                                                        <td>
                                                            <div class="text-truncate" style="max-width: 200px;">
                                                                {{ $log->description }}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('admin.logs.show', $log) }}" 
                                                               class="btn btn-xs btn-outline-primary">
                                                                詳細
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted">他のログはありません。</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> ログ一覧に戻る
                        </a>
                        <div>
                            <button class="btn btn-outline-info" onclick="window.print()">
                                <i class="fas fa-print"></i> 印刷
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

