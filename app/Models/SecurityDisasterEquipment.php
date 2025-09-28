<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityDisasterEquipment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'security_disaster_equipment';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lifeline_equipment_id',
        'basic_info',
        'security_systems',
        'disaster_prevention',
        'emergency_equipment',
        'maintenance_records',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'basic_info' => 'array',
        'security_systems' => 'array',
        'disaster_prevention' => 'array',
        'emergency_equipment' => 'array',
        'maintenance_records' => 'array',
    ];

    /**
     * Get the lifeline equipment that owns this security disaster equipment.
     */
    public function lifelineEquipment(): BelongsTo
    {
        return $this->belongsTo(LifelineEquipment::class, 'lifeline_equipment_id', 'id');
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
     * Get the facility through the lifeline equipment relationship.
     */
    public function facility()
    {
        return $this->lifelineEquipment()->facility();
    }

    /**
     * Check if security systems are configured.
     */
    public function hasSecuritySystems(): bool
    {
        return !empty($this->security_systems) && is_array($this->security_systems);
    }

    /**
     * Check if disaster prevention systems are configured.
     */
    public function hasDisasterPrevention(): bool
    {
        return !empty($this->disaster_prevention) && is_array($this->disaster_prevention);
    }

    /**
     * Check if emergency equipment is configured.
     */
    public function hasEmergencyEquipment(): bool
    {
        return !empty($this->emergency_equipment) && is_array($this->emergency_equipment);
    }

    /**
     * Get formatted maintenance records.
     */
    public function getFormattedMaintenanceRecords(): array
    {
        if (!$this->maintenance_records || !is_array($this->maintenance_records)) {
            return [];
        }

        return collect($this->maintenance_records)
            ->sortByDesc('date')
            ->values()
            ->toArray();
    }
}