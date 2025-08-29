<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'facility_id',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'file_type',
        'uploaded_by',
    ];

    /**
     * File types
     */
    const TYPE_CONTRACT = 'contract';
    const TYPE_BLUEPRINT = 'blueprint';
    const TYPE_INSPECTION = 'inspection';
    const TYPE_OTHER = 'other';

    /**
     * Get the facility this file belongs to
     */
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the user who uploaded this file
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope for files by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Scope for contract files
     */
    public function scopeContracts($query)
    {
        return $query->where('file_type', self::TYPE_CONTRACT);
    }

    /**
     * Scope for blueprint files
     */
    public function scopeBlueprints($query)
    {
        return $query->where('file_type', self::TYPE_BLUEPRINT);
    }

    /**
     * Scope for inspection files
     */
    public function scopeInspections($query)
    {
        return $query->where('file_type', self::TYPE_INSPECTION);
    }

    /**
     * Check if file is PDF
     */
    public function isPdf()
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Get display name for file type
     */
    public function getFileTypeDisplayNameAttribute()
    {
        $types = [
            self::TYPE_CONTRACT => '契約書',
            self::TYPE_BLUEPRINT => '図面',
            self::TYPE_INSPECTION => '検査書類',
            self::TYPE_OTHER => 'その他',
        ];

        return $types[$this->file_type] ?? $this->file_type;
    }

    /**
     * Get human readable file size
     */
    public function getHumanFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file extension from original name
     */
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }
}