<?php

namespace App\Services;

use App\Models\Facility;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BatchPdfService
{
    protected SecurePdfService $securePdfService;

    public function __construct(SecurePdfService $securePdfService)
    {
        $this->securePdfService = $securePdfService;
    }

    /**
     * Generate batch PDF with progress tracking
     */
    public function generateBatchPdf(Collection $facilities, array $options = []): array
    {
        $batchId = $this->generateBatchId();
        $useSecure = $options['secure'] ?? true;
        $totalFacilities = $facilities->count();
        
        // Initialize progress tracking
        $this->initializeProgress($batchId, $totalFacilities);
        
        try {
            $zipFilename = $this->generateZipFilename($useSecure);
            $zipPath = storage_path('app/temp/' . $zipFilename);
            
            // Ensure temp directory exists
            if (!file_exists(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            $zip = new ZipArchive;
            
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('ZIP ファイルの作成に失敗しました。');
            }

            $processedCount = 0;
            $errors = [];

            foreach ($facilities as $facility) {
                try {
                    // Update progress
                    $this->updateProgress($batchId, $processedCount, $totalFacilities, $facility->facility_name);
                    
                    if ($useSecure) {
                        $pdfContent = $this->securePdfService->generateSecureFacilityPdf($facility, $options);
                        $pdfFilename = $this->securePdfService->generateSecureFilename($facility);
                    } else {
                        // Use DomPDF for standard PDF
                        $pdfContent = $this->generateStandardPdf($facility);
                        $pdfFilename = $this->generateStandardFilename($facility);
                    }
                    
                    $zip->addFromString($pdfFilename, $pdfContent);
                    $processedCount++;
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'facility' => $facility->facility_name,
                        'error' => $e->getMessage()
                    ];
                    Log::error('Batch PDF generation error', [
                        'facility_id' => $facility->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $zip->close();
            
            // Complete progress
            $this->completeProgress($batchId, $processedCount, $totalFacilities, $errors);
            
            return [
                'success' => true,
                'batch_id' => $batchId,
                'zip_path' => $zipPath,
                'zip_filename' => $zipFilename,
                'processed_count' => $processedCount,
                'total_count' => $totalFacilities,
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            $this->failProgress($batchId, $e->getMessage());
            
            return [
                'success' => false,
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get batch progress
     */
    public function getBatchProgress(string $batchId): array
    {
        return Cache::get("batch_pdf_progress_{$batchId}", [
            'status' => 'not_found',
            'message' => 'バッチが見つかりません'
        ]);
    }

    /**
     * Generate standard PDF using DomPDF
     */
    private function generateStandardPdf(Facility $facility): string
    {
        $data = [
            'facility' => $facility,
            'generated_at' => now(),
            'generated_by' => Auth::user(),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('export.pdf.facility-report', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->output();
    }

    /**
     * Generate standard filename
     */
    private function generateStandardFilename(Facility $facility): string
    {
        $safeFilename = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $facility->facility_name);
        return "facility_report_{$facility->office_code}_{$safeFilename}_" . now()->format('Y-m-d') . '.pdf';
    }

    /**
     * Generate batch ID
     */
    private function generateBatchId(): string
    {
        return 'batch_' . Auth::id() . '_' . now()->format('YmdHis') . '_' . substr(uniqid(), -6);
    }

    /**
     * Generate ZIP filename
     */
    private function generateZipFilename(bool $useSecure): string
    {
        $prefix = $useSecure ? 'secure_' : '';
        return $prefix . 'facility_reports_' . now()->format('Y-m-d_H-i-s') . '.zip';
    }

    /**
     * Initialize progress tracking
     */
    private function initializeProgress(string $batchId, int $totalCount): void
    {
        $progress = [
            'status' => 'processing',
            'processed_count' => 0,
            'total_count' => $totalCount,
            'current_facility' => '',
            'started_at' => now()->toISOString(),
            'errors' => []
        ];
        
        Cache::put("batch_pdf_progress_{$batchId}", $progress, now()->addHours(2));
    }

    /**
     * Update progress
     */
    private function updateProgress(string $batchId, int $processedCount, int $totalCount, string $currentFacility): void
    {
        $progress = Cache::get("batch_pdf_progress_{$batchId}", []);
        
        $progress['processed_count'] = $processedCount;
        $progress['current_facility'] = $currentFacility;
        $progress['percentage'] = round(($processedCount / $totalCount) * 100, 1);
        
        Cache::put("batch_pdf_progress_{$batchId}", $progress, now()->addHours(2));
    }

    /**
     * Complete progress
     */
    private function completeProgress(string $batchId, int $processedCount, int $totalCount, array $errors): void
    {
        $progress = Cache::get("batch_pdf_progress_{$batchId}", []);
        
        $progress['status'] = 'completed';
        $progress['processed_count'] = $processedCount;
        $progress['percentage'] = 100;
        $progress['completed_at'] = now()->toISOString();
        $progress['errors'] = $errors;
        
        Cache::put("batch_pdf_progress_{$batchId}", $progress, now()->addHours(24));
    }

    /**
     * Fail progress
     */
    private function failProgress(string $batchId, string $error): void
    {
        $progress = Cache::get("batch_pdf_progress_{$batchId}", []);
        
        $progress['status'] = 'failed';
        $progress['error'] = $error;
        $progress['failed_at'] = now()->toISOString();
        
        Cache::put("batch_pdf_progress_{$batchId}", $progress, now()->addHours(24));
    }

    /**
     * Clean up old batch data
     */
    public function cleanupOldBatches(): int
    {
        // This would typically be run as a scheduled job
        // For now, we'll just return 0 as a placeholder
        return 0;
    }
}