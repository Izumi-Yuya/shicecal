<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GasEquipment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'gas_equipment';

    protected $fillable = [
        'lifeline_equipment_id',
        'basic_info',
        'notes',
    ];

    protected $casts = [
        'basic_info' => 'array',
    ];

    /**
     * Get the lifeline equipment that owns this gas equipment.
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
        return $this->hasOneThrough(
            Facility::class,
            LifelineEquipment::class,
            'id',
            'id',
            'lifeline_equipment_id',
            'facility_id'
        );
    }
}