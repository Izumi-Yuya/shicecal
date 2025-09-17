<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityService extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'service_type',
        'section',
        'renewal_start_date',
        'renewal_end_date',
    ];

    protected $casts = [
        'renewal_start_date' => 'date',
        'renewal_end_date' => 'date',
    ];

    /**
     * Get the facility that owns this service
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get formatted renewal period
     */
    public function getRenewalPeriodAttribute(): string
    {
        if (! $this->renewal_start_date || ! $this->renewal_end_date) {
            return '未設定';
        }

        return $this->renewal_start_date->format('Y年m月d日').' ～ '.$this->renewal_end_date->format('Y年m月d日');
    }

    /**
     * Check if the service designation is expiring soon (within 6 months)
     */
    public function isExpiringSoon(): bool
    {
        if (! $this->renewal_end_date) {
            return false;
        }

        return $this->renewal_end_date->diffInMonths(now()) <= 6;
    }

    /**
     * Check if the service designation has expired
     */
    public function isExpired(): bool
    {
        if (! $this->renewal_end_date) {
            return false;
        }

        return $this->renewal_end_date->isPast();
    }
}
