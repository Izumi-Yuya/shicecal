<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\FacilityContract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractService
{
    /**
     * 指定施設の契約書情報を取得
     * 
     * @param Facility $facility 対象施設
     * @return FacilityContract|null 契約書（存在しない場合はnull）
     */
    public function getContract(Facility $facility): ?FacilityContract
    {
        return $facility->contract;
    }

    /**
     * 契約書データを作成または更新
     * 
     * @param Facility $facility 対象施設
     * @param array $data 更新データ
     * @param User $user 更新実行ユーザー
     * @return FacilityContract 更新後の契約書
     */
    public function createOrUpdateContract(Facility $facility, array $data, User $user): FacilityContract
    {
        return DB::transaction(function () use ($facility, $data, $user) {
            $contract = $facility->contract ?? new FacilityContract(['facility_id' => $facility->id]);

            // その他契約書データを更新
            if (isset($data['others'])) {
                $this->updateOthersData($contract, $data['others']);
            }

            // 給食契約書データを更新
            if (isset($data['meal_service'])) {
                $contract->meal_service_data = $data['meal_service'];
            }

            // 駐車場契約書データを更新
            if (isset($data['parking'])) {
                $contract->parking_data = $data['parking'];
            }

            // 契約書データを保存
            $contract->save();

            Log::info('Contract updated', [
                'facility_id' => $facility->id,
                'user_id' => $user->id,
                'contract_id' => $contract->id,
            ]);

            return $contract;
        });
    }

    /**
     * その他契約書データを更新
     * 
     * @param FacilityContract $contract 契約書モデル
     * @param array $data 更新データ
     * @return void
     */
    private function updateOthersData(FacilityContract $contract, array $data): void
    {
        $contract->update([
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
     * 契約書データを表示用に整形
     * 
     * @param FacilityContract $contract 契約書モデル
     * @return array 表示用データ配列
     */
    public function formatContractDataForDisplay(FacilityContract $contract): array
    {
        return [
            'others' => $contract->others_data,
            'meal_service' => $contract->meal_service_data ?? [],
            'parking' => $contract->parking_data ?? [],
        ];
    }
}