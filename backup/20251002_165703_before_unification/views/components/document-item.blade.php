@props([
    'item',
    'type' => 'file', // 'file' or 'folder'
    'viewMode' => 'list', // 'list' or 'grid'
    'canUpdate' => false,
    'canDelete' => false
])

@if($viewMode === 'list')
    <tr class="document-item" data-type="{{ $type }}" data-id="{{ $item['id'] }}">
        <td>
            <input type="checkbox" class="form-check-input item-checkbox" value="{{ $item['id'] }}">
        </td>
        <td>
            <div class="d-flex align-items-center">
                @if($type === 'folder')
                    <i class="fas fa-folder text-warning me-2 file-icon"></i>
                    <span class="folder-name">{{ $item['name'] }}</span>
                @else
                    <i class="{{ $item['icon'] ?? 'fas fa-file' }} {{ $item['color'] ?? 'text-muted' }} me-2 file-icon"></i>
                    <span class="file-name">{{ $item['name'] }}</span>
                @endif
            </div>
        </td>
        <td>
            @if($type === 'folder')
                <small class="text-muted">—</small>
            @else
                <small class="text-muted">{{ $item['formatted_size'] ?? '—' }}</small>
            @endif
        </td>
        <td>
            <small class="text-muted">{{ $item['updated_at'] ? $item['updated_at']->format('Y/m/d H:i') : '—' }}</small>
        </td>
        <td>
            <small class="text-muted">{{ $item['created_by'] ?? $item['uploaded_by'] ?? '—' }}</small>
        </td>
        <td>
            <div class="btn-group btn-group-sm">
                @if($type === 'folder')
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="documentManager.openFolder({{ $item['id'] }})">
                        <i class="fas fa-folder-open"></i>
                    </button>
                @else
                    @if($item['can_preview'] ?? false)
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="documentManager.previewFile({{ $item['id'] }})">
                            <i class="fas fa-eye"></i>
                        </button>
                    @endif
                    <a href="{{ $item['download_url'] ?? '#' }}" class="btn btn-outline-primary btn-sm" download>
                        <i class="fas fa-download"></i>
                    </a>
                @endif
            </div>
        </td>
    </tr>
@else
    <div class="col-md-2 col-sm-3 col-4 mb-3">
        <div class="document-card" data-type="{{ $type }}" data-id="{{ $item['id'] }}">
            <div class="document-icon">
                @if($type === 'folder')
                    <i class="fas fa-folder text-warning"></i>
                @else
                    <i class="{{ $item['icon'] ?? 'fas fa-file' }} {{ $item['color'] ?? 'text-muted' }}"></i>
                @endif
            </div>
            <div class="document-name" title="{{ $item['name'] }}">
                {{ Str::limit($item['name'], 20) }}
            </div>
            @if($type === 'file' && isset($item['formatted_size']))
                <div class="document-size">
                    <small class="text-muted">{{ $item['formatted_size'] }}</small>
                </div>
            @endif
        </div>
    </div>
@endif