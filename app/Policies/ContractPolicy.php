<?php

namespace App\Policies;

use App\Models\Facility;
use App\Models\FacilityContract;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;

    /**
     * 契約書を表示できるかどうか
     */
    public function view(User $user, $facility): bool
    {
        return $user->canViewFacility($facility->id ?? $facility);
    }

    /**
     * 契約書を更新できるかどうか
     */
    public function update(User $user, $facility): bool
    {
        $facilityId = $facility->id ?? $facility;
        $canEdit = $user->canEditFacility($facilityId);
        
        // Debug logging
        \Log::info('ContractPolicy update check', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'facility_id' => $facilityId,
            'canEditFacility' => $canEdit,
            'canEdit' => $user->canEdit(),
            'canAccessFacility' => $user->canAccessFacility($facilityId),
        ]);
        
        return $canEdit;
    }

    /**
     * 契約書を作成できるかどうか
     */
    public function create(User $user, $facility): bool
    {
        return $user->canEditFacility($facility->id ?? $facility);
    }
}
