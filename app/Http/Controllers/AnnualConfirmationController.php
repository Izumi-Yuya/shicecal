<?php

namespace App\Http\Controllers;

use App\Models\AnnualConfirmation;
use App\Models\Facility;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnnualConfirmationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display annual confirmation management page
     */
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        
        $confirmations = AnnualConfirmation::with(['facility', 'requestedBy', 'facilityManager'])
            ->forYear($year)
            ->orderBy('requested_at', 'desc')
            ->paginate(20);

        $years = AnnualConfirmation::selectRaw('DISTINCT confirmation_year')
            ->orderBy('confirmation_year', 'desc')
            ->pluck('confirmation_year');

        return view('annual-confirmation.index', compact('confirmations', 'years', 'year'));
    }

    /**
     * Show form to create annual confirmation requests
     */
    public function create()
    {
        $facilities = Facility::where('status', 'approved')
            ->orderBy('facility_name')
            ->get();

        return view('annual-confirmation.create', compact('facilities'));
    }

    /**
     * Send annual confirmation requests
     */
    public function store(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $facilityIds = $request->input('facility_ids', []);
        
        if (empty($facilityIds)) {
            return redirect()->back()->with('error', '確認対象の施設を選択してください。');
        }

        DB::transaction(function () use ($year, $facilityIds) {
            foreach ($facilityIds as $facilityId) {
                $facility = Facility::findOrFail($facilityId);
                
                // Find facility manager (user with role 'viewer' associated with this facility)
                // For now, we'll use a simple approach - find any viewer user
                // In production, this would be based on proper facility-user relationships
                $facilityManager = User::where('role', 'viewer')->first();

                $confirmation = AnnualConfirmation::updateOrCreate(
                    [
                        'confirmation_year' => $year,
                        'facility_id' => $facilityId,
                    ],
                    [
                        'requested_by' => Auth::id(),
                        'facility_manager_id' => $facilityManager?->id,
                        'status' => 'pending',
                        'requested_at' => now(),
                        'responded_at' => null,
                        'resolved_at' => null,
                    ]
                );

                // Send notification to facility manager
                if ($facilityManager) {
                    $this->notificationService->sendAnnualConfirmationRequest(
                        $facilityManager,
                        $facility,
                        $year
                    );
                }
            }
        });

        return redirect()->route('annual-confirmation.index')
            ->with('success', '年次確認依頼を送信しました。');
    }

    /**
     * Show confirmation response form for facility managers
     */
    public function show(AnnualConfirmation $annualConfirmation)
    {
        // Check if user has permission to view this confirmation
        $user = Auth::user();
        $canView = $user->isAdmin() || 
                   $user->isEditor() || 
                   $user->id === $annualConfirmation->facility_manager_id;
        
        if (!$canView) {
            abort(403, 'この確認依頼にアクセスする権限がありません。');
        }

        return view('annual-confirmation.show', compact('annualConfirmation'));
    }

    /**
     * Process facility manager's response
     */
    public function respond(Request $request, AnnualConfirmation $annualConfirmation)
    {
        // Check if user is the facility manager for this confirmation
        if (Auth::id() !== $annualConfirmation->facility_manager_id) {
            abort(403, 'この確認依頼に回答する権限がありません。');
        }

        $response = $request->input('response'); // 'confirmed' or 'discrepancy'
        $discrepancyDetails = $request->input('discrepancy_details');

        if ($response === 'discrepancy' && empty($discrepancyDetails)) {
            return redirect()->back()->with('error', '相違内容を入力してください。');
        }

        $status = $response === 'confirmed' ? 'confirmed' : 'discrepancy_reported';

        $annualConfirmation->update([
            'status' => $status,
            'discrepancy_details' => $discrepancyDetails,
            'responded_at' => now(),
        ]);

        // If discrepancy reported, notify editors
        if ($status === 'discrepancy_reported') {
            $editors = User::where('role', 'editor')->get();
            foreach ($editors as $editor) {
                $this->notificationService->sendDiscrepancyNotification(
                    $editor,
                    $annualConfirmation
                );
            }
        }

        $message = $status === 'confirmed' 
            ? '確認完了を報告しました。' 
            : '相違報告を送信しました。';

        return redirect()->route('annual-confirmation.show', $annualConfirmation)
            ->with('success', $message);
    }

    /**
     * Mark discrepancy as resolved (for editors)
     */
    public function resolve(AnnualConfirmation $annualConfirmation)
    {
        if (!Auth::user()->isEditor() && !Auth::user()->isAdmin()) {
            abort(403, '相違を解決する権限がありません。');
        }

        // Only allow resolving discrepancy_reported confirmations
        if ($annualConfirmation->status !== 'discrepancy_reported') {
            return redirect()->back()->with('error', '相違報告されていない確認は解決できません。');
        }

        $annualConfirmation->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return redirect()->back()->with('success', '相違を解決済みとしてマークしました。');
    }

    /**
     * Get facilities for AJAX requests
     */
    public function getFacilities(Request $request)
    {
        $search = $request->get('search', '');
        
        $facilities = Facility::where('status', 'approved')
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('facility_name', 'like', "%{$search}%")
                      ->orWhere('office_code', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('facility_name')
            ->limit(50)
            ->get(['id', 'facility_name', 'office_code', 'company_name']);

        return response()->json($facilities);
    }
}