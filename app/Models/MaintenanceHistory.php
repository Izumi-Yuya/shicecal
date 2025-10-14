<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceHistory extends Model
{
    use HasFactory;

    /**
     * カテゴリ定数
     */
    const CATEGORIES = [
        'exterior' => '外装',
        'interior' => '内装リニューアル',
        'other' => 'その他'
    ];

    /**
     * サブカテゴリ定数
     */
    const SUBCATEGORIES = [
        'exterior' => [
            'waterproof' => '防水',
            'painting' => '塗装',
            'termite_control' => '白アリ駆除'
        ],
        'interior' => [
            'renovation' => '内装リニューアル',
            'design' => '内装・意匠'
        ],
        'other' => [
            'renovation_work' => '改修工事'
        ]
    ];

    protected $fillable = [
        'facility_id',
        'maintenance_date',
        'content',
        'cost',
        'contractor',
        'category',
        'subcategory',
        'contact_person',
        'phone_number',
        'notes',
        'special_notes',
        'warranty_period_years',
        'warranty_period_months',
        'warranty_start_date',
        'warranty_end_date',
        'created_by',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'warranty_period_years' => 'integer',
        'warranty_period_months' => 'integer',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the facility that owns the maintenance history.
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the user who created the maintenance history.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to filter by facility.
     */
    public function scopeForFacility($query, $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('maintenance_date', [$startDate, $endDate]);
    }

    /**
     * Scope to search by content.
     */
    public function scopeSearchContent($query, $search)
    {
        return $query->where('content', 'like', '%'.$search.'%');
    }

    /**
     * Scope to order by maintenance date (newest first).
     */
    public function scopeLatestByDate($query)
    {
        return $query->orderBy('maintenance_date', 'desc');
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by subcategory.
     */
    public function scopeBySubcategory($query, $subcategory)
    {
        return $query->where('subcategory', $subcategory);
    }

    /**
     * Scope to order by maintenance date.
     */
    public function scopeOrderByDate($query, $direction = 'desc')
    {
        return $query->orderBy('maintenance_date', $direction);
    }

    /**
     * Get the category label in Japanese.
     * 
     * @return string The Japanese translation for the category
     */
    public function getCategoryLabelAttribute()
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Get the subcategory label in Japanese.
     * 
     * @return string|null The Japanese translation for the subcategory
     */
    public function getSubcategoryLabelAttribute()
    {
        if (!$this->category || !$this->subcategory) {
            return $this->subcategory;
        }

        return self::SUBCATEGORIES[$this->category][$this->subcategory] ?? $this->subcategory;
    }

    /**
     * Get available subcategories for a given category.
     * 
     * @param string $category The category to get subcategories for
     * @return array The available subcategories for the category
     */
    public static function getSubcategoriesForCategory($category)
    {
        return self::SUBCATEGORIES[$category] ?? [];
    }
}
