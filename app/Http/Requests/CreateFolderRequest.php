<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DocumentFolder;
use App\Rules\ValidFolderName;
use App\Rules\UniqueFolderName;

class CreateFolderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $facilityId = $this->route('facility')->id;
        $parentId = $this->input('parent_id');

        return [
            'name' => [
                'required',
                'string',
                new ValidFolderName(),
                new UniqueFolderName($facilityId, $parentId),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:document_folders,id',
                function ($attribute, $value, $fail) use ($facilityId) {
                    if ($value) {
                        $parentFolder = DocumentFolder::find($value);
                        if (!$parentFolder || $parentFolder->facility_id !== $facilityId) {
                            $fail('指定された親フォルダが見つからないか、この施設に属していません。');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'フォルダ名は必須です。',
            'name.string' => 'フォルダ名は文字列である必要があります。',
            'name.min' => 'フォルダ名は1文字以上で入力してください。',
            'name.max' => 'フォルダ名は255文字以内で入力してください。',
            'name.regex' => 'フォルダ名に使用できない文字が含まれています（/ \ : * ? " < > | は使用できません）。',
            'name.unique' => 'このフォルダ名は既に存在します。別の名前を入力してください。',
            'parent_id.integer' => '親フォルダIDは整数である必要があります。',
            'parent_id.exists' => '指定された親フォルダが存在しません。',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'フォルダ名',
            'parent_id' => '親フォルダ',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->input('name', '')),
        ]);
    }
}