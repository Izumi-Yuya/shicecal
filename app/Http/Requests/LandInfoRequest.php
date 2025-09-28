<?php

namespace App\Http\Requests;

use App\Services\ValidationRuleService;
use Illuminate\Foundation\Http\FormRequest;

class LandInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->canEditLandInfo();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $ownershipType = $this->input('ownership_type', '');

        // Use centralized validation service
        return ValidationRuleService::getLandInfoRules($ownershipType);
    }

    /**
     * Get validation configuration for frontend
     */
    public function getValidationConfig(): array
    {
        return ValidationRuleService::getValidationConfig();
    }

    /**
     * Get validation rules for specific ownership type (for API responses)
     */
    public function getRulesForOwnershipType(string $ownershipType): array
    {
        return ValidationRuleService::getLandInfoRules($ownershipType);
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'ownership_type.required' => '所有形態は必須項目です。',
            'ownership_type.in' => '所有形態は有効な値を選択してください。',
            'parking_spaces.integer' => '敷地内駐車場台数は整数で入力してください。',
            'parking_spaces.min' => '敷地内駐車場台数は0以上で入力してください。',
            'parking_spaces.max' => '敷地内駐車場台数は9,999,999,999以下で入力してください。',
            'site_area_sqm.numeric' => '敷地面積（㎡）は数値で入力してください。',
            'site_area_sqm.min' => '敷地面積（㎡）は0以上で入力してください。',
            'site_area_sqm.max' => '敷地面積（㎡）は99,999,999.99以下で入力してください。',
            'site_area_tsubo.numeric' => '敷地面積（坪数）は数値で入力してください。',
            'site_area_tsubo.min' => '敷地面積（坪数）は0以上で入力してください。',
            'site_area_tsubo.max' => '敷地面積（坪数）は99,999,999.99以下で入力してください。',
            'purchase_price.required' => '購入金額は必須項目です。',
            'purchase_price.numeric' => '購入金額は数値で入力してください。',
            'purchase_price.min' => '購入金額は0以上で入力してください。',
            'purchase_price.max' => '購入金額は999,999,999,999,999以下で入力してください。',
            'site_area_tsubo.required_without' => '敷地面積（坪数）または敷地面積（㎡）のいずれかは必須項目です。',
            'site_area_sqm.required_without' => '敷地面積（㎡）または敷地面積（坪数）のいずれかは必須項目です。',
            'monthly_rent.required' => '家賃は必須項目です。',
            'monthly_rent.numeric' => '家賃は数値で入力してください。',
            'monthly_rent.min' => '家賃は0以上で入力してください。',
            'monthly_rent.max' => '家賃は999,999,999,999,999以下で入力してください。',
            'contract_start_date.required' => '契約開始日は必須項目です。',
            'contract_start_date.date' => '契約開始日は有効な日付を入力してください。',
            'contract_start_date.before_or_equal' => '契約開始日は契約終了日以前の日付を入力してください。',
            'contract_end_date.required' => '契約終了日は必須項目です。',
            'contract_end_date.date' => '契約終了日は有効な日付を入力してください。',
            'contract_end_date.after_or_equal' => '契約終了日は契約開始日以降の日付を入力してください。',
            'auto_renewal.in' => '自動更新の有無は有効な値を選択してください。',
            'management_company_postal_code.regex' => '管理会社の郵便番号は正しい形式で入力してください。（例: 123-4567）',
            'management_company_phone.regex' => '管理会社の電話番号は正しい形式で入力してください。（例: 03-1234-5678）',
            'management_company_fax.regex' => '管理会社のFAX番号は正しい形式で入力してください。（例: 03-1234-5679）',
            'management_company_email.email' => '管理会社のメールアドレスは正しい形式で入力してください。',
            'management_company_url.url' => '管理会社のURLは正しい形式で入力してください。',
            'owner_postal_code.regex' => 'オーナーの郵便番号は正しい形式で入力してください。（例: 123-4567）',
            'owner_phone.regex' => 'オーナーの電話番号は正しい形式で入力してください。（例: 03-1234-5678）',
            'owner_fax.regex' => 'オーナーのFAX番号は正しい形式で入力してください。（例: 03-1234-5679）',
            'owner_email.email' => 'オーナーのメールアドレスは正しい形式で入力してください。',
            'owner_url.url' => 'オーナーのURLは正しい形式で入力してください。',
            'notes.max' => '備考は1000文字以下で入力してください。',
            'management_company_notes.max' => '管理会社の備考は1000文字以下で入力してください。',
            'owner_notes.max' => 'オーナーの備考は1000文字以下で入力してください。',
            // PDF file upload messages
            'lease_contract_pdf.file' => '賃貸借契約書・覚書は有効なファイルを選択してください。',
            'lease_contract_pdf.mimes' => '賃貸借契約書・覚書はPDFファイルのみアップロード可能です。',
            'lease_contract_pdf.max' => '賃貸借契約書・覚書のファイルサイズは10MB以下にしてください。',
            'registry_pdf.file' => '謄本は有効なファイルを選択してください。',
            'registry_pdf.mimes' => '謄本はPDFファイルのみアップロード可能です。',
            'registry_pdf.max' => '謄本のファイルサイズは10MB以下にしてください。',
        ];
    }

    /**
     * Get custom attributes for validator errors
     */
    public function attributes(): array
    {
        return [
            'ownership_type' => '所有形態',
            'parking_spaces' => '敷地内駐車場台数',
            'site_area_sqm' => '敷地面積（㎡）',
            'site_area_tsubo' => '敷地面積（坪数）',
            'purchase_price' => '購入金額',
            'monthly_rent' => '家賃',
            'contract_start_date' => '契約開始日',
            'contract_end_date' => '契約終了日',
            'auto_renewal' => '自動更新の有無',
            'management_company_name' => '管理会社名',
            'management_company_postal_code' => '管理会社郵便番号',
            'management_company_address' => '管理会社住所',
            'management_company_building' => '管理会社建物名',
            'management_company_phone' => '管理会社電話番号',
            'management_company_fax' => '管理会社FAX番号',
            'management_company_email' => '管理会社メールアドレス',
            'management_company_url' => '管理会社URL',
            'management_company_notes' => '管理会社備考',
            'owner_name' => 'オーナー名',
            'owner_postal_code' => 'オーナー郵便番号',
            'owner_address' => 'オーナー住所',
            'owner_building' => 'オーナー建物名',
            'owner_phone' => 'オーナー電話番号',
            'owner_fax' => 'オーナーFAX番号',
            'owner_email' => 'オーナーメールアドレス',
            'owner_url' => 'オーナーURL',
            'owner_notes' => 'オーナー備考',
            'notes' => '備考',
            'lease_contract_pdf' => '賃貸借契約書・覚書',
            'registry_pdf' => '謄本',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Clean up currency inputs
        if ($this->has('purchase_price')) {
            $this->merge([
                'purchase_price' => $this->cleanCurrencyInput($this->purchase_price),
            ]);
        }

        if ($this->has('monthly_rent')) {
            $this->merge([
                'monthly_rent' => $this->cleanCurrencyInput($this->monthly_rent),
            ]);
        }
    }

    /**
     * Clean currency input by removing formatting
     */
    protected function cleanCurrencyInput(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Remove commas, spaces, and currency symbols
        return preg_replace('/[,\s円¥]/', '', $value);
    }

    /**
     * Handle a failed validation attempt
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        // Log validation failures for debugging
        logger()->warning('Land info validation failed', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['_token', '_method']),
            'user_id' => auth()->id(),
            'facility_id' => $this->route('facility')?->id,
        ]);

        parent::failedValidation($validator);
    }
}
