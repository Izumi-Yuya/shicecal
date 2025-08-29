<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_name',
        'office_code',
        'designation_number',
        'facility_name',
        'postal_code',
        'address',
        'phone_number',
        'fax_number',
        'status',
        'approved_at',
        'approved_by',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Facility statuses
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';

    /**
     * Get the user who created this facility
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this facility
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved this facility
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get files associated with this facility
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }

    /**
     * Get comments for this facility
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get maintenance histories for this facility
     */
    public function maintenanceHistories()
    {
        return $this->hasMany(MaintenanceHistory::class);
    }

    /**
     * Scope for approved facilities
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for pending approval facilities
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    /**
     * Scope for draft facilities
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for search by facility name
     */
    public function scopeSearchByName($query, $name)
    {
        return $query->where('facility_name', 'like', '%' . $name . '%');
    }

    /**
     * Scope for search by office code
     */
    public function scopeSearchByOfficeCode($query, $code)
    {
        return $query->where('office_code', 'like', '%' . $code . '%');
    }

    /**
     * Scope for search by address (prefecture)
     */
    public function scopeSearchByAddress($query, $address)
    {
        return $query->where('address', 'like', '%' . $address . '%');
    }

    /**
     * Check if facility is approved
     */
    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if facility is pending approval
     */
    public function isPendingApproval()
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Get display name for status
     */
    public function getStatusDisplayNameAttribute()
    {
        $statuses = [
            self::STATUS_DRAFT => '下書き',
            self::STATUS_PENDING_APPROVAL => '承認待ち',
            self::STATUS_APPROVED => '承認済み',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get full address with postal code
     */
    public function getFullAddressAttribute()
    {
        return "〒{$this->postal_code} {$this->address}";
    }
}