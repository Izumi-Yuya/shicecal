<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HvacLightingEquipment extends Model
{
    use HasFactory;

    protected $table = 'hvac_lighting_equipment';

    protected $fillable = [
        'lifeline_equipment_id',
        'basic_info',
        'notes',
    ];

    protected $casts = [
        'basic_info' => 'array',
    ];

    /**
     * Get the lifeline equipment that owns this HVAC/Lighting equipment.
     */
    public function lifelineEquipment()
    {
        return $this->belongsTo(LifelineEquipment::class);
    }

    /**
     * Get the facility through the lifeline equipment relationship.
     */
    public function facility()
    {
        return $this->hasOneThrough(Facility::class, LifelineEquipment::class, 'id', 'id', 'lifeline_equipment_id', 'facility_id');
    }
}
