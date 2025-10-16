<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 既存データをバックアップ
        $existingContracts = DB::table('facility_contracts')->get();
        
        Schema::table('facility_contracts', function (Blueprint $table) {
            // 給食契約書の個別カラム追加
            $table->string('meal_service_company_name')->nullable()->after('others_contact_info');
            $table->bigInteger('meal_service_management_fee')->nullable();
            $table->text('meal_service_contract_content')->nullable();
            $table->bigInteger('meal_service_breakfast_price')->nullable();
            $table->date('meal_service_contract_start_date')->nullable();
            $table->bigInteger('meal_service_lunch_price')->nullable();
            $table->string('meal_service_contract_type')->nullable();
            $table->bigInteger('meal_service_dinner_price')->nullable();
            $table->string('meal_service_auto_renewal')->nullable();
            $table->text('meal_service_auto_renewal_details')->nullable();
            $table->bigInteger('meal_service_snack_price')->nullable();
            $table->text('meal_service_cancellation_conditions')->nullable();
            $table->bigInteger('meal_service_event_meal_price')->nullable();
            $table->string('meal_service_renewal_notice_period')->nullable();
            $table->bigInteger('meal_service_staff_meal_price')->nullable();
            $table->text('meal_service_other_matters')->nullable();
            $table->text('meal_service_remarks')->nullable();
            
            // 駐車場契約書の個別カラム追加
            $table->string('parking_name')->nullable();
            $table->date('parking_contract_start_date')->nullable();
            $table->text('parking_location')->nullable();
            $table->date('parking_contract_end_date')->nullable();
            $table->integer('parking_spaces')->nullable();
            $table->string('parking_auto_renewal')->nullable();
            $table->text('parking_position')->nullable();
            $table->text('parking_cancellation_conditions')->nullable();
            $table->string('parking_renewal_notice_period')->nullable();
            $table->bigInteger('parking_price_per_space')->nullable();
            $table->text('parking_usage_purpose')->nullable();
            $table->text('parking_other_matters')->nullable();
            
            // 駐車場管理会社情報
            $table->string('parking_management_company_name')->nullable();
            $table->string('parking_management_postal_code', 10)->nullable();
            $table->text('parking_management_address')->nullable();
            $table->string('parking_management_building_name')->nullable();
            $table->string('parking_management_phone', 20)->nullable();
            $table->string('parking_management_fax', 20)->nullable();
            $table->string('parking_management_email')->nullable();
            $table->string('parking_management_url')->nullable();
            $table->text('parking_management_notes')->nullable();
            
            // 駐車場オーナー情報
            $table->string('parking_owner_name')->nullable();
            $table->string('parking_owner_postal_code', 10)->nullable();
            $table->text('parking_owner_address')->nullable();
            $table->string('parking_owner_building_name')->nullable();
            $table->string('parking_owner_phone', 20)->nullable();
            $table->string('parking_owner_fax', 20)->nullable();
            $table->string('parking_owner_email')->nullable();
            $table->string('parking_owner_url')->nullable();
            $table->text('parking_owner_notes')->nullable();
        });
        
        // 既存のJSONデータを個別カラムに移行
        foreach ($existingContracts as $contract) {
            $updates = [];
            
            // 給食データの移行
            if ($contract->meal_service_data) {
                $mealData = json_decode($contract->meal_service_data, true);
                if (is_array($mealData)) {
                    $updates['meal_service_company_name'] = $mealData['company_name'] ?? null;
                    $updates['meal_service_management_fee'] = $mealData['management_fee'] ?? null;
                    $updates['meal_service_contract_content'] = $mealData['contract_content'] ?? null;
                    $updates['meal_service_breakfast_price'] = $mealData['breakfast_price'] ?? null;
                    $updates['meal_service_contract_start_date'] = $mealData['contract_start_date'] ?? null;
                    $updates['meal_service_lunch_price'] = $mealData['lunch_price'] ?? null;
                    $updates['meal_service_contract_type'] = $mealData['contract_type'] ?? null;
                    $updates['meal_service_dinner_price'] = $mealData['dinner_price'] ?? null;
                    $updates['meal_service_auto_renewal'] = $mealData['auto_renewal'] ?? null;
                    $updates['meal_service_auto_renewal_details'] = $mealData['auto_renewal_details'] ?? null;
                    $updates['meal_service_snack_price'] = $mealData['snack_price'] ?? null;
                    $updates['meal_service_cancellation_conditions'] = $mealData['cancellation_conditions'] ?? null;
                    $updates['meal_service_event_meal_price'] = $mealData['event_meal_price'] ?? null;
                    $updates['meal_service_renewal_notice_period'] = $mealData['renewal_notice_period'] ?? null;
                    $updates['meal_service_staff_meal_price'] = $mealData['staff_meal_price'] ?? null;
                    $updates['meal_service_other_matters'] = $mealData['other_matters'] ?? null;
                    $updates['meal_service_remarks'] = $mealData['remarks'] ?? null;
                }
            }
            
            // 駐車場データの移行
            if ($contract->parking_data) {
                $parkingData = json_decode($contract->parking_data, true);
                if (is_array($parkingData)) {
                    $updates['parking_name'] = $parkingData['parking_name'] ?? null;
                    $updates['parking_contract_start_date'] = $parkingData['contract_start_date'] ?? null;
                    $updates['parking_location'] = $parkingData['parking_location'] ?? null;
                    $updates['parking_contract_end_date'] = $parkingData['contract_end_date'] ?? null;
                    $updates['parking_spaces'] = $parkingData['parking_spaces'] ?? null;
                    $updates['parking_auto_renewal'] = $parkingData['auto_renewal'] ?? null;
                    $updates['parking_position'] = $parkingData['parking_position'] ?? null;
                    $updates['parking_cancellation_conditions'] = $parkingData['cancellation_conditions'] ?? null;
                    $updates['parking_renewal_notice_period'] = $parkingData['renewal_notice_period'] ?? null;
                    $updates['parking_price_per_space'] = $parkingData['price_per_space'] ?? null;
                    $updates['parking_usage_purpose'] = $parkingData['usage_purpose'] ?? null;
                    $updates['parking_other_matters'] = $parkingData['other_matters'] ?? null;
                    
                    // 管理会社情報
                    $updates['parking_management_company_name'] = $parkingData['management_company_name'] ?? null;
                    $updates['parking_management_postal_code'] = $parkingData['management_postal_code'] ?? null;
                    $updates['parking_management_address'] = $parkingData['management_address'] ?? null;
                    $updates['parking_management_building_name'] = $parkingData['management_building_name'] ?? null;
                    $updates['parking_management_phone'] = $parkingData['management_phone'] ?? null;
                    $updates['parking_management_fax'] = $parkingData['management_fax'] ?? null;
                    $updates['parking_management_email'] = $parkingData['management_email'] ?? null;
                    $updates['parking_management_url'] = $parkingData['management_url'] ?? null;
                    $updates['parking_management_notes'] = $parkingData['management_notes'] ?? null;
                    
                    // オーナー情報
                    $updates['parking_owner_name'] = $parkingData['owner_name'] ?? null;
                    $updates['parking_owner_postal_code'] = $parkingData['owner_postal_code'] ?? null;
                    $updates['parking_owner_address'] = $parkingData['owner_address'] ?? null;
                    $updates['parking_owner_building_name'] = $parkingData['owner_building_name'] ?? null;
                    $updates['parking_owner_phone'] = $parkingData['owner_phone'] ?? null;
                    $updates['parking_owner_fax'] = $parkingData['owner_fax'] ?? null;
                    $updates['parking_owner_email'] = $parkingData['owner_email'] ?? null;
                    $updates['parking_owner_url'] = $parkingData['owner_url'] ?? null;
                    $updates['parking_owner_notes'] = $parkingData['owner_notes'] ?? null;
                }
            }
            
            if (!empty($updates)) {
                DB::table('facility_contracts')
                    ->where('id', $contract->id)
                    ->update($updates);
            }
        }
        
        // JSONカラムを削除
        Schema::table('facility_contracts', function (Blueprint $table) {
            $table->dropColumn(['meal_service_data', 'parking_data']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 個別カラムのデータをJSONに戻す
        $contracts = DB::table('facility_contracts')->get();
        
        Schema::table('facility_contracts', function (Blueprint $table) {
            $table->json('meal_service_data')->nullable();
            $table->json('parking_data')->nullable();
        });
        
        foreach ($contracts as $contract) {
            $mealData = [
                'company_name' => $contract->meal_service_company_name,
                'management_fee' => $contract->meal_service_management_fee,
                'contract_content' => $contract->meal_service_contract_content,
                'breakfast_price' => $contract->meal_service_breakfast_price,
                'contract_start_date' => $contract->meal_service_contract_start_date,
                'lunch_price' => $contract->meal_service_lunch_price,
                'contract_type' => $contract->meal_service_contract_type,
                'dinner_price' => $contract->meal_service_dinner_price,
                'auto_renewal' => $contract->meal_service_auto_renewal,
                'auto_renewal_details' => $contract->meal_service_auto_renewal_details,
                'snack_price' => $contract->meal_service_snack_price,
                'cancellation_conditions' => $contract->meal_service_cancellation_conditions,
                'event_meal_price' => $contract->meal_service_event_meal_price,
                'renewal_notice_period' => $contract->meal_service_renewal_notice_period,
                'staff_meal_price' => $contract->meal_service_staff_meal_price,
                'other_matters' => $contract->meal_service_other_matters,
                'remarks' => $contract->meal_service_remarks,
            ];
            
            $parkingData = [
                'parking_name' => $contract->parking_name,
                'contract_start_date' => $contract->parking_contract_start_date,
                'parking_location' => $contract->parking_location,
                'contract_end_date' => $contract->parking_contract_end_date,
                'parking_spaces' => $contract->parking_spaces,
                'auto_renewal' => $contract->parking_auto_renewal,
                'parking_position' => $contract->parking_position,
                'cancellation_conditions' => $contract->parking_cancellation_conditions,
                'renewal_notice_period' => $contract->parking_renewal_notice_period,
                'price_per_space' => $contract->parking_price_per_space,
                'usage_purpose' => $contract->parking_usage_purpose,
                'other_matters' => $contract->parking_other_matters,
                'management_company_name' => $contract->parking_management_company_name,
                'management_postal_code' => $contract->parking_management_postal_code,
                'management_address' => $contract->parking_management_address,
                'management_building_name' => $contract->parking_management_building_name,
                'management_phone' => $contract->parking_management_phone,
                'management_fax' => $contract->parking_management_fax,
                'management_email' => $contract->parking_management_email,
                'management_url' => $contract->parking_management_url,
                'management_notes' => $contract->parking_management_notes,
                'owner_name' => $contract->parking_owner_name,
                'owner_postal_code' => $contract->parking_owner_postal_code,
                'owner_address' => $contract->parking_owner_address,
                'owner_building_name' => $contract->parking_owner_building_name,
                'owner_phone' => $contract->parking_owner_phone,
                'owner_fax' => $contract->parking_owner_fax,
                'owner_email' => $contract->parking_owner_email,
                'owner_url' => $contract->parking_owner_url,
                'owner_notes' => $contract->parking_owner_notes,
            ];
            
            DB::table('facility_contracts')
                ->where('id', $contract->id)
                ->update([
                    'meal_service_data' => json_encode($mealData),
                    'parking_data' => json_encode($parkingData),
                ]);
        }
        
        Schema::table('facility_contracts', function (Blueprint $table) {
            // 給食契約書カラムを削除
            $table->dropColumn([
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
            ]);
            
            // 駐車場契約書カラムを削除
            $table->dropColumn([
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
                'parking_management_company_name',
                'parking_management_postal_code',
                'parking_management_address',
                'parking_management_building_name',
                'parking_management_phone',
                'parking_management_fax',
                'parking_management_email',
                'parking_management_url',
                'parking_management_notes',
                'parking_owner_name',
                'parking_owner_postal_code',
                'parking_owner_address',
                'parking_owner_building_name',
                'parking_owner_phone',
                'parking_owner_fax',
                'parking_owner_email',
                'parking_owner_url',
                'parking_owner_notes',
            ]);
        });
    }
};
