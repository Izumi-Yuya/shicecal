<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class FacilityAccessService
{
    /**
     * Get facilities query based on user role and access scope
     */
    public function getFacilitiesQuery(User $user): Builder
    {
        $query = Facility::approved()->with('landInfo');

        switch ($user->role) {
            case 'admin':
            case 'editor':
                // Admin and editor can see all facilities
                break;

            case 'viewer':
                $this->applyViewerScope($query, $user);
                break;

            default:
                // Other roles get no facilities by default
                $query->whereRaw('1 = 0');
                break;
        }

        return $query->orderBy('company_name')
            ->orderBy('facility_name');
    }

    /**
     * Get facilities based on user permissions
     */
    public function getFacilitiesForUser(?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $user = $user ?? auth()->user();
        return $this->getFacilitiesQuery($user)->get();
    }

    /**
     * Check if user can view a specific facility
     */
    public function canViewFacility(User $user, Facility $facility): bool
    {
        return $this->getFacilitiesQuery($user)
            ->where('facilities.id', $facility->id)
            ->exists();
    }

    /**
     * Apply viewer scope restrictions
     */
    private function applyViewerScope(Builder $query, User $user): void
    {
        if (!isset($user->access_scope['type'])) {
            return;
        }

        switch ($user->access_scope['type']) {
            case 'department':
                if (isset($user->access_scope['departments'])) {
                    $query->whereIn('department', $user->access_scope['departments']);
                }
                break;

            case 'region':
                if (isset($user->access_scope['regions'])) {
                    $query->where(function ($q) use ($user) {
                        foreach ($user->access_scope['regions'] as $region) {
                            $q->orWhere('address', 'like', "%{$region}%");
                        }
                    });
                }
                break;

            case 'facility':
                if (isset($user->access_scope['facility_ids'])) {
                    $query->whereIn('id', $user->access_scope['facility_ids']);
                }
                break;
        }
    }
}