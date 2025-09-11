<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'maintenance_date',
        'content',
        'cost',
        'contractor',
        'created_by',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the facility that owns the maintenance history.
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the user who created the maintenance history.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to filter by facility.
     */
    public function scopeForFacility($query, $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('maintenance_date', [$startDate, $endDate]);
    }

    /**
     * Scope to search by content.
     */
    public function scopeSearchContent($query, $search)
    {
        return $query->where('content', 'like', '%'.$search.'%');
    }

    /**
     * Scope to order by maintenance date (newest first).
     */
    public function scopeLatestByDate($query)
    {
        return $query->orderBy('maintenance_date', 'desc');
    }
}
