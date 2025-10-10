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
        'care_insurance_business_number',
        'insurer',
        'designation_date',
        'renewal_start_date',
        'renewal_end_date',
        'remaining_months',
    ];

    protected $casts = [
        'designation_date' => 'date',
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

    /**
     * Calculate remaining months until renewal end date
     */
    public function calculateRemainingMonths(): ?int
    {
        if (! $this->renewal_end_date) {
            return null;
        }

        $today = now();
        $endDate = $this->renewal_end_date;

        if ($endDate->isPast()) {
            return 0;
        }

        // 年と月の差を計算
        $years = $endDate->year - $today->year;
        $months = $endDate->month - $today->month;

        if ($months < 0) {
            $years--;
            $months += 12;
        }

        // 日付も考慮
        if ($endDate->day < $today->day) {
            $months--;
            if ($months < 0) {
                $years--;
                $months += 12;
            }
        }

        return max(0, $years * 12 + $months);
    }

    /**
     * Update remaining months based on renewal end date
     */
    public function updateRemainingMonths(): void
    {
        $this->remaining_months = $this->calculateRemainingMonths();
        $this->save();
    }
}
