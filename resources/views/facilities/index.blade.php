@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>施設一覧</h4>
                    @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                        <a href="{{ route('facilities.create') }}" class="btn btn-primary">新規登録</a>
                    @endif
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($facilities->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>事業所コード</th>
                                        <th>施設名</th>
                                        <th>会社名</th>
                                        <th>住所</th>
                                        <th>最終更新日</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($facilities as $facility)
                                        <tr>
                                            <td>{{ $facility->office_code }}</td>
                                            <td>
                                                <a href="{{ route('facilities.show', $facility) }}">
                                                    {{ $facility->facility_name }}
                                                </a>
                                            </td>
                                            <td>{{ $facility->company_name }}</td>
                                            <td>{{ $facility->address }}</td>
                                            <td>{{ $facility->updated_at->format('Y年m月d日') }}</td>
                                            <td>
                                                <a href="{{ route('facilities.show', $facility) }}" class="btn btn-sm btn-outline-primary">詳細</a>
                                                @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                                                    <a href="{{ route('facilities.edit', $facility) }}" class="btn btn-sm btn-outline-secondary">編集</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- ページネーション -->
                        <div class="d-flex justify-content-center">
                            {{ $facilities->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">施設が登録されていません。</p>
                            @if(auth()->user()->isEditor() || auth()->user()->isAdmin())
                                <a href="{{ route('facilities.create') }}" class="btn btn-primary">最初の施設を登録する</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection