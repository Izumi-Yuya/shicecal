<?php

namespace App\Policies;

use App\Models\LandInfo;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LandInfoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view land information.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
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
     * Determine whether the user can view any land information.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            'admin',
            'editor',
            'primary_responder',
            'approver',
            'viewer'
        ]);
    }

    /**
     * Determine whether the user can create land information.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function create(User $user, Facility $facility): bool
    {
        // Only editors and above can create land information
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can update land information.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function update(User $user, Facility $facility): bool
    {
        // Only editors and above can update land information
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        if (!$user->canAccessFacility($facility->id)) {
            return false;
        }

        // Check if user has permission to edit land info (simplified)
        return $user->canEditLandInfo();
    }

    /**
     * Determine whether the user can update basic land information fields.
     */
    public function updateBasicInfo(User $user, Facility $facility): bool
    {
        return $user->canEditLandBasicInfo() && $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can update financial land information fields.
     */
    public function updateFinancialInfo(User $user, Facility $facility): bool
    {
        return $user->canEditLandFinancialInfo() && $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can update management/owner information fields.
     */
    public function updateManagementInfo(User $user, Facility $facility): bool
    {
        return $user->canEditLandManagementInfo() && $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can update land documents.
     */
    public function updateDocuments(User $user, Facility $facility): bool
    {
        return $user->canEditLandDocuments() && $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can delete land information.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function delete(User $user, Facility $facility): bool
    {
        // Only admins can delete land information
        if (!$user->isAdmin()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can approve land information changes.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
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
     * Determine whether the user can reject land information changes.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
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
     * Determine whether the user can upload land documents.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function uploadDocuments(User $user, Facility $facility): bool
    {
        // Only editors and above can upload documents
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can download land documents.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function downloadDocuments(User $user, Facility $facility): bool
    {
        // Same as view permission
        return $this->view($user, $facility);
    }

    /**
     * Determine whether the user can delete land documents.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function deleteDocuments(User $user, Facility $facility): bool
    {
        // Only editors and above can delete documents
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can export land information.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function export(User $user): bool
    {
        // All authenticated users can export (filtered by their access scope)
        return in_array($user->role, [
            'admin',
            'editor',
            'primary_responder',
            'approver',
            'viewer'
        ]);
    }

    /**
     * Determine whether the user can view audit logs for land information.
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
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
