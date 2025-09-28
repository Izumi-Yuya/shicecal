<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 統一されたファイル処理サービス
 * 
 * A unified file processing service for facility and lifeline equipment operations.
 * Provides consistent handling of file uploads, downloads, and display operations
 * throughout the application.
 */
class FileHandlingService
{
    /**
     * サポートされるファイルタイプ
     */
    const SUPPORTED_FILE_TYPES = [
        'pdf' => [
            'mime_types' => ['application/pdf'],
            'extensions' => ['pdf'],
            'max_size' => 10 * 1024 * 1024, // 10MB
            'icon' => 'fas fa-file-pdf',
            'color' => 'text-danger',
        ],
        'image' => [
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif'],
            'extensions' => ['jpg', 'jpeg', 'png', 'gif'],
            'max_size' => 5 * 1024 * 1024, // 5MB
            'icon' => 'fas fa-image',
            'color' => 'text-info',
        ],
        'document' => [
            'mime_types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'extensions' => ['pdf', 'doc', 'docx'],
            'max_size' => 10 * 1024 * 1024, // 10MB
            'icon' => 'fas fa-file-alt',
            'color' => 'text-primary',
        ],
    ];

    /**
     * ファイルをアップロードして保存
     *
     * @return array Returns array with success status, filename, path, and metadata
     *
     * @throws Exception
     */
    public function uploadFile(UploadedFile $file, string $directory, string $fileType = 'pdf'): array
    {
        try {
            // ファイルタイプの検証
            $this->validateFileType($file, $fileType);

            // ファイルサイズの検証
            $this->validateFileSize($file, $fileType);

            // 一意なファイル名の生成
            $filename = $this->generateUniqueFilename($file);

            // ファイルの保存
            $path = $file->storeAs($directory, $filename, 'public');

            Log::info('File uploaded successfully', [
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
            ]);

            return [
                'success' => true,
                'filename' => $file->getClientOriginalName(),
                'stored_filename' => $filename,
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
            ];

        } catch (Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'directory' => $directory,
            ]);

