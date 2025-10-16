<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'parent_id',
        'category',
        'name',
        'path',
        'created_by',
    ];

    protected $casts = [
        'facility_id' => 'integer',
        'parent_id' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * Get the facility that owns this folder
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the parent folder
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'parent_id');
    }

    /**
     * Get the child folders
     */
    public function children(): HasMany
    {
        return $this->hasMany(DocumentFolder::class, 'parent_id');
    }

    /**
     * Get the files in this folder
     */
    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class, 'folder_id');
    }

    /**
     * Get the user who created this folder
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: メインドキュメント管理のフォルダのみ
     */
    public function scopeMain($query)
    {
        return $query->whereNull('category');
    }

    /**
     * Scope: ライフライン設備のフォルダのみ
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
     * Scope: 修繕履歴のフォルダのみ
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
     * Scope: 契約書のフォルダのみ
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
     * Get the full path of the folder
     */
    public function getFullPath(): string
    {
        if ($this->parent_id === null) {
            return $this->name;
        }

        $pathParts = [];
        $current = $this;

        while ($current) {
            array_unshift($pathParts, $current->name);
            $current = $current->parent;
        }

        return implode('/', $pathParts);
    }

    /**
     * Check if the folder has child folders (optimized with caching)
     */
    public function hasChildren(): bool
    {
        // 関連データが既に読み込まれている場合はそれを使用
        if ($this->relationLoaded('children')) {
            return $this->children->isNotEmpty();
        }

        // キャッシュを使用して頻繁なクエリを削減
        $cacheKey = "folder_has_children_{$this->id}";
        return cache()->remember($cacheKey, 300, function () {
            return $this->children()->exists();
        });
    }

    /**
     * Get the direct file count in this folder only (optimized)
     */
    public function getDirectFileCount(): int
    {
        // 関連データが既に読み込まれている場合はそれを使用
        if ($this->relationLoaded('files')) {
            return $this->files->count();
        }

        // キャッシュを使用
        $cacheKey = "folder_direct_file_count_{$this->id}";
        return cache()->remember($cacheKey, 300, function () {
            return $this->files()->count();
        });
    }

    /**
     * Get the total number of files in this folder (including subfolders) - optimized
     */
    public function getFileCount(): int
    {
        $cacheKey = "folder_total_file_count_{$this->id}";
        return cache()->remember($cacheKey, 600, function () {
            // 単一クエリで再帰的にファイル数を取得
            $descendantIds = $this->getDescendantIds();
            $descendantIds[] = $this->id;

            return DocumentFile::whereIn('folder_id', $descendantIds)->count();
        });
    }

    /**
     * Get the total size of all files in this folder (including subfolders) - optimized
     */
    public function getTotalSize(): int
    {
        $cacheKey = "folder_total_size_{$this->id}";
        return cache()->remember($cacheKey, 600, function () {
            // 単一クエリで再帰的にファイルサイズを取得
            $descendantIds = $this->getDescendantIds();
            $descendantIds[] = $this->id;

            return DocumentFile::whereIn('folder_id', $descendantIds)->sum('file_size');
        });
    }

    /**
     * Check if the folder can be deleted (optimized)
     */
    public function canDelete(): bool
    {
        $cacheKey = "folder_can_delete_{$this->id}";
        return cache()->remember($cacheKey, 60, function () {
            // 単一クエリで子フォルダとファイルの存在を確認
            $hasChildren = $this->children()->exists();
            $hasFiles = $this->files()->exists();
            
            return !$hasChildren && !$hasFiles;
        });
    }

    /**
     * Get descendant folder IDs efficiently
     */
    private function getDescendantIds(): array
    {
        $cacheKey = "folder_descendant_ids_{$this->id}";
        return cache()->remember($cacheKey, 600, function () {
            $ids = [];
            $this->collectDescendantIds($ids);
            return $ids;
        });
    }

    /**
     * Recursively collect descendant IDs
     */
    private function collectDescendantIds(array &$ids): void
    {
        $children = $this->children()->pluck('id');
        foreach ($children as $childId) {
            $ids[] = $childId;
            $child = static::find($childId);
            if ($child) {
                $child->collectDescendantIds($ids);
            }
        }
    }

    /**
     * Get folder statistics efficiently
     */
    public function getStatsAttribute(): array
    {
        $cacheKey = "folder_stats_{$this->id}";
        return cache()->remember($cacheKey, 300, function () {
            // 直接のファイル統計
            $directFileStats = $this->files()
                ->selectRaw('COUNT(*) as file_count, SUM(file_size) as total_size')
                ->first();

            // 直接の子フォルダ数
            $directFolderCount = $this->children()->count();

            return [
                'direct_file_count' => $directFileStats->file_count ?? 0,
                'direct_folder_count' => $directFolderCount,
                'direct_total_size' => $directFileStats->total_size ?? 0,
                'total_file_count' => $this->getFileCount(),
                'total_size' => $this->getTotalSize(),
                'formatted_size' => app(\App\Services\FileHandlingService::class)->formatFileSize($directFileStats->total_size ?? 0),
                'formatted_total_size' => app(\App\Services\FileHandlingService::class)->formatFileSize($this->getTotalSize()),
            ];
        });
    }

    /**
     * Get all descendant folders (recursive)
     */
    public function getDescendants(): \Illuminate\Database\Eloquent\Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Get the breadcrumb path for navigation
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $current = $this;

        while ($current) {
            array_unshift($breadcrumbs, [
                'id' => $current->id,
                'name' => $current->name,
                'path' => $current->path,
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    /**
     * Clear all caches related to this folder
     */
    public function clearCache(): void
    {
        $keys = [
            "folder_has_children_{$this->id}",
            "folder_direct_file_count_{$this->id}",
            "folder_total_file_count_{$this->id}",
            "folder_total_size_{$this->id}",
            "folder_can_delete_{$this->id}",
            "folder_descendant_ids_{$this->id}",
            "folder_stats_{$this->id}",
        ];

        foreach ($keys as $key) {
            cache()->forget($key);
        }

        // 親フォルダのキャッシュもクリア（統計に影響するため）
        if ($this->parent_id) {
            $parent = static::find($this->parent_id);
            if ($parent) {
                $parent->clearCache();
            }
        }

        // 施設レベルのキャッシュもクリア
        cache()->forget("facility_document_stats_{$this->facility_id}");
    }

    /**
     * Clear cache for all folders in the facility
     */
    public static function clearFacilityCache(int $facilityId): void
    {
        $folders = static::where('facility_id', $facilityId)->get();
        foreach ($folders as $folder) {
            $folder->clearCache();
        }
        cache()->forget("facility_document_stats_{$facilityId}");
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Update path when creating or updating
        static::saving(function ($folder) {
            if ($folder->parent_id === null) {
                $folder->path = $folder->name;
            } else {
                $parent = static::find($folder->parent_id);
                $folder->path = $parent ? $parent->path . '/' . $folder->name : $folder->name;
            }
        });

        // Update child paths when path changes
        static::saved(function ($folder) {
            if ($folder->wasChanged('path')) {
                $folder->updateChildPaths();
            }
            // キャッシュクリア
            $folder->clearCache();
        });

        // フォルダ作成・削除時にキャッシュをクリア
        static::created(function ($folder) {
            $folder->clearCache();
        });

        static::deleted(function ($folder) {
            $folder->clearCache();
        });
    }

    /**
     * Update paths of all child folders recursively
     */
    private function updateChildPaths(): void
    {
        foreach ($this->children as $child) {
            $child->path = $this->path . '/' . $child->name;
            $child->saveQuietly(); // Save without triggering events to avoid infinite recursion
            $child->updateChildPaths();
        }
    }
}