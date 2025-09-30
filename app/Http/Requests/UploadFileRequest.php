<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DocumentFolder;
use App\Rules\SecureFileUpload;

class UploadFileRequest extends FormRequest
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

        return [
            'files' => [
                'required',
                'array',
                'min:1',
                'max:10', // Maximum 10 files at once
            ],
            'files.*' => [
                'required',
                new SecureFileUpload(),
            ],
            'folder_id' => [
                'nullable',
                'integer',
                'exists:document_folders,id',
                function ($attribute, $value, $fail) use ($facilityId) {
                    if ($value) {
                        $folder = DocumentFolder::find($value);
                        if (!$folder || $folder->facility_id !== $facilityId) {
                            $fail('指定されたフォルダが見つからないか、この施設に属していません。');
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
        $maxSizeMB = $this->getMaxFileSize() / 1024;
        $allowedTypes = implode('、', $this->getAllowedExtensions());

        return [
            'files.required' => 'アップロードするファイルを選択してください。',
            'files.array' => 'ファイルデータが正しくありません。',
            'files.min' => '少なくとも1つのファイルを選択してください。',
            'files.max' => '一度にアップロードできるファイルは最大10個までです。',
            'files.*.required' => 'ファイルが選択されていません。',
            'files.*.file' => '有効なファイルを選択してください。',
            'files.*.max' => "ファイルサイズは{$maxSizeMB}MB以下にしてください。",
            'files.*.mimes' => "対応しているファイル形式は {$allowedTypes} です。",
            'folder_id.integer' => 'フォルダIDは整数である必要があります。',
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
     * Get the maximum file size in KB.
     */
    protected function getMaxFileSize(): int
    {
        return config('facility-document.max_file_size', 10240); // Default 10MB
    }

    /**
     * Get allowed MIME types.
     */
    protected function getAllowedMimeTypes(): array
    {
        return [
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'txt',
            'jpg',
            'jpeg',
            'png',
            'gif',
            'bmp',
            'svg',
            'zip',
            'rar',
            '7z',
        ];
    }

    /**
     * Get allowed file extensions for display.
     */
    protected function getAllowedExtensions(): array
    {
        return [
            'PDF',
            'Word (DOC, DOCX)',
            'Excel (XLS, XLSX)',
            'PowerPoint (PPT, PPTX)',
            'テキスト (TXT)',
            '画像 (JPG, PNG, GIF, BMP, SVG)',
            'アーカイブ (ZIP, RAR, 7Z)',
        ];
    }

    /**
     * Validate file content and security.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('files')) {
                foreach ($this->file('files') as $index => $file) {
                    // Check for dangerous file names
                    $originalName = $file->getClientOriginalName();
                    if ($this->isDangerousFileName($originalName)) {
                        $validator->errors()->add(
                            "files.{$index}",
                            'ファイル名に危険な文字が含まれています。'
                        );
                    }

                    // Additional MIME type validation
                    if (!$this->isValidMimeType($file)) {
                        $validator->errors()->add(
                            "files.{$index}",
                            'ファイル形式が正しくありません。'
                        );
                    }

                    // Check file size against PHP limits
                    if ($this->exceedsPhpLimits($file)) {
                        $validator->errors()->add(
                            "files.{$index}",
                            'ファイルサイズがサーバーの制限を超えています。'
                        );
                    }
                }
            }
        });
    }

    /**
     * Check if filename contains dangerous characters.
     */
    protected function isDangerousFileName(string $filename): bool
    {
        // Check for dangerous patterns
        $dangerousPatterns = [
            '/\.\./', // Path traversal
            '/[\/\\:*?"<>|]/', // Invalid filesystem characters
            '/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(\.|$)/i', // Windows reserved names
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate MIME type more strictly.
     */
    protected function isValidMimeType($file): bool
    {
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/svg+xml',
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ];

        return in_array($file->getMimeType(), $allowedMimes);
    }

    /**
     * Check if file exceeds PHP upload limits.
     */
    protected function exceedsPhpLimits($file): bool
    {
        $maxUploadSize = $this->parseSize(ini_get('upload_max_filesize'));
        $maxPostSize = $this->parseSize(ini_get('post_max_size'));
        $fileSize = $file->getSize();

        return $fileSize > $maxUploadSize || $fileSize > $maxPostSize;
    }

    /**
     * Parse size string to bytes.
     */
    protected function parseSize(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        switch ($unit) {
            case 'g':
                $value *= 1024;
                // fall through
            case 'm':
                $value *= 1024;
                // fall through
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}