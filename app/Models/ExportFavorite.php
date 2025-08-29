<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportFavorite extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'facility_ids',
        'export_fields',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'facility_ids' => 'array',
        'export_fields' => 'array',
    ];

    /**
     * Get the user who owns this export favorite
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get facilities associated with this favorite
     */
    public function facilities()
    {
        return Facility::whereIn('id', $this->facility_ids ?? []);
    }

    /**
     * Scope for favorites by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for favorites by name search
     */
    public function scopeSearchByName($query, $name)
    {
        return $query->where('name', 'like', '%' . $name . '%');
    }

    /**
     * Get count of selected facilities
     */
    public function getFacilityCountAttribute()
    {
        return count($this->facility_ids ?? []);
    }

    /**
     * Get count of selected export fields
     */
    public function getFieldCountAttribute()
    {
        return count($this->export_fields ?? []);
    }

    /**
     * Check if favorite includes specific facility
     */
    public function includesFacility($facilityId)
    {
        return in_array($facilityId, $this->facility_ids ?? []);
    }

    /**
     * Check if favorite includes specific field
     */
    public function includesField($field)
    {
        return in_array($field, $this->export_fields ?? []);
    }

    /**
     * Add facility to favorite
     */
    public function addFacility($facilityId)
    {
        $facilityIds = $this->facility_ids ?? [];
        if (!in_array($facilityId, $facilityIds)) {
            $facilityIds[] = $facilityId;
            $this->facility_ids = $facilityIds;
            $this->save();
        }
    }

    /**
     * Remove facility from favorite
     */
    public function removeFacility($facilityId)
    {
        $facilityIds = $this->facility_ids ?? [];
        $facilityIds = array_filter($facilityIds, function($id) use ($facilityId) {
            return $id != $facilityId;
        });
        $this->facility_ids = array_values($facilityIds);
        $this->save();
    }

    /**
     * Add export field to favorite
     */
    public function addExportField($field)
    {
        $exportFields = $this->export_fields ?? [];
        if (!in_array($field, $exportFields)) {
            $exportFields[] = $field;
            $this->export_fields = $exportFields;
            $this->save();
        }
    }

    /**
     * Remove export field from favorite
     */
    public function removeExportField($field)
    {
        $exportFields = $this->export_fields ?? [];
        $exportFields = array_filter($exportFields, function($f) use ($field) {
            return $f != $field;
        });
        $this->export_fields = array_values($exportFields);
        $this->save();
    }
}