<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'facility_id',
        'maintenance_date',
        'content',
        'cost',
        'contractor',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'maintenance_date' => 'date',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the facility this maintenance history belongs to
     */
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the user who created this maintenance history
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for maintenance histories by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('maintenance_date', [$startDate, $endDate]);
    }

    /**
     * Scope for maintenance histories by facility
     */
    public function scopeByFacility($query, $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    /**
     * Scope for maintenance histories by content search
     */
    public function scopeSearchByContent($query, $content)
    {
        return $query->where('content', 'like', '%' . $content . '%');
    }

    /**
     * Scope for maintenance histories by contractor
     */
    public function scopeByContractor($query, $contractor)
    {
        return $query->where('contractor', 'like', '%' . $contractor . '%');
    }

    /**
     * Scope for maintenance histories with cost
     */
    public function scopeWithCost($query)
    {
        return $query->whereNotNull('cost');
    }

    /**
     * Scope for recent maintenance histories
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('maintenance_date', '>=', now()->subDays($days));
    }

    /**
     * Get formatted cost
     */
    public function getFormattedCostAttribute()
    {
        if ($this->cost === null) {
            return '未記録';
        }
        
        return '¥' . number_format($this->cost);
    }

    /**
     * Get maintenance year
     */
    public function getMaintenanceYearAttribute()
    {
        return $this->maintenance_date->year;
    }

    /**
     * Get maintenance month
     */
    public function getMaintenanceMonthAttribute()
    {
        return $this->maintenance_date->month;
    }

    /**
     * Check if maintenance was expensive (over 100,000 yen)
     */
    public function isExpensive()
    {
        return $this->cost && $this->cost > 100000;
    }
}