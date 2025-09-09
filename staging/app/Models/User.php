<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'access_scope',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'access_scope' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the comments posted by this user.
     */
    public function postedComments(): HasMany
    {
        return $this->hasMany(Comment::class, 'posted_by');
    }

    /**
     * Get the comments assigned to this user.
     */
    public function assignedComments(): HasMany
    {
        return $this->hasMany(Comment::class, 'assigned_to');
    }

    /**
     * Get the export favorites for this user.
     */
    public function exportFavorites(): HasMany
    {
        return $this->hasMany(ExportFavorite::class);
    }

    /**
     * Get the notifications for this user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Check if user has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has editor role.
     */
    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    /**
     * Check if user has primary responder role.
     */
    public function isPrimaryResponder(): bool
    {
        return $this->role === 'primary_responder';
    }

    /**
     * Check if user has approver role.
     */
    public function isApprover(): bool
    {
        return $this->role === 'approver';
    }

    /**
     * Check if user has viewer role.
     */
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    /**
     * Check if user can edit facilities.
     */
    public function canEdit(): bool
    {
        return in_array($this->role, ['admin', 'editor', 'primary_responder']);
    }

    /**
     * Check if user can approve changes.
     */
    public function canApprove(): bool
    {
        return in_array($this->role, ['admin', 'approver']);
    }

    /**
     * Check if user can manage system settings.
     */
    public function canManageSystem(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can view all facilities.
     */
    public function canViewAll(): bool
    {
        return in_array($this->role, ['admin', 'editor', 'primary_responder', 'approver']) ||
            ($this->role === 'viewer' && empty($this->access_scope));
    }

    /**
     * Get accessible facility IDs based on user's access scope.
     */
    public function getAccessibleFacilityIds(): array
    {
        if ($this->canViewAll()) {
            return []; // Empty array means all facilities
        }

        if ($this->role === 'viewer' && !empty($this->access_scope)) {
            // Get facilities based on access scope
            $query = \App\Models\Facility::query();

            if (isset($this->access_scope['prefectures'])) {
                $query->whereIn('prefecture', $this->access_scope['prefectures']);
            }

            return $query->pluck('id')->toArray();
        }

        return [];
    }

    /**
     * Check if user can access specific facility.
     */
    public function canAccessFacility($facilityId): bool
    {
        if ($this->canViewAll()) {
            return true;
        }

        $accessibleIds = $this->getAccessibleFacilityIds();
        return empty($accessibleIds) || in_array($facilityId, $accessibleIds);
    }

    /**
     * Check if user can edit specific facility.
     */
    public function canEditFacility($facilityId): bool
    {
        // Must have edit permissions and access to the facility
        return $this->canEdit() && $this->canAccessFacility($facilityId);
    }

    /**
     * Check if user can view specific facility.
     */
    public function canViewFacility($facilityId): bool
    {
        // Same as canAccessFacility for now
        return $this->canAccessFacility($facilityId);
    }

    /**
     * Check if user has multiple departments (comma-separated).
     */
    public function hasMultipleDepartments(): bool
    {
        return str_contains($this->department, ',');
    }

    /**
     * Get array of user's departments.
     */
    public function getDepartments(): array
    {
        if ($this->hasMultipleDepartments()) {
            return array_map('trim', explode(',', $this->department));
        }

        return [$this->department];
    }

    /**
     * Check if user is in specific department (supports multiple departments).
     */
    public function isInDepartment(string $department): bool
    {
        return in_array($department, $this->getDepartments());
    }

    /**
     * Check if user is in land affairs department (土地総務).
     */
    public function isLandAffairs(): bool
    {
        return $this->isInDepartment('land_affairs') || $this->isAdmin();
    }

    /**
     * Check if user is in accounting department (経理).
     */
    public function isAccounting(): bool
    {
        return $this->isInDepartment('accounting') || $this->isAdmin();
    }

    /**
     * Check if user is in construction planning department (工程表).
     */
    public function isConstructionPlanning(): bool
    {
        return $this->isInDepartment('construction_planning') || $this->isAdmin();
    }

    /**
     * Check if user can edit land information (simplified).
     * Only admins and editors can edit land information.
     */
    public function canEditLandInfo(): bool
    {
        return $this->isAdmin() || $this->isEditor();
    }

    /**
     * Legacy methods for backward compatibility.
     */
    public function canEditLandBasicInfo(): bool
    {
        return $this->canEditLandInfo();
    }

    public function canEditLandFinancialInfo(): bool
    {
        return $this->canEditLandInfo();
    }

    public function canEditLandManagementInfo(): bool
    {
        return $this->canEditLandInfo();
    }

    public function canEditLandDocuments(): bool
    {
        return $this->canEditLandInfo();
    }
}
