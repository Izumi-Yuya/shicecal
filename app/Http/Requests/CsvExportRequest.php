<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CsvExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $exportService = app(\App\Services\ExportService::class);
        $availableFields = array_keys($exportService->getAvailableFields());
        
        return [
            'facility_ids' => ['required', 'array', 'min:1', 'max:100'],
            'facility_ids.*' => ['integer', 'exists:facilities,id'],
            'export_fields' => ['required', 'array', 'min:1', 'max:200'],
            'export_fields.*' => ['string', Rule::in($availableFields)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'facility_ids.required' => '施設を選択してください。',
            'facility_ids.min' => '少なくとも1つの施設を選択してください。',
            'facility_ids.max' => '一度に選択できる施設は100件までです。',
            'facility_ids.*.exists' => '選択された施設が見つかりません。',
            'export_fields.required' => '出力項目を選択してください。',
            'export_fields.min' => '少なくとも1つの項目を選択してください。',
            'export_fields.max' => '一度に選択できる項目は200個までです。',
            'export_fields.*.in' => '無効な出力項目が選択されています。',
        ];
    }
}