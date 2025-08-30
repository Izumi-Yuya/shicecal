@extends('layouts.app')

@section('title', '修繕履歴編集')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">修繕履歴編集</h1>
                <div class="btn-group">
                    <a href="{{ route('maintenance.show', $maintenanceHistory) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> 詳細に戻る
                    </a>
                    <a href="{{ route('maintenance.index') }}" class="btn btn-outline-secondary">一覧</a>
                </div>
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
                    <form method="POST" action="{{ route('maintenance.update', $maintenanceHistory) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="facility_id" class="form-label">施設 <span class="text-danger">*</span></label>
                                <select name="facility_id" id="facility_id" class="form-select @error('facility_id') is-invalid @enderror" required>
                                    <option value="">施設を選択してください</option>
                                    @foreach($facilities as $facility)
                                        <option value="{{ $facility->id }}" 
                                            {{ (old('facility_id', $maintenanceHistory->facility_id) == $facility->id) ? 'selected' : '' }}>
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
                                    value="{{ old('maintenance_date', $maintenanceHistory->maintenance_date->format('Y-m-d')) }}" required>
                                @error('maintenance_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">修繕内容 <span class="text-danger">*</span></label>
                            <textarea name="content" id="content" rows="4" 
                                class="form-control @error('content') is-invalid @enderror" 
                                placeholder="修繕の詳細内容を入力してください" required>{{ old('content', $maintenanceHistory->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cost" class="form-label">費用（円）</label>
                                <input type="number" name="cost" id="cost" 
                                    class="form-control @error('cost') is-invalid @enderror" 
                                    value="{{ old('cost', $maintenanceHistory->cost) }}" min="0" step="0.01" 
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
                                    value="{{ old('contractor', $maintenanceHistory->contractor) }}" 
                                    placeholder="例: 株式会社○○工務店">
                                @error('contractor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>登録情報:</strong> 
                            {{ $maintenanceHistory->creator->name }} が 
                            {{ $maintenanceHistory->created_at->format('Y年m月d日 H:i') }} に登録
                            @if($maintenanceHistory->updated_at != $maintenanceHistory->created_at)
                                <br>最終更新: {{ $maintenanceHistory->updated_at->format('Y年m月d日 H:i') }}
                            @endif
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('maintenance.show', $maintenanceHistory) }}" class="btn btn-secondary">キャンセル</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 更新
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