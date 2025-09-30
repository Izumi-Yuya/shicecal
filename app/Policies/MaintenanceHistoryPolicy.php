<?php

namespace App\Policies;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenanceHistoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view maintenance history information.
     * Checks facility access permissions and user role authorization.
     * 
     * @param User $user The user requesting access
     * @param Facility $facility The facility containing the maintenance history
     * @return bool True if the user can view the maintenance history
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
     * Determine whether the user can view any maintenance history information.
     * 
     * @param User $user The user requesting access
     * @return bool True if the user can view any maintenance history
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
     * Determine whether the user can create maintenance history information.
     * Only editors and above with facility access can create records.
     * 
     * @param User $user The user requesting access
     * @param Facility $facility The facility for which the maintenance history will be created
     * @return bool True if the user can create maintenance history records
     */
    public function create(User $user, Facility $facility): bool
    {
        // Only editors and above can create maintenance history information
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can update maintenance history information.
     * Only editors and above with facility access can update records.
     * 
     * @param User $user The user requesting access
     * @param Facility $facility The facility for which the maintenance history will be updated
     * @return bool True if the user can update maintenance history records
     */
    public function update(User $user, Facility $facility): bool
    {
        // Only editors and above can update maintenance history information
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can delete maintenance history information.
     * 
     * @param User $user The user requesting access
     * @param Facility $facility The facility for the maintenance history
     * @return bool True if the user can delete maintenance history
     */
    public function delete(User $user, Facility $facility): bool
    {
        // Only admins can delete maintenance history information
        if (!$user->isAdmin()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can approve maintenance history changes.
     * 
     * @param User $user The user requesting access
     * @param Facility $facility The facility for the maintenance history
     * @return bool True if the user can approve maintenance history changes
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
     * Determine whether the user can reject maintenance history changes.
     * 
     * @param User $user The user requesting access
     * @param Facility $facility The facility for the maintenance history
     * @return bool True if the user can reject maintenance history changes
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
     * Determine whether the user can export maintenance history information.
     * 
     * @param User $user The user requesting access
     * @return bool True if the user can export maintenance history
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
     * Determine whether the user can view audit logs for maintenance history.
     * 
     * @param User $user The user requesting access
     * @param Facility $facility The facility for the maintenance history
     * @return bool True if the user can view audit logs
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