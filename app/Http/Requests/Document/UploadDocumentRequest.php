<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UploadDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx'])
                    ->max(10 * 1024), // 10MB
            ],
            'folder_id' => ['nullable', 'integer', 'exists:document_folders,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'files.required' => 'アップロードするファイルを選択してください。',
            'files.array' => 'ファイルの形式が正しくありません。',
            'files.min' => '少なくとも1つのファイルを選択してください。',
            'files.max' => '一度にアップロードできるファイルは10個までです。',
            'files.*.required' => 'ファイルが選択されていません。',
            'files.*.mimes' => 'PDFファイル、画像ファイル、またはWordファイルのみアップロード可能です。',
            'files.*.max' => 'ファイルサイズは10MB以下にしてください。',
            'folder_id.integer' => 'フォルダIDが正しくありません。',
            'folder_id.exists' => '指定されたフォルダが存在しません。',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'files' => 'ファイル',
            'files.*' => 'ファイル',
            'folder_id' => 'フォルダ',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional custom validation logic
            $this->validateFileNames($validator);
            $this->validateTotalSize($validator);
        });
    }

    /**
     * Validate file names for security
     */
    private function validateFileNames($validator): void
    {
        if (!$this->hasFile('files')) {
            return;
        }

        foreach ($this->file('files') as $index => $file) {
            $originalName = $file->getClientOriginalName();
            
            // Check for dangerous characters
            if (preg_match('/[<>:"|?*\\\\\/\x00-\x1f]/', $originalName)) {
                $validator->errors()->add(
                    "files.{$index}",
                    "ファイル名に使用できない文字が含まれています: {$originalName}"
                );
            }
            
            // Check for directory traversal attempts
            if (str_contains($originalName, '..')) {
                $validator->errors()->add(
                    "files.{$index}",
                    "ファイル名が不正です: {$originalName}"
                );
            }
        }
    }

    /**
     * Validate total upload size
     */
    private function validateTotalSize($validator): void
    {
        if (!$this->hasFile('files')) {
            return;
        }

        $totalSize = 0;
        foreach ($this->file('files') as $file) {
            $totalSize += $file->getSize();
        }

        // 50MB total limit
        $maxTotalSize = 50 * 1024 * 1024;
        if ($totalSize > $maxTotalSize) {
            $validator->errors()->add(
                'files',
                'アップロードファイルの合計サイズが50MBを超えています。'
            );
        }
    }
}