<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'others_company_name',
        'others_contract_type',
        'others_contract_content',
        'others_auto_renewal',
        'others_auto_renewal_details',
        'others_contract_start_date',
        'others_cancellation_conditions',
        'others_renewal_notice_period',
        'others_contract_end_date',
        'others_other_matters',
        'others_amount',
        'others_contact_info',
        'meal_service_data',
        'parking_data',
    ];

    protected $casts = [
        'others_contract_start_date' => 'date',
        'others_contract_end_date' => 'date',
        'others_amount' => 'integer',
        'meal_service_data' => 'array',
        'parking_data' => 'array',
    ];

    /**
     * 施設との関係を定義
     * 
     * @return BelongsTo 施設モデルとの関係
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * その他契約書データを配列として取得
     * 
     * @return array その他契約書の全データ
     */
    public function getOthersDataAttribute(): array
    {
        return [
            'company_name' => $this->others_company_name,
            'contract_type' => $this->others_contract_type,
            'contract_content' => $this->others_contract_content,
            'auto_renewal' => $this->others_auto_renewal,
            'auto_renewal_details' => $this->others_auto_renewal_details,
            'contract_start_date' => $this->others_contract_start_date?->format('Y-m-d'),
            'cancellation_conditions' => $this->others_cancellation_conditions,
            'renewal_notice_period' => $this->others_renewal_notice_period,
            'contract_end_date' => $this->others_contract_end_date?->format('Y-m-d'),
            'other_matters' => $this->others_other_matters,
            'amount' => $this->others_amount,
            'contact_info' => $this->others_contact_info,
        ];
    }

    /**
     * その他契約書データを一括更新
     * 
     * @param array $data 更新データ配列
     * @return void
     */
    public function updateOthersData(array $data): void
    {
        $this->update([
            'others_company_name' => $data['company_name'] ?? null,
            'others_contract_type' => $data['contract_type'] ?? null,
            'others_contract_content' => $data['contract_content'] ?? null,
            'others_auto_renewal' => $data['auto_renewal'] ?? null,
            'others_auto_renewal_details' => $data['auto_renewal_details'] ?? null,
            'others_contract_start_date' => $data['contract_start_date'] ?? null,
            'others_cancellation_conditions' => $data['cancellation_conditions'] ?? null,
            'others_renewal_notice_period' => $data['renewal_notice_period'] ?? null,
            'others_contract_end_date' => $data['contract_end_date'] ?? null,
            'others_other_matters' => $data['other_matters'] ?? null,
            'others_amount' => $data['amount'] ?? null,
            'others_contact_info' => $data['contact_info'] ?? null,
        ]);
    }
}
