<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DrawingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 認可はポリシーで処理
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 建物図面
            'building_drawings.floor_plan' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'building_drawings.site_plan' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'building_drawings.elevation' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'building_drawings.development' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'building_drawings.area_calculation' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            
            // 建物図面削除フラグ
            'building_drawings.delete_floor_plan' => ['nullable', 'boolean'],
            'building_drawings.delete_site_plan' => ['nullable', 'boolean'],
            'building_drawings.delete_elevation' => ['nullable', 'boolean'],
            'building_drawings.delete_development' => ['nullable', 'boolean'],
            'building_drawings.delete_area_calculation' => ['nullable', 'boolean'],

            // 設備図面
            'equipment_drawings.electrical_equipment' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'equipment_drawings.lighting_equipment' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'equipment_drawings.hvac_equipment' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'equipment_drawings.plumbing_equipment' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'equipment_drawings.kitchen_equipment' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            
            // 設備図面削除フラグ
            'equipment_drawings.delete_electrical_equipment' => ['nullable', 'boolean'],
            'equipment_drawings.delete_lighting_equipment' => ['nullable', 'boolean'],
            'equipment_drawings.delete_hvac_equipment' => ['nullable', 'boolean'],
            'equipment_drawings.delete_plumbing_equipment' => ['nullable', 'boolean'],
            'equipment_drawings.delete_kitchen_equipment' => ['nullable', 'boolean'],

            // 追加建物図面
            'additional_building_drawings.*.title' => ['nullable', 'string', 'max:255'],
            'additional_building_drawings.*.file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'additional_building_drawings.*.delete' => ['nullable', 'boolean'],

            // 追加設備図面
            'additional_equipment_drawings.*.title' => ['nullable', 'string', 'max:255'],
            'additional_equipment_drawings.*.file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'additional_equipment_drawings.*.delete' => ['nullable', 'boolean'],

            // 引き渡し図面
            'handover_drawings.construction_drawings' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'handover_drawings.delete_construction_drawings' => ['nullable', 'boolean'],
            'handover_drawings.additional.*.title' => ['nullable', 'string', 'max:255'],
            'handover_drawings.additional.*.file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'handover_drawings.additional.*.delete' => ['nullable', 'boolean'],
            'handover_drawings_notes' => ['nullable', 'string', 'max:2000'],

            // 備考
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // 建物図面
            'building_drawings.floor_plan.mimes' => '平面図はPDFファイルのみアップロード可能です。',
            'building_drawings.floor_plan.max' => '平面図のファイルサイズは10MB以下にしてください。',
            'building_drawings.site_plan.mimes' => '配置図はPDFファイルのみアップロード可能です。',
            'building_drawings.site_plan.max' => '配置図のファイルサイズは10MB以下にしてください。',
            'building_drawings.elevation.mimes' => '立面図はPDFファイルのみアップロード可能です。',
            'building_drawings.elevation.max' => '立面図のファイルサイズは10MB以下にしてください。',
            'building_drawings.development.mimes' => '展開図はPDFファイルのみアップロード可能です。',
            'building_drawings.development.max' => '展開図のファイルサイズは10MB以下にしてください。',
            'building_drawings.area_calculation.mimes' => '求積図はPDFファイルのみアップロード可能です。',
            'building_drawings.area_calculation.max' => '求積図のファイルサイズは10MB以下にしてください。',

            // 設備図面
            'equipment_drawings.electrical_equipment.mimes' => '電気設備図面はPDFファイルのみアップロード可能です。',
            'equipment_drawings.electrical_equipment.max' => '電気設備図面のファイルサイズは10MB以下にしてください。',
            'equipment_drawings.lighting_equipment.mimes' => '電灯設備図面はPDFファイルのみアップロード可能です。',
            'equipment_drawings.lighting_equipment.max' => '電灯設備図面のファイルサイズは10MB以下にしてください。',
            'equipment_drawings.hvac_equipment.mimes' => '空調設備図面はPDFファイルのみアップロード可能です。',
            'equipment_drawings.hvac_equipment.max' => '空調設備図面のファイルサイズは10MB以下にしてください。',
            'equipment_drawings.plumbing_equipment.mimes' => '給排水衛生設備図面はPDFファイルのみアップロード可能です。',
            'equipment_drawings.plumbing_equipment.max' => '給排水衛生設備図面のファイルサイズは10MB以下にしてください。',
            'equipment_drawings.kitchen_equipment.mimes' => '厨房設備図面はPDFファイルのみアップロード可能です。',
            'equipment_drawings.kitchen_equipment.max' => '厨房設備図面のファイルサイズは10MB以下にしてください。',

            // 追加図面
            'additional_building_drawings.*.title.max' => '図面タイトルは255文字以内で入力してください。',
            'additional_building_drawings.*.file.mimes' => '追加建物図面はPDFファイルのみアップロード可能です。',
            'additional_building_drawings.*.file.max' => '追加建物図面のファイルサイズは10MB以下にしてください。',
            'additional_equipment_drawings.*.title.max' => '図面タイトルは255文字以内で入力してください。',
            'additional_equipment_drawings.*.file.mimes' => '追加設備図面はPDFファイルのみアップロード可能です。',
            'additional_equipment_drawings.*.file.max' => '追加設備図面のファイルサイズは10MB以下にしてください。',

            // 引き渡し図面
            'handover_drawings.construction_drawings.mimes' => '施工図面一式はPDFファイルのみアップロード可能です。',
            'handover_drawings.construction_drawings.max' => '施工図面一式のファイルサイズは10MB以下にしてください。',
            'handover_drawings.additional.*.title.max' => '図面タイトルは255文字以内で入力してください。',
            'handover_drawings.additional.*.file.mimes' => '引き渡し図面はPDFファイルのみアップロード可能です。',
            'handover_drawings.additional.*.file.max' => '引き渡し図面のファイルサイズは10MB以下にしてください。',
            'handover_drawings_notes.max' => '引き渡し図面備考は2000文字以内で入力してください。',

            // 備考
            'notes.max' => '備考は2000文字以内で入力してください。',
        ];
    }
}