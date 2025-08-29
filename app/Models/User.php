<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
     * User roles
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    const ROLE_PRIMARY_RESPONDER = 'primary_responder';
    const ROLE_APPROVER = 'approver';
    const ROLE_VIEWER = 'viewer';

    /**
     * Get facilities created by this user
     */
    public function createdFacilities()
    {
        return $this->hasMany(Facility::class, 'created_by');
    }

    /**
     * Get facilities updated by this user
     */
    public function updatedFacilities()
    {
        return $this->hasMany(Facility::class, 'updated_by');
    }

    /**
     * Get facilities approved by this user
     */
    public function approvedFacilities()
    {
        return $this->hasMany(Facility::class, 'approved_by');
    }

    /**
     * Get files uploaded by this user
     */
    public function uploadedFiles()
    {
        return $this->hasMany(File::class, 'uploaded_by');
    }

    /**
     * Get comments posted by this user
     */
    public function postedComments()
    {
        return $this->hasMany(Comment::class, 'posted_by');
    }

    /**
     * Get comments assigned to this user
     */
    public function assignedComments()
    {
        return $this->hasMany(Comment::class, 'assigned_to');
    }

    /**
     * Get maintenance histories created by this user
     */
    public function maintenanceHistories()
    {
        return $this->hasMany(MaintenanceHistory::class, 'created_by');
    }

    /**
     * Get export favorites for this user
     */
    public function exportFavorites()
    {
        return $this->hasMany(ExportFavorite::class);
    }

    /**
     * Get system settings updated by this user
     */
    public function systemSettings()
    {
        return $this->hasMany(SystemSetting::class, 'updated_by');
    }

    /**
     * Get activity logs for this user
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for users by role
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Check if user has specific role(s)
     */
    public function hasRole($roles)
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        
        return $this->role === $roles;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Check if user is editor
     */
    public function isEditor()
    {
        return $this->hasRole(self::ROLE_EDITOR);
    }

    /**
     * Check if user is approver
     */
    public function isApprover()
    {
        return $this->hasRole(self::ROLE_APPROVER);
    }

    /**
     * Get display name for role
     */
    public function getRoleDisplayNameAttribute()
    {
        $roles = [
            self::ROLE_ADMIN => '管理者',
            self::ROLE_EDITOR => '編集者',
            self::ROLE_PRIMARY_RESPONDER => '一次対応者',
            self::ROLE_APPROVER => '承認者',
            self::ROLE_VIEWER => '閲覧者',
        ];

        return $roles[$this->role] ?? $this->role;
    }
}
