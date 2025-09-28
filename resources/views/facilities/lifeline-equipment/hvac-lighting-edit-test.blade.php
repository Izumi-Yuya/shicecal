@extends('layouts.app')

@section('title', '空調・照明設備編集 - ' . $facility->facility_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1>空調・照明設備編集</h1>
            <p>施設: {{ $facility->facility_name }}</p>
            <p>カテゴリ: {{ $category }}</p>
            
            <a href="{{ route('facilities.show', $facility) }}" class="btn btn-secondary">戻る</a>
        </div>
    </div>
</div>
@endsection