<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of maintenance histories.
     */
    public function index(Request $request)
    {
        $query = MaintenanceHistory::with(['facility', 'creator']);

        // Filter by facility if specified
        if ($request->filled('facility_id')) {
            $query->forFacility($request->facility_id);
        }

        // Filter by date range if specified
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Search by content if specified
        if ($request->filled('search')) {
            $query->searchContent($request->search);
        }

        $maintenanceHistories = $query->latest()->paginate(20);

        // Get facilities for the filter dropdown
        $facilities = Facility::approved()
            ->select('id', 'facility_name', 'office_code')
            ->orderBy('facility_name')
            ->get();

        return view('maintenance.index', compact('maintenanceHistories', 'facilities'));
    }

    /**
     * Show the form for creating a new maintenance history.
     */
    public function create(Request $request)
    {
        // Get facilities for the dropdown
        $facilities = Facility::approved()
            ->select('id', 'facility_name', 'office_code')
            ->orderBy('facility_name')
            ->get();

        // Pre-select facility if specified in request
        $selectedFacilityId = $request->get('facility_id');

        return view('maintenance.create', compact('facilities', 'selectedFacilityId'));
    }

    /**
     * Store a newly created maintenance history in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'maintenance_date' => 'required|date',
            'content' => 'required|string',
            'cost' => 'nullable|numeric|min:0',
            'contractor' => 'nullable|string|max:255',
        ]);

        $validatedData['created_by'] = Auth::id();

        MaintenanceHistory::create($validatedData);

        return redirect()
            ->route('maintenance.index')
            ->with('success', '修繕履歴を登録しました。');
    }

    /**
     * Display the specified maintenance history.
     */
    public function show(MaintenanceHistory $maintenanceHistory)
    {
        $maintenanceHistory->load(['facility', 'creator']);

        return view('maintenance.show', compact('maintenanceHistory'));
    }

    /**
     * Show the form for editing the specified maintenance history.
     */
    public function edit(MaintenanceHistory $maintenanceHistory)
    {
        // Get facilities for the dropdown
        $facilities = Facility::approved()
            ->select('id', 'facility_name', 'office_code')
            ->orderBy('facility_name')
            ->get();

        return view('maintenance.edit', compact('maintenanceHistory', 'facilities'));
    }

    /**
     * Update the specified maintenance history in storage.
     */
    public function update(Request $request, MaintenanceHistory $maintenanceHistory)
    {
        $validatedData = $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'maintenance_date' => 'required|date',
            'content' => 'required|string',
            'cost' => 'nullable|numeric|min:0',
            'contractor' => 'nullable|string|max:255',
        ]);

        $maintenanceHistory->update($validatedData);

        return redirect()
            ->route('maintenance.show', $maintenanceHistory)
            ->with('success', '修繕履歴を更新しました。');
    }

    /**
     * Remove the specified maintenance history from storage.
     */
    public function destroy(MaintenanceHistory $maintenanceHistory)
    {
        $maintenanceHistory->delete();

        return redirect()
            ->route('maintenance.index')
            ->with('success', '修繕履歴を削除しました。');
    }

    /**
     * Get maintenance histories for a specific facility (AJAX).
     */
    public function getFacilityHistories(Facility $facility)
    {
        $histories = $facility->maintenanceHistories()
            ->with('creator')
            ->latest()
            ->get();

        return response()->json($histories);
    }
}