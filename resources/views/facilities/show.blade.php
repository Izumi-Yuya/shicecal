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

                    <!-- コメント機能 -->
                    @include('comments.comment-section', ['facility' => $facility])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection