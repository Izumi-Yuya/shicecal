<?php

namespace App\Services;

use App\Models\File;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

/**
 * File management service for land documents
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5
 */
class FileService
{
    /**
     * Upload land document file
     * Requirements: 6.1, 6.2, 6.3
     *
     * @param Facility $facility
     * @param UploadedFile $file
     * @param string $documentType
     * @param User $user
     * @return File
     * @throws Exception
     */
    public function uploadLandDocument(Facility $facility, UploadedFile $file, string $documentType, User $user): File
    {
        try {
            DB::beginTransaction();

            // Validate file
            $this->validateLandDocument($file);

            // Generate unique filename
            $filename = $this->generateUniqueFilename($file, $facility, $documentType);

            // Store file
            $path = $this->storeLandDocument($file, $facility, $filename);

            // Create file record
            $fileRecord = File::create([
                'facility_id' => $facility->id,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'land_document_type' => $documentType,
                'uploaded_by' => $user->id,
            ]);

            DB::commit();

            Log::info('Land document uploaded successfully', [
                'file_id' => $fileRecord->id,
                'facility_id' => $facility->id,
                'document_type' => $documentType,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_by' => $user->id,
            ]);

            return $fileRecord;
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to upload land document', [
                'facility_id' => $facility->id,
                'document_type' => $documentType,
                'original_name' => $file->getClientOriginalName(),
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Upload multiple lease contract files
     * Requirements: 6.1
     *
     * @param Facility $facility
     * @param array $files
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function uploadMultipleLeaseContracts(Facility $facility, array $files, User $user): array
    {
        $uploadedFiles = [];
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $uploadedFile = $this->uploadLandDocument($facility, $file, 'lease_contract', $user);
                $uploadedFiles[] = $uploadedFile;
            } catch (Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (!empty($errors)) {
            Log::warning('Some lease contract files failed to upload', [
                'facility_id' => $facility->id,
                'errors' => $errors,
                'successful_uploads' => count($uploadedFiles),
            ]);
        }

        return [
            'uploaded_files' => $uploadedFiles,
            'errors' => $errors,
        ];
    }

    /**
     * Get land documents for a facility
     * Requirements: 6.4
     *
     * @param Facility $facility
     * @param string|null $documentType
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLandDocuments(Facility $facility, ?string $documentType = null)
    {
        $query = File::where('facility_id', $facility->id)
            ->whereNotNull('land_document_type')
            ->with(['uploader']);

        if ($documentType) {
            $query->where('land_document_type', $documentType);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Download land document
     * Requirements: 6.4
     *
     * @param File $file
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws Exception
     */
    public function downloadLandDocument(File $file, User $user)
    {
        // Verify this is a land document
        if (empty($file->land_document_type)) {
            throw new Exception('指定されたファイルは土地関連書類ではありません。');
        }

        // Check if file exists
        if (!Storage::exists($file->file_path)) {
            throw new Exception('ファイルが見つかりません。');
        }

        Log::info('Land document downloaded', [
            'file_id' => $file->id,
            'facility_id' => $file->facility_id,
            'document_type' => $file->land_document_type,
            'downloaded_by' => $user->id,
        ]);

        return Storage::download($file->file_path, $file->original_name);
    }

    /**
     * Delete land document
     * Requirements: 6.5
     *
     * @param File $file
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public function deleteLandDocument(File $file, User $user): bool
    {
        try {
            DB::beginTransaction();

            // Verify this is a land document
            if (empty($file->land_document_type)) {
                throw new Exception('指定されたファイルは土地関連書類ではありません。');
            }

            // Delete physical file
            if (Storage::exists($file->file_path)) {
                Storage::delete($file->file_path);
            }

            // Delete database record
            $file->delete();

            DB::commit();

            Log::info('Land document deleted', [
                'file_id' => $file->id,
                'facility_id' => $file->facility_id,
                'document_type' => $file->land_document_type,
                'deleted_by' => $user->id,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete land document', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Replace existing land document (for property register)
     * Requirements: 6.5
     *
     * @param Facility $facility
     * @param UploadedFile $newFile
     * @param string $documentType
     * @param User $user
     * @return File
     * @throws Exception
     */
    public function replaceLandDocument(Facility $facility, UploadedFile $newFile, string $documentType, User $user): File
    {
        try {
            DB::beginTransaction();

            // Find existing document of the same type
            $existingFile = File::where('facility_id', $facility->id)
                ->where('land_document_type', $documentType)
                ->first();

            // Delete existing file if it exists
            if ($existingFile) {
                $this->deleteLandDocument($existingFile, $user);
            }

            // Upload new file
            $newFileRecord = $this->uploadLandDocument($facility, $newFile, $documentType, $user);

            DB::commit();

            return $newFileRecord;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate land document file
     * Requirements: 6.1, 6.2
     *
     * @param UploadedFile $file
     * @return void
     * @throws Exception
     */
    protected function validateLandDocument(UploadedFile $file): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw new Exception('アップロードされたファイルが無効です。');
        }

        // Check file size (10MB limit)
        $maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if ($file->getSize() > $maxSize) {
            throw new Exception('ファイルサイズが10MBを超えています。');
        }

        // Check MIME type (PDF only)
        $allowedMimeTypes = ['application/pdf'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new Exception('PDFファイルのみアップロード可能です。');
        }

        // Check file extension
        $allowedExtensions = ['pdf'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('PDFファイルのみアップロード可能です。');
        }
    }

    /**
     * Generate unique filename for land document
     *
     * @param UploadedFile $file
     * @param Facility $facility
     * @param string $documentType
     * @return string
     */
    protected function generateUniqueFilename(UploadedFile $file, Facility $facility, string $documentType): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        return "facility_{$facility->id}_{$documentType}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Store land document file
     *
     * @param UploadedFile $file
     * @param Facility $facility
     * @param string $filename
     * @return string
     */
    protected function storeLandDocument(UploadedFile $file, Facility $facility, string $filename): string
    {
        $directory = "facilities/{$facility->id}/land_documents";
        return $file->storeAs($directory, $filename);
    }

    /**
     * Get formatted file size
     *
     * @param int $bytes
     * @return string
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get document type display name
     *
     * @param string $documentType
     * @return string
     */
    public function getDocumentTypeDisplayName(string $documentType): string
    {
        $displayNames = [
            'lease_contract' => '賃貸借契約書・覚書',
            'property_register' => '謄本',
            'other' => 'その他',
        ];

        return $displayNames[$documentType] ?? $documentType;
    }
}
