<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'office_code',
        'designation_number',
        'facility_name',
        'postal_code',
        'address',
        'building_name',
        'phone_number',
        'fax_number',
        'toll_free_number',
        'email',
        'website_url',
        'opening_date',
        'years_in_operation',
        'building_structure',
        'building_floors',
        'paid_rooms_count',
        'ss_rooms_count',
        'capacity',
        'service_types',
        'designation_renewal_date',
        'status',
        'approved_at',
        'approved_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'opening_date' => 'date',
        'designation_renewal_date' => 'date',
        'service_types' => 'array',
        'years_in_operation' => 'integer',
        'building_floors' => 'integer',
        'paid_rooms_count' => 'integer',
        'ss_rooms_count' => 'integer',
        'capacity' => 'integer',
    ];

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
     * Get the comments associated with this facility
     */
    public function comments(): HasMany
    {
        return $this->hasMany(FacilityComment::class);
    }

    /**
     * Get the maintenance histories associated with this facility
     */
    public function maintenanceHistories(): HasMany
    {
        return $this->hasMany(MaintenanceHistory::class);
    }

    /**
     * Get the services associated with this facility
     */
    public function services(): HasMany
    {
        return $this->hasMany(FacilityService::class);
    }

    /**
     * Get the land information associated with this facility
     */
    public function landInfo(): HasOne
    {
        return $this->hasOne(LandInfo::class);
    }

    /**
     * Get the land documents associated with this facility
     */
    public function landDocuments(): HasMany
    {
        return $this->hasMany(File::class)
            ->whereNotNull('land_document_type');
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

    /**
     * Get service types as formatted string
     */
    public function getServiceTypesStringAttribute(): string
    {
        if (! $this->service_types || ! is_array($this->service_types)) {
            return '';
        }

        return implode('、', $this->service_types);
    }

    /**
     * Calculate years in operation from opening date
     */
    public function calculateYearsInOperation(): ?int
    {
        if (! $this->opening_date) {
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
}
