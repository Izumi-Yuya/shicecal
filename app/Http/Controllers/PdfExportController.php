<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Services\SecurePdfService;
use App\Services\BatchPdfService;
use App\Services\ActivityLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use ZipArchive;
use Illuminate\Support\Facades\Storage;

class PdfExportController extends Controller
{
    protected SecurePdfService $securePdfService;
    protected BatchPdfService $batchPdfService;
    protected ActivityLogService $activityLogService;

    public function __construct(SecurePdfService $securePdfService, BatchPdfService $batchPdfService, ActivityLogService $activityLogService)
    {
        $this->securePdfService = $securePdfService;
        $this->batchPdfService = $batchPdfService;
        $this->activityLogService = $activityLogService;
    }
    /**
     * Display PDF export page
     */
    public function index()
    {
        // Get facilities based on user permissions
        $facilities = $this->getFacilitiesForUser();

        return view('export.pdf.index', compact('facilities'));
    }

    /**
     * Generate PDF for a single facility
     */
    public function generateSingle(Request $request, Facility $facility)
    {
        // Check if user has permission to view this facility
        if (!$this->canViewFacility($facility)) {
            abort(403, 'この施設の情報を閲覧する権限がありません。');
        }

        // Only export approved information
        if (!$facility->isApproved()) {
            abort(403, '承認済みの施設情報のみPDF出力可能です。');
        }

        // Check if secure PDF is requested
        $useSecure = $request->get('secure', true);

        if ($useSecure) {
            return $this->generateSecureSingle($facility);
        }

        $pdf = $this->generateFacilityPdf($facility);

        $filename = $this->generatePdfFilename($facility);

        // Log PDF export
        $this->activityLogService->logPdfExported([$facility->id], $request);

        return $pdf->download($filename);
    }

    /**
     * Generate secure PDF for a single facility
     */
    public function generateSecureSingle(Facility $facility)
    {
        $pdfContent = $this->securePdfService->generateSecureFacilityPdf($facility);
        $filename = $this->securePdfService->generateSecureFilename($facility);

        // Log secure PDF export
        $this->activityLogService->logPdfExported([$facility->id], request());

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Generate PDF for multiple facilities
     */
    public function generateBatch(Request $request)
    {
        $facilityIds = $request->input('facility_ids', []);
        $useSecure = $request->input('secure', true);

        if (empty($facilityIds)) {
            return back()->with('error', '出力する施設を選択してください。');
        }

        $facilities = Facility::whereIn('id', $facilityIds)
            ->approved()
            ->with('landInfo')
            ->get();

        // Filter facilities based on user permissions
        $facilities = $facilities->filter(function ($facility) {
            return $this->canViewFacility($facility);
        });

        if ($facilities->isEmpty()) {
            return back()->with('error', '出力可能な施設がありません。');
        }

        if ($facilities->count() === 1) {
            // Single facility - direct PDF download
            $facility = $facilities->first();

            if ($useSecure) {
                return $this->generateSecureSingle($facility);
            }

            $pdf = $this->generateFacilityPdf($facility);
            $filename = $this->generatePdfFilename($facility);

            // Log single facility PDF export
            $this->activityLogService->logPdfExported([$facility->id], $request);

            return $pdf->download($filename);
        }

        // Multiple facilities - use batch service for better progress tracking
        return $this->generateBatchWithProgress($facilities, $useSecure);
    }

    /**
     * Generate batch PDF with progress tracking
     */
    private function generateBatchWithProgress($facilities, bool $useSecure = true)
    {
        $options = ['secure' => $useSecure];
        $result = $this->batchPdfService->generateBatchPdf($facilities, $options);

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        // Log batch PDF export
        $facilityIds = $facilities->pluck('id')->toArray();
        $this->activityLogService->logPdfExported($facilityIds, request());

        $response = response()->download($result['zip_path'], $result['zip_filename'])
            ->deleteFileAfterSend(true);

        // Add batch information to session for potential progress tracking
        session()->flash('batch_info', [
            'batch_id' => $result['batch_id'],
            'processed_count' => $result['processed_count'],
            'total_count' => $result['total_count'],
            'errors' => $result['errors']
        ]);

        return $response;
    }

    /**
     * Get batch progress (AJAX endpoint)
     */
    public function getBatchProgress(Request $request, string $batchId)
    {
        $progress = $this->batchPdfService->getBatchProgress($batchId);

        return response()->json($progress);
    }

    /**
     * Generate PDF for a single facility
     */
    private function generateFacilityPdf(Facility $facility)
    {
        // Load land info if not already loaded
        if (!$facility->relationLoaded('landInfo')) {
            $facility->load('landInfo');
        }

        $data = [
            'facility' => $facility,
            'landInfo' => $facility->landInfo,
            'generated_at' => now(),
            'generated_by' => Auth::user(),
        ];

        $pdf = Pdf::loadView('export.pdf.facility-report', $data);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    /**
     * Generate ZIP file containing multiple PDFs
     */
    private function generateBatchZip($facilities, bool $useSecure = true)
    {
        $zipFilename = ($useSecure ? 'secure_' : '') . 'facility_reports_' . now()->format('Y-m-d_H-i-s') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFilename);

        // Ensure temp directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($facilities as $facility) {
                if ($useSecure) {
                    $pdfContent = $this->securePdfService->generateSecureFacilityPdf($facility);
                    $pdfFilename = $this->securePdfService->generateSecureFilename($facility);
                } else {
                    $pdf = $this->generateFacilityPdf($facility);
                    $pdfContent = $pdf->output();
                    $pdfFilename = $this->generatePdfFilename($facility);
                }

                $zip->addFromString($pdfFilename, $pdfContent);
            }

            $zip->close();

            return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'ZIP ファイルの作成に失敗しました。');
    }

    /**
     * Generate PDF filename for a facility
     */
    private function generatePdfFilename(Facility $facility): string
    {
        $safeFilename = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $facility->facility_name);
        return "facility_report_{$facility->office_code}_{$safeFilename}_" . now()->format('Y-m-d') . '.pdf';
    }

    /**
     * Get facilities based on user permissions
     */
    private function getFacilitiesForUser()
    {
        $user = Auth::user();

        // For now, return all approved facilities with land info
        // This will be enhanced with proper permission checking in later tasks
        return Facility::approved()->with('landInfo')->orderBy('facility_name')->get();
    }

    /**
     * Check if user can view a specific facility
     */
    private function canViewFacility(Facility $facility): bool
    {
        // For now, allow all authenticated users to view approved facilities
        // This will be enhanced with proper permission checking in later tasks
        return true; // Allow access for testing, will be restricted to approved facilities in the generate methods
    }
}
