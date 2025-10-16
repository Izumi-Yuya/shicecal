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
                $this->updateMealServiceData($contract, $data['meal_service']);
            }

            // 駐車場契約書データを更新
            if (isset($data['parking'])) {
                $this->updateParkingData($contract, $data['parking']);
            }

            Log::info('Contract updated successfully', [
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
        $contract->updateOthersData($data);
    }

    /**
     * 給食契約書データを更新
     * 
     * @param FacilityContract $contract 契約書モデル
     * @param array $data 更新データ
     * @return void
     */
    private function updateMealServiceData(FacilityContract $contract, array $data): void
    {
        $contract->updateMealServiceData($data);
    }

    /**
     * 駐車場契約書データを更新
     * 
     * @param FacilityContract $contract 契約書モデル
     * @param array $data 更新データ
     * @return void
     */
    private function updateParkingData(FacilityContract $contract, array $data): void
    {
        $contract->updateParkingData($data);
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
            'meal_service' => $contract->meal_service_data,
            'parking' => $contract->parking_data,
        ];
    }
}