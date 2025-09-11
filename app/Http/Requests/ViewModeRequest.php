<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for view mode validation
 * Centralizes validation rules and provides better error messages
 */
class ViewModeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // View mode changes are allowed for all authenticated users
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'view_mode' => [
                'required',
                'string',
                'in:card,table',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'view_mode.required' => '表示形式を選択してください。',
            'view_mode.in' => '無効な表示形式が選択されました。',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'view_mode' => '表示形式',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace and convert to lowercase for consistent validation
        if ($this->has('view_mode')) {
            $this->merge([
                'view_mode' => trim(strtolower($this->input('view_mode'))),
            ]);
        }
    }
}
