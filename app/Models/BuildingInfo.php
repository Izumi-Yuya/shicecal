<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'ownership_type',
        'building_area_sqm',
        'building_area_tsubo',
        'total_floor_area_sqm',
        'total_floor_area_tsubo',
        'construction_cost',
        'cost_per_tsubo',
        'construction_cooperation_fee',
        'monthly_rent',
        'contract_start_date',
        'contract_end_date',
        'auto_renewal',
        'contract_years',
        'management_company_name',
        'management_company_postal_code',
        'management_company_address',
        'management_company_building_name',
        'management_company_phone',
        'management_company_fax',
        'management_company_email',
        'management_company_url',
        'management_company_notes',
        'owner_name',
        'owner_postal_code',
        'owner_address',
        'owner_building_name',
        'owner_phone',
        'owner_fax',
        'owner_email',
        'owner_url',
        'owner_notes',
        'construction_company_name',
        'construction_company_phone',
        'construction_company_notes',
        'completion_date',
        'building_age',
        'useful_life',
        'building_permit_pdf',
        'building_inspection_pdf',
        'fire_equipment_inspection_pdf',
        'periodic_inspection_type',
        'periodic_inspection_company_phone',
        'periodic_inspection_date',
        'periodic_inspection_pdf',
        'periodic_inspection_notes',
        'construction_contract_pdf',
        'lease_contract_pdf',
        'registry_pdf',
        'notes',
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'completion_date' => 'date',
        'periodic_inspection_date' => 'date',
        'auto_renewal' => 'boolean',
        'building_area_sqm' => 'decimal:2',
        'building_area_tsubo' => 'decimal:2',
        'total_floor_area_sqm' => 'decimal:2',
        'total_floor_area_tsubo' => 'decimal:2',
        'cost_per_tsubo' => 'decimal:2',
        'construction_cost' => 'integer',
        'construction_cooperation_fee' => 'integer',
        'monthly_rent' => 'integer',
        'contract_years' => 'integer',
        'building_age' => 'integer',
        'useful_life' => 'integer',
    ];

    /**
     * Get the facility that owns this building info
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Calculate cost per tsubo from construction cost and building area
     */
    public function calculateCostPerTsubo(): ?float
    {
        if (! $this->construction_cost || ! $this->building_area_tsubo) {
            return null;
        }

        return round($this->construction_cost / $this->building_area_tsubo, 2);
    }

    /**
     * Calculate contract years from start and end dates
     */
    public function calculateContractYears(): ?int
    {
        if (! $this->contract_start_date || ! $this->contract_end_date) {
            return null;
        }

        return $this->contract_start_date->diffInYears($this->contract_end_date);
    }

    /**
     * Calculate building age from completion date
     */
    public function calculateBuildingAge(): ?int
    {
        if (! $this->completion_date) {
            return null;
        }

        return $this->completion_date->diffInYears(now());
    }

    /**
     * Update calculated fields
     */
    public function updateCalculatedFields(): void
    {
        $this->cost_per_tsubo = $this->calculateCostPerTsubo();
        $this->contract_years = $this->calculateContractYears();
        $this->building_age = $this->calculateBuildingAge();
        $this->save();
    }

    /**
     * Get formatted construction cost
     */
    public function getFormattedConstructionCostAttribute(): ?string
    {
        if (! $this->construction_cost) {
            return null;
        }

        return '¥'.number_format($this->construction_cost);
    }

    /**
     * Get formatted monthly rent
     */
    public function getFormattedMonthlyRentAttribute(): ?string
    {
        if (! $this->monthly_rent) {
            return null;
        }

        return '¥'.number_format($this->monthly_rent);
    }

    /**
     * Get formatted postal code for management company
     */
    public function getFormattedManagementPostalCodeAttribute(): ?string
    {
        if (! $this->management_company_postal_code) {
            return null;
        }

        $code = preg_replace('/[^0-9]/', '', $this->management_company_postal_code);
        if (strlen($code) === 7) {
            return substr($code, 0, 3).'-'.substr($code, 3);
        }

        return $this->management_company_postal_code;
    }

    /**
     * Get formatted postal code for owner
     */
    public function getFormattedOwnerPostalCodeAttribute(): ?string
    {
        if (! $this->owner_postal_code) {
            return null;
        }

        $code = preg_replace('/[^0-9]/', '', $this->owner_postal_code);
        if (strlen($code) === 7) {
            return substr($code, 0, 3).'-'.substr($code, 3);
        }

        return $this->owner_postal_code;
    }
}
