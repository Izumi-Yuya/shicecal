@extends('layouts.app')

@section('title', '修繕履歴登録')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">修繕履歴登録</h1>
                <a href="{{ route('maintenance.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> 一覧に戻る
                </a>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('maintenance.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="facility_id" class="form-label">施設 <span class="text-danger">*</span></label>
                                <select name="facility_id" id="facility_id" class="form-select @error('facility_id') is-invalid @enderror" required>
                                    <option value="">施設を選択してください</option>
                                    @foreach($facilities as $facility)
                                        <option value="{{ $facility->id }}" 
                                            {{ (old('facility_id', $selectedFacilityId) == $facility->id) ? 'selected' : '' }}>
                                            {{ $facility->office_code }} - {{ $facility->facility_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('facility_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="maintenance_date" class="form-label">修繕対応日 <span class="text-danger">*</span></label>
                                <input type="date" name="maintenance_date" id="maintenance_date" 
                                    class="form-control @error('maintenance_date') is-invalid @enderror" 
                                    value="{{ old('maintenance_date', date('Y-m-d')) }}">
                                @error('maintenance_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">修繕内容 <span class="text-danger">*</span></label>
                            <textarea name="content" id="content" rows="4" 
                                class="form-control @error('content') is-invalid @enderror" 
                                placeholder="修繕の詳細内容を入力してください" required>{{ old('content') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cost" class="form-label">費用（円）</label>
                                <input type="number" name="cost" id="cost" 
                                    class="form-control @error('cost') is-invalid @enderror" 
                                    value="{{ old('cost') }}" min="0" step="0.01" 
                                    placeholder="例: 50000">
                                @error('cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">費用が不明な場合は空欄のままにしてください</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="contractor" class="form-label">業者名</label>
                                <input type="text" name="contractor" id="contractor" 
                                    class="form-control @error('contractor') is-invalid @enderror" 
                                    value="{{ old('contractor') }}" 
                                    placeholder="例: 株式会社○○工務店">
                                @error('contractor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('maintenance.index') }}" class="btn btn-secondary">キャンセル</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 登録
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-format cost input
    const costInput = document.getElementById('cost');
    if (costInput) {
        costInput.addEventListener('input', function() {
            // Remove non-numeric characters except decimal point
            this.value = this.value.replace(/[^0-9.]/g, '');
        });
    }
});
</script>
@endpush