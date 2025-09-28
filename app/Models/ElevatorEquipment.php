<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElevatorEquipment extends Model
{
    use HasFactory;

    protected $table = 'elevator_equipment';

    protected $fillable = [
        'lifeline_equipment_id',
        'basic_info',
        'notes',
    ];

    protected $casts = [
        'basic_info' => 'array',
    ];

    /**
     * Get the lifeline equipment that owns this elevator equipment.
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
            'id', // Foreign key on lifeline_equipment table
            'id', // Foreign key on facilities table
            'lifeline_equipment_id', // Local key on elevator_equipment table
            'facility_id' // Local key on lifeline_equipment table
        );
    }
}
