@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>活動サマリー</h4>
                    <a href="{{ route('my-page.index') }}" class="btn btn-secondary btn-sm">マイページに戻る</a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- 月別コメント投稿数 -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">月別コメント投稿数（過去6ヶ月）</h6>
                                </div>
                                <div class="card-body">
                                    @if($commentsByMonth->count() > 0)
                                        <canvas id="commentsChart" width="400" height="200"></canvas>
                                    @else
                                        <p class="text-muted text-center py-4">データがありません。</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 対応時間統計 -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">対応時間統計</h6>
                                </div>
                                <div class="card-body">
                                    @if($responseStats && $responseStats->avg_response_hours)
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="text-primary">
                                                    <i class="fas fa-clock fa-2x"></i>
                                                    <div class="mt-2">
                                                        <strong>{{ round($responseStats->avg_response_hours, 1) }}時間</strong>
                                                        <br><small>平均対応時間</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-success">
                                                    <i class="fas fa-tachometer-alt fa-2x"></i>
                                                    <div class="mt-2">
                                                        <strong>{{ round($responseStats->min_response_hours, 1) }}時間</strong>
                                                        <br><small>最短対応時間</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-warning">
                                                    <i class="fas fa-hourglass-half fa-2x"></i>
                                                    <div class="mt-2">
                                                        <strong>{{ round($responseStats->max_response_hours, 1) }}時間</strong>
                                                        <br><small>最長対応時間</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-muted text-center py-4">対応完了したコメントがありません。</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- よくコメントする施設 -->
                        <div class="col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">よくコメントする施設 TOP5</h6>
                                </div>
                                <div class="card-body">
                                    @if($topFacilities->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>順位</th>
                                                        <th>施設名</th>
                                                        <th>コメント数</th>
                                                        <th>割合</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $totalComments = $topFacilities->sum('comment_count'); @endphp
                                                    @foreach($topFacilities as $index => $facilityData)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>
                                                                <a href="{{ route('facilities.show', $facilityData->facility) }}">
                                                                    {{ $facilityData->facility->facility_name }}
                                                                </a>
                                                            </td>
                                                            <td>{{ $facilityData->comment_count }}件</td>
                                                            <td>
                                                                @php $percentage = $totalComments > 0 ? round(($facilityData->comment_count / $totalComments) * 100, 1) : 0; @endphp
                                                                <div class="progress" style="height: 20px;">
                                                                    <div class="progress-bar" role="progressbar" 
                                                                         style="width: {{ $percentage }}%" 
                                                                         aria-valuenow="{{ $percentage }}" 
                                                                         aria-valuemin="0" aria-valuemax="100">
                                                                        {{ $percentage }}%
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted text-center py-4">コメント投稿履歴がありません。</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- アクションボタン -->
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <a href="{{ route('my-page.my-comments') }}" class="btn btn-primary me-2">
                                <i class="fas fa-comments me-1"></i>マイコメント一覧
                            </a>
                            <a href="{{ route('facilities.index') }}" class="btn btn-outline-primary">
                                <i class="fas fa-building me-1"></i>施設一覧
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($commentsByMonth->count() > 0)
        const ctx = document.getElementById('commentsChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($commentsByMonth->keys()) !!},
                datasets: [{
                    label: 'コメント数',
                    data: {!! json_encode($commentsByMonth->values()) !!},
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    @endif
});
</script>
@endpush