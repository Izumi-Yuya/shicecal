@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>施設詳細</h4>
                    <div>
                        @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                            <a href="{{ route('facilities.edit', $facility) }}" class="btn btn-primary btn-sm">編集</a>
                        @endif
                        <a href="{{ route('facilities.index') }}" class="btn btn-secondary btn-sm">一覧に戻る</a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- 施設基本情報 -->
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th class="bg-light" style="width: 30%;">会社名</th>
                                    <td>{{ $facility->company_name }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">事業所コード</th>
                                    <td>{{ $facility->office_code }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">指定番号</th>
                                    <td>{{ $facility->designation_number }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">施設名</th>
                                    <td>{{ $facility->facility_name }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th class="bg-light" style="width: 30%;">郵便番号</th>
                                    <td>{{ $facility->postal_code }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">住所</th>
                                    <td>{{ $facility->address }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">電話番号</th>
                                    <td>{{ $facility->phone_number }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">FAX番号</th>
                                    <td>{{ $facility->fax_number }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- 修繕履歴 -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-tools me-2"></i>修繕履歴</h5>
                            @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                                <a href="{{ route('maintenance.create', ['facility_id' => $facility->id]) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-plus"></i> 履歴追加
                                </a>
                            @endif
                        </div>
                        
                        <div id="maintenance-histories">
                            @if($facility->maintenanceHistories->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>修繕日</th>
                                                <th>内容</th>
                                                <th>費用</th>
                                                <th>業者</th>
                                                <th>登録者</th>
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($facility->maintenanceHistories->take(5) as $history)
                                                <tr>
                                                    <td>{{ $history->maintenance_date->format('Y/m/d') }}</td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $history->content }}">
                                                            {{ $history->content }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($history->cost)
                                                            ¥{{ number_format($history->cost) }}
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $history->contractor ?? '-' }}</td>
                                                    <td>{{ $history->creator->name }}</td>
                                                    <td>
                                                        <a href="{{ route('maintenance.show', $history) }}" class="btn btn-outline-primary btn-xs">詳細</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($facility->maintenanceHistories->count() > 5)
                                    <div class="text-center mt-2">
                                        <a href="{{ route('maintenance.index', ['facility_id' => $facility->id]) }}" class="btn btn-outline-secondary btn-sm">
                                            すべての履歴を見る ({{ $facility->maintenanceHistories->count() }}件)
                                        </a>
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-3 text-muted">
                                    <i class="fas fa-tools fa-2x mb-2"></i>
                                    <p>修繕履歴がありません</p>
                                    @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                                        <a href="{{ route('maintenance.create', ['facility_id' => $facility->id]) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus"></i> 最初の履歴を追加
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- コメント機能 -->
                    @include('comments.comment-section', ['facility' => $facility])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection