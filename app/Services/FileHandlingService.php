<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 統一されたファイル処理サービス
 *
 * Unified file handling service for the facility management system.
 * Provides secure file upload, download, and management operations
 * with comprehensive validation and error handling.
 *
 * Features:
 * - File type and size validation with configurable limits
 * - Unique filename generation to prevent naming conflicts
 * - Secure file storage with proper directory structure
 * - Comprehensive error logging and exception handling
 * - Support for PDF, image, and document file types
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
            'mime_types' => [
                'application/pdf', 
                'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ],
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'max_size' => 10 * 1024 * 1024, // 10MB
            'icon' => 'fas fa-file-alt',
            'color' => 'text-primary',
        ],
        // ドキュメント管理用の拡張ファイルタイプ定義
        'office_document' => [
            'mime_types' => [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ],
            'extensions' => ['doc', 'docx'],
            'max_size' => 15 * 1024 * 1024, // 15MB
            'icon' => 'fas fa-file-word',
            'color' => 'text-primary',
        ],
        'spreadsheet' => [
            'mime_types' => [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ],
            'extensions' => ['xls', 'xlsx'],
            'max_size' => 15 * 1024 * 1024, // 15MB
            'icon' => 'fas fa-file-excel',
            'color' => 'text-success',
        ],
        'facility_document' => [
            'mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png'
            ],
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
            'max_size' => 20 * 1024 * 1024, // 20MB for facility documents
            'icon' => 'fas fa-folder-open',
            'color' => 'text-warning',
        ],
    ];

    /**
     * ドキュメント管理用のストレージディスクを取得
     */
    public function getDocumentStorageDisk(): string
    {
        return config('app.env') === 'testing' ? 'public' : 
               (config('filesystems.default_document_disk', 'public'));
    }

    /**
     * 環境別ストレージ設定の取得
     */
    public function getStorageConfig(): array
    {
        $disk = $this->getDocumentStorageDisk();
        
        return [
            'disk' => $disk,
            'is_s3' => $disk === 'documents_s3',
            'base_path' => $disk === 'documents_s3' ? '' : 'documents',
        ];
    }

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

            // 環境別ストレージ設定の取得
            $storageConfig = $this->getStorageConfig();
            $disk = $storageConfig['disk'];
            
            // ディレクトリパスの調整
            $fullDirectory = $storageConfig['base_path'] ? 
                $storageConfig['base_path'] . '/' . $directory : 
                $directory;

            // ファイルの保存
            $path = $file->storeAs($fullDirectory, $filename, $disk);

            Log::info('File uploaded successfully', [
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'disk' => $disk,
            ]);

            return [
                'success' => true,
                'filename' => $file->getClientOriginalName(),
                'stored_filename' => $filename,
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'disk' => $disk,
            ];

        } catch (Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'directory' => $directory,
            ]);

            throw new Exception('ファイルのアップロードに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ファイルの存在確認
     */
    public function fileExists(string $path, ?string $disk = null): bool
    {
        $disk = $disk ?? $this->getDocumentStorageDisk();
        return Storage::disk($disk)->exists($path);
    }

    /**
     * ファイルのダウンロードレスポンスを生成
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @throws Exception
     */
    public function downloadFile(string $path, string $filename, ?string $disk = null)
    {
        $disk = $disk ?? $this->getDocumentStorageDisk();
        
        if (! $this->fileExists($path, $disk)) {
            throw new Exception('ファイルが見つかりません。');
        }

        return Storage::disk($disk)->download($path, $filename);
    }

    /**
     * Generates file display data for UI components based on specified parameters and facility context.
     * This method formats file information for consistent display throughout the application.
     *
     * @param  array  $fileData  File information array containing path and filename
     * @param  string  $category  Equipment category (electrical, gas, water, etc.) or 'document' for document management
     * @param  object  $facility  Facility model instance
     * @param  array  $options  Additional options for URL generation
     * @return array|null Formatted file display data, or null if no file exists
     */
    public function generateFileDisplayData(array $fileData, string $category, object $facility, array $options = []): ?array
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
            if ($category === 'document') {
                // For document management system
                if (isset($options['file_id'])) {
                    $downloadUrl = route('facilities.documents.files.download', [
                        'facility' => $facility->id,
                        'file' => $options['file_id'],
                    ]);
                }
            } elseif ($category === 'land-info') {
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

            // ファイルサイズの取得（可能な場合）
            $fileSize = null;
            $formattedSize = null;
            if (isset($fileData['size'])) {
                $fileSize = $fileData['size'];
                $formattedSize = $this->formatFileSize($fileSize);
            } elseif ($this->fileExists($path)) {
                try {
                    $disk = $this->getDocumentStorageDisk();
                    $fileSize = Storage::disk($disk)->size($path);
                    $formattedSize = $this->formatFileSize($fileSize);
                } catch (Exception $e) {
                    Log::debug('Could not get file size', ['path' => $path, 'error' => $e->getMessage()]);
                }
            }

            $result = [
                'filename' => $filename,
                'path' => $path,
                'exists' => $this->fileExists($path),
                'download_url' => $downloadUrl,
                'icon' => $config['icon'],
                'color' => $config['color'],
                'type' => $fileType,
                'size' => $fileSize,
                'formatted_size' => $formattedSize,
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
    public function deleteFile(string $path, ?string $disk = null): bool
    {
        try {
            $disk = $disk ?? $this->getDocumentStorageDisk();
            
            if ($this->fileExists($path, $disk)) {
                Storage::disk($disk)->delete($path);
                Log::info('File deleted successfully', ['path' => $path, 'disk' => $disk]);

                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('File deletion failed', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * バッチファイル削除
     */
    public function deleteFiles(array $paths, ?string $disk = null): array
    {
        $disk = $disk ?? $this->getDocumentStorageDisk();
        $results = [];
        
        foreach ($paths as $path) {
            $results[$path] = $this->deleteFile($path, $disk);
        }
        
        Log::info('Batch file deletion completed', [
            'total_files' => count($paths),
            'successful' => count(array_filter($results)),
            'failed' => count($paths) - count(array_filter($results)),
            'disk' => $disk,
        ]);
        
        return $results;
    }

    /**
     * ファイル移動
     */
    public function moveFile(string $fromPath, string $toPath, ?string $disk = null): bool
    {
        try {
            $disk = $disk ?? $this->getDocumentStorageDisk();
            
            if (!$this->fileExists($fromPath, $disk)) {
                throw new Exception('移動元ファイルが見つかりません。');
            }
            
            // 移動先ディレクトリの作成
            $toDirectory = dirname($toPath);
            if (!Storage::disk($disk)->exists($toDirectory)) {
                Storage::disk($disk)->makeDirectory($toDirectory);
            }
            
            $result = Storage::disk($disk)->move($fromPath, $toPath);
            
            if ($result) {
                Log::info('File moved successfully', [
                    'from' => $fromPath,
                    'to' => $toPath,
                    'disk' => $disk,
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('File move failed', [
                'from' => $fromPath,
                'to' => $toPath,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * ディレクトリ作成
     */
    public function createDirectory(string $path, ?string $disk = null): bool
    {
        try {
            $disk = $disk ?? $this->getDocumentStorageDisk();
            
            if (Storage::disk($disk)->exists($path)) {
                return true; // Already exists
            }
            
            $result = Storage::disk($disk)->makeDirectory($path);
            
            Log::info('Directory created successfully', [
                'path' => $path,
                'disk' => $disk,
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Directory creation failed', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * ディレクトリ削除（中身も含めて）
     */
    public function deleteDirectory(string $path, ?string $disk = null): bool
    {
        try {
            $disk = $disk ?? $this->getDocumentStorageDisk();
            
            if (!Storage::disk($disk)->exists($path)) {
                return true; // Already doesn't exist
            }
            
            $result = Storage::disk($disk)->deleteDirectory($path);
            
            Log::info('Directory deleted successfully', [
                'path' => $path,
                'disk' => $disk,
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Directory deletion failed', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * ディレクトリ内のファイル一覧取得
     */
    public function listFiles(string $directory, ?string $disk = null): array
    {
        try {
            $disk = $disk ?? $this->getDocumentStorageDisk();
            
            if (!Storage::disk($disk)->exists($directory)) {
                return [];
            }
            
            $files = Storage::disk($disk)->files($directory);
            $result = [];
            
            foreach ($files as $file) {
                $result[] = [
                    'path' => $file,
                    'name' => basename($file),
                    'size' => Storage::disk($disk)->size($file),
                    'last_modified' => Storage::disk($disk)->lastModified($file),
                    'mime_type' => Storage::disk($disk)->mimeType($file),
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('File listing failed', [
                'directory' => $directory,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * ストレージ使用量の取得
     */
    public function getStorageUsage(string $directory, ?string $disk = null): array
    {
        try {
            $disk = $disk ?? $this->getDocumentStorageDisk();
            
            if (!Storage::disk($disk)->exists($directory)) {
                return [
                    'total_size' => 0,
                    'file_count' => 0,
                    'folder_count' => 0,
                ];
            }
            
            $files = Storage::disk($disk)->allFiles($directory);
            $directories = Storage::disk($disk)->allDirectories($directory);
            
            $totalSize = 0;
            foreach ($files as $file) {
                $totalSize += Storage::disk($disk)->size($file);
            }
            
            return [
                'total_size' => $totalSize,
                'file_count' => count($files),
                'folder_count' => count($directories),
                'formatted_size' => $this->formatFileSize($totalSize),
            ];
            
        } catch (Exception $e) {
            Log::error('Storage usage calculation failed', [
                'directory' => $directory,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'total_size' => 0,
                'file_count' => 0,
                'folder_count' => 0,
                'formatted_size' => '0 B',
            ];
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
            throw new Exception('指定されたファイル形式はサポートされていません。');
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
            throw new Exception('指定されたファイル形式はサポートされていません。');
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

        // より具体的なタイプを優先
        if (in_array($extension, ['doc', 'docx'])) {
            return 'office_document';
        }
        
        if (in_array($extension, ['xls', 'xlsx'])) {
            return 'spreadsheet';
        }
        
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return 'image';
        }
        
        if ($extension === 'pdf') {
            return 'pdf';
        }

        foreach (self::SUPPORTED_FILE_TYPES as $type => $config) {
            if (in_array($extension, $config['extensions'])) {
                return $type;
            }
        }

        return 'document'; // デフォルト
    }

    /**
     * ファイルサイズのフォーマット
     */
    public function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $index = floor($base);
        
        return round(pow(1024, $base - $index), 2) . ' ' . $units[$index];
    }

    /**
     * ファイルタイプエラーメッセージの生成
     */
    private function getFileTypeErrorMessage(string $fileType): string
    {
        $config = self::SUPPORTED_FILE_TYPES[$fileType] ?? null;

        if (! $config) {
            return '指定されたファイル形式はサポートされていません。';
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
