<?php

namespace App\Services;

use App\Models\LifelineEquipment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
            default => throw new \InvalidArgumentException('無効なカテゴリです: ' . $category),
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
     * Get validation rules for gas equipment (basic structure).
     */
    private function getGasValidationRules(): array
    {
        return [
            'basic_info' => 'sometimes|array',
            'basic_info.gas_supplier' => 'nullable|string|max:255',
            'basic_info.safety_management_company' => 'nullable|string|max:255',
            'basic_info.maintenance_inspection_date' => 'nullable|date',
            'basic_info.inspection_report_pdf' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get validation messages for gas equipment.
     */
    private function getGasValidationMessages(): array
    {
        return [
            'basic_info.array' => '基本情報は配列形式である必要があります。',
            'basic_info.gas_supplier.string' => 'ガス供給会社は文字列で入力してください。',
            'basic_info.gas_supplier.max' => 'ガス供給会社は255文字以内で入力してください。',
            'basic_info.safety_management_company.string' => '保安管理業者は文字列で入力してください。',
            'basic_info.safety_management_company.max' => '保安管理業者は255文字以内で入力してください。',
            'basic_info.maintenance_inspection_date.date' => 'ガス保守点検実施日は有効な日付を入力してください。',
            'basic_info.inspection_report_pdf.string' => '点検実施報告書は文字列で入力してください。',
            'basic_info.inspection_report_pdf.max' => '点検実施報告書は255文字以内で入力してください。',
            'notes.string' => '備考は文字列で入力してください。',
            'notes.max' => '備考は2000文字以内で入力してください。',
        ];
    }

    /**
     * Get validation rules for water equipment (basic structure).
     */
    private function getWaterValidationRules(): array
    {
        return [
            'basic_info' => 'sometimes|array',
            'basic_info.water_contractor' => 'nullable|string|max:255',
            'basic_info.maintenance_company' => 'nullable|string|max:255',
            'basic_info.maintenance_date' => 'nullable|date|before_or_equal:today',
            'basic_info.inspection_report' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get validation messages for water equipment.
     */
    private function getWaterValidationMessages(): array
    {
        return [
            'basic_info.array' => '基本情報は配列形式である必要があります。',
            'basic_info.water_contractor.string' => '水道契約会社は文字列で入力してください。',
            'basic_info.water_contractor.max' => '水道契約会社は255文字以内で入力してください。',
            'basic_info.maintenance_company.string' => '水道保守点検業者は文字列で入力してください。',
            'basic_info.maintenance_company.max' => '水道保守点検業者は255文字以内で入力してください。',
            'basic_info.maintenance_date.date' => '水道保守点検実施日は有効な日付を入力してください。',
            'basic_info.maintenance_date.before_or_equal' => '水道保守点検実施日は今日以前の日付を入力してください。',
            'basic_info.inspection_report.string' => '点検実施報告書は文字列で入力してください。',
            'basic_info.inspection_report.max' => '点検実施報告書は255文字以内で入力してください。',
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
            default => throw new \InvalidArgumentException('無効なカードタイプです: ' . $cardType),
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