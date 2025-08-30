<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    protected $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $facilities = Facility::with(['creator', 'updater'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('facilities.index', compact('facilities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('facilities.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $facility = Facility::create([
            'company_name' => $request->input('company_name'),
            'office_code' => $request->input('office_code'),
            'designation_number' => $request->input('designation_number'),
            'facility_name' => $request->input('facility_name'),
            'postal_code' => $request->input('postal_code'),
            'address' => $request->input('address'),
            'phone_number' => $request->input('phone_number'),
            'fax_number' => $request->input('fax_number'),
            'status' => 'approved', // For now, directly approve
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // Log facility creation
        $this->activityLogService->logFacilityCreated(
            $facility->id,
            $facility->facility_name,
            $request
        );

        return redirect()->route('facilities.show', $facility)
            ->with('success', '施設を登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Facility $facility)
    {
        $facility->load([
            'comments.poster', 
            'comments.assignee',
            'maintenanceHistories' => function($query) {
                $query->with('creator')->latest('maintenance_date');
            }
        ]);
        
        return view('facilities.show', compact('facility'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Facility $facility)
    {
        return view('facilities.edit', compact('facility'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Facility $facility)
    {
        $facility->update([
            'company_name' => $request->input('company_name'),
            'office_code' => $request->input('office_code'),
            'designation_number' => $request->input('designation_number'),
            'facility_name' => $request->input('facility_name'),
            'postal_code' => $request->input('postal_code'),
            'address' => $request->input('address'),
            'phone_number' => $request->input('phone_number'),
            'fax_number' => $request->input('fax_number'),
            'updated_by' => auth()->id(),
        ]);

        // Log facility update
        $this->activityLogService->logFacilityUpdated(
            $facility->id,
            $facility->facility_name,
            $request
        );

        return redirect()->route('facilities.show', $facility)
            ->with('success', '施設情報を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Facility $facility)
    {
        $facilityName = $facility->facility_name;
        $facilityId = $facility->id;
        
        $facility->delete();

        // Log facility deletion
        $this->activityLogService->logFacilityDeleted(
            $facilityId,
            $facilityName,
            request()
        );

        return redirect()->route('facilities.index')
            ->with('success', '施設を削除しました。');
    }
}
