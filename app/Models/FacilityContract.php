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
        // その他契約書
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
        // 給食契約書
        'meal_service_company_name',
        'meal_service_management_fee',
        'meal_service_contract_content',
        'meal_service_breakfast_price',
        'meal_service_contract_start_date',
        'meal_service_lunch_price',
        'meal_service_contract_type',
        'meal_service_dinner_price',
        'meal_service_auto_renewal',
        'meal_service_auto_renewal_details',
        'meal_service_snack_price',
        'meal_service_cancellation_conditions',
        'meal_service_event_meal_price',
        'meal_service_renewal_notice_period',
        'meal_service_staff_meal_price',
        'meal_service_other_matters',
        'meal_service_remarks',
        // 駐車場契約書
        'parking_name',
        'parking_contract_start_date',
        'parking_location',
        'parking_contract_end_date',
        'parking_spaces',
        'parking_auto_renewal',
        'parking_position',
        'parking_cancellation_conditions',
        'parking_renewal_notice_period',
        'parking_price_per_space',
        'parking_usage_purpose',
        'parking_other_matters',
        // 駐車場管理会社
        'parking_management_company_name',
        'parking_management_postal_code',
        'parking_management_address',
        'parking_management_building_name',
        'parking_management_phone',
        'parking_management_fax',
        'parking_management_email',
        'parking_management_url',
        'parking_management_notes',
        // 駐車場オーナー
        'parking_owner_name',
        'parking_owner_postal_code',
        'parking_owner_address',
        'parking_owner_building_name',
        'parking_owner_phone',
        'parking_owner_fax',
        'parking_owner_email',
        'parking_owner_url',
        'parking_owner_notes',
    ];

    protected $casts = [
        'others_contract_start_date' => 'date',
        'others_contract_end_date' => 'date',
        'others_amount' => 'integer',
        'meal_service_contract_start_date' => 'date',
        'meal_service_management_fee' => 'integer',
        'meal_service_breakfast_price' => 'integer',
        'meal_service_lunch_price' => 'integer',
        'meal_service_dinner_price' => 'integer',
        'meal_service_snack_price' => 'integer',
        'meal_service_event_meal_price' => 'integer',
        'meal_service_staff_meal_price' => 'integer',
        'parking_contract_start_date' => 'date',
        'parking_contract_end_date' => 'date',
        'parking_spaces' => 'integer',
        'parking_price_per_space' => 'integer',
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

    /**
     * 給食契約書データを配列として取得
     * 
     * @return array 給食契約書の全データ
     */
    public function getMealServiceDataAttribute(): array
    {
        return [
            'company_name' => $this->meal_service_company_name,
            'management_fee' => $this->meal_service_management_fee,
            'contract_content' => $this->meal_service_contract_content,
            'breakfast_price' => $this->meal_service_breakfast_price,
            'contract_start_date' => $this->meal_service_contract_start_date?->format('Y-m-d'),
            'lunch_price' => $this->meal_service_lunch_price,
            'contract_type' => $this->meal_service_contract_type,
            'dinner_price' => $this->meal_service_dinner_price,
            'auto_renewal' => $this->meal_service_auto_renewal,
            'auto_renewal_details' => $this->meal_service_auto_renewal_details,
            'snack_price' => $this->meal_service_snack_price,
            'cancellation_conditions' => $this->meal_service_cancellation_conditions,
            'event_meal_price' => $this->meal_service_event_meal_price,
            'renewal_notice_period' => $this->meal_service_renewal_notice_period,
            'staff_meal_price' => $this->meal_service_staff_meal_price,
            'other_matters' => $this->meal_service_other_matters,
            'remarks' => $this->meal_service_remarks,
        ];
    }

    /**
     * 給食契約書データを一括更新
     * 
     * @param array $data 更新データ配列
     * @return void
     */
    public function updateMealServiceData(array $data): void
    {
        $this->update([
            'meal_service_company_name' => $data['company_name'] ?? null,
            'meal_service_management_fee' => $data['management_fee'] ?? null,
            'meal_service_contract_content' => $data['contract_content'] ?? null,
            'meal_service_breakfast_price' => $data['breakfast_price'] ?? null,
            'meal_service_contract_start_date' => $data['contract_start_date'] ?? null,
            'meal_service_lunch_price' => $data['lunch_price'] ?? null,
            'meal_service_contract_type' => $data['contract_type'] ?? null,
            'meal_service_dinner_price' => $data['dinner_price'] ?? null,
            'meal_service_auto_renewal' => $data['auto_renewal'] ?? null,
            'meal_service_auto_renewal_details' => $data['auto_renewal_details'] ?? null,
            'meal_service_snack_price' => $data['snack_price'] ?? null,
            'meal_service_cancellation_conditions' => $data['cancellation_conditions'] ?? null,
            'meal_service_event_meal_price' => $data['event_meal_price'] ?? null,
            'meal_service_renewal_notice_period' => $data['renewal_notice_period'] ?? null,
            'meal_service_staff_meal_price' => $data['staff_meal_price'] ?? null,
            'meal_service_other_matters' => $data['other_matters'] ?? null,
            'meal_service_remarks' => $data['remarks'] ?? null,
        ]);
    }

    /**
     * 駐車場契約書データを配列として取得
     * 
     * @return array 駐車場契約書の全データ
     */
    public function getParkingDataAttribute(): array
    {
        return [
            'parking_name' => $this->parking_name,
            'contract_start_date' => $this->parking_contract_start_date?->format('Y-m-d'),
            'parking_location' => $this->parking_location,
            'contract_end_date' => $this->parking_contract_end_date?->format('Y-m-d'),
            'parking_spaces' => $this->parking_spaces,
            'auto_renewal' => $this->parking_auto_renewal,
            'parking_position' => $this->parking_position,
            'cancellation_conditions' => $this->parking_cancellation_conditions,
            'renewal_notice_period' => $this->parking_renewal_notice_period,
            'price_per_space' => $this->parking_price_per_space,
            'usage_purpose' => $this->parking_usage_purpose,
            'other_matters' => $this->parking_other_matters,
            'management_company_name' => $this->parking_management_company_name,
            'management_postal_code' => $this->parking_management_postal_code,
            'management_address' => $this->parking_management_address,
            'management_building_name' => $this->parking_management_building_name,
            'management_phone' => $this->parking_management_phone,
            'management_fax' => $this->parking_management_fax,
            'management_email' => $this->parking_management_email,
            'management_url' => $this->parking_management_url,
            'management_notes' => $this->parking_management_notes,
            'owner_name' => $this->parking_owner_name,
            'owner_postal_code' => $this->parking_owner_postal_code,
            'owner_address' => $this->parking_owner_address,
            'owner_building_name' => $this->parking_owner_building_name,
            'owner_phone' => $this->parking_owner_phone,
            'owner_fax' => $this->parking_owner_fax,
            'owner_email' => $this->parking_owner_email,
            'owner_url' => $this->parking_owner_url,
            'owner_notes' => $this->parking_owner_notes,
        ];
    }

    /**
     * 駐車場契約書データを一括更新
     * 
     * @param array $data 更新データ配列
     * @return void
     */
    public function updateParkingData(array $data): void
    {
        $this->update([
            'parking_name' => $data['parking_name'] ?? null,
            'parking_contract_start_date' => $data['contract_start_date'] ?? null,
            'parking_location' => $data['parking_location'] ?? null,
            'parking_contract_end_date' => $data['contract_end_date'] ?? null,
            'parking_spaces' => $data['parking_spaces'] ?? null,
            'parking_auto_renewal' => $data['auto_renewal'] ?? null,
            'parking_position' => $data['parking_position'] ?? null,
            'parking_cancellation_conditions' => $data['cancellation_conditions'] ?? null,
            'parking_renewal_notice_period' => $data['renewal_notice_period'] ?? null,
            'parking_price_per_space' => $data['price_per_space'] ?? null,
            'parking_usage_purpose' => $data['usage_purpose'] ?? null,
            'parking_other_matters' => $data['other_matters'] ?? null,
            'parking_management_company_name' => $data['management_company_name'] ?? null,
            'parking_management_postal_code' => $data['management_postal_code'] ?? null,
            'parking_management_address' => $data['management_address'] ?? null,
            'parking_management_building_name' => $data['management_building_name'] ?? null,
            'parking_management_phone' => $data['management_phone'] ?? null,
            'parking_management_fax' => $data['management_fax'] ?? null,
            'parking_management_email' => $data['management_email'] ?? null,
            'parking_management_url' => $data['management_url'] ?? null,
            'parking_management_notes' => $data['management_notes'] ?? null,
            'parking_owner_name' => $data['owner_name'] ?? null,
            'parking_owner_postal_code' => $data['owner_postal_code'] ?? null,
            'parking_owner_address' => $data['owner_address'] ?? null,
            'parking_owner_building_name' => $data['owner_building_name'] ?? null,
            'parking_owner_phone' => $data['owner_phone'] ?? null,
            'parking_owner_fax' => $data['owner_fax'] ?? null,
            'parking_owner_email' => $data['owner_email'] ?? null,
            'parking_owner_url' => $data['owner_url'] ?? null,
            'parking_owner_notes' => $data['owner_notes'] ?? null,
        ]);
    }
}
