
@extends('layouts.app')

@section('title', '空調・照明設備編集 - ' . $facility->facility_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">空調・照明設備編集</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('facilities.index') }}">施設一覧</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('facilities.show', $facility) }}">{{ $facility->facility_name }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">空調・照明設備編集</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>戻る
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('facilities.lifeline-equipment.update', [$facility, 'hvac-lighting']) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    {{-- 左側：空調設備 --}}
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-snowflake me-2"></i>空調設備
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="freon_inspector" class="form-label">フロンガス点検業者</label>
                                    <input type="text" class="form-control" 
                                           id="freon_inspector" 
                                           name="basic_info[hvac][freon_inspector]" 
                                           placeholder="例：東京空調サービス株式会社">
                                </div>

                                <div class="mb-3">
                                    <label for="hvac_inspection_date" class="form-label">点検実施日</label>
                                    <input type="date" class="form-control" 
                                           id="hvac_inspection_date" 
                                           name="basic_info[hvac][inspection_date]">
                                </div>

                                <div class="mb-3">
                                    <label for="target_equipment" class="form-label">点検対象機器</label>
                                    <textarea class="form-control" 
                                              id="target_equipment" 
                                              name="basic_info[hvac][target_equipment]" 
                                              rows="3"
                                              placeholder="点検対象機器の詳細を記入してください"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="hvac_notes" class="form-label">備考</label>
                                    <textarea class="form-control" 
                                              id="hvac_notes" 
                                              name="basic_info[hvac][notes]" 
                                              rows="3"
                                              placeholder="空調設備に関する追加情報があれば記入してください"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 右側：照明設備 --}}
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-lightbulb me-2"></i>照明設備
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="manufacturer" class="form-label">メーカー</label>
                                    <input type="text" class="form-control" 
                                           id="manufacturer" 
                                           name="basic_info[lighting][manufacturer]" 
                                           placeholder="例：パナソニック">
                                </div>

                                <div class="mb-3">
                                    <label for="update_date" class="form-label">更新日</label>
                                    <input type="date" class="form-control" 
                                           id="update_date" 
                                           name="basic_info[lighting][update_date]">
                                </div>

                                <div class="mb-3">
                                    <label for="warranty_period" class="form-label">保証期間</label>
                                    <input type="text" class="form-control" 
                                           id="warranty_period" 
                                           name="basic_info[lighting][warranty_period]" 
                                           placeholder="例：5年">
                                </div>

                                <div class="mb-3">
                                    <label for="lighting_notes" class="form-label">備考</label>
                                    <textarea class="form-control" 
                                              id="lighting_notes" 
                                              name="basic_info[lighting][notes]" 
                                              rows="3"
                                              placeholder="照明設備に関する追加情報があれば記入してください"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 保存ボタン --}}
                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="{{ route('facilities.show', $facility) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>キャンセル
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection