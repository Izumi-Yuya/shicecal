<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LandInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'ownership_type' => 'required|in:owned,leased,owned_rental',
            'parking_spaces' => 'nullable|integer|min:0|max:9999999999',
            'site_area_sqm' => 'nullable|numeric|min:0|max:99999999.99',
            'site_area_tsubo' => 'nullable|numeric|min:0|max:99999999.99',
            'purchase_price' => 'nullable|integer|min:0|max:999999999999999',
            'monthly_rent' => 'nullable|integer|min:0|max:999999999999999',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'auto_renewal' => 'nullable|in:yes,no',

            // 管理会社情報
            'management_company_name' => 'nullable|string|max:30',
            'management_company_postal_code' => 'nullable|regex:/^\d{3}-\d{4}$/',
            'management_company_address' => 'nullable|string|max:30',
            'management_company_building' => 'nullable|string|max:20',
            'management_company_phone' => 'nullable|regex:/^\d{2,4}-\d{2,4}-\d{4}$/',
            'management_company_fax' => 'nullable|regex:/^\d{2,4}-\d{2,4}-\d{4}$/',
            'management_company_email' => 'nullable|email|max:100',
            'management_company_url' => 'nullable|url|max:100',
            'management_company_notes' => 'nullable|string|max:1000',

            // オーナー情報
            'owner_name' => 'nullable|string|max:30',
            'owner_postal_code' => 'nullable|regex:/^\d{3}-\d{4}$/',
            'owner_address' => 'nullable|string|max:30',
            'owner_building' => 'nullable|string|max:20',
            'owner_phone' => 'nullable|regex:/^\d{2,4}-\d{2,4}-\d{4}$/',
            'owner_fax' => 'nullable|regex:/^\d{2,4}-\d{2,4}-\d{4}$/',
            'owner_email' => 'nullable|email|max:100',
            'owner_url' => 'nullable|url|max:100',
            'owner_notes' => 'nullable|string|max:1000',

            'notes' => 'nullable|string|max:2000',

            // PDFファイルアップロード
            'lease_contract_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'registry_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ownership_type.required' => '所有形態を選択してください。',
            'ownership_type.in' => '有効な所有形態を選択してください。',
            'parking_spaces.integer' => '駐車場台数は整数で入力してください。',
            'parking_spaces.min' => '駐車場台数は0以上で入力してください。',
            'parking_spaces.max' => '駐車場台数は10桁以内で入力してください。',
            'site_area_sqm.numeric' => '敷地面積(㎡)は数値で入力してください。',
            'site_area_sqm.min' => '敷地面積(㎡)は0以上で入力してください。',
            'site_area_sqm.max' => '敷地面積(㎡)は10桁以内で入力してください。',
            'site_area_tsubo.numeric' => '敷地面積(坪数)は数値で入力してください。',
            'site_area_tsubo.min' => '敷地面積(坪数)は0以上で入力してください。',
            'site_area_tsubo.max' => '敷地面積(坪数)は10桁以内で入力してください。',
            'purchase_price.integer' => '購入金額は整数で入力してください。',
            'purchase_price.min' => '購入金額は0以上で入力してください。',
            'purchase_price.max' => '購入金額は15桁以内で入力してください。',
            'monthly_rent.integer' => '家賃は整数で入力してください。',
            'monthly_rent.min' => '家賃は0以上で入力してください。',
            'monthly_rent.max' => '家賃は15桁以内で入力してください。',
            'contract_start_date.date' => '契約開始日は有効な日付で入力してください。',
            'contract_end_date.date' => '契約終了日は有効な日付で入力してください。',
            'contract_end_date.after' => '契約終了日は契約開始日より後の日付で入力してください。',
            'auto_renewal.in' => '自動更新の有無を正しく選択してください。',

            // 管理会社情報
            'management_company_name.max' => '管理会社名は30文字以内で入力してください。',
            'management_company_postal_code.regex' => '管理会社の郵便番号は000-0000の形式で入力してください。',
            'management_company_address.max' => '管理会社の住所は30文字以内で入力してください。',
            'management_company_building.max' => '管理会社の建物名は20文字以内で入力してください。',
            'management_company_phone.regex' => '管理会社の電話番号は00-0000-0000の形式で入力してください。',
            'management_company_fax.regex' => '管理会社のFAX番号は00-0000-0000の形式で入力してください。',
            'management_company_email.email' => '管理会社のメールアドレスは有効な形式で入力してください。',
            'management_company_email.max' => '管理会社のメールアドレスは100文字以内で入力してください。',
            'management_company_url.url' => '管理会社のURLは有効な形式で入力してください。',
            'management_company_url.max' => '管理会社のURLは100文字以内で入力してください。',
            'management_company_notes.max' => '管理会社の備考は1000文字以内で入力してください。',

            // オーナー情報
            'owner_name.max' => 'オーナー名は30文字以内で入力してください。',
            'owner_postal_code.regex' => 'オーナーの郵便番号は000-0000の形式で入力してください。',
            'owner_address.max' => 'オーナーの住所は30文字以内で入力してください。',
            'owner_building.max' => 'オーナーの建物名は20文字以内で入力してください。',
            'owner_phone.regex' => 'オーナーの電話番号は00-0000-0000の形式で入力してください。',
            'owner_fax.regex' => 'オーナーのFAX番号は00-0000-0000の形式で入力してください。',
            'owner_email.email' => 'オーナーのメールアドレスは有効な形式で入力してください。',
            'owner_email.max' => 'オーナーのメールアドレスは100文字以内で入力してください。',
            'owner_url.url' => 'オーナーのURLは有効な形式で入力してください。',
            'owner_url.max' => 'オーナーのURLは100文字以内で入力してください。',
            'owner_notes.max' => 'オーナーの備考は1000文字以内で入力してください。',

            'notes.max' => '備考は2000文字以内で入力してください。',

            // PDFファイルアップロード
            'lease_contract_pdf.file' => '賃貸借契約書・覚書PDFは有効なファイルをアップロードしてください。',
            'lease_contract_pdf.mimes' => '賃貸借契約書・覚書PDFはPDFファイルをアップロードしてください。',
            'lease_contract_pdf.max' => '賃貸借契約書・覚書PDFのファイルサイズは10MB以下にしてください。',
            'registry_pdf.file' => '謄本PDFは有効なファイルをアップロードしてください。',
            'registry_pdf.mimes' => '謄本PDFはPDFファイルをアップロードしてください。',
            'registry_pdf.max' => '謄本PDFのファイルサイズは10MB以下にしてください。',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // 全角数字を半角に変換
        $numericFields = ['parking_spaces', 'purchase_price', 'monthly_rent'];

        foreach ($numericFields as $field) {
            if ($this->has($field) && !is_null($this->input($field))) {
                $this->merge([
                    $field => mb_convert_kana($this->input($field), 'n')
                ]);
            }
        }
    }
}
