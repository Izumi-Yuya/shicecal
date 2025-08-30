<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceSearchFavorite;
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

        // Get user's search favorites
        $searchFavorites = MaintenanceSearchFavorite::forUser(Auth::id())
            ->with('facility')
            ->orderBy('name')
            ->get();

        return view('maintenance.index', compact('maintenanceHistories', 'facilities', 'searchFavorites'));
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

    /**
     * Save search conditions as favorite.
     */
    public function saveSearchFavorite(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'facility_id' => 'nullable|exists:facilities,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search_content' => 'nullable|string|max:255',
        ]);

        $validatedData['user_id'] = Auth::id();

        $favorite = MaintenanceSearchFavorite::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => '検索条件を保存しました。',
            'favorite' => $favorite->load('facility')
        ]);
    }

    /**
     * Load search favorite.
     */
    public function loadSearchFavorite($favoriteId)
    {
        $favorite = MaintenanceSearchFavorite::find($favoriteId);
        
        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りが見つかりません。'
            ], 404);
        }

        // Check if the favorite belongs to the current user
        if ($favorite->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'アクセス権限がありません。'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'favorite' => $favorite->load('facility')
        ]);
    }

    /**
     * Update search favorite.
     */
    public function updateSearchFavorite(Request $request, $favoriteId)
    {
        $favorite = MaintenanceSearchFavorite::find($favoriteId);
        
        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りが見つかりません。'
            ], 404);
        }

        // Check if the favorite belongs to the current user
        if ($favorite->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'アクセス権限がありません。'
            ], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'facility_id' => 'nullable|exists:facilities,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search_content' => 'nullable|string|max:255',
        ]);

        $favorite->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => '検索条件を更新しました。',
            'favorite' => $favorite->load('facility')
        ]);
    }

    /**
     * Delete search favorite.
     */
    public function deleteSearchFavorite($favoriteId)
    {
        $favorite = MaintenanceSearchFavorite::find($favoriteId);
        
        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りが見つかりません。'
            ], 404);
        }

        // Check if the favorite belongs to the current user
        if ($favorite->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'アクセス権限がありません。'
            ], 403);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => '検索条件を削除しました。'
        ]);
    }

    /**
     * Get user's search favorites.
     */
    public function getSearchFavorites()
    {
        $favorites = MaintenanceSearchFavorite::forUser(Auth::id())
            ->with('facility')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'favorites' => $favorites
        ]);
    }
}