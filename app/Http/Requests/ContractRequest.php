<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // アクティブサブタブ
            'active_sub_tab' => ['nullable', 'string', 'in:others,meal-service,parking'],
            
            // その他契約書
            'others.company_name' => ['nullable', 'string', 'max:255'],
            'others.contract_type' => ['nullable', 'string', 'max:255'],
            'others.contract_content' => ['nullable', 'string', 'max:2000'],
            'others.auto_renewal' => ['nullable', 'string', 'in:あり,なし,条件付き'],
            'others.auto_renewal_details' => ['nullable', 'string', 'max:1000'],
            'others.contract_start_date' => ['nullable', 'date'],
            'others.cancellation_conditions' => ['nullable', 'string', 'max:1000'],
            'others.renewal_notice_period' => ['nullable', 'string', 'max:255'],
            'others.contract_end_date' => ['nullable', 'date', 'after_or_equal:others.contract_start_date'],
            'others.other_matters' => ['nullable', 'string', 'max:2000'],
            'others.amount' => ['nullable', 'integer', 'min:0'],
            'others.contact_info' => ['nullable', 'string', 'max:1000'],
            
            // 給食契約書
            'meal_service.company_name' => ['nullable', 'string', 'max:255'],
            'meal_service.management_fee' => ['nullable', 'integer', 'min:0'],
            'meal_service.contract_content' => ['nullable', 'string', 'max:2000'],
            'meal_service.breakfast_price' => ['nullable', 'integer', 'min:0'],
            'meal_service.contract_start_date' => ['nullable', 'date'],
            'meal_service.lunch_price' => ['nullable', 'integer', 'min:0'],
            'meal_service.contract_type' => ['nullable', 'string', 'max:255'],
            'meal_service.dinner_price' => ['nullable', 'integer', 'min:0'],
            'meal_service.auto_renewal' => ['nullable', 'string', 'in:あり,なし'],
            'meal_service.auto_renewal_details' => ['nullable', 'string', 'max:1000'],
            'meal_service.snack_price' => ['nullable', 'integer', 'min:0'],
            'meal_service.cancellation_conditions' => ['nullable', 'string', 'max:1000'],
            'meal_service.event_meal_price' => ['nullable', 'integer', 'min:0'],
            'meal_service.renewal_notice_period' => ['nullable', 'string', 'max:255'],
            'meal_service.staff_meal_price' => ['nullable', 'integer', 'min:0'],
            'meal_service.other_matters' => ['nullable', 'string', 'max:2000'],
            
            // 駐車場契約書
            'parking.parking_name' => ['nullable', 'string', 'max:255'],
            'parking.contract_start_date' => ['nullable', 'date'],
            'parking.parking_location' => ['nullable', 'string', 'max:1000'],
            'parking.contract_end_date' => ['nullable', 'date', 'after_or_equal:parking.contract_start_date'],
            'parking.parking_spaces' => ['nullable', 'integer', 'min:0'],
            'parking.auto_renewal' => ['nullable', 'string', 'in:あり,なし,条件付き'],
            'parking.parking_position' => ['nullable', 'string', 'max:1000'],
            'parking.cancellation_conditions' => ['nullable', 'string', 'max:1000'],
            'parking.renewal_notice_period' => ['nullable', 'string', 'max:255'],
            'parking.price_per_space' => ['nullable', 'integer', 'min:0'],
            'parking.usage_purpose' => ['nullable', 'string', 'max:1000'],
            'parking.other_matters' => ['nullable', 'string', 'max:2000'],
            
            // 駐車場管理会社情報
            'parking.management_company_name' => ['nullable', 'string', 'max:255'],
            'parking.management_postal_code' => ['nullable', 'string', 'max:10'],
            'parking.management_address' => ['nullable', 'string', 'max:1000'],
            'parking.management_building_name' => ['nullable', 'string', 'max:255'],
            'parking.management_phone' => ['nullable', 'string', 'max:20'],
            'parking.management_fax' => ['nullable', 'string', 'max:20'],
            'parking.management_email' => ['nullable', 'email', 'max:255'],
            'parking.management_url' => ['nullable', 'url', 'max:255'],
            'parking.management_notes' => ['nullable', 'string', 'max:2000'],
            
            // 駐車場オーナー情報
            'parking.owner_name' => ['nullable', 'string', 'max:255'],
            'parking.owner_postal_code' => ['nullable', 'string', 'max:10'],
            'parking.owner_address' => ['nullable', 'string', 'max:1000'],
            'parking.owner_building_name' => ['nullable', 'string', 'max:255'],
            'parking.owner_phone' => ['nullable', 'string', 'max:20'],
            'parking.owner_fax' => ['nullable', 'string', 'max:20'],
            'parking.owner_email' => ['nullable', 'email', 'max:255'],
            'parking.owner_url' => ['nullable', 'url', 'max:255'],
            'parking.owner_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages()
    {
        return [
            'others.company_name.max' => '会社名は255文字以内で入力してください。',
            'others.contract_type.max' => '契約書の種類は255文字以内で入力してください。',
            'others.contract_content.max' => '契約内容は2000文字以内で入力してください。',
            'others.auto_renewal.in' => '自動更新の有無は「あり」「なし」「条件付き」のいずれかを選択してください。',
            'others.auto_renewal_details.max' => '自動更新の詳細は1000文字以内で入力してください。',
            'others.contract_start_date.date' => '契約開始日は正しい日付形式で入力してください。',
            'others.cancellation_conditions.max' => '解約条件は1000文字以内で入力してください。',
            'others.renewal_notice_period.max' => '更新通知期限は255文字以内で入力してください。',
            'others.contract_end_date.date' => '契約終了日は正しい日付形式で入力してください。',
            'others.contract_end_date.after_or_equal' => '契約終了日は契約開始日以降の日付を入力してください。',
            'others.other_matters.max' => 'その他事項は2000文字以内で入力してください。',
            'others.amount.integer' => '金額は数値で入力してください。',
            'others.amount.min' => '金額は0以上で入力してください。',
            'others.contact_info.max' => '連絡先は1000文字以内で入力してください。',
            
            // 給食契約書のメッセージ
            'meal_service.company_name.max' => '会社名は255文字以内で入力してください。',
            'meal_service.management_fee.integer' => '管理費は数値で入力してください。',
            'meal_service.management_fee.min' => '管理費は0以上で入力してください。',
            'meal_service.contract_content.max' => '契約内容は2000文字以内で入力してください。',
            'meal_service.breakfast_price.integer' => '朝食単価は数値で入力してください。',
            'meal_service.breakfast_price.min' => '朝食単価は0以上で入力してください。',
            'meal_service.contract_start_date.date' => '契約開始日は正しい日付形式で入力してください。',
            'meal_service.lunch_price.integer' => '昼食単価は数値で入力してください。',
            'meal_service.lunch_price.min' => '昼食単価は0以上で入力してください。',
            'meal_service.contract_type.max' => '契約書の種類は255文字以内で入力してください。',
            'meal_service.dinner_price.integer' => '夕食単価は数値で入力してください。',
            'meal_service.dinner_price.min' => '夕食単価は0以上で入力してください。',
            'meal_service.auto_renewal.in' => '自動更新の有無は「あり」または「なし」を選択してください。',
            'meal_service.auto_renewal_details.max' => '自動更新の詳細条件は1000文字以内で入力してください。',
            'meal_service.snack_price.integer' => 'おやつ単価は数値で入力してください。',
            'meal_service.snack_price.min' => 'おやつ単価は0以上で入力してください。',
            'meal_service.cancellation_conditions.max' => '解約条件は1000文字以内で入力してください。',
            'meal_service.event_meal_price.integer' => '行事食単価は数値で入力してください。',
            'meal_service.event_meal_price.min' => '行事食単価は0以上で入力してください。',
            'meal_service.renewal_notice_period.max' => '更新通知期限は255文字以内で入力してください。',
            'meal_service.staff_meal_price.integer' => '職員食単価は数値で入力してください。',
            'meal_service.staff_meal_price.min' => '職員食単価は0以上で入力してください。',
            'meal_service.other_matters.max' => 'その他事項は2000文字以内で入力してください。',
            
            // 駐車場契約書のメッセージ
            'parking.parking_name.max' => '駐車場名は255文字以内で入力してください。',
            'parking.contract_start_date.date' => '契約開始日は正しい日付形式で入力してください。',
            'parking.parking_location.max' => '駐車場所在地は1000文字以内で入力してください。',
            'parking.contract_end_date.date' => '契約終了日は正しい日付形式で入力してください。',
            'parking.contract_end_date.after_or_equal' => '契約終了日は契約開始日以降の日付を入力してください。',
            'parking.parking_spaces.integer' => '台数は数値で入力してください。',
            'parking.parking_spaces.min' => '台数は0以上で入力してください。',
            'parking.auto_renewal.in' => '更新の有無は「あり」「なし」「条件付き」のいずれかを選択してください。',
            'parking.parking_position.max' => '停車位置は1000文字以内で入力してください。',
            'parking.cancellation_conditions.max' => '解約条件は1000文字以内で入力してください。',
            'parking.renewal_notice_period.max' => '更新通知期限は255文字以内で入力してください。',
            'parking.price_per_space.integer' => '１台あたりの金額は数値で入力してください。',
            'parking.price_per_space.min' => '１台あたりの金額は0以上で入力してください。',
            'parking.usage_purpose.max' => '使用用途は1000文字以内で入力してください。',
            'parking.other_matters.max' => 'その他事項は2000文字以内で入力してください。',
            
            // 駐車場管理会社情報のメッセージ
            'parking.management_company_name.max' => '管理会社名は255文字以内で入力してください。',
            'parking.management_postal_code.max' => '郵便番号は10文字以内で入力してください。',
            'parking.management_address.max' => '住所は1000文字以内で入力してください。',
            'parking.management_building_name.max' => '建物名は255文字以内で入力してください。',
            'parking.management_phone.max' => '電話番号は20文字以内で入力してください。',
            'parking.management_fax.max' => 'FAX番号は20文字以内で入力してください。',
            'parking.management_email.email' => '正しいメールアドレス形式で入力してください。',
            'parking.management_email.max' => 'メールアドレスは255文字以内で入力してください。',
            'parking.management_url.url' => '正しいURL形式で入力してください。',
            'parking.management_url.max' => 'URLは255文字以内で入力してください。',
            'parking.management_notes.max' => '備考は2000文字以内で入力してください。',
            
            // 駐車場オーナー情報のメッセージ
            'parking.owner_name.max' => 'オーナー氏名は255文字以内で入力してください。',
            'parking.owner_postal_code.max' => '郵便番号は10文字以内で入力してください。',
            'parking.owner_address.max' => '住所は1000文字以内で入力してください。',
            'parking.owner_building_name.max' => '建物名は255文字以内で入力してください。',
            'parking.owner_phone.max' => '電話番号は20文字以内で入力してください。',
            'parking.owner_fax.max' => 'FAX番号は20文字以内で入力してください。',
            'parking.owner_email.email' => '正しいメールアドレス形式で入力してください。',
            'parking.owner_email.max' => 'メールアドレスは255文字以内で入力してください。',
            'parking.owner_url.url' => '正しいURL形式で入力してください。',
            'parking.owner_url.max' => 'URLは255文字以内で入力してください。',
            'parking.owner_notes.max' => '備考は2000文字以内で入力してください。',
        ];
    }
}
