<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\FacilityService;
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
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'office_code' => 'required|string|max:20',
            'designation_number' => 'nullable|string|max:50',
            'facility_name' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'building_name' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
            'fax_number' => 'nullable|string|max:20',
            'toll_free_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:500',
            'opening_date' => 'nullable|date',
            'years_in_operation' => 'nullable|integer|min:0',
            'building_structure' => 'nullable|string|max:100',
            'building_floors' => 'nullable|integer|min:1',
            'paid_rooms_count' => 'nullable|integer|min:0',
            'ss_rooms_count' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'service_types' => 'nullable|array',
            'designation_renewal_date' => 'nullable|date',
        ]);

        $facility = Facility::create(array_merge($validated, [
            'status' => 'approved', // For now, directly approve
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

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
            'services',
            'comments.poster',
            'maintenanceHistories' => function ($query) {
                $query->with('creator')->latest('maintenance_date');
            },
            'landInfo'
        ]);

        $landInfo = $facility->landInfo;

        return view('facilities.show', compact('facility', 'landInfo'));
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
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'office_code' => 'required|string|max:20',
            'designation_number' => 'nullable|string|max:50',
            'facility_name' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'building_name' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
            'fax_number' => 'nullable|string|max:20',
            'toll_free_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:500',
            'opening_date' => 'nullable|date',
            'years_in_operation' => 'nullable|integer|min:0',
            'building_structure' => 'nullable|string|max:100',
            'building_floors' => 'nullable|integer|min:1',
            'paid_rooms_count' => 'nullable|integer|min:0',
            'ss_rooms_count' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'service_types' => 'nullable|array',
            'designation_renewal_date' => 'nullable|date',
        ]);

        $facility->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

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

    /**
     * Display the basic information of the facility.
     */
    public function basicInfo(Facility $facility)
    {
        return view('facilities.basic-info', compact('facility'));
    }

    /**
     * Show the form for editing basic information.
     */
    public function editBasicInfo(Facility $facility)
    {
        $facility->load('services');
        return view('facilities.edit-basic-info', compact('facility'));
    }

    /**
     * Update the basic information of the facility.
     */
    public function updateBasicInfo(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'office_code' => 'required|string|max:20',
            'designation_number' => 'nullable|string|max:50',
            'facility_name' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'building_name' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
            'fax_number' => 'nullable|string|max:20',
            'toll_free_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:500',
            'opening_date' => 'nullable|date',
            'years_in_operation' => 'nullable|integer|min:0',
            'building_structure' => 'nullable|string|max:100',
            'building_floors' => 'nullable|integer|min:1',
            'paid_rooms_count' => 'nullable|integer|min:0',
            'ss_rooms_count' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'services' => 'nullable|array',
            'services.*.service_type' => 'nullable|string|max:255',
            'services.*.renewal_start_date' => 'nullable|date',
            'services.*.renewal_end_date' => 'nullable|date|after_or_equal:services.*.renewal_start_date',
        ]);

        // 基本情報を更新（servicesを除く）
        $basicInfo = collect($validated)->except('services')->toArray();
        $facility->update(array_merge($basicInfo, [
            'updated_by' => auth()->id(),
        ]));

        // サービス情報を更新
        if (isset($validated['services'])) {
            $this->updateFacilityServices($facility, $validated['services']);
        }

        // Log facility basic info update
        $this->activityLogService->logFacilityUpdated(
            $facility->id,
            $facility->facility_name,
            $request
        );

        return redirect()->route('facilities.show', $facility)
            ->with('success', '施設基本情報を更新しました。');
    }

    /**
     * Update facility services
     */
    private function updateFacilityServices(Facility $facility, array $services)
    {
        // 既存のサービス情報を削除
        $facility->services()->delete();

        // 新しいサービス情報を保存（空でない行のみ）
        foreach ($services as $serviceData) {
            if (!empty($serviceData['service_type'])) {
                $facility->services()->create([
                    'service_type' => $serviceData['service_type'],
                    'renewal_start_date' => $serviceData['renewal_start_date'] ?? null,
                    'renewal_end_date' => $serviceData['renewal_end_date'] ?? null,
                ]);
            }
        }
    }
}
