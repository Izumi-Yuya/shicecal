<?php

namespace App\Policies;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LifelineEquipmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view lifeline equipment information.
     */
    public function view(User $user, Facility $facility): bool
    {
        // Check if user can access the facility
        if (!$user->canAccessFacility($facility->id)) {
            return false;
        }

        // Role-based access control
        switch ($user->role) {
            case 'admin':
            case 'editor':
            case 'primary_responder':
            case 'approver':
                return true;

            case 'viewer':
                // Viewers can only see facilities within their access scope
                return $user->canAccessFacility($facility->id);

            default:
                return false;
        }
    }

    /**
     * Determine whether the user can view any lifeline equipment information.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            'admin',
            'editor',
            'primary_responder',
            'approver',
            'viewer',
        ]);
    }

    /**
     * Determine whether the user can create lifeline equipment information.
     */
    public function create(User $user, Facility $facility): bool
    {
        // Only editors and above can create lifeline equipment information
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can update lifeline equipment information.
     */
    public function update(User $user, Facility $facility): bool
    {
        // Only editors and above can update lifeline equipment information
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can delete lifeline equipment information.
     */
    public function delete(User $user, Facility $facility): bool
    {
        // Only admins can delete lifeline equipment information
        if (!$user->isAdmin()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can approve lifeline equipment changes.
     */
    public function approve(User $user, Facility $facility): bool
    {
        // Only approvers and admins can approve changes
        if (!$user->canApprove()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can reject lifeline equipment changes.
     */
    public function reject(User $user, Facility $facility): bool
    {
        // Only approvers and admins can reject changes
        if (!$user->canApprove()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can export lifeline equipment information.
     */
    public function export(User $user): bool
    {
        // All authenticated users can export (filtered by their access scope)
        return in_array($user->role, [
            'admin',
            'editor',
            'primary_responder',
            'approver',
            'viewer',
        ]);
    }

    /**
     * Determine whether the user can view audit logs for lifeline equipment.
     */
    public function viewAuditLogs(User $user, Facility $facility): bool
    {
        // Only admins and approvers can view audit logs
        if (!in_array($user->role, ['admin', 'approver'])) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }
}