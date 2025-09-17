<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LifelineEquipment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'lifeline_equipment';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'facility_id',
        'category',
        'status',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Available categories for lifeline equipment.
     */
    public const CATEGORIES = [
        'electrical' => '電気',
        'gas' => 'ガス',
        'water' => '水道',
        'elevator' => 'エレベーター',
        'hvac_lighting' => '空調・照明',
    ];

    /**
     * Available statuses for lifeline equipment.
     */
    public const STATUSES = [
        'active' => 'アクティブ',
        'inactive' => '非アクティブ',
        'decommissioned' => '廃止',
        'draft' => '下書き',
        'pending_approval' => '承認待ち',
        'approved' => '承認済み',
        'rejected' => '却下',
    ];

    /**
     * Get the facility that owns the lifeline equipment.
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved this record.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the electrical equipment associated with this lifeline equipment.
     */
    public function electricalEquipment(): HasOne
    {
        return $this->hasOne(ElectricalEquipment::class);
    }

    /**
     * Get the gas equipment associated with this lifeline equipment.
     */
    public function gasEquipment(): HasOne
    {
        return $this->hasOne(GasEquipment::class);
    }

    /**
     * Get the water equipment associated with this lifeline equipment.
     */
    public function waterEquipment(): HasOne
    {
        return $this->hasOne(WaterEquipment::class);
    }

    /**
     * Get the elevator equipment associated with this lifeline equipment.
     */
    public function elevatorEquipment(): HasOne
    {
        return $this->hasOne(ElevatorEquipment::class);
    }

    /**
     * Get the HVAC/Lighting equipment associated with this lifeline equipment.
     */
    public function hvacLightingEquipment(): HasOne
    {
        return $this->hasOne(HvacLightingEquipment::class);
    }

    /**
     * Check if the equipment is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the equipment is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Check if the equipment is decommissioned.
     */
    public function isDecommissioned(): bool
    {
        return $this->status === 'decommissioned';
    }

    /**
     * Check if the equipment is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the equipment is pending approval.
     */
    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if the equipment is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the equipment is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Get the category display name.
     */
    public function getCategoryDisplayName(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayName(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
