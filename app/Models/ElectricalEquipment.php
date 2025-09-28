<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectricalEquipment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'electrical_equipment';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lifeline_equipment_id',
        'basic_info',
        'pas_info',
        'cubicle_info',
        'generator_info',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'basic_info' => 'array',
        'pas_info' => 'array',
        'cubicle_info' => 'array',
        'generator_info' => 'array',
    ];

    /**
     * Get the lifeline equipment that owns this electrical equipment.
     */
    public function lifelineEquipment(): BelongsTo
    {
        return $this->belongsTo(LifelineEquipment::class);
    }

    /**
     * Get the associated facility through the lifeline equipment relationship.
     */
    public function facility(): BelongsTo
    {
        return $this->lifelineEquipment()->facility();
    }

    /**
     * Get the value of a specific basic information field.
     */
    public function getBasicInfoField(string $field): mixed
    {
        return $this->basic_info[$field] ?? null;
    }

    /**
     * Set the value of a specific basic information field.
     */
    public function setBasicInfoField(string $field, mixed $value): void
    {
        $basicInfo = $this->basic_info ?? [];
        $basicInfo[$field] = $value;
        $this->basic_info = $basicInfo;
    }

    /**
     * Get the value of a specific PAS information field.
     */
    public function getPasInfoField(string $field): mixed
    {
        return $this->pas_info[$field] ?? null;
    }

    /**
     * Set the value of a specific PAS information field.
     */
    public function setPasInfoField(string $field, mixed $value): void
    {
        $pasInfo = $this->pas_info ?? [];
        $pasInfo[$field] = $value;
        $this->pas_info = $pasInfo;
    }

    /**
     * Get the value of a specific cubicle information field.
     */
    public function getCubicleInfoField(string $field): mixed
    {
        return $this->cubicle_info[$field] ?? null;
    }

    /**
     * Set the value of a specific cubicle information field.
     */
    public function setCubicleInfoField(string $field, mixed $value): void
    {
        $cubicleInfo = $this->cubicle_info ?? [];
        $cubicleInfo[$field] = $value;
        $this->cubicle_info = $cubicleInfo;
    }

    /**
     * Get the value of a specific generator information field.
     */
    public function getGeneratorInfoField(string $field): mixed
    {
        return $this->generator_info[$field] ?? null;
    }

    /**
     * Set the value of a specific generator information field.
     */
    public function setGeneratorInfoField(string $field, mixed $value): void
    {
        $generatorInfo = $this->generator_info ?? [];
        $generatorInfo[$field] = $value;
        $this->generator_info = $generatorInfo;
    }

    /**
     * Check if PAS is available.
     */
    public function hasPas(): bool
    {
        return $this->getPasInfoField('availability') === '有';
    }

    /**
     * Check if cubicle is available.
     */
    public function hasCubicle(): bool
    {
        return $this->getCubicleInfoField('availability') === '有';
    }

    /**
     * Check if generator is available.
     */
    public function hasGenerator(): bool
    {
        return $this->getGeneratorInfoField('availability') === '有';
    }

    /**
     * Get the list of cubicle equipment.
     */
    public function getCubicleEquipmentList(): array
    {
        return $this->getCubicleInfoField('equipment_list') ?? [];
    }

    /**
     * Get the list of generator equipment.
     */
    public function getGeneratorEquipmentList(): array
    {
        return $this->getGeneratorInfoField('equipment_list') ?? [];
    }
}