            throw new Exception('ファイルのアップロードに失敗しました: '.$e->getMessage());
        }
    }

    /**
     * ファイルの存在確認
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }

    /**
     * ファイルのダウンロードレスポンスを生成
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @throws Exception
     */
    public function downloadFile(string $path, string $filename)
    {
        if (! $this->fileExists($path)) {
            throw new Exception('ファイルが見つかりません。');
        }

        return Storage::disk('public')->download($path, $filename);
    }

    /**
     * Generates file display data for UI components based on specified parameters and facility context.
     * This method formats file information for consistent display throughout the application.
     * 施設コンテキストに基づいてUI表示用のファイルデータを生成します。
     *
     * @param  array  $fileData  File information array containing path and filename
     * @param  string  $category  Equipment category (electrical, gas, water, etc.)
     * @param  object  $facility  Facility model instance
     * @return array|null Formatted file display data, or null if no file exists
     */
    public function generateFileDisplayData(array $fileData, string $category, object $facility): ?array
    {
        try {
            // 必要なデータが存在するかチェック
            if (empty($fileData['path']) && empty($fileData['filename'])) {
                Log::debug('FileHandlingService: No file data provided', ['fileData' => $fileData]);

                return null;
            }

            $filename = $fileData['filename'] ?? '';
            $path = $fileData['path'] ?? '';

            // パスからファイル名を取得（ファイル名が空の場合）
            if (empty($filename) && ! empty($path)) {
                $filename = basename($path);
            }

            if (empty($filename) || empty($path)) {
                Log::debug('FileHandlingService: Missing filename or path', [
                    'filename' => $filename,
                    'path' => $path,
                    'fileData' => $fileData,
                ]);

                return null;
            }

            $fileType = $this->detectFileType($filename);
            $config = self::SUPPORTED_FILE_TYPES[$fileType] ?? self::SUPPORTED_FILE_TYPES['document'];

            // Generate appropriate download URL based on category
            $downloadUrl = '';
            if ($category === 'land-info') {
                // For land-info, determine type based on filename or path
                $type = str_contains($path, 'lease_contract') ? 'lease_contract' : 'registry';
                $downloadUrl = route('facilities.land-info.download', [
                    'facility' => $facility->id,
                    'type' => $type,
                ]);
            } else {
                // For lifeline equipment
                $type = $category === 'hvac-lighting' ? 'hvac_inspection_report' : 'inspection_report';
                $downloadUrl = route('facilities.lifeline-equipment.download-file', [
                    'facility' => $facility->id,
                    'category' => $category,
                    'type' => $type,
                ]);
            }

            $result = [
                'filename' => $filename,
                'path' => $path,
                'exists' => $this->fileExists($path),
                'download_url' => $downloadUrl,
                'icon' => $config['icon'],
                'color' => $config['color'],
                'type' => $fileType,
            ];

            Log::debug('FileHandlingService: Generated file display data', ['result' => $result]);

            return $result;

        } catch (Exception $e) {
            Log::error('FileHandlingService: Error generating file display data', [
                'error' => $e->getMessage(),
                'fileData' => $fileData,
                'category' => $category,
                'facility_id' => $facility->id ?? 'unknown',
            ]);

            return null;
        }
    }

    /**
     * ファイル削除
     */
    public function deleteFile(string $path): bool
    {
        try {
            if ($this->fileExists($path)) {
                Storage::disk('public')->delete($path);
                Log::info('File deleted successfully', ['path' => $path]);

                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('File deletion failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ファイルタイプの検証
     *
     * @throws Exception
     */
    private function validateFileType(UploadedFile $file, string $fileType): void
    {
        $config = self::SUPPORTED_FILE_TYPES[$fileType] ?? null;

        if (! $config) {
            throw new Exception('このファイル形式はサポートされていません。');
        }

        // MIMEタイプの検証
        if (! in_array($file->getClientMimeType(), $config['mime_types'])) {
            throw new Exception($this->getFileTypeErrorMessage($fileType));
        }

        // 拡張子の検証
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $config['extensions'])) {
            throw new Exception($this->getFileTypeErrorMessage($fileType));
        }
    }

    /**
     * ファイルサイズの検証
     *
     * @throws Exception
     */
    private function validateFileSize(UploadedFile $file, string $fileType): void
    {
        $config = self::SUPPORTED_FILE_TYPES[$fileType] ?? null;

        if (! $config) {
            throw new Exception('このファイル形式はサポートされていません。');
        }

        if ($file->getSize() > $config['max_size']) {
            $maxSizeMB = $config['max_size'] / (1024 * 1024);
            throw new Exception("ファイルサイズは{$maxSizeMB}MB以下にしてください。");
        }
    }

    /**
     * 一意なファイル名の生成
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        // 安全なファイル名に変換
        $safeBasename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $basename);

        return $safeBasename.'_'.time().'.'.$extension;
    }

    /**
     * ファイル名からファイルタイプを検出
     */
    private function detectFileType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        foreach (self::SUPPORTED_FILE_TYPES as $type => $config) {
            if (in_array($extension, $config['extensions'])) {
                return $type;
            }
        }

        return 'document'; // デフォルト
    }

    /**
     * ファイルタイプエラーメッセージの生成
     */
    private function getFileTypeErrorMessage(string $fileType): string
    {
        $config = self::SUPPORTED_FILE_TYPES[$fileType] ?? null;

        if (! $config) {
            return 'このファイル形式はサポートされていません。';
        }

        $extensions = implode(', ', array_map('strtoupper', $config['extensions']));

        return "{$extensions}ファイルのみアップロード可能です。";
    }

    /**
     * バリデーションルールの生成
     */
    public function getValidationRules(string $fileType = 'pdf', bool $required = false): array
    {
        $config = self::SUPPORTED_FILE_TYPES[$fileType] ?? self::SUPPORTED_FILE_TYPES['pdf'];
        $maxSizeKB = $config['max_size'] / 1024;
        $extensions = implode(',', $config['extensions']);

        $rules = [
            $required ? 'required' : 'nullable',
            'file',
            "mimes:{$extensions}",
            "max:{$maxSizeKB}",
        ];

        return $rules;
    }

    /**
     * バリデーションメッセージの生成
     */
    public function getValidationMessages(string $fieldName, string $fileType = 'pdf'): array
    {
        $config = self::SUPPORTED_FILE_TYPES[$fileType] ?? self::SUPPORTED_FILE_TYPES['pdf'];
        $maxSizeMB = $config['max_size'] / (1024 * 1024);
        $extensions = implode(', ', array_map('strtoupper', $config['extensions']));

        return [
            "{$fieldName}.required" => 'ファイルを選択してください。',
            "{$fieldName}.file" => '有効なファイルを選択してください。',
            "{$fieldName}.mimes" => "{$extensions}ファイルのみアップロード可能です。",
            "{$fieldName}.max" => "ファイルサイズは{$maxSizeMB}MB以下にしてください。",
        ];
    }
}
