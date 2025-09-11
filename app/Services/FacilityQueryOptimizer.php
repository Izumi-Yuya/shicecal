<?php

namespace App\Services;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for optimizing facility-related database queries
 */
class FacilityQueryOptimizer
{
    /**
     * Get facility with all necessary relationships for display
     */
    public function getFacilityForDisplay(int $facilityId): Facility
    {
        return Facility::with([
            'services' => function ($query) {
                $query->select(['id', 'facility_id', 'service_type', 'renewal_start_date', 'renewal_end_date'])
                    ->orderBy('service_type');
            },
            'landInfo' => function ($query) {
                $query->select(['id', 'facility_id', 'status', 'approved_at', 'approved_by']);
            },
            'comments' => function ($query) {
                $query->select(['id', 'facility_id', 'section', 'comment', 'created_at', 'user_id'])
                    ->with('user:id,name')
                    ->where('status', 'active')
                    ->orderBy('created_at', 'desc');
            },
        ])->findOrFail($facilityId);
    }

    /**
     * Get facilities with minimal data for listing
     */
    public function getFacilitiesForListing(): Collection
    {
        return Facility::select([
            'id', 'company_name', 'facility_name', 'office_code',
            'address', 'phone_number', 'status', 'updated_at',
        ])
            ->with(['services:id,facility_id,service_type'])
            ->orderBy('company_name')
            ->get();
    }

    /**
     * Get facilities with comment counts
     */
    public function getFacilitiesWithCommentCounts(): Collection
    {
        return Facility::withCount([
            'comments as total_comments_count',
            'comments as active_comments_count' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();
    }

    /**
     * Optimize query for specific view mode
     */
    public function optimizeForViewMode(Builder $query, string $viewMode): Builder
    {
        if ($viewMode === 'table') {
            // Table view needs all fields for display
            return $query->with([
                'services:id,facility_id,service_type,renewal_start_date,renewal_end_date',
            ]);
        } else {
            // Card view can be more selective
            return $query->with([
                'services' => function ($serviceQuery) {
                    $serviceQuery->select(['id', 'facility_id', 'service_type', 'renewal_start_date', 'renewal_end_date'])
                        ->limit(5); // Limit for card view
                },
            ]);
        }
    }

    /**
     * Batch load comment counts for multiple facilities
     */
    public function batchLoadCommentCounts(Collection $facilities): array
    {
        $facilityIds = $facilities->pluck('id')->toArray();

        $commentCounts = \DB::table('comments')
            ->select('facility_id', 'section', \DB::raw('COUNT(*) as count'))
            ->whereIn('facility_id', $facilityIds)
            ->where('status', 'active')
            ->groupBy('facility_id', 'section')
            ->get()
            ->groupBy('facility_id');

        $result = [];
        foreach ($facilities as $facility) {
            $result[$facility->id] = $commentCounts->get($facility->id, collect())
                ->pluck('count', 'section')
                ->toArray();
        }

        return $result;
    }
}
