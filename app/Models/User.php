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
}
