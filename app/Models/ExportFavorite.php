<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'facility_ids',
        'export_fields',
        'options',
        'last_used_at',
    ];

    protected $casts = [
        'facility_ids' => 'array',
        'export_fields' => 'array',
        'options' => 'array',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the favorite
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for CSV favorites
     */
    public function scopeCsv($query)
    {
        return $query->where('type', 'csv');
    }

    /**
     * Scope for PDF favorites
     */
    public function scopePdf($query)
    {
        return $query->where('type', 'pdf');
    }
}