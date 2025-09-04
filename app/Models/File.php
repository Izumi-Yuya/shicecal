<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'land_document_type',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Get the facility that owns this file
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the user who uploaded this file
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Check if this is a land document
     */
    public function isLandDocument(): bool
    {
        return !empty($this->land_document_type);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($this->file_size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get document type display name
     */
    public function getDocumentTypeDisplayNameAttribute(): string
    {
        if (empty($this->land_document_type)) {
            return '';
        }

        $displayNames = [
            'lease_contract' => '賃貸借契約書・覚書',
            'property_register' => '謄本',
            'other' => 'その他',
        ];

        return $displayNames[$this->land_document_type] ?? $this->land_document_type;
    }
}
