<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualConfirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'confirmation_year',
        'facility_id',
        'requested_by',
        'facility_manager_id',
        'status',
        'discrepancy_details',
        'requested_at',
        'responded_at',
        'resolved_at',
    ];

    protected $casts = [
        'confirmation_year' => 'integer',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function facilityManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facility_manager_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('confirmation_year', $year);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function hasDiscrepancy(): bool
    {
        return $this->status === 'discrepancy_reported';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }
}
