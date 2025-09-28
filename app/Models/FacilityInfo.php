<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 施設基本情報モデル
 * 施設の識別情報と連絡先情報を管理
 */
class FacilityInfo extends Model
{
    use HasFactory;

    protected $table = 'facilities';

    protected $fillable = [
        // 基本識別情報
        'company_name',
        'office_code',
        'designation_number',
        'facility_name',

        // 連絡先情報
        'postal_code',
        'address',
        'building_name',
        'phone_number',
        'fax_number',
        'toll_free_number',
        'email',
        'website_url',

        // システム管理
        'status',
        'approved_at',
        'approved_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Get the facility basic information
     */
    public function facilityBasic(): HasOne
    {
        return $this->hasOne(FacilityBasic::class, 'facility_id');
    }

    /**
     * Get the services associated with this facility
     */
    public function services(): HasMany
    {
        return $this->hasMany(FacilityService::class, 'facility_id');
    }

    /**
     * Get the land information associated with this facility
     */
    public function landInfo(): HasOne
    {
        return $this->hasOne(LandInfo::class, 'facility_id');
    }

    /**
     * Get the building information associated with this facility
     */
    public function buildingInfo(): HasOne
    {
        return $this->hasOne(BuildingInfo::class, 'facility_id');
    }

    /**
     * Get the comments associated with this facility
     */
    public function comments(): HasMany
    {
        return $this->hasMany(FacilityComment::class, 'facility_id');
    }

    /**
     * Get the maintenance histories associated with this facility
     */
    public function maintenanceHistories(): HasMany
    {
        return $this->hasMany(MaintenanceHistory::class, 'facility_id');
    }

    /**
     * Get the lifeline equipment associated with this facility
     */
    public function lifelineEquipments(): HasMany
    {
        return $this->hasMany(LifelineEquipment::class, 'facility_id');
    }

    /**
     * Get the user who created this facility
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this facility
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved this facility
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if the facility is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Scope to get only approved facilities
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Get formatted address with building name
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address ?? '';
        if ($this->building_name) {
            $address .= ' '.$this->building_name;
        }

        return trim($address);
    }

    /**
     * Get formatted postal code
     */
    public function getFormattedPostalCodeAttribute(): ?string
    {
        if (! $this->postal_code) {
            return null;
        }

        // Format as XXX-XXXX if it's 7 digits
        $code = preg_replace('/[^0-9]/', '', $this->postal_code);
        if (strlen($code) === 7) {
            return substr($code, 0, 3).'-'.substr($code, 3);
        }

        return $this->postal_code;
    }

    /**
     * Check if facility has complete basic information
     */
    public function hasCompleteBasicInfo(): bool
    {
        $requiredFields = [
            'company_name',
            'facility_name',
            'address',
            'phone_number',
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }
}
