@props([
    'fileData' => null,
    'label' => 'ファイル',
    'showLabel' => true,
    'downloadText' => 'ダウンロード',
    'noFileText' => 'ファイルなし',
    'size' => 'sm', // sm, md, lg
    'style' => 'button' // button, link, badge
])

@php
    $hasFile = !empty($fileData) && is_array($fileData) && !empty($fileData['filename']);
    $fileExists = $hasFile && ($fileData['exists'] ?? true);
    
    // サイズクラスの設定
    $sizeClasses = [
        'sm' => 'btn-sm',
        'md' => '',
        'lg' => 'btn-lg'
    ];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['sm'];
    
    // スタイルクラスの設定
    $styleClasses = [
        'button' => 'btn btn-outline-primary',
        'link' => 'text-decoration-none',
        'badge' => 'badge bg-primary'
    ];
    $styleClass = $styleClasses[$style] ?? $styleClasses['button'];
@endphp

<div class="file-display-component">
    @if($showLabel)
        <label class="form-label">{{ $label }}</label>
    @endif
    
    <div class="file-content">
        @if($hasFile && $fileExists)
            <!-- ファイルが存在する場合 -->
            <div class="d-flex align-items-center">
                <i class="{{ $fileData['icon'] ?? 'fas fa-file' }} {{ $fileData['color'] ?? 'text-primary' }} me-2"></i>
                
                @if($style === 'button')
                    <a href="{{ $fileData['download_url'] }}" 
                       class="{{ $styleClass }} {{ $sizeClass }}" 
                       target="_blank"
                       title="ファイルをダウンロード">
                        <i class="fas fa-download me-1"></i>{{ $fileData['filename'] ?? 'ファイル' }}
                    </a>
                @elseif($style === 'link')
                    <a href="{{ $fileData['download_url'] }}" 
                       class="{{ $styleClass }}" 
                       target="_blank"
                       title="ファイルをダウンロード">
                        {{ $fileData['filename'] ?? 'ファイル' }}
                    </a>
                @elseif($style === 'badge')
                    <a href="{{ $fileData['download_url'] }}" 
                       class="{{ $styleClass }} text-decoration-none" 
                       target="_blank"
                       title="ファイルをダウンロード">
                        {{ $downloadText }}
                    </a>
                @endif
            </div>
        @elseif($hasFile && !$fileExists)
            <!-- ファイルが登録されているが存在しない場合 -->
            <div class="d-flex align-items-center text-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <span>{{ $fileData['filename'] ?? 'ファイル名を取得できません' }}（ファイルが見つかりません）</span>
            </div>
        @else
            <!-- ファイルがない場合 -->
            <div class="text-muted">
                <i class="fas fa-minus me-2"></i>{{ $noFileText }}
            </div>
        @endif
    </div>
</div>