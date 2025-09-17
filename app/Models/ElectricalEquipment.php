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
     * Get the facility through the lifeline equipment relationship.
     */
    public function facility(): BelongsTo
    {
        return $this->lifelineEquipment()->facility();
    }

    /**
     * Get basic information field value.
     */
    public function getBasicInfoField(string $field): mixed
    {
        return $this->basic_info[$field] ?? null;
    }

    /**
     * Set basic information field value.
     */
    public function setBasicInfoField(string $field, mixed $value): void
    {
        $basicInfo = $this->basic_info ?? [];
        $basicInfo[$field] = $value;
        $this->basic_info = $basicInfo;
    }

    /**
     * Get PAS information field value.
     */
    public function getPasInfoField(string $field): mixed
    {
        return $this->pas_info[$field] ?? null;
    }

    /**
     * Set PAS information field value.
     */
    public function setPasInfoField(string $field, mixed $value): void
    {
        $pasInfo = $this->pas_info ?? [];
        $pasInfo[$field] = $value;
        $this->pas_info = $pasInfo;
    }

    /**
     * Get cubicle information field value.
     */
    public function getCubicleInfoField(string $field): mixed
    {
        return $this->cubicle_info[$field] ?? null;
    }

    /**
     * Set cubicle information field value.
     */
    public function setCubicleInfoField(string $field, mixed $value): void
    {
        $cubicleInfo = $this->cubicle_info ?? [];
        $cubicleInfo[$field] = $value;
        $this->cubicle_info = $cubicleInfo;
    }

    /**
     * Get generator information field value.
     */
    public function getGeneratorInfoField(string $field): mixed
    {
        return $this->generator_info[$field] ?? null;
    }

    /**
     * Set generator information field value.
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
     * Get cubicle equipment list.
     */
    public function getCubicleEquipmentList(): array
    {
        return $this->getCubicleInfoField('equipment_list') ?? [];
    }

    /**
     * Get generator equipment list.
     */
    public function getGeneratorEquipmentList(): array
    {
        return $this->getGeneratorInfoField('equipment_list') ?? [];
    }
}
