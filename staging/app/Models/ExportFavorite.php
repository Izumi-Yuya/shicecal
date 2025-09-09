<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'facility_ids',
        'export_fields',
    ];

    protected $casts = [
        'facility_ids' => 'array',
        'export_fields' => 'array',
    ];

    /**
     * Get the user that owns this favorite
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
