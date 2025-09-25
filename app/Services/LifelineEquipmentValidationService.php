<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

class LifelineEquipmentValidationService
{
    /**
     * Validate category-specific data.
     */
    public function validateCategoryData(string $category, array $data): array
    {
        try {
            $rules = $this->getValidationRules($category);
            $messages = $this->getValidationMessages($category);

            $validator = Validator::make($data, $rules, $messages);

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => '入力内容に誤りがあります。',
                    'errors' => $validator->errors()->toArray(),
                    'category' => $category,
                ];
            }

            return [
                'success' => true,
                'data' => $validator->validated(),
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['category' => [$e->getMessage()]],
                'category' => $category,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => ['validation' => [$e->getMessage()]],
                'category' => $category,
            ];
        }
    }

    /**
     * Get validation rules for a specific category.
     */
    public function getValidationRules(string $category): array
    {
        return match ($category) {
            'electrical' => $this->getElectricalValidationRules(),
            'gas' => $this->getGasValidationRules(),
            'water' => $this->getWaterValidationRules(),
            'elevator' => $this->getElevatorValidationRules(),
            'hvac_lighting' => $this->getHvacLightingValidationRules(),
            default => throw new \InvalidArgumentException('無効なカテゴリです: '.$category),
        };
    }

    /**
     * Get validation messages for a specific category.
     */
    public function getValidationMessages(string $category): array
    {
        return match ($category) {
            'electrical' => $this->getElectricalValidationMessages(),
            'gas' => $this->getGasValidationMessages(),
            'water' => $this->getWaterValidationMessages(),
            'elevator' => $this->getElevatorValidationMessages(),
            'hvac_lighting' => $this->getHvacLightingValidationMessages(),
            default => [],
        };
    }

    /**
     * Get validation rules for electrical equipment.
     */
    private function getElectricalValidationRules(): array
    {
        return [
            // Basic info validation rules
            'basic_info' => 'nullable|array',
            'basic_info.electrical_contractor' => 'nullable|string|max:255',
            'basic_info.safety_management_company' => 'nullable|string|max:255',
            'basic_info.maintenance_inspection_date' => 'nullable|date|before_or_equal:today',
            'basic_info.inspection_report_pdf' => 'nullable|string|max:255',
            'basic_info.inspection_report_pdf_file' => 'nullable|file|mimes:pdf|max:10240', // 10MB max

            // PAS info validation rules
            'pas_info' => 'nullable|array',
            'pas_info.availability' => 'nullable|in:有,無',
            'pas_info.details' => 'nullable|string|max:1000',
            'pas_info.update_date' => 'nullable|date|before_or_equal:today',

            // Cubicle info validation rules
            'cubicle_info' => 'nullable|array',
            'cubicle_info.availability' => 'nullable|in:有,無',
            'cubicle_info.details' => 'nullable|string|max:1000',
            'cubicle_info.equipment_list' => 'nullable|array|max:20',
            'cubicle_info.equipment_list.*.equipment_number' => 'nullable|string|max:50',
            'cubicle_info.equipment_list.*.manufacturer' => 'nullable|string|max:255',
            'cubicle_info.equipment_list.*.model_year' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[0-9]{4}$/',
            ],
            'cubicle_info.equipment_list.*.update_date' => 'nullable|date|before_or_equal:today',

            // Generator info validation rules
            'generator_info' => 'nullable|array',
            'generator_info.availability' => 'nullable|in:有,無',
            'generator_info.availability_details' => 'nullable|string|max:1000',
            'generator_info.equipment_list' => 'nullable|array|max:20',
            'generator_info.equipment_list.*.equipment_number' => 'nullable|string|max:50',
            'generator_info.equipment_list.*.manufacturer' => 'nullable|string|max:255',
            'generator_info.equipment_list.*.model_year' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[0-9]{4}$/',
            ],
            'generator_info.equipment_list.*.update_date' => 'nullable|date|before_or_equal:today',

            // Notes validation rules
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get validation messages for electrical equipment.
     */
    private function getElectricalValidationMessages(): array
    {
        return [
            // Basic info messages
            'basic_info.electrical_contractor.max' => '電気契約会社名は255文字以内で入力してください。',
            'basic_info.safety_management_company.max' => '保安管理業者名は255文字以内で入力してください。',
            'basic_info.maintenance_inspection_date.date' => '電気保守点検実施日は有効な日付を入力してください。',
            'basic_info.maintenance_inspection_date.before_or_equal' => '電気保守点検実施日は今日以前の日付を入力してください。',
            'basic_info.inspection_report_pdf.max' => '点検実施報告書ファイル名は255文字以内で入力してください。',
            'basic_info.inspection_report_pdf_file.file' => '点検実施報告書は有効なファイルを選択してください。',
            'basic_info.inspection_report_pdf_file.mimes' => '点検実施報告書はPDFファイルのみアップロード可能です。',
            'basic_info.inspection_report_pdf_file.max' => '点検実施報告書のファイルサイズは10MB以下にしてください。',

            // PAS info messages
            'pas_info.availability.in' => 'PASの有無は「有」または「無」を選択してください。',
            'pas_info.details.max' => 'PAS詳細は1000文字以内で入力してください。',
            'pas_info.update_date.date' => 'PAS更新年月日は有効な日付を入力してください。',
            'pas_info.update_date.before_or_equal' => 'PAS更新年月日は今日以前の日付を入力してください。',

            // Cubicle info messages
            'cubicle_info.availability.in' => 'キュービクルの有無は「有」または「無」を選択してください。',
            'cubicle_info.details.max' => 'キュービクル詳細は1000文字以内で入力してください。',
            'cubicle_info.equipment_list.max' => 'キュービクル設備は20台まで登録できます。',
            'cubicle_info.equipment_list.*.equipment_number.max' => '設備番号は50文字以内で入力してください。',
            'cubicle_info.equipment_list.*.manufacturer.max' => 'メーカー名は255文字以内で入力してください。',
            'cubicle_info.equipment_list.*.model_year.regex' => '年式は4桁の数字で入力してください（例：2024）。',
            'cubicle_info.equipment_list.*.update_date.date' => '更新年月日は有効な日付を入力してください。',
            'cubicle_info.equipment_list.*.update_date.before_or_equal' => '更新年月日は今日以前の日付を入力してください。',

            // Generator info messages
            'generator_info.availability.in' => '非常用発電機の有無は「有」または「無」を選択してください。',
            'generator_info.availability_details.max' => '非常用発電機詳細は1000文字以内で入力してください。',
            'generator_info.equipment_list.max' => '非常用発電機設備は20台まで登録できます。',
            'generator_info.equipment_list.*.equipment_number.max' => '設備番号は50文字以内で入力してください。',
            'generator_info.equipment_list.*.manufacturer.max' => 'メーカー名は255文字以内で入力してください。',
            'generator_info.equipment_list.*.model_year.regex' => '年式は4桁の数字で入力してください（例：2024）。',
            'generator_info.equipment_list.*.update_date.date' => '更新年月日は有効な日付を入力してください。',
            'generator_info.equipment_list.*.update_date.before_or_equal' => '更新年月日は今日以前の日付を入力してください。',

            // Notes messages
            'notes.max' => '備考は2000文字以内で入力してください。',
        ];
    }

    /**
     * Get validation rules for the gas equipment.
     */
    private function getGasValidationRules(): array
    {
        return [
            // Basic information validation rules
            'basic_info' => 'sometimes|array',
            'basic_info.gas_supplier' => 'nullable|string|max:255',
            'basic_info.safety_management_company' => 'nullable|string|max:255',
            'basic_info.maintenance_inspection_date' => 'nullable|date|before_or_equal:today',
            'basic_info.inspection_report_pdf' => 'nullable|string|max:255',
            'basic_info.inspection_report_pdf_file' => 'nullable|file|mimes:pdf|max:10240', // 10MB max
            
            // Gas equipment detail validation rules
            'basic_info.gas_meter_number' => 'nullable|string|max:100',
            'basic_info.gas_type' => 'nullable|string|max:100',
            'basic_info.supply_pressure' => 'nullable|in:低圧,中圧,高圧',
            'basic_info.pipe_material' => 'nullable|in:鋼管,ポリエチレン管,銅管',
            'basic_info.installation_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'basic_info.emergency_shutoff_valve' => 'nullable|in:有,無',
            'basic_info.leak_detector' => 'nullable|in:設置済み,未設置',
            
            // Water heater validation rules
            'basic_info.water_heater_info' => 'sometimes|array',
            'basic_info.water_heater_info.availability' => 'nullable|in:有,無',
            'basic_info.water_heater_info.water_heaters' => 'sometimes|array',
            'basic_info.water_heater_info.water_heaters.*.manufacturer' => 'nullable|string|max:255',
            'basic_info.water_heater_info.water_heaters.*.model_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'basic_info.water_heater_info.water_heaters.*.update_date' => 'nullable|date',
            
            // Notes validation rules
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get validation messages for gas equipment.
     */
    private function getGasValidationMessages(): array
    {
        return [
            // Basic info messages
            'basic_info.array' => '基本情報は配列形式である必要があります。',
            'basic_info.gas_supplier.string' => 'ガス供給会社は文字列で入力してください。',
            'basic_info.gas_supplier.max' => 'ガス供給会社は255文字以内で入力してください。',
            'basic_info.safety_management_company.string' => '保安管理会社は文字列で入力してください。',
            'basic_info.safety_management_company.max' => '保安管理会社は255文字以内で入力してください。',
            'basic_info.maintenance_inspection_date.date' => '保守点検実施日は有効な日付を入力してください。',
            'basic_info.maintenance_inspection_date.before_or_equal' => '保守点検実施日は今日以前の日付を入力してください。',
            'basic_info.inspection_report_pdf.string' => '点検報告書は文字列で入力してください。',
            'basic_info.inspection_report_pdf.max' => '点検報告書は255文字以内で入力してください。',
            'basic_info.inspection_report_pdf_file.file' => '点検報告書は有効なファイルを選択してください。',
            'basic_info.inspection_report_pdf_file.mimes' => '点検報告書はPDFファイルのみアップロード可能です。',
            'basic_info.inspection_report_pdf_file.max' => '点検報告書のファイルサイズは10MB以下にしてください。',
            
            // Gas equipment details messages
            'basic_info.gas_meter_number.string' => 'ガスメーター番号は文字列で入力してください。',
            'basic_info.gas_meter_number.max' => 'ガスメーター番号は100文字以内で入力してください。',
            'basic_info.gas_type.string' => 'ガス種別は文字列で入力してください。',
            'basic_info.gas_type.max' => 'ガス種別は100文字以内で入力してください。',
            'basic_info.supply_pressure.in' => '供給圧力は「低圧」「中圧」「高圧」のいずれかを選択してください。',
            'basic_info.pipe_material.in' => '配管材質は「鋼管」「ポリエチレン管」「銅管」のいずれかを選択してください。',
            'basic_info.installation_year.integer' => '設置年は数値で入力してください。',
            'basic_info.installation_year.min' => '設置年は1900年以降を入力してください。',
            'basic_info.installation_year.max' => '設置年は今年以前を入力してください。',
            'basic_info.emergency_shutoff_valve.in' => '緊急遮断弁は「有」「無」のいずれかを選択してください。',
            'basic_info.leak_detector.in' => 'ガス漏れ検知器は「設置済み」「未設置」のいずれかを選択してください。',
            
            // Water heater messages
            'basic_info.water_heater_info.array' => '給湯器情報は配列形式である必要があります。',
            'basic_info.water_heater_info.availability.in' => '給湯器の有無は「有」「無」のいずれかを選択してください。',
            'basic_info.water_heater_info.water_heaters.array' => '給湯器設備は配列形式である必要があります。',
            'basic_info.water_heater_info.water_heaters.*.manufacturer.string' => '給湯器のメーカーは文字列で入力してください。',
            'basic_info.water_heater_info.water_heaters.*.manufacturer.max' => '給湯器のメーカーは255文字以内で入力してください。',
            'basic_info.water_heater_info.water_heaters.*.model_year.integer' => '給湯器の年式は数値で入力してください。',
            'basic_info.water_heater_info.water_heaters.*.model_year.min' => '給湯器の年式は1900年以降を入力してください。',
            'basic_info.water_heater_info.water_heaters.*.model_year.max' => '給湯器の年式は来年以前を入力してください。',
            'basic_info.water_heater_info.water_heaters.*.update_date.date' => '給湯器の更新年月日は有効な日付を入力してください。',
            
            // Notes messages
            'notes.string' => '備考は文字列で入力してください。',
            'notes.max' => '備考は2000文字以内で入力してください。',
        ];
    }

    /**
     * Get validation rules for water equipment.
     */
    private function getWaterValidationRules(): array
    {
        return [
            // Basic info validation rules
            'basic_info' => 'sometimes|array',
            'basic_info.water_contractor' => 'nullable|string|max:255',
            'basic_info.tank_cleaning_company' => 'nullable|string|max:255',
            'basic_info.tank_cleaning_date' => 'nullable|date|before_or_equal:today',
            'basic_info.tank_cleaning_report_pdf' => 'nullable|file|mimes:pdf|max:10240', // 10MB max

            // Tank info validation rules
            'basic_info.tank_info' => 'sometimes|array',
            'basic_info.tank_info.availability' => 'nullable|in:有,無',
            'basic_info.tank_info.manufacturer' => 'nullable|string|max:255',
            'basic_info.tank_info.model_year' => 'nullable|integer|min:1900|max:'.(date('Y') + 5),

            // Filter info validation rules
            'basic_info.filter_info' => 'sometimes|array',
            'basic_info.filter_info.bath_system' => 'nullable|in:循環式,掛け流し式',
            'basic_info.filter_info.availability' => 'nullable|in:有,無',
            'basic_info.filter_info.manufacturer' => 'nullable|string|max:255',
            'basic_info.filter_info.model_year' => 'nullable|integer|min:1900|max:'.(date('Y') + 5),

            // Pump info validation rules
            'basic_info.pump_info' => 'sometimes|array',
            'basic_info.pump_info.pumps' => 'sometimes|array',
            'basic_info.pump_info.pumps.*' => 'array',
            'basic_info.pump_info.pumps.*.manufacturer' => 'nullable|string|max:255',
            'basic_info.pump_info.pumps.*.model_year' => 'nullable|integer|min:1900|max:'.(date('Y') + 5),
            'basic_info.pump_info.pumps.*.update_date' => 'nullable|date|before_or_equal:today',

            // Notes
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get validation messages for water equipment.
     */
    private function getWaterValidationMessages(): array
    {
        return [
            // Basic info messages
            'basic_info.array' => '基本情報は配列形式である必要があります。',
            'basic_info.water_contractor.string' => '水道契約会社は文字列で入力してください。',
            'basic_info.water_contractor.max' => '水道契約会社は255文字以内で入力してください。',
            'basic_info.tank_cleaning_company.string' => '受水槽・配管清掃業者は文字列で入力してください。',
            'basic_info.tank_cleaning_company.max' => '受水槽・配管清掃業者は255文字以内で入力してください。',
            'basic_info.tank_cleaning_date.date' => '受水槽・配管清掃実施日は有効な日付を入力してください。',
            'basic_info.tank_cleaning_date.before_or_equal' => '受水槽・配管清掃実施日は今日以前の日付を入力してください。',
            'basic_info.tank_cleaning_report_pdf.file' => '受水槽・配管清掃実施報告書はファイルをアップロードしてください。',
            'basic_info.tank_cleaning_report_pdf.mimes' => '受水槽・配管清掃実施報告書はPDFファイルをアップロードしてください。',
            'basic_info.tank_cleaning_report_pdf.max' => '受水槽・配管清掃実施報告書は10MB以下のファイルをアップロードしてください。',

            // Tank info messages
            'basic_info.tank_info.array' => '受水槽情報は配列形式である必要があります。',
            'basic_info.tank_info.availability.in' => '受水槽の有無は「有」または「無」を選択してください。',
            'basic_info.tank_info.manufacturer.string' => 'メーカーは文字列で入力してください。',
            'basic_info.tank_info.manufacturer.max' => 'メーカーは255文字以内で入力してください。',
            'basic_info.tank_info.model_year.integer' => '年式は数値で入力してください。',
            'basic_info.tank_info.model_year.min' => '年式は1900年以降を入力してください。',
            'basic_info.tank_info.model_year.max' => '年式は'.(date('Y') + 5).'年以前を入力してください。',

            // Filter info messages
            'basic_info.filter_info.array' => 'ろ過器情報は配列形式である必要があります。',
            'basic_info.filter_info.bath_system.in' => '浴槽方式は「循環式」または「掛け流し式」を選択してください。',
            'basic_info.filter_info.availability.in' => 'ろ過器の有無は「有」または「無」を選択してください。',
            'basic_info.filter_info.manufacturer.string' => 'メーカーは文字列で入力してください。',
            'basic_info.filter_info.manufacturer.max' => 'メーカーは255文字以内で入力してください。',
            'basic_info.filter_info.model_year.integer' => '年式は数値で入力してください。',
            'basic_info.filter_info.model_year.min' => '年式は1900年以降を入力してください。',
            'basic_info.filter_info.model_year.max' => '年式は'.(date('Y') + 5).'年以前を入力してください。',

            // Pump info messages
            'basic_info.pump_info.array' => '加圧ポンプ情報は配列形式である必要があります。',
            'basic_info.pump_info.pumps.array' => '加圧ポンプリストは配列形式である必要があります。',
            'basic_info.pump_info.pumps.*.array' => '各加圧ポンプ情報は配列形式である必要があります。',
            'basic_info.pump_info.pumps.*.manufacturer.string' => 'メーカーは文字列で入力してください。',
            'basic_info.pump_info.pumps.*.manufacturer.max' => 'メーカーは255文字以内で入力してください。',
            'basic_info.pump_info.pumps.*.model_year.integer' => '年式は数値で入力してください。',
            'basic_info.pump_info.pumps.*.model_year.min' => '年式は1900年以降を入力してください。',
            'basic_info.pump_info.pumps.*.model_year.max' => '年式は'.(date('Y') + 5).'年以前を入力してください。',
            'basic_info.pump_info.pumps.*.update_date.date' => '更新年月日は有効な日付を入力してください。',
            'basic_info.pump_info.pumps.*.update_date.before_or_equal' => '更新年月日は今日以前の日付を入力してください。',
            'basic_info.filter_info.model_year.min' => '年式は1900年以降を入力してください。',
            'basic_info.filter_info.model_year.max' => '年式は'.(date('Y') + 5).'年以前を入力してください。',

            // Notes messages
            'notes.string' => '備考は文字列で入力してください。',
            'notes.max' => '備考は2000文字以内で入力してください。',
        ];
    }

    /**
     * Get validation rules for elevator equipment (basic structure).
     */
    private function getElevatorValidationRules(): array
    {
        return [
            'status' => 'required|string|in:under_development',
        ];
    }

    /**
     * Get validation messages for elevator equipment.
     */
    private function getElevatorValidationMessages(): array
    {
        return [
            'status.required' => 'ステータスは必須です。',
            'status.in' => 'エレベーター設備は開発中です。',
        ];
    }

    /**
     * Get validation rules for HVAC and lighting equipment.
     */
    private function getHvacLightingValidationRules(): array
    {
        return [
            'basic_info' => 'sometimes|array',
            'basic_info.hvac_contractor' => 'nullable|string|max:255',
            'basic_info.maintenance_company' => 'nullable|string|max:255',
            'basic_info.last_inspection_date' => 'nullable|date',
            'basic_info.next_inspection_date' => 'nullable|date|after_or_equal:basic_info.last_inspection_date',
            'basic_info.system_type' => 'nullable|string|in:中央空調,個別空調,ハイブリッド',
            'basic_info.lighting_type' => 'nullable|string|in:LED,蛍光灯,ハロゲン,ミックス',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get validation messages for HVAC and lighting equipment.
     */
    private function getHvacLightingValidationMessages(): array
    {
        return [
            'basic_info.array' => '基本情報は配列形式である必要があります。',
            'basic_info.hvac_contractor.string' => '空調業者名は文字列で入力してください。',
            'basic_info.hvac_contractor.max' => '空調業者名は255文字以内で入力してください。',
            'basic_info.maintenance_company.string' => '保守管理業者名は文字列で入力してください。',
            'basic_info.maintenance_company.max' => '保守管理業者名は255文字以内で入力してください。',
            'basic_info.last_inspection_date.date' => '前回点検日は有効な日付を入力してください。',
            'basic_info.next_inspection_date.date' => '次回点検予定日は有効な日付を入力してください。',
            'basic_info.next_inspection_date.after_or_equal' => '次回点検予定日は前回点検日以降の日付を入力してください。',
            'basic_info.system_type.in' => '空調システム種別は「中央空調」「個別空調」「ハイブリッド」のいずれかを選択してください。',
            'basic_info.lighting_type.in' => '照明種別は「LED」「蛍光灯」「ハロゲン」「ミックス」のいずれかを選択してください。',
            'notes.string' => '備考は文字列で入力してください。',
            'notes.max' => '備考は2000文字以内で入力してください。',
        ];
    }

    /**
     * Validate specific card data for electrical equipment.
     */
    public function validateElectricalCardData(string $cardType, array $data): array
    {
        $rules = $this->getElectricalCardValidationRules($cardType);
        $messages = $this->getElectricalCardValidationMessages($cardType);

        try {
            $validator = Validator::make($data, $rules, $messages);

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => '入力内容に誤りがあります。',
                    'errors' => $validator->errors()->toArray(),
                    'card_type' => $cardType,
                ];
            }

            return [
                'success' => true,
                'data' => $validator->validated(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => ['validation' => [$e->getMessage()]],
                'card_type' => $cardType,
            ];
        }
    }

    /**
     * Get validation rules for specific electrical equipment card.
     */
    private function getElectricalCardValidationRules(string $cardType): array
    {
        return match ($cardType) {
            'basic_info' => [
                'electrical_contractor' => 'nullable|string|max:255',
                'safety_management_company' => 'nullable|string|max:255',
                'maintenance_inspection_date' => 'nullable|date|before_or_equal:today',
                'inspection_report_pdf' => 'nullable|string|max:255',
            ],
            'pas_info' => [
                'availability' => 'nullable|in:有,無',
                'details' => 'nullable|string|max:1000',
                'update_date' => 'nullable|date|before_or_equal:today',
            ],
            'cubicle_info' => [
                'availability' => 'nullable|in:有,無',
                'details' => 'nullable|string|max:1000',
                'equipment_list' => 'nullable|array|max:20',
                'equipment_list.*.equipment_number' => 'nullable|string|max:50',
                'equipment_list.*.manufacturer' => 'nullable|string|max:255',
                'equipment_list.*.model_year' => ['nullable', 'string', 'max:10', 'regex:/^[0-9]{4}$/'],
                'equipment_list.*.update_date' => 'nullable|date|before_or_equal:today',
            ],
            'generator_info' => [
                'availability' => 'nullable|in:有,無',
                'availability_details' => 'nullable|string|max:1000',
                'equipment_list' => 'nullable|array|max:20',
                'equipment_list.*.equipment_number' => 'nullable|string|max:50',
                'equipment_list.*.manufacturer' => 'nullable|string|max:255',
                'equipment_list.*.model_year' => ['nullable', 'string', 'max:10', 'regex:/^[0-9]{4}$/'],
                'equipment_list.*.update_date' => 'nullable|date|before_or_equal:today',
            ],
            'notes' => [
                'notes' => 'nullable|string|max:2000',
            ],
            default => throw new \InvalidArgumentException('無効なカードタイプです: '.$cardType),
        };
    }

    /**
     * Get validation messages for specific electrical equipment card.
     */
    private function getElectricalCardValidationMessages(string $cardType): array
    {
        return match ($cardType) {
            'basic_info' => [
                'electrical_contractor.max' => '電気契約会社名は255文字以内で入力してください。',
                'safety_management_company.max' => '保安管理業者名は255文字以内で入力してください。',
                'maintenance_inspection_date.date' => '電気保守点検実施日は有効な日付を入力してください。',
                'maintenance_inspection_date.before_or_equal' => '電気保守点検実施日は今日以前の日付を入力してください。',
                'inspection_report_pdf.max' => '点検実施報告書ファイル名は255文字以内で入力してください。',
            ],
            'pas_info' => [
                'availability.in' => 'PASの有無は「有」または「無」を選択してください。',
                'details.max' => 'PAS詳細は1000文字以内で入力してください。',
                'update_date.date' => 'PAS更新年月日は有効な日付を入力してください。',
                'update_date.before_or_equal' => 'PAS更新年月日は今日以前の日付を入力してください。',
            ],
            'cubicle_info' => [
                'availability.in' => 'キュービクルの有無は「有」または「無」を選択してください。',
                'details.max' => 'キュービクル詳細は1000文字以内で入力してください。',
                'equipment_list.max' => 'キュービクル設備は20台まで登録できます。',
                'equipment_list.*.equipment_number.max' => '設備番号は50文字以内で入力してください。',
                'equipment_list.*.manufacturer.max' => 'メーカー名は255文字以内で入力してください。',
                'equipment_list.*.model_year.regex' => '年式は4桁の数字で入力してください（例：2024）。',
                'equipment_list.*.update_date.date' => '更新年月日は有効な日付を入力してください。',
                'equipment_list.*.update_date.before_or_equal' => '更新年月日は今日以前の日付を入力してください。',
            ],
            'generator_info' => [
                'availability.in' => '非常用発電機の有無は「有」または「無」を選択してください。',
                'availability_details.max' => '非常用発電機詳細は1000文字以内で入力してください。',
                'equipment_list.max' => '非常用発電機設備は20台まで登録できます。',
                'equipment_list.*.equipment_number.max' => '設備番号は50文字以内で入力してください。',
                'equipment_list.*.manufacturer.max' => 'メーカー名は255文字以内で入力してください。',
                'equipment_list.*.model_year.regex' => '年式は4桁の数字で入力してください（例：2024）。',
                'equipment_list.*.update_date.date' => '更新年月日は有効な日付を入力してください。',
                'equipment_list.*.update_date.before_or_equal' => '更新年月日は今日以前の日付を入力してください。',
            ],
            'notes' => [
                'notes.max' => '備考は2000文字以内で入力してください。',
            ],
            default => [],
        };
    }
}
