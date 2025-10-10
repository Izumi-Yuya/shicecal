<?php

namespace App\Services;

use App\Models\Facility;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ExportService
{
    /**
     * Get total field count for CSV export
     */
    public function getTotalFieldCount(): int
    {
        return count($this->getAvailableFields());
    }

    /**
     * Get available fields for CSV export
     * 
     * Note: This method returns only text-based fields for CSV export.
     * File paths, binary data, and file references are excluded.
     * Only human-readable text information is included.
     */
    public function getAvailableFields(): array
    {
        return [
            // 基本情報
            'company_name' => '会社名',
            'office_code' => '事業所コード',
            'facility_name' => '施設名',
            'designation_number' => '指定番号1',
            'designation_number_2' => '指定番号2',
            'postal_code' => '郵便番号',
            'opening_date' => '開設日',
            'address' => '住所',
            'opening_years' => '開設年数',
            'months_in_operation' => '運営月数',
            'building_name' => '住所（建物名）',
            'building_structure' => '建物構造',
            'phone_number' => '電話番号',
            'building_floors' => '建物階数',
            'fax_number' => 'FAX番号',
            'paid_rooms_count' => '居室数',
            'toll_free_number' => 'フリーダイヤル',
            'ss_rooms_count' => 'ショートステイ居室数',
            'email' => 'メールアドレス',
            'capacity' => '定員数',
            'website_url' => 'URL',
            'service_types' => 'サービス種類',
            'service_validity_periods' => 'サービス有効期限',

            // 土地情報
            'land_ownership_type' => '土地所有区分',
            'land_site_area_sqm' => '敷地面積（㎡数）',
            'land_site_area_tsubo' => '敷地面積（坪数）',
            'land_parking_spaces' => '敷地内駐車場台数',
            'land_purchase_price' => '購入金額',
            'land_unit_price_per_tsubo' => '坪単価',
            'land_monthly_rent' => '家賃',
            'land_contract_period' => '契約期間',
            'land_auto_renewal' => '自動更新の有無',
            'land_contract_period_text' => '契約年数',
            'land_notes' => '土地備考',
            'land_management_company_name' => '土地管理会社名',
            'land_management_company_postal_code' => '土地管理会社郵便番号',
            'land_management_company_address' => '土地管理会社住所',
            'land_management_company_building' => '土地管理会社住所（建物名）',
            'land_management_company_phone' => '土地管理会社電話番号',
            'land_management_company_fax' => '土地管理会社FAX番号',
            'land_management_company_email' => '土地管理会社メールアドレス',
            'land_management_company_url' => '土地管理会社URL',
            'land_management_company_notes' => '土地管理会社備考',
            'land_owner_name' => '土地オーナー氏名',
            'land_owner_postal_code' => '土地オーナー郵便番号',
            'land_owner_address' => '土地オーナー住所',
            'land_owner_building' => '土地オーナー住所（建物名）',
            'land_owner_phone' => '土地オーナー電話番号',
            'land_owner_fax' => '土地オーナーFAX番号',
            'land_owner_email' => '土地オーナーメールアドレス',
            'land_owner_url' => '土地オーナーURL',
            'land_owner_notes' => '土地オーナー備考',

            // ライフライン設備 - 電気
            'electrical_contractor' => '電力会社',
            'electrical_safety_management_company' => '電気保安管理業者',
            'electrical_maintenance_inspection_date' => '電気保守点検実施日',
            'electrical_pas_availability' => 'PAS有無',
            'electrical_pas_update_date' => 'PAS更新年月日',
            'electrical_cubicle_availability' => 'キュービクル有無',
            'electrical_cubicle_manufacturers' => 'キュービクルメーカー',
            'electrical_cubicle_model_years' => 'キュービクル年式',
            'electrical_generator_availability' => '非常用発電機有無',
            'electrical_generator_manufacturers' => '非常用発電機メーカー',
            'electrical_generator_model_years' => '非常用発電機年式',
            'electrical_notes' => '電気設備備考',

            // ライフライン設備 - 水道
            'water_contractor' => '水道契約会社',
            'water_tank_cleaning_company' => '受水槽清掃業者',
            'water_tank_cleaning_date' => '受水槽清掃実施日',
            'water_filter_bath_system' => '浴槽循環方式',
            'water_filter_availability' => 'ろ過器設置の有無',
            'water_filter_manufacturer' => 'ろ過器メーカー',
            'water_filter_model_year' => 'ろ過器年式',
            'water_tank_availability' => '受水槽設置の有無',
            'water_tank_manufacturer' => '受水槽メーカー',
            'water_tank_model_year' => '受水槽年式',
            'water_pump_manufacturers' => '加圧ポンプメーカー',
            'water_pump_model_years' => '加圧ポンプ年式',
            'water_septic_tank_availability' => '浄化槽設置の有無',
            'water_septic_tank_manufacturer' => '浄化槽メーカー',
            'water_septic_tank_model_year' => '浄化槽年式',
            'water_septic_tank_inspection_company' => '浄化槽点検・清掃業者',
            'water_septic_tank_inspection_date' => '浄化槽点検・清掃実施日',
            'water_legionella_inspection_dates' => 'レジオネラ検査実施日',
            'water_legionella_first_results' => 'レジオネラ検査結果（初回）',
            'water_legionella_second_results' => 'レジオネラ検査結果（2回目）',
            'water_notes' => '水道設備備考',

            // ライフライン設備 - ガス
            'gas_contractor' => 'ガス会社',
            'gas_safety_management_company' => 'ガス保安管理業者',
            'gas_maintenance_inspection_date' => 'ガス保守点検実施日',
            'gas_notes' => 'ガス設備備考',

            // ライフライン設備 - エレベーター
            'elevator_availability' => 'エレベーター有無',
            'elevator_manufacturer' => 'エレベーターメーカー',
            'elevator_model_year' => 'エレベーター年式',
            'elevator_maintenance_company' => 'エレベーター保守会社',
            'elevator_maintenance_date' => 'エレベーター保守実施日',
            'elevator_notes' => 'エレベーター設備備考',

            // ライフライン設備 - 空調・照明
            'hvac_lighting_availability' => '空調・照明設備有無',
            'hvac_lighting_manufacturer' => '空調・照明設備メーカー',
            'hvac_lighting_model_year' => '空調・照明設備年式',
            'hvac_lighting_maintenance_company' => '空調・照明保守会社',
            'hvac_lighting_maintenance_date' => '空調・照明保守実施日',
            'hvac_lighting_notes' => '空調・照明設備備考',

            // 防犯・防災設備 - 防犯カメラ・電子錠
            'security_camera_management_company' => '防犯カメラ管理業者',
            'security_camera_model_year' => '防犯カメラ年式',
            'security_camera_notes' => '防犯カメラ備考',
            'security_lock_management_company' => '電子錠管理業者',
            'security_lock_model_year' => '電子錠年式',
            'security_lock_notes' => '電子錠備考',

            // 防犯・防災設備 - 消防・防災
            'fire_manager' => '防火管理者',
            'fire_training_date' => '消防訓練実施日',
            'fire_inspection_company' => '消防設備点検業者',
            'fire_inspection_date' => '消防設備点検実施日',
            'disaster_practical_training_date' => '防災実地訓練実施日',
            'disaster_riding_training_date' => '防災起動訓練実施日',

            // 建物情報
            'building_ownership_type' => '建物所有区分',
            'building_area_sqm' => '建築面積（㎡数）',
            'building_area_tsubo' => '建築面積（坪数）',
            'building_completion_date' => '竣工日',
            'building_total_floor_area_sqm' => '延床面積（㎡数）',
            'building_total_floor_area_tsubo' => '延べ床面積（坪数）',
            'building_age' => '築年数',
            'building_construction_cost' => '建築費用',
            'building_cost_per_tsubo' => '坪単価',
            'building_useful_life' => '耐用年数',
            'building_construction_cooperation_fee' => '建設協力金',
            'building_monthly_rent' => '建物家賃',
            'building_contract_years' => '建物契約年数',
            'building_contract_start_date' => '建物契約開始日',
            'building_contract_end_date' => '建物契約終了日',
            'building_auto_renewal' => '建物自動更新の有無',
            'building_construction_company_name' => '施工会社',
            'building_construction_company_phone' => '施工会社連絡先',
            'building_periodic_inspection_type' => '定期調査会社',
            'building_periodic_inspection_date' => '調査日',
            'building_notes' => '建物備考',
            'building_management_company_name' => '建物管理会社名',
            'building_management_company_phone' => '建物管理会社電話番号',
            'building_management_company_email' => '建物管理会社メールアドレス',
            'building_owner_name' => '建物オーナー氏名',
            'building_owner_phone' => '建物オーナー電話番号',
            'building_owner_email' => '建物オーナーメールアドレス',

            // 図面 - 引き渡し図面 (ファイル名のみ、ファイルパスは含まない)
            'drawing_handover_startup_drawing' => '引き渡し図面_就航図面',
            'drawing_handover_row_2' => '引き渡し図面_2行目',
            'drawing_handover_row_3' => '引き渡し図面_3行目',
            'drawing_handover_row_4' => '引き渡し図面_4行目',
            'drawing_handover_row_5' => '引き渡し図面_5行目',

            // 図面 - 完成図面 (ファイル名のみ、ファイルパスは含まない)
            'drawing_completion_row_1' => '完成図面_1行目',
            'drawing_completion_row_2' => '完成図面_2行目',
            'drawing_completion_row_3' => '完成図面_3行目',
            'drawing_completion_row_4' => '完成図面_4行目',
            'drawing_completion_row_5' => '完成図面_5行目',

            // 図面 - その他図面 (ファイル名のみ、ファイルパスは含まない)
            'drawing_others_row_1' => 'その他図面_1行目',
            'drawing_others_row_2' => 'その他図面_2行目',
            'drawing_others_row_3' => 'その他図面_3行目',
            'drawing_others_row_4' => 'その他図面_4行目',
            'drawing_others_row_5' => 'その他図面_5行目',
            'drawing_notes' => '図面備考',

            // 修繕履歴
            'maintenance_latest_date' => '修繕履歴_最新修繕日',
            'maintenance_latest_content' => '修繕履歴_最新修繕内容',
            'maintenance_latest_cost' => '修繕履歴_最新修繕費用',
            'maintenance_latest_contractor' => '修繕履歴_最新施工業者',
            'maintenance_latest_category' => '修繕履歴_最新カテゴリ',
            'maintenance_latest_subcategory' => '修繕履歴_最新サブカテゴリ',
            'maintenance_latest_contact_person' => '修繕履歴_最新担当者',
            'maintenance_latest_phone_number' => '修繕履歴_最新電話番号',
            'maintenance_latest_notes' => '修繕履歴_最新備考',
            'maintenance_latest_warranty_period' => '修繕履歴_最新保証期間',
            'maintenance_total_count' => '修繕履歴_総件数',
            'maintenance_total_cost' => '修繕履歴_総費用',

            // 契約書 - その他契約書
            'contract_others_company_name' => 'その他契約書_会社名',
            'contract_others_contract_type' => 'その他契約書_契約書の種類',
            'contract_others_contract_content' => 'その他契約書_契約内容',
            'contract_others_auto_renewal' => 'その他契約書_自動更新の有無',
            'contract_others_auto_renewal_details' => 'その他契約書_自動更新詳細',
            'contract_others_contract_start_date' => 'その他契約書_契約開始日',
            'contract_others_contract_end_date' => 'その他契約書_契約終了日',
            'contract_others_amount' => 'その他契約書_金額',
            'contract_others_notes' => 'その他契約書_備考',

            // 契約書 - 給食契約書
            'contract_meal_service_company_name' => '給食契約書_会社名',
            'contract_meal_service_contract_type' => '給食契約書_契約書の種類',
            'contract_meal_service_contract_content' => '給食契約書_契約内容',
            'contract_meal_service_auto_renewal' => '給食契約書_自動更新の有無',
            'contract_meal_service_contract_start_date' => '給食契約書_契約開始日',
            'contract_meal_service_contract_end_date' => '給食契約書_契約終了日',
            'contract_meal_service_amount' => '給食契約書_金額',
            'contract_meal_service_notes' => '給食契約書_備考',

            // 契約書 - 駐車場契約書
            'contract_parking_company_name' => '駐車場契約書_会社名',
            'contract_parking_contract_type' => '駐車場契約書_契約書の種類',
            'contract_parking_contract_content' => '駐車場契約書_契約内容',
            'contract_parking_auto_renewal' => '駐車場契約書_自動更新の有無',
            'contract_parking_contract_start_date' => '駐車場契約書_契約開始日',
            'contract_parking_contract_end_date' => '駐車場契約書_契約終了日',
            'contract_parking_amount' => '駐車場契約書_金額',
            'contract_parking_spaces' => '駐車場契約書_駐車場台数',
            'contract_parking_notes' => '駐車場契約書_備考',
            'contract_meal_contract_type' => '給食契約書_契約書の種類',
            'contract_meal_dinner_price' => '給食契約書_夕食単価',
            'contract_meal_auto_renewal' => '給食契約書_自動更新の有無',
            'contract_meal_auto_renewal_details' => '給食契約書_自動更新詳細',
            'contract_meal_snack_price' => '給食契約書_おやつ単価',
            'contract_meal_cancellation_conditions' => '給食契約書_解約条件',
            'contract_meal_event_meal_price' => '給食契約書_行事食単価',
            'contract_meal_renewal_notice_period' => '給食契約書_更新通知期限',
            'contract_meal_staff_meal_price' => '給食契約書_職員食単価',
            'contract_meal_other_matters' => '給食契約書_その他事項',

            // 契約書 - 駐車場契約書
            'contract_parking_name' => '駐車場契約書_駐車場名',
            'contract_parking_location' => '駐車場契約書_駐車場所在地',
            'contract_parking_spaces' => '駐車場契約書_台数',
            'contract_parking_position' => '駐車場契約書_停車位置',
            'contract_parking_price_per_space' => '駐車場契約書_1台あたりの金額',
            'contract_parking_usage_purpose' => '駐車場契約書_使用用途',
            'contract_parking_contract_start_date' => '駐車場契約書_契約開始日',
            'contract_parking_contract_end_date' => '駐車場契約書_契約終了日',
            'contract_parking_auto_renewal' => '駐車場契約書_自動更新の有無',
            'contract_parking_cancellation_conditions' => '駐車場契約書_解約条件',
            'contract_parking_renewal_notice_period' => '駐車場契約書_更新通知期限',
            'contract_parking_other_matters' => '駐車場契約書_その他事項',
            'contract_parking_management_company_name' => '駐車場契約書_管理会社名',
            'contract_parking_management_postal_code' => '駐車場契約書_管理会社郵便番号',
            'contract_parking_management_address' => '駐車場契約書_管理会社住所',
            'contract_parking_management_building_name' => '駐車場契約書_管理会社住所（建物名）',
            'contract_parking_management_phone' => '駐車場契約書_管理会社電話番号',
            'contract_parking_management_fax' => '駐車場契約書_管理会社FAX番号',
            'contract_parking_management_email' => '駐車場契約書_管理会社メールアドレス',
            'contract_parking_management_url' => '駐車場契約書_管理会社URL',
            'contract_parking_management_notes' => '駐車場契約書_管理会社備考',
            'contract_parking_owner_name' => '駐車場契約書_オーナー氏名',
            'contract_parking_owner_postal_code' => '駐車場契約書_オーナー郵便番号',
            'contract_parking_owner_address' => '駐車場契約書_オーナー住所',
            'contract_parking_owner_building_name' => '駐車場契約書_オーナー住所（建物名）',
            'contract_parking_owner_phone' => '駐車場契約書_オーナー電話番号',
            'contract_parking_owner_fax' => '駐車場契約書_オーナーFAX番号',
            'contract_parking_owner_email' => '駐車場契約書_オーナーメールアドレス',
            'contract_parking_owner_url' => '駐車場契約書_オーナーURL',
            'contract_parking_owner_notes' => '駐車場契約書_オーナー備考',
        ];
    }

    /**
     * Generate CSV content
     */
    public function generateCsv(array $facilityIds, array $exportFields): string
    {
        // Determine which relationships to load based on selected fields
        $relationships = $this->determineRequiredRelationships($exportFields);

        $facilities = Facility::whereIn('id', $facilityIds)
            ->with($relationships)
            ->select($this->determineRequiredColumns($exportFields))
            ->get();

        return $this->buildCsvContent($facilities, $exportFields);
    }

    /**
     * Build CSV content from facilities data
     */
    private function buildCsvContent($facilities, array $exportFields): string
    {
        $availableFields = $this->getAvailableFields();
        $selectedFields = array_intersect_key($availableFields, array_flip($exportFields));

        // Use memory-efficient streaming for large datasets
        $output = fopen('php://temp', 'r+');

        // Add BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Write header
        fputcsv($output, array_values($selectedFields));

        // Process facilities in chunks to manage memory usage
        $facilities->chunk(100)->each(function ($chunk) use ($output, $exportFields) {
            foreach ($chunk as $facility) {
                $row = [];
                foreach ($exportFields as $field) {
                    $row[] = $this->getFieldValue($facility, $field);
                }
                fputcsv($output, $row);
            }

            // Force garbage collection for large datasets
            if (memory_get_usage() > 50 * 1024 * 1024) { // 50MB threshold
                gc_collect_cycles();
            }
        });

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Get field value from facility
     * 
     * Note: This method returns only text-based values for CSV export.
     * File paths, binary data, and file references are converted to 
     * human-readable text (e.g., file names only, not file paths).
     */
    private function getFieldValue(Facility $facility, string $field): string
    {
        switch ($field) {
            // 基本情報
            case 'company_name':
                return $facility->company_name ?? '';
            case 'office_code':
                return $facility->office_code ?? '';
            case 'facility_name':
                return $facility->facility_name ?? '';
            case 'designation_number':
                return $facility->designation_number ?? '';
            case 'postal_code':
                return $facility->formatted_postal_code ?? $facility->postal_code ?? '';
            case 'opening_date':
                return $facility->opening_date ? $facility->opening_date->format('Y-m-d') : '';
            case 'address':
                return $facility->full_address ?? $facility->address ?? '';
            case 'opening_years':
                return $facility->opening_date ? (string)$facility->opening_date->diffInYears(now()) : '';
            case 'months_in_operation':
                return $facility->months_in_operation !== null ? (string)$facility->months_in_operation : '';
            case 'building_name':
                return $facility->building_name ?? '';
            case 'building_structure':
                return $facility->building_structure ?? '';
            case 'phone_number':
                return $facility->phone_number ?? '';
            case 'building_floors':
                return $facility->building_floors ? (string)$facility->building_floors : '';
            case 'fax_number':
                return $facility->fax_number ?? '';
            case 'paid_rooms_count':
                return $facility->paid_rooms_count !== null ? (string)$facility->paid_rooms_count : '';
            case 'toll_free_number':
                return $facility->toll_free_number ?? '';
            case 'ss_rooms_count':
                return $facility->ss_rooms_count !== null ? (string)$facility->ss_rooms_count : '';
            case 'email':
                return $facility->email ?? '';
            case 'capacity':
                return $facility->capacity ? (string)$facility->capacity : '';
            case 'website_url':
                return $facility->website_url ?? '';
            case 'service_types':
                return $facility->services ? $facility->services->pluck('service_type')->join(', ') : '';
            case 'service_validity_periods':
                return $facility->services ? $facility->services->map(function ($service) {
                    if ($service->renewal_start_date && $service->renewal_end_date) {
                        return $service->renewal_start_date->format('Y年m月d日') . ' ～ ' . $service->renewal_end_date->format('Y年m月d日');
                    } elseif ($service->renewal_start_date) {
                        return $service->renewal_start_date->format('Y年m月d日') . ' ～';
                    } elseif ($service->renewal_end_date) {
                        return '～ ' . $service->renewal_end_date->format('Y年m月d日');
                    }
                    return '';
                })->filter()->join(', ') : '';
            case 'status':
                return $facility->status ?? '';
            case 'approved_at':
                return $facility->approved_at ? $facility->approved_at->format('Y-m-d H:i:s') : '';
            case 'created_at':
                return $facility->created_at ? $facility->created_at->format('Y-m-d H:i:s') : '';
            case 'updated_at':
                return $facility->updated_at ? $facility->updated_at->format('Y-m-d H:i:s') : '';

                // 土地情報
            case 'land_ownership_type':
                return $facility->landInfo ? $this->formatOwnershipType($facility->landInfo->ownership_type ?? '') : '';
            case 'land_site_area_sqm':
                return $facility->landInfo && $facility->landInfo->site_area_sqm ? (string)$facility->landInfo->site_area_sqm : '';
            case 'land_site_area_tsubo':
                return $facility->landInfo && $facility->landInfo->site_area_tsubo ? (string)$facility->landInfo->site_area_tsubo : '';
            case 'land_parking_spaces':
                return $facility->landInfo && $facility->landInfo->parking_spaces ? (string)$facility->landInfo->parking_spaces : '';
            case 'land_purchase_price':
                return $facility->landInfo && $facility->landInfo->purchase_price ? (string)$facility->landInfo->purchase_price : '';
            case 'land_unit_price_per_tsubo':
                return $facility->landInfo && $facility->landInfo->unit_price_per_tsubo ? (string)$facility->landInfo->unit_price_per_tsubo : '';
            case 'land_monthly_rent':
                return $facility->landInfo && $facility->landInfo->monthly_rent ? (string)$facility->landInfo->monthly_rent : '';
            case 'land_contract_period':
                if ($facility->landInfo && $facility->landInfo->contract_start_date && $facility->landInfo->contract_end_date) {
                    return $facility->landInfo->contract_start_date->format('Y年n月j日') . ' ～ ' . $facility->landInfo->contract_end_date->format('Y年n月j日');
                }
                return '';
            case 'land_auto_renewal':
                return $facility->landInfo ? $this->formatAutoRenewal($facility->landInfo->auto_renewal ?? '') : '';
            case 'land_contract_period_text':
                return $facility->landInfo ? ($facility->landInfo->contract_period_text ?? '') : '';
            case 'land_notes':
                return $facility->landInfo ? ($facility->landInfo->notes ?? '') : '';
            case 'land_management_company_name':
                return $facility->landInfo ? ($facility->landInfo->management_company_name ?? '') : '';
            case 'land_management_company_postal_code':
                return $facility->landInfo ? ($facility->landInfo->management_company_postal_code ?? '') : '';
            case 'land_management_company_address':
                return $facility->landInfo ? ($facility->landInfo->management_company_address ?? '') : '';
            case 'land_management_company_building':
                return $facility->landInfo ? ($facility->landInfo->management_company_building ?? '') : '';
            case 'land_management_company_phone':
                return $facility->landInfo ? ($facility->landInfo->management_company_phone ?? '') : '';
            case 'land_management_company_fax':
                return $facility->landInfo ? ($facility->landInfo->management_company_fax ?? '') : '';
            case 'land_management_company_email':
                return $facility->landInfo ? ($facility->landInfo->management_company_email ?? '') : '';
            case 'land_management_company_url':
                return $facility->landInfo ? ($facility->landInfo->management_company_url ?? '') : '';
            case 'land_management_company_notes':
                return $facility->landInfo ? ($facility->landInfo->management_company_notes ?? '') : '';
            case 'land_owner_name':
                return $facility->landInfo ? ($facility->landInfo->owner_name ?? '') : '';
            case 'land_owner_postal_code':
                return $facility->landInfo ? ($facility->landInfo->owner_postal_code ?? '') : '';
            case 'land_owner_address':
                return $facility->landInfo ? ($facility->landInfo->owner_address ?? '') : '';
            case 'land_owner_building':
                return $facility->landInfo ? ($facility->landInfo->owner_building ?? '') : '';
            case 'land_owner_phone':
                return $facility->landInfo ? ($facility->landInfo->owner_phone ?? '') : '';
            case 'land_owner_fax':
                return $facility->landInfo ? ($facility->landInfo->owner_fax ?? '') : '';
            case 'land_owner_email':
                return $facility->landInfo ? ($facility->landInfo->owner_email ?? '') : '';
            case 'land_owner_url':
                return $facility->landInfo ? ($facility->landInfo->owner_url ?? '') : '';
            case 'land_owner_notes':
                return $facility->landInfo ? ($facility->landInfo->owner_notes ?? '') : '';

                // ライフライン設備 - 電気
            case 'electrical_contractor':
                $electrical = $facility->getElectricalEquipment();
                return $electrical ? ($electrical->basic_info['electrical_contractor'] ?? '') : '';
            case 'electrical_safety_management_company':
                $electrical = $facility->getElectricalEquipment();
                return $electrical ? ($electrical->basic_info['safety_management_company'] ?? '') : '';
            case 'electrical_maintenance_inspection_date':
                $electrical = $facility->getElectricalEquipment();
                if ($electrical && !empty($electrical->basic_info['maintenance_inspection_date'])) {
                    return \Carbon\Carbon::parse($electrical->basic_info['maintenance_inspection_date'])->format('Y-m-d');
                }
                return '';
            case 'electrical_pas_availability':
                $electrical = $facility->getElectricalEquipment();
                return $electrical ? ($electrical->pas_info['availability'] ?? '') : '';
            case 'electrical_pas_update_date':
                $electrical = $facility->getElectricalEquipment();
                if ($electrical && !empty($electrical->pas_info['update_date'])) {
                    return \Carbon\Carbon::parse($electrical->pas_info['update_date'])->format('Y-m-d');
                }
                return '';
            case 'electrical_cubicle_availability':
                $electrical = $facility->getElectricalEquipment();
                return $electrical ? ($electrical->cubicle_info['availability'] ?? '') : '';
            case 'electrical_cubicle_manufacturers':
                $electrical = $facility->getElectricalEquipment();
                if ($electrical && !empty($electrical->cubicle_info['equipment_list'])) {
                    return collect($electrical->cubicle_info['equipment_list'])->pluck('manufacturer')->filter()->join(', ');
                }
                return '';
            case 'electrical_cubicle_model_years':
                $electrical = $facility->getElectricalEquipment();
                if ($electrical && !empty($electrical->cubicle_info['equipment_list'])) {
                    return collect($electrical->cubicle_info['equipment_list'])->pluck('model_year')->filter()->join(', ');
                }
                return '';
            case 'electrical_generator_availability':
                $electrical = $facility->getElectricalEquipment();
                return $electrical ? ($electrical->generator_info['availability'] ?? '') : '';
            case 'electrical_generator_manufacturers':
                $electrical = $facility->getElectricalEquipment();
                if ($electrical && !empty($electrical->generator_info['equipment_list'])) {
                    return collect($electrical->generator_info['equipment_list'])->pluck('manufacturer')->filter()->join(', ');
                }
                return '';
            case 'electrical_generator_model_years':
                $electrical = $facility->getElectricalEquipment();
                if ($electrical && !empty($electrical->generator_info['equipment_list'])) {
                    return collect($electrical->generator_info['equipment_list'])->pluck('model_year')->filter()->join(', ');
                }
                return '';
            case 'electrical_notes':
                $electrical = $facility->getElectricalEquipment();
                return $electrical ? ($electrical->notes ?? '') : '';

                // ライフライン設備 - 水道
            case 'water_contractor':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['water_contractor'] ?? '') : '';
            case 'water_tank_cleaning_company':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['tank_cleaning_company'] ?? '') : '';
            case 'water_tank_cleaning_date':
                $water = $facility->getWaterEquipment();
                if ($water && !empty($water->basic_info['tank_cleaning_date'])) {
                    return \Carbon\Carbon::parse($water->basic_info['tank_cleaning_date'])->format('Y-m-d');
                }
                return '';
            case 'water_filter_bath_system':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['filter_info']['bath_system'] ?? '') : '';
            case 'water_filter_availability':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['filter_info']['availability'] ?? '') : '';
            case 'water_filter_manufacturer':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['filter_info']['manufacturer'] ?? '') : '';
            case 'water_filter_model_year':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['filter_info']['model_year'] ?? '') : '';
            case 'water_tank_availability':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['tank_info']['availability'] ?? '') : '';
            case 'water_tank_manufacturer':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['tank_info']['manufacturer'] ?? '') : '';
            case 'water_tank_model_year':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['tank_info']['model_year'] ?? '') : '';
            case 'water_pump_manufacturers':
                $water = $facility->getWaterEquipment();
                if ($water && !empty($water->basic_info['pump_info']['pumps'])) {
                    return collect($water->basic_info['pump_info']['pumps'])->pluck('manufacturer')->filter()->join(', ');
                }
                return $water ? ($water->basic_info['pump_info']['manufacturer'] ?? '') : '';
            case 'water_pump_model_years':
                $water = $facility->getWaterEquipment();
                if ($water && !empty($water->basic_info['pump_info']['pumps'])) {
                    return collect($water->basic_info['pump_info']['pumps'])->pluck('model_year')->filter()->join(', ');
                }
                return $water ? ($water->basic_info['pump_info']['model_year'] ?? '') : '';
            case 'water_septic_tank_availability':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['septic_tank_info']['availability'] ?? '') : '';
            case 'water_septic_tank_manufacturer':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['septic_tank_info']['manufacturer'] ?? '') : '';
            case 'water_septic_tank_model_year':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['septic_tank_info']['model_year'] ?? '') : '';
            case 'water_septic_tank_inspection_company':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->basic_info['septic_tank_info']['inspection_company'] ?? '') : '';
            case 'water_septic_tank_inspection_date':
                $water = $facility->getWaterEquipment();
                if ($water && !empty($water->basic_info['septic_tank_info']['inspection_date'])) {
                    return \Carbon\Carbon::parse($water->basic_info['septic_tank_info']['inspection_date'])->format('Y-m-d');
                }
                return '';
            case 'water_legionella_inspection_dates':
                $water = $facility->getWaterEquipment();
                if ($water && !empty($water->basic_info['legionella_info']['inspections'])) {
                    return collect($water->basic_info['legionella_info']['inspections'])
                        ->pluck('inspection_date')
                        ->filter()
                        ->map(function ($date) {
                            return \Carbon\Carbon::parse($date)->format('Y-m-d');
                        })
                        ->join(', ');
                }
                return $water && !empty($water->basic_info['legionella_info']['inspection_date'])
                    ? \Carbon\Carbon::parse($water->basic_info['legionella_info']['inspection_date'])->format('Y-m-d') : '';
            case 'water_legionella_first_results':
                $water = $facility->getWaterEquipment();
                if ($water && !empty($water->basic_info['legionella_info']['inspections'])) {
                    return collect($water->basic_info['legionella_info']['inspections'])->pluck('first_result')->filter()->join(', ');
                }
                return $water ? ($water->basic_info['legionella_info']['first_result'] ?? '') : '';
            case 'water_legionella_second_results':
                $water = $facility->getWaterEquipment();
                if ($water && !empty($water->basic_info['legionella_info']['inspections'])) {
                    return collect($water->basic_info['legionella_info']['inspections'])->pluck('second_result')->filter()->join(', ');
                }
                return $water ? ($water->basic_info['legionella_info']['second_result'] ?? '') : '';
            case 'water_notes':
                $water = $facility->getWaterEquipment();
                return $water ? ($water->notes ?? '') : '';

                // ライフライン設備 - ガス
            case 'gas_contractor':
                $gas = $facility->getGasEquipment();
                return $gas ? ($gas->basic_info['gas_contractor'] ?? '') : '';
            case 'gas_safety_management_company':
                $gas = $facility->getGasEquipment();
                return $gas ? ($gas->basic_info['safety_management_company'] ?? '') : '';
            case 'gas_maintenance_inspection_date':
                $gas = $facility->getGasEquipment();
                if ($gas && !empty($gas->basic_info['maintenance_inspection_date'])) {
                    return \Carbon\Carbon::parse($gas->basic_info['maintenance_inspection_date'])->format('Y-m-d');
                }
                return '';
            case 'gas_notes':
                $gas = $facility->getGasEquipment();
                return $gas ? ($gas->notes ?? '') : '';

                // ライフライン設備 - エレベーター
            case 'elevator_availability':
                $elevator = $facility->getElevatorEquipment();
                return $elevator ? ($elevator->basic_info['availability'] ?? '') : '';
            case 'elevator_manufacturer':
                $elevator = $facility->getElevatorEquipment();
                return $elevator ? ($elevator->basic_info['manufacturer'] ?? '') : '';
            case 'elevator_model_year':
                $elevator = $facility->getElevatorEquipment();
                return $elevator ? ($elevator->basic_info['model_year'] ?? '') : '';
            case 'elevator_maintenance_company':
                $elevator = $facility->getElevatorEquipment();
                return $elevator ? ($elevator->basic_info['maintenance_company'] ?? '') : '';
            case 'elevator_maintenance_date':
                $elevator = $facility->getElevatorEquipment();
                if ($elevator && !empty($elevator->basic_info['maintenance_date'])) {
                    return \Carbon\Carbon::parse($elevator->basic_info['maintenance_date'])->format('Y-m-d');
                }
                return '';
            case 'elevator_notes':
                $elevator = $facility->getElevatorEquipment();
                return $elevator ? ($elevator->notes ?? '') : '';

                // ライフライン設備 - 空調・照明
            case 'hvac_lighting_availability':
                $hvac = $facility->getHvacLightingEquipment();
                return $hvac ? ($hvac->basic_info['availability'] ?? '') : '';
            case 'hvac_lighting_manufacturer':
                $hvac = $facility->getHvacLightingEquipment();
                return $hvac ? ($hvac->basic_info['manufacturer'] ?? '') : '';
            case 'hvac_lighting_model_year':
                $hvac = $facility->getHvacLightingEquipment();
                return $hvac ? ($hvac->basic_info['model_year'] ?? '') : '';
            case 'hvac_lighting_maintenance_company':
                $hvac = $facility->getHvacLightingEquipment();
                return $hvac ? ($hvac->basic_info['maintenance_company'] ?? '') : '';
            case 'hvac_lighting_maintenance_date':
                $hvac = $facility->getHvacLightingEquipment();
                if ($hvac && !empty($hvac->basic_info['maintenance_date'])) {
                    return \Carbon\Carbon::parse($hvac->basic_info['maintenance_date'])->format('Y-m-d');
                }
                return '';
            case 'hvac_lighting_notes':
                $hvac = $facility->getHvacLightingEquipment();
                return $hvac ? ($hvac->notes ?? '') : '';

                // 防犯・防災設備 - 防犯カメラ・電子錠
            case 'security_camera_management_company':
                $security = $facility->getSecurityDisasterEquipment();
                return $security ? ($security->security_systems['camera_lock']['camera']['management_company'] ?? '') : '';
            case 'security_camera_model_year':
                $security = $facility->getSecurityDisasterEquipment();
                return $security ? ($security->security_systems['camera_lock']['camera']['model_year'] ?? '') : '';
            case 'security_camera_notes':
                $security = $facility->getSecurityDisasterEquipment();
                return $security ? ($security->security_systems['camera_lock']['camera']['notes'] ?? '') : '';
            case 'security_lock_management_company':
                $security = $facility->getSecurityDisasterEquipment();
                return $security ? ($security->security_systems['camera_lock']['lock']['management_company'] ?? '') : '';
            case 'security_lock_model_year':
                $security = $facility->getSecurityDisasterEquipment();
                return $security ? ($security->security_systems['camera_lock']['lock']['model_year'] ?? '') : '';
            case 'security_lock_notes':
                $security = $facility->getSecurityDisasterEquipment();
                return $security ? ($security->security_systems['camera_lock']['lock']['notes'] ?? '') : '';

                // 防犯・防災設備 - 消防・防災
            case 'fire_manager':
                $security = $facility->getSecurityDisasterEquipment();
                return $security ? ($security->fire_disaster_prevention['fire_prevention']['fire_manager'] ?? '') : '';
            case 'fire_training_date':
                $security = $facility->getSecurityDisasterEquipment();
                if ($security && !empty($security->fire_disaster_prevention['fire_prevention']['training_date'])) {
                    return \Carbon\Carbon::parse($security->fire_disaster_prevention['fire_prevention']['training_date'])->format('Y-m-d');
                }
                return '';
            case 'fire_inspection_company':
                $security = $facility->getSecurityDisasterEquipment();
                return $security ? ($security->fire_disaster_prevention['fire_prevention']['inspection_company'] ?? '') : '';
            case 'fire_inspection_date':
                $security = $facility->getSecurityDisasterEquipment();
                if ($security && !empty($security->fire_disaster_prevention['fire_prevention']['inspection_date'])) {
                    return \Carbon\Carbon::parse($security->fire_disaster_prevention['fire_prevention']['inspection_date'])->format('Y-m-d');
                }
                return '';
            case 'disaster_practical_training_date':
                $security = $facility->getSecurityDisasterEquipment();
                if ($security && !empty($security->fire_disaster_prevention['disaster_prevention']['practical_training_date'])) {
                    return \Carbon\Carbon::parse($security->fire_disaster_prevention['disaster_prevention']['practical_training_date'])->format('Y-m-d');
                }
                return '';
            case 'disaster_riding_training_date':
                $security = $facility->getSecurityDisasterEquipment();
                if ($security && !empty($security->fire_disaster_prevention['disaster_prevention']['riding_training_date'])) {
                    return \Carbon\Carbon::parse($security->fire_disaster_prevention['disaster_prevention']['riding_training_date'])->format('Y-m-d');
                }
                return '';

                // 建物情報
            case 'building_ownership_type':
                return $facility->buildingInfo ? ($facility->buildingInfo->ownership_type ?? '') : '';
            case 'building_area_sqm':
                return $facility->buildingInfo && $facility->buildingInfo->building_area_sqm ? (string)$facility->buildingInfo->building_area_sqm : '';
            case 'building_area_tsubo':
                return $facility->buildingInfo && $facility->buildingInfo->building_area_tsubo ? (string)$facility->buildingInfo->building_area_tsubo : '';
            case 'building_completion_date':
                return $facility->buildingInfo && $facility->buildingInfo->completion_date ? $facility->buildingInfo->completion_date->format('Y-m-d') : '';
            case 'building_total_floor_area_sqm':
                return $facility->buildingInfo && $facility->buildingInfo->total_floor_area_sqm ? (string)$facility->buildingInfo->total_floor_area_sqm : '';
            case 'building_total_floor_area_tsubo':
                return $facility->buildingInfo && $facility->buildingInfo->total_floor_area_tsubo ? (string)$facility->buildingInfo->total_floor_area_tsubo : '';
            case 'building_age':
                return $facility->buildingInfo && $facility->buildingInfo->building_age ? (string)$facility->buildingInfo->building_age : '';
            case 'building_construction_cost':
                return $facility->buildingInfo && $facility->buildingInfo->construction_cost ? (string)$facility->buildingInfo->construction_cost : '';
            case 'building_cost_per_tsubo':
                return $facility->buildingInfo && $facility->buildingInfo->cost_per_tsubo ? (string)$facility->buildingInfo->cost_per_tsubo : '';
            case 'building_useful_life':
                return $facility->buildingInfo && $facility->buildingInfo->useful_life ? (string)$facility->buildingInfo->useful_life : '';
            case 'building_construction_cooperation_fee':
                return $facility->buildingInfo && $facility->buildingInfo->construction_cooperation_fee ? (string)$facility->buildingInfo->construction_cooperation_fee : '';
            case 'building_monthly_rent':
                return $facility->buildingInfo && $facility->buildingInfo->monthly_rent ? (string)$facility->buildingInfo->monthly_rent : '';
            case 'building_contract_years':
                return $facility->buildingInfo && $facility->buildingInfo->contract_years ? (string)$facility->buildingInfo->contract_years : '';
            case 'building_contract_start_date':
                return $facility->buildingInfo && $facility->buildingInfo->contract_start_date ? $facility->buildingInfo->contract_start_date->format('Y-m-d') : '';
            case 'building_contract_end_date':
                return $facility->buildingInfo && $facility->buildingInfo->contract_end_date ? $facility->buildingInfo->contract_end_date->format('Y-m-d') : '';
            case 'building_auto_renewal':
                return $facility->buildingInfo && $facility->buildingInfo->auto_renewal !== null ? ($facility->buildingInfo->auto_renewal ? 'あり' : 'なし') : '';
            case 'building_construction_company_name':
                return $facility->buildingInfo ? ($facility->buildingInfo->construction_company_name ?? '') : '';
            case 'building_construction_company_phone':
                return $facility->buildingInfo ? ($facility->buildingInfo->construction_company_phone ?? '') : '';
            case 'building_periodic_inspection_type':
                return $facility->buildingInfo ? ($facility->buildingInfo->periodic_inspection_type ?? '') : '';
            case 'building_periodic_inspection_date':
                return $facility->buildingInfo && $facility->buildingInfo->periodic_inspection_date ? $facility->buildingInfo->periodic_inspection_date->format('Y-m-d') : '';
            case 'building_notes':
                return $facility->buildingInfo ? ($facility->buildingInfo->notes ?? '') : '';
            case 'building_management_company_name':
                return $facility->buildingInfo ? ($facility->buildingInfo->management_company_name ?? '') : '';
            case 'building_management_company_phone':
                return $facility->buildingInfo ? ($facility->buildingInfo->management_company_phone ?? '') : '';
            case 'building_management_company_email':
                return $facility->buildingInfo ? ($facility->buildingInfo->management_company_email ?? '') : '';
            case 'building_owner_name':
                return $facility->buildingInfo ? ($facility->buildingInfo->owner_name ?? '') : '';
            case 'building_owner_phone':
                return $facility->buildingInfo ? ($facility->buildingInfo->owner_phone ?? '') : '';
            case 'building_owner_email':
                return $facility->buildingInfo ? ($facility->buildingInfo->owner_email ?? '') : '';

                // 契約書 - その他契約書
            case 'contract_others_company_name':
                return $facility->contract ? ($facility->contract->others_company_name ?? '') : '';
            case 'contract_others_contract_type':
                return $facility->contract ? ($facility->contract->others_contract_type ?? '') : '';
            case 'contract_others_contract_content':
                return $facility->contract ? ($facility->contract->others_contract_content ?? '') : '';
            case 'contract_others_auto_renewal':
                return $facility->contract ? ($facility->contract->others_auto_renewal ?? '') : '';
            case 'contract_others_auto_renewal_details':
                return $facility->contract ? ($facility->contract->others_auto_renewal_details ?? '') : '';
            case 'contract_others_contract_start_date':
                return $facility->contract && $facility->contract->others_contract_start_date
                    ? $facility->contract->others_contract_start_date->format('Y-m-d') : '';
            case 'contract_others_contract_end_date':
                return $facility->contract && $facility->contract->others_contract_end_date
                    ? $facility->contract->others_contract_end_date->format('Y-m-d') : '';
            case 'contract_others_amount':
                return $facility->contract && $facility->contract->others_amount
                    ? (string)$facility->contract->others_amount : '';
            case 'contract_others_notes':
                return $facility->contract ? ($facility->contract->others_notes ?? '') : '';

                // 契約書 - 給食契約書
            case 'contract_meal_service_company_name':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['company_name'] ?? '') : '';
            case 'contract_meal_service_contract_type':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['contract_type'] ?? '') : '';
            case 'contract_meal_service_contract_content':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['contract_content'] ?? '') : '';
            case 'contract_meal_service_auto_renewal':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['auto_renewal'] ?? '') : '';
            case 'contract_meal_service_contract_start_date':
                return $facility->contract && $facility->contract->meal_service_data && isset($facility->contract->meal_service_data['contract_start_date'])
                    ? $facility->contract->meal_service_data['contract_start_date'] : '';
            case 'contract_meal_service_contract_end_date':
                return $facility->contract && $facility->contract->meal_service_data && isset($facility->contract->meal_service_data['contract_end_date'])
                    ? $facility->contract->meal_service_data['contract_end_date'] : '';
            case 'contract_meal_service_amount':
                return $facility->contract && $facility->contract->meal_service_data && isset($facility->contract->meal_service_data['amount'])
                    ? (string)$facility->contract->meal_service_data['amount'] : '';
            case 'contract_meal_service_notes':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['notes'] ?? '') : '';

                // 契約書 - 駐車場契約書
            case 'contract_parking_company_name':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['company_name'] ?? '') : '';
            case 'contract_parking_contract_type':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['contract_type'] ?? '') : '';
            case 'contract_parking_contract_content':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['contract_content'] ?? '') : '';
            case 'contract_parking_auto_renewal':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['auto_renewal'] ?? '') : '';
            case 'contract_parking_contract_start_date':
                return $facility->contract && $facility->contract->parking_data && isset($facility->contract->parking_data['contract_start_date'])
                    ? $facility->contract->parking_data['contract_start_date'] : '';
            case 'contract_parking_contract_end_date':
                return $facility->contract && $facility->contract->parking_data && isset($facility->contract->parking_data['contract_end_date'])
                    ? $facility->contract->parking_data['contract_end_date'] : '';
            case 'contract_parking_amount':
                return $facility->contract && $facility->contract->parking_data && isset($facility->contract->parking_data['amount'])
                    ? (string)$facility->contract->parking_data['amount'] : '';
            case 'contract_parking_spaces':
                return $facility->contract && $facility->contract->parking_data && isset($facility->contract->parking_data['spaces'])
                    ? (string)$facility->contract->parking_data['spaces'] : '';
            case 'contract_parking_notes':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['notes'] ?? '') : '';

                // 契約書 - 給食契約書
            case 'contract_meal_company_name':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['company_name'] ?? '') : '';
            case 'contract_meal_management_fee':
                return $facility->contract && $facility->contract->meal_service_data && isset($facility->contract->meal_service_data['management_fee'])
                    ? (string)$facility->contract->meal_service_data['management_fee'] : '';
            case 'contract_meal_contract_content':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['contract_content'] ?? '') : '';
            case 'contract_meal_breakfast_price':
                return $facility->contract && $facility->contract->meal_service_data && isset($facility->contract->meal_service_data['breakfast_price'])
                    ? (string)$facility->contract->meal_service_data['breakfast_price'] : '';
            case 'contract_meal_contract_start_date':
                return $facility->contract && $facility->contract->meal_service_data && isset($facility->contract->meal_service_data['contract_start_date'])
                    ? $facility->contract->meal_service_data['contract_start_date'] : '';
            case 'contract_meal_lunch_price':
                return $facility->contract && $facility->contract->meal_service_data && isset($facility->contract->meal_service_data['lunch_price'])
                    ? (string)$facility->contract->meal_service_data['lunch_price'] : '';
            case 'contract_meal_contract_type':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['contract_type'] ?? '') : '';
            case 'contract_meal_dinner_price':
                return $facility->contract && $facility->contract->meal_service_data && isset($facility->contract->meal_service_data['dinner_price'])
                    ? (string)$facility->contract->meal_service_data['dinner_price'] : '';
            case 'contract_meal_auto_renewal':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['auto_renewal'] ?? '') : '';
            case 'contract_meal_auto_renewal_details':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['auto_renewal_details'] ?? '') : '';
            case 'contract_meal_snack_price':
                return $facility->contract && $facility->contract->meal_service_data && isset($facility->contract->meal_service_data['snack_price'])
                    ? (string)$facility->contract->meal_service_data['snack_price'] : '';
            case 'contract_meal_cancellation_conditions':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['cancellation_conditions'] ?? '') : '';
            case 'contract_meal_event_meal_price':
                return $facility->contract && $facility->contract->meal_service_data && isset($facility->contract->meal_service_data['event_meal_price'])
                    ? (string)$facility->contract->meal_service_data['event_meal_price'] : '';
            case 'contract_meal_renewal_notice_period':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['renewal_notice_period'] ?? '') : '';
            case 'contract_meal_staff_meal_price':
                return $facility->contract && $facility->contract->meal_service_data && isset($facility->contract->meal_service_data['staff_meal_price'])
                    ? (string)$facility->contract->meal_service_data['staff_meal_price'] : '';
            case 'contract_meal_other_matters':
                return $facility->contract && $facility->contract->meal_service_data
                    ? ($facility->contract->meal_service_data['other_matters'] ?? '') : '';

                // 契約書 - 駐車場契約書
            case 'contract_parking_name':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['parking_name'] ?? '') : '';
            case 'contract_parking_location':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['parking_location'] ?? '') : '';
            case 'contract_parking_spaces':
                return $facility->contract && $facility->contract->parking_data && isset($facility->contract->parking_data['parking_spaces'])
                    ? (string)$facility->contract->parking_data['parking_spaces'] : '';
            case 'contract_parking_position':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['parking_position'] ?? '') : '';
            case 'contract_parking_price_per_space':
                return $facility->contract && $facility->contract->parking_data && isset($facility->contract->parking_data['price_per_space'])
                    ? (string)$facility->contract->parking_data['price_per_space'] : '';
            case 'contract_parking_usage_purpose':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['usage_purpose'] ?? '') : '';
            case 'contract_parking_contract_start_date':
                return $facility->contract && $facility->contract->parking_data && isset($facility->contract->parking_data['contract_start_date'])
                    ? $facility->contract->parking_data['contract_start_date'] : '';
            case 'contract_parking_contract_end_date':
                return $facility->contract && $facility->contract->parking_data && isset($facility->contract->parking_data['contract_end_date'])
                    ? $facility->contract->parking_data['contract_end_date'] : '';
            case 'contract_parking_auto_renewal':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['auto_renewal'] ?? '') : '';
            case 'contract_parking_cancellation_conditions':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['cancellation_conditions'] ?? '') : '';
            case 'contract_parking_renewal_notice_period':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['renewal_notice_period'] ?? '') : '';
            case 'contract_parking_other_matters':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['other_matters'] ?? '') : '';
            case 'contract_parking_management_company_name':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['management_company_name'] ?? '') : '';
            case 'contract_parking_management_postal_code':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['management_postal_code'] ?? '') : '';
            case 'contract_parking_management_address':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['management_address'] ?? '') : '';
            case 'contract_parking_management_building_name':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['management_building_name'] ?? '') : '';
            case 'contract_parking_management_phone':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['management_phone'] ?? '') : '';
            case 'contract_parking_management_fax':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['management_fax'] ?? '') : '';
            case 'contract_parking_management_email':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['management_email'] ?? '') : '';
            case 'contract_parking_management_url':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['management_url'] ?? '') : '';
            case 'contract_parking_management_notes':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['management_notes'] ?? '') : '';
            case 'contract_parking_owner_name':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['owner_name'] ?? '') : '';
            case 'contract_parking_owner_postal_code':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['owner_postal_code'] ?? '') : '';
            case 'contract_parking_owner_address':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['owner_address'] ?? '') : '';
            case 'contract_parking_owner_building_name':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['owner_building_name'] ?? '') : '';
            case 'contract_parking_owner_phone':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['owner_phone'] ?? '') : '';
            case 'contract_parking_owner_fax':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['owner_fax'] ?? '') : '';
            case 'contract_parking_owner_email':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['owner_email'] ?? '') : '';
            case 'contract_parking_owner_url':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['owner_url'] ?? '') : '';
            case 'contract_parking_owner_notes':
                return $facility->contract && $facility->contract->parking_data
                    ? ($facility->contract->parking_data['owner_notes'] ?? '') : '';

                // 図面 - 引き渡し図面
            case 'drawing_handover_startup_drawing':
                return $facility->drawing && $facility->drawing->handover_drawings && isset($facility->drawing->handover_drawings[0])
                    ? ($facility->drawing->handover_drawings[0]['name'] ?? '就航図面') : '';
            case 'drawing_handover_row_2':
                return $facility->drawing && $facility->drawing->handover_drawings && isset($facility->drawing->handover_drawings[1])
                    ? ($facility->drawing->handover_drawings[1]['name'] ?? '') : '';
            case 'drawing_handover_row_3':
                return $facility->drawing && $facility->drawing->handover_drawings && isset($facility->drawing->handover_drawings[2])
                    ? ($facility->drawing->handover_drawings[2]['name'] ?? '') : '';
            case 'drawing_handover_row_4':
                return $facility->drawing && $facility->drawing->handover_drawings && isset($facility->drawing->handover_drawings[3])
                    ? ($facility->drawing->handover_drawings[3]['name'] ?? '') : '';
            case 'drawing_handover_row_5':
                return $facility->drawing && $facility->drawing->handover_drawings && isset($facility->drawing->handover_drawings[4])
                    ? ($facility->drawing->handover_drawings[4]['name'] ?? '') : '';

                // 図面 - 完成図面
            case 'drawing_completion_row_1':
                return $facility->drawing && $facility->drawing->completion_drawings && isset($facility->drawing->completion_drawings[0])
                    ? ($facility->drawing->completion_drawings[0]['name'] ?? '') : '';
            case 'drawing_completion_row_2':
                return $facility->drawing && $facility->drawing->completion_drawings && isset($facility->drawing->completion_drawings[1])
                    ? ($facility->drawing->completion_drawings[1]['name'] ?? '') : '';
            case 'drawing_completion_row_3':
                return $facility->drawing && $facility->drawing->completion_drawings && isset($facility->drawing->completion_drawings[2])
                    ? ($facility->drawing->completion_drawings[2]['name'] ?? '') : '';
            case 'drawing_completion_row_4':
                return $facility->drawing && $facility->drawing->completion_drawings && isset($facility->drawing->completion_drawings[3])
                    ? ($facility->drawing->completion_drawings[3]['name'] ?? '') : '';
            case 'drawing_completion_row_5':
                return $facility->drawing && $facility->drawing->completion_drawings && isset($facility->drawing->completion_drawings[4])
                    ? ($facility->drawing->completion_drawings[4]['name'] ?? '') : '';

                // 図面 - その他図面
            case 'drawing_others_row_1':
                return $facility->drawing && $facility->drawing->other_drawings && isset($facility->drawing->other_drawings[0])
                    ? ($facility->drawing->other_drawings[0]['name'] ?? '') : '';
            case 'drawing_others_row_2':
                return $facility->drawing && $facility->drawing->other_drawings && isset($facility->drawing->other_drawings[1])
                    ? ($facility->drawing->other_drawings[1]['name'] ?? '') : '';
            case 'drawing_others_row_3':
                return $facility->drawing && $facility->drawing->other_drawings && isset($facility->drawing->other_drawings[2])
                    ? ($facility->drawing->other_drawings[2]['name'] ?? '') : '';
            case 'drawing_others_row_4':
                return $facility->drawing && $facility->drawing->other_drawings && isset($facility->drawing->other_drawings[3])
                    ? ($facility->drawing->other_drawings[3]['name'] ?? '') : '';
            case 'drawing_others_row_5':
                return $facility->drawing && $facility->drawing->other_drawings && isset($facility->drawing->other_drawings[4])
                    ? ($facility->drawing->other_drawings[4]['name'] ?? '') : '';
            case 'drawing_notes':
                return $facility->drawing ? ($facility->drawing->notes ?? '') : '';

                // 修繕履歴
            case 'maintenance_latest_date':
                $latest = $facility->maintenanceHistories->sortByDesc('maintenance_date')->first();
                return $latest && $latest->maintenance_date ? $latest->maintenance_date->format('Y-m-d') : '';
            case 'maintenance_latest_content':
                $latest = $facility->maintenanceHistories->sortByDesc('maintenance_date')->first();
                return $latest ? ($latest->content ?? '') : '';
            case 'maintenance_latest_cost':
                $latest = $facility->maintenanceHistories->sortByDesc('maintenance_date')->first();
                return $latest && $latest->cost ? (string)$latest->cost : '';
            case 'maintenance_latest_contractor':
                $latest = $facility->maintenanceHistories->sortByDesc('maintenance_date')->first();
                return $latest ? ($latest->contractor ?? '') : '';
            case 'maintenance_latest_category':
                $latest = $facility->maintenanceHistories->sortByDesc('maintenance_date')->first();
                return $latest ? ($latest->category_label ?? '') : '';
            case 'maintenance_latest_subcategory':
                $latest = $facility->maintenanceHistories->sortByDesc('maintenance_date')->first();
                return $latest ? ($latest->subcategory_label ?? '') : '';
            case 'maintenance_latest_contact_person':
                $latest = $facility->maintenanceHistories->sortByDesc('maintenance_date')->first();
                return $latest ? ($latest->contact_person ?? '') : '';
            case 'maintenance_latest_phone_number':
                $latest = $facility->maintenanceHistories->sortByDesc('maintenance_date')->first();
                return $latest ? ($latest->phone_number ?? '') : '';
            case 'maintenance_latest_notes':
                $latest = $facility->maintenanceHistories->sortByDesc('maintenance_date')->first();
                return $latest ? ($latest->notes ?? '') : '';
            case 'maintenance_latest_warranty_period':
                $latest = $facility->maintenanceHistories->sortByDesc('maintenance_date')->first();
                if ($latest && $latest->warranty_period_years) {
                    return $latest->warranty_period_years . '年';
                }
                return '';
            case 'maintenance_total_count':
                return (string)$facility->maintenanceHistories->count();
            case 'maintenance_total_cost':
                $totalCost = $facility->maintenanceHistories->sum('cost');
                return $totalCost ? (string)$totalCost : '0';

            default:
                return '';
        }
    }

    /**
     * Format ownership type for display
     */
    private function formatOwnershipType(string $type): string
    {
        switch ($type) {
            case 'owned':
                return '自社';
            case 'leased':
                return '賃借';
            case 'owned_rental':
                return '自社（賃貸）';
            default:
                return $type;
        }
    }

    /**
     * Format auto renewal for display
     */
    private function formatAutoRenewal(string $renewal): string
    {
        switch ($renewal) {
            case 'yes':
                return 'あり';
            case 'no':
                return 'なし';
            default:
                return $renewal;
        }
    }

    /**
     * Generate facility PDF (placeholder for PDF functionality)
     */
    public function generateFacilityPdf(Facility $facility): string
    {
        // This is a placeholder - actual PDF generation would be implemented here
        Log::info('PDF generation requested for facility', ['facility_id' => $facility->id]);
        return '';
    }

    /**
     * Generate secure facility PDF (placeholder for PDF functionality)
     */
    public function generateSecureFacilityPdf(Facility $facility): string
    {
        // This is a placeholder - actual secure PDF generation would be implemented here
        Log::info('Secure PDF generation requested for facility', ['facility_id' => $facility->id]);
        return '';
    }

    /**
     * Generate secure filename
     */
    public function generateSecureFilename(Facility $facility): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $facilityName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $facility->facility_name);
        return "facility_{$facilityName}_{$timestamp}.pdf";
    }

    /**
     * Generate batch PDF (placeholder for batch PDF functionality)
     */
    public function generateBatchPdf(Collection $facilities, array $options = []): array
    {
        // This is a placeholder - actual batch PDF generation would be implemented here
        Log::info('Batch PDF generation requested', [
            'facility_count' => $facilities->count(),
            'options' => $options
        ]);

        return [
            'success' => false,
            'error' => 'バッチPDF生成は未実装です。'
        ];
    }

    /**
     * Get batch progress (placeholder for batch progress tracking)
     */
    public function getBatchProgress(string $batchId): array
    {
        // This is a placeholder - actual batch progress tracking would be implemented here
        return [
            'progress' => 0,
            'status' => 'not_found',
            'message' => 'バッチが見つかりません。'
        ];
    }

    /**
     * Determine required relationships based on export fields
     */
    private function determineRequiredRelationships(array $exportFields): array
    {
        $relationships = [];

        foreach ($exportFields as $field) {
            if (str_starts_with($field, 'land_')) {
                $relationships[] = 'landInfo';
            } elseif (str_starts_with($field, 'building_')) {
                $relationships[] = 'buildingInfo';
            } elseif (str_starts_with($field, 'electrical_')) {
                $relationships[] = 'lifelineEquipment.electricalEquipment';
            } elseif (str_starts_with($field, 'water_')) {
                $relationships[] = 'lifelineEquipment.waterEquipment';
            } elseif (str_starts_with($field, 'gas_')) {
                $relationships[] = 'lifelineEquipment.gasEquipment';
            } elseif (str_starts_with($field, 'elevator_')) {
                $relationships[] = 'lifelineEquipment.elevatorEquipment';
            } elseif (str_starts_with($field, 'hvac_')) {
                $relationships[] = 'lifelineEquipment.hvacLightingEquipment';
            } elseif (str_starts_with($field, 'security_') || str_starts_with($field, 'fire_') || str_starts_with($field, 'disaster_')) {
                $relationships[] = 'lifelineEquipment.securityDisasterEquipment';
            } elseif (str_starts_with($field, 'contract_')) {
                $relationships[] = 'contract';
            } elseif (str_starts_with($field, 'drawing_')) {
                $relationships[] = 'drawing';
            } elseif (str_starts_with($field, 'maintenance_')) {
                $relationships[] = 'maintenanceHistories';
            } elseif (str_starts_with($field, 'service_')) {
                $relationships[] = 'services';
            }
        }

        return array_unique($relationships);
    }

    /**
     * Determine required columns based on export fields
     */
    private function determineRequiredColumns(array $exportFields): array
    {
        $baseColumns = ['id', 'created_at', 'updated_at'];
        $facilityColumns = [];

        foreach ($exportFields as $field) {
            // Map export fields to actual database columns
            switch ($field) {
                case 'company_name':
                    $facilityColumns[] = 'company_name';
                    break;
                case 'office_code':
                    $facilityColumns[] = 'office_code';
                    break;
                case 'facility_name':
                    $facilityColumns[] = 'facility_name';
                    break;
                case 'designation_number':
                    $facilityColumns[] = 'designation_number';
                    break;
                case 'postal_code':
                    $facilityColumns[] = 'postal_code';
                    break;
                case 'opening_date':
                    $facilityColumns[] = 'opening_date';
                    break;
                case 'address':
                    $facilityColumns[] = 'address';
                    break;
                case 'building_name':
                    $facilityColumns[] = 'building_name';
                    break;
                case 'building_structure':
                    $facilityColumns[] = 'building_structure';
                    break;
                case 'phone_number':
                    $facilityColumns[] = 'phone_number';
                    break;
                case 'building_floors':
                    $facilityColumns[] = 'building_floors';
                    break;
                case 'fax_number':
                    $facilityColumns[] = 'fax_number';
                    break;
                case 'paid_rooms_count':
                    $facilityColumns[] = 'paid_rooms_count';
                    break;
                case 'toll_free_number':
                    $facilityColumns[] = 'toll_free_number';
                    break;
                case 'ss_rooms_count':
                    $facilityColumns[] = 'ss_rooms_count';
                    break;
                case 'email':
                    $facilityColumns[] = 'email';
                    break;
                case 'capacity':
                    $facilityColumns[] = 'capacity';
                    break;
                case 'website_url':
                    $facilityColumns[] = 'website_url';
                    break;
                case 'status':
                    $facilityColumns[] = 'status';
                    break;
                case 'approved_at':
                    $facilityColumns[] = 'approved_at';
                    break;
            }
        }

        return array_merge($baseColumns, array_unique($facilityColumns));
    }

    /**
     * Stream CSV content directly to output to prevent Quirks Mode
     * This method ensures no HTML content is mixed with CSV data
     */
    public function streamCsv($output, array $facilityIds, array $exportFields): void
    {
        // Determine which relationships to load based on selected fields
        $relationships = $this->determineRequiredRelationships($exportFields);

        $facilities = Facility::whereIn('id', $facilityIds)
            ->with($relationships)
            ->select($this->determineRequiredColumns($exportFields))
            ->get();

        $availableFields = $this->getAvailableFields();
        $selectedFields = array_intersect_key($availableFields, array_flip($exportFields));

        // Write header
        fputcsv($output, array_values($selectedFields));

        // Write data rows
        foreach ($facilities as $facility) {
            $row = [];
            foreach ($exportFields as $field) {
                $row[] = $this->getFieldValue($facility, $field);
            }
            fputcsv($output, $row);
        }
    }
}
