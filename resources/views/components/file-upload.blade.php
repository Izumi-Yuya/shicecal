@props([
    'name' => 'file',
    'label' => 'ファイル',
    'fileType' => 'pdf',
    'currentFile' => null,
    'required' => false,
    'helpText' => null,
    'showRemoveOption' => true,
    'accept' => null,
    'removeFieldName' => null
])

@php
    use App\Services\FileHandlingService;
    
    $fileService = app(FileHandlingService::class);
    $config = FileHandlingService::SUPPORTED_FILE_TYPES[$fileType] ?? FileHandlingService::SUPPORTED_FILE_TYPES['pdf'];
    $maxSizeMB = $config['max_size'] / (1024 * 1024);
    $extensions = implode(', ', array_map('strtoupper', $config['extensions']));
    
    $acceptAttribute = $accept ?? '.' . implode(',.', $config['extensions']);
    $defaultHelpText = "{$extensions}ファイルをアップロードできます（最大{$maxSizeMB}MB）";
    $helpText = $helpText ?? $defaultHelpText;
    
    $hasCurrentFile = !empty($currentFile) && is_array($currentFile) && !empty($currentFile['filename']);
@endphp

<div class="file-upload-component">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    
    @if($hasCurrentFile)
        <!-- 現在のファイル表示 -->
        <div class="current-file mb-2">
            <div class="alert alert-info d-flex align-items-center">
                <i class="{{ $currentFile['icon'] ?? 'fas fa-file' }} {{ $currentFile['color'] ?? 'text-primary' }} me-2"></i>
                <span class="flex-grow-1">
                    現在のファイル: {{ $currentFile['filename'] ?? 'ファイル名不明' }}
                </span>
                @if(!empty($currentFile['download_url']))
                    <a href="{{ $currentFile['download_url'] }}" 
                       class="btn btn-sm btn-outline-primary ms-2" 
                       target="_blank">
                        <i class="fas fa-download"></i> ダウンロード
                    </a>
                @endif
            </div>
        </div>
    @endif
    
    <!-- ファイル入力フィールド -->
    <input type="file" 
           class="form-control @error($name) is-invalid @enderror" 
           id="{{ $name }}" 
           name="{{ $name }}" 
           accept="{{ $acceptAttribute }}"
           {{ $required ? 'required' : '' }}>
    
    @if($helpText)
        <div class="form-text">{{ $helpText }}</div>
    @endif
    
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    
    @if($hasCurrentFile && $showRemoveOption)
        @php
            $removeFieldId = $removeFieldName ?? "remove_{$name}";
            $removeFieldNameAttr = $removeFieldName ?? "remove_{$name}";
        @endphp
        <!-- ファイル削除オプション -->
        <div class="form-check mt-2">
            <input class="form-check-input" 
                   type="checkbox" 
                   id="{{ $removeFieldId }}" 
                   name="{{ $removeFieldNameAttr }}" 
                   value="1">
            <label class="form-check-label text-danger" for="{{ $removeFieldId }}">
                現在のファイルを削除する
            </label>
        </div>
    @endif
    
    <!-- 既存ファイル情報を保持するhiddenフィールド -->
    @if($hasCurrentFile)
        <input type="hidden" name="{{ $name }}_current_filename" value="{{ $currentFile['filename'] ?? '' }}">
        <input type="hidden" name="{{ $name }}_current_path" value="{{ $currentFile['path'] ?? '' }}">
    @endif
</div>