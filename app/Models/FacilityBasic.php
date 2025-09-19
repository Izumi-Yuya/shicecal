<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 施設詳細情報モデル
 * 詳細画面で表示・編集される詳細情報を管理
 */
class FacilityBasic extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        
        // 運営情報
        'opening_date',
        'years_in_operation',
        'designation_renewal_date',
        
        // 建物情報
        'building_structure',
        'building_floors',
        
        // 定員・部屋数
        'paid_rooms_count',
        'ss_rooms_count',
        'capacity',
        
        // サービス情報
        'service_types',
        
        // 部門
        'section',
        
        // システム管理
        'status',
        'approved_at',
        'approved_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'designation_renewal_date' => 'date',
        'service_types' => 'array',
        'years_in_operation' => 'integer',
        'building_floors' => 'integer',
        'paid_rooms_count' => 'integer',
        'ss_rooms_count' => 'integer',
        'capacity' => 'integer',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the facility info that owns this basic information
     */
    public function facilityInfo(): BelongsTo
    {
        return $this->belongsTo(FacilityInfo::class, 'facility_id');
    }

    /**
     * Get the user who created this record
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved this record
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if the basic info is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Scope to get only approved basic info
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Get service types as formatted string
     */
    public function getServiceTypesStringAttribute(): string
    {
        if (!$this->service_types || !is_array($this->service_types)) {
            return '';
        }
        return implode('、', $this->service_types);
    }

    /**
     * Calculate years in operation from opening date
     */
    public function calculateYearsInOperation(): ?int
    {
        if (!$this->opening_date) {
            return null;
        }

        $now = new \DateTime;
        $openingDate = new \DateTime($this->opening_date);
        return $now->diff($openingDate)->y;
    }

    /**
     * Update years in operation based on opening date
     */
    public function updateYearsInOperation(): void
    {
        $this->years_in_operation = $this->calculateYearsInOperation();
        $this->save();
    }

    /**
     * Check if facility has complete detailed information
     */
    public function hasCompleteDetailedInfo(): bool
    {
        $requiredFields = [
            'opening_date',
            'building_structure',
            'capacity',
            'section',
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get total room count
     */
    public function getTotalRoomsAttribute(): int
    {
        return ($this->paid_rooms_count ?? 0) + ($this->ss_rooms_count ?? 0);
    }
}