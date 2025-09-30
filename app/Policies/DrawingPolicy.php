<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Facility;
use App\Models\FacilityDrawing;

class DrawingPolicy
{
    /**
     * 図面を表示する権限
     */
    public function view(User $user, $facility): bool
    {
        $facilityId = $facility instanceof Facility ? $facility->id : $facility;
        return $user->canViewFacility($facilityId);
    }

    /**
     * 図面を更新する権限
     */
    public function update(User $user, $facility): bool
    {
        $facilityId = $facility instanceof Facility ? $facility->id : $facility;
        return $user->canEditFacility($facilityId);
    }

    /**
     * 図面を作成する権限
     */
    public function create(User $user, $facility): bool
    {
        $facilityId = $facility instanceof Facility ? $facility->id : $facility;
        return $user->canEditFacility($facilityId);
    }
}