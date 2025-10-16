<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'folder_id',
        'category',
        'original_name',
        'stored_name',
        'file_path',
        'file_size',
        'mime_type',
        'file_extension',
        'uploaded_by',
    ];

    protected $casts = [
        'facility_id' => 'integer',
        'folder_id' => 'integer',
        'file_size' => 'integer',
        'uploaded_by' => 'integer',
    ];

    /**
     * File type configurations for icons and colors
     */
    private const FILE_TYPES = [
        'pdf' => [
            'icon' => 'fas fa-file-pdf',
            'color' => 'text-danger',
        ],
        'doc' => [
            'icon' => 'fas fa-file-word',
            'color' => 'text-primary',
        ],
        'docx' => [
            'icon' => 'fas fa-file-word',
            'color' => 'text-primary',
        ],
        'xls' => [
            'icon' => 'fas fa-file-excel',
            'color' => 'text-success',
        ],
        'xlsx' => [
            'icon' => 'fas fa-file-excel',
            'color' => 'text-success',
        ],
        'ppt' => [
            'icon' => 'fas fa-file-powerpoint',
            'color' => 'text-warning',
        ],
        'pptx' => [
            'icon' => 'fas fa-file-powerpoint',
            'color' => 'text-warning',
        ],
        'jpg' => [
            'icon' => 'fas fa-file-image',
            'color' => 'text-info',
        ],
        'jpeg' => [
            'icon' => 'fas fa-file-image',
            'color' => 'text-info',
        ],
        'png' => [
            'icon' => 'fas fa-file-image',
            'color' => 'text-info',
        ],
        'gif' => [
            'icon' => 'fas fa-file-image',
            'color' => 'text-info',
        ],
        'txt' => [
            'icon' => 'fas fa-file-alt',
            'color' => 'text-secondary',
        ],
        'zip' => [
            'icon' => 'fas fa-file-archive',
            'color' => 'text-dark',
        ],
        'rar' => [
            'icon' => 'fas fa-file-archive',
            'color' => 'text-dark',
        ],
        'default' => [
            'icon' => 'fas fa-file',
            'color' => 'text-muted',
        ],
    ];

    /**
     * Get the facility that owns this file
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the folder that contains this file
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    /**
     * Get the user who uploaded this file
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope: メインドキュメント管理のファイルのみ
     */
    public function scopeMain($query)
    {
        return $query->whereNull('category');
    }

    /**
     * Scope: ライフライン設備のファイルのみ
     */
    public function scopeLifeline($query, ?string $category = null)
    {
        $query = $query->where('category', 'like', 'lifeline_%');
        
        if ($category) {
            $query->where('category', 'lifeline_' . $category);
        }
        
        return $query;
    }

    /**
     * Scope: 修繕履歴のファイルのみ
     */
    public function scopeMaintenance($query, ?string $category = null)
    {
        $query = $query->where('category', 'like', 'maintenance_%');
        
        if ($category) {
            $query->where('category', 'maintenance_' . $category);
        }
        
        return $query;
    }

    /**
     * Scope: 契約書のファイルのみ
     */
    public function scopeContracts($query)
    {
        return $query->where('category', 'contracts');
    }

    /**
     * カテゴリがライフライン設備かどうか
     */
    public function isLifeline(): bool
    {
        return $this->category && str_starts_with($this->category, 'lifeline_');
    }

    /**
     * カテゴリが修繕履歴かどうか
     */
    public function isMaintenance(): bool
    {
        return $this->category && str_starts_with($this->category, 'maintenance_');
    }

    /**
     * カテゴリが契約書かどうか
     */
    public function isContracts(): bool
    {
        return $this->category === 'contracts';
    }

    /**
     * カテゴリがメインドキュメントかどうか
     */
    public function isMain(): bool
    {
        return $this->category === null;
    }

    /**
     * Get formatted file size (e.g., "1.5 MB", "256 KB")
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->file_size;

        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));
        $size = round($bytes / pow(1024, $factor), 2);

        return $size . ' ' . $units[$factor];
    }

    /**
     * Get the appropriate icon class for the file type
     */
    public function getFileIcon(): string
    {
        $extension = strtolower($this->file_extension);
        return self::FILE_TYPES[$extension]['icon'] ?? self::FILE_TYPES['default']['icon'];
    }

    /**
     * Get the appropriate color class for the file type
     */
    public function getFileColor(): string
    {
        $extension = strtolower($this->file_extension);
        return self::FILE_TYPES[$extension]['color'] ?? self::FILE_TYPES['default']['color'];
    }

    /**
     * Get the download URL for this file
     */
    public function getDownloadUrl(): string
    {
        return route('facilities.documents.files.download', [
            'facility' => $this->facility,
            'file' => $this,
        ]);
    }

    /**
     * Get the preview URL for this file (if supported)
     */
    public function getPreviewUrl(): ?string
    {
        if (!$this->canPreview()) {
            return null;
        }

        return route('facilities.documents.files.preview', [
            'facility' => $this->facility,
            'file' => $this,
        ]);
    }

    /**
     * Check if the file can be previewed in the browser
     */
    public function canPreview(): bool
    {
        $previewableTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt'];
        return in_array(strtolower($this->file_extension), $previewableTypes);
    }

    /**
     * Check if the file is an image
     */
    public function isImage(): bool
    {
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        return in_array(strtolower($this->file_extension), $imageTypes);
    }

    /**
     * Check if the file is a PDF
     */
    public function isPdf(): bool
    {
        return strtolower($this->file_extension) === 'pdf';
    }

    /**
     * Check if the file is a document (Word, Excel, PowerPoint)
     */
    public function isDocument(): bool
    {
        $documentTypes = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
        return in_array(strtolower($this->file_extension), $documentTypes);
    }

    /**
     * Get file type category for grouping
     */
    public function getFileCategory(): string
    {
        if ($this->isImage()) {
            return 'image';
        }

        if ($this->isPdf()) {
            return 'pdf';
        }

        if ($this->isDocument()) {
            return 'document';
        }

        $archiveTypes = ['zip', 'rar', '7z', 'tar', 'gz'];
        if (in_array(strtolower($this->file_extension), $archiveTypes)) {
            return 'archive';
        }

        return 'other';
    }

    /**
     * Get display data for file components
     */
    public function getDisplayData(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->original_name,
            'stored_name' => $this->stored_name,
            'size' => $this->getFormattedSize(),
            'size_bytes' => $this->file_size,
            'extension' => $this->file_extension,
            'mime_type' => $this->mime_type,
            'icon' => $this->getFileIcon(),
            'color' => $this->getFileColor(),
            'category' => $this->getFileCategory(),
            'download_url' => $this->getDownloadUrl(),
            'preview_url' => $this->getPreviewUrl(),
            'can_preview' => $this->canPreview(),
            'is_image' => $this->isImage(),
            'is_pdf' => $this->isPdf(),
            'is_document' => $this->isDocument(),
            'uploaded_at' => $this->created_at,
            'uploaded_by' => $this->uploader->name ?? 'Unknown',
            'folder_name' => $this->folder->name ?? 'Root',
        ];
    }

    /**
     * Scope to filter by file extension
     */
    public function scopeByExtension($query, string $extension)
    {
        return $query->where('file_extension', strtolower($extension));
    }

    /**
     * Scope to filter by file category
     */
    public function scopeByCategory($query, string $category)
    {
        switch ($category) {
            case 'image':
                return $query->whereIn('file_extension', ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
            case 'pdf':
                return $query->where('file_extension', 'pdf');
            case 'document':
                return $query->whereIn('file_extension', ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
            case 'archive':
                return $query->whereIn('file_extension', ['zip', 'rar', '7z', 'tar', 'gz']);
            default:
                return $query;
        }
    }

    /**
     * Scope to order by file size
     */
    public function scopeOrderBySize($query, string $direction = 'asc')
    {
        return $query->orderBy('file_size', $direction);
    }

    /**
     * Scope to order by upload date
     */
    public function scopeOrderByUploadDate($query, string $direction = 'desc')
    {
        return $query->orderBy('created_at', $direction);
    }

    /**
     * Scope to order by file name
     */
    public function scopeOrderByName($query, string $direction = 'asc')
    {
        return $query->orderBy('original_name', $direction);
    }

    /**
     * Clear related caches when file is modified
     */
    public function clearRelatedCaches(): void
    {
        // フォルダのキャッシュをクリア
        if ($this->folder_id) {
            $folder = DocumentFolder::find($this->folder_id);
            if ($folder) {
                $folder->clearCache();
            }
        }

        // 施設レベルのキャッシュをクリア
        cache()->forget("facility_document_stats_{$this->facility_id}");
    }

    /**
     * Get file statistics for a facility
     */
    public static function getFacilityStats(int $facilityId): array
    {
        $cacheKey = "facility_file_stats_{$facilityId}";
        return cache()->remember($cacheKey, 600, function () use ($facilityId) {
            $stats = static::where('facility_id', $facilityId)
                ->selectRaw('
                    COUNT(*) as total_files,
                    SUM(file_size) as total_size,
                    file_extension,
                    COUNT(*) as count_by_extension
                ')
                ->groupBy('file_extension')
                ->get();

            $totalFiles = $stats->sum('count_by_extension');
            $totalSize = $stats->sum('total_size');

            $byExtension = $stats->mapWithKeys(function ($item) {
                return [
                    $item->file_extension => [
                        'count' => $item->count_by_extension,
                        'size' => $item->total_size,
                    ]
                ];
            });

            return [
                'total_files' => $totalFiles,
                'total_size' => $totalSize,
                'by_extension' => $byExtension,
                'last_updated' => now(),
            ];
        });
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // ファイル作成・更新・削除時にキャッシュをクリア
        static::created(function ($file) {
            $file->clearRelatedCaches();
        });

        static::updated(function ($file) {
            $file->clearRelatedCaches();
        });

        static::deleted(function ($file) {
            $file->clearRelatedCaches();
        });
    }
}