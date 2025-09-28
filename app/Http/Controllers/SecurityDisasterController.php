<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Services\LifelineEquipmentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SecurityDisasterController extends Controller
{
    protected LifelineEquipmentService $lifelineEquipmentService;

    public function __construct(LifelineEquipmentService $lifelineEquipmentService)
    {
        $this->lifelineEquipmentService = $lifelineEquipmentService;
    }

    public function show(Facility $facility)
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);
            return view('facilities.security-disaster.show', ['facility' => $facility]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'この施設の防犯・防災情報を閲覧する権限がありません。');
        }
    }

    public function edit(Facility $facility)
    {
        try {
            $this->authorize('update', [LifelineEquipment::class, $facility]);
            return view('facilities.security-disaster.edit', ['facility' => $facility]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'この施設の防犯・防災情報を編集する権限がありません。');
        }
    }

    public function update(Request $request, Facility $facility)
    {
        try {
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            $validatedData = $request->validate([
                // 防犯カメラ・電子錠
                'security_systems.camera_lock.camera.management_company' => 'nullable|string|max:255',
                'security_systems.camera_lock.camera.model_year' => 'nullable|string|max:255',
                'security_systems.camera_lock.camera.notes' => 'nullable|string',
                'security_systems.camera_lock.lock.management_company' => 'nullable|string|max:255',
                'security_systems.camera_lock.lock.model_year' => 'nullable|string|max:255',
                'security_systems.camera_lock.lock.notes' => 'nullable|string',
                'camera_layout_pdf' => 'nullable|file|mimes:pdf|max:10240',
                'lock_layout_pdf' => 'nullable|file|mimes:pdf|max:10240',
                'delete_camera_layout_pdf' => 'nullable|boolean',
                'delete_lock_layout_pdf' => 'nullable|boolean',
                
                // 消防・防災
                'fire_disaster_prevention.basic_info.hazard_map_pdf' => 'nullable|file|mimes:pdf|max:10240',
                'fire_disaster_prevention.basic_info.evacuation_route_pdf' => 'nullable|file|mimes:pdf|max:10240',
                'fire_disaster_prevention.fire_prevention.fire_manager' => 'nullable|string|max:255',
                'fire_disaster_prevention.fire_prevention.training_date' => 'nullable|date',
                'fire_disaster_prevention.fire_prevention.training_report_pdf' => 'nullable|file|mimes:pdf|max:10240',
                'fire_disaster_prevention.fire_prevention.inspection_company' => 'nullable|string|max:255',
                'fire_disaster_prevention.fire_prevention.inspection_date' => 'nullable|date',
                'fire_disaster_prevention.fire_prevention.inspection_report_pdf' => 'nullable|file|mimes:pdf|max:10240',
                'fire_disaster_prevention.disaster_prevention.practical_training_date' => 'nullable|date',
                'fire_disaster_prevention.disaster_prevention.practical_training_report_pdf' => 'nullable|file|mimes:pdf|max:10240',
                'fire_disaster_prevention.disaster_prevention.riding_training_date' => 'nullable|date',
                'fire_disaster_prevention.disaster_prevention.riding_training_report_pdf' => 'nullable|file|mimes:pdf|max:10240',
                'fire_disaster_prevention.disaster_prevention.emergency_supplies_pdf' => 'nullable|file|mimes:pdf|max:10240',
                'fire_disaster_prevention.notes' => 'nullable|string',
                'delete_hazard_map_pdf' => 'nullable|boolean',
                'delete_evacuation_route_pdf' => 'nullable|boolean',
                'delete_fire_training_report_pdf' => 'nullable|boolean',
                'delete_fire_inspection_report_pdf' => 'nullable|boolean',
                'delete_practical_training_report_pdf' => 'nullable|boolean',
                'delete_riding_training_report_pdf' => 'nullable|boolean',
                'delete_emergency_supplies_pdf' => 'nullable|boolean',
                'active_sub_tab' => 'nullable|string',
            ]);

            DB::transaction(function () use ($facility, $validatedData, $request) {
                $lifelineEquipment = LifelineEquipment::firstOrCreate(
                    ['facility_id' => $facility->id, 'category' => 'security_disaster'],
                    ['status' => 'active', 'created_by' => auth()->id(), 'updated_by' => auth()->id()]
                );

                $lifelineEquipment->update(['updated_by' => auth()->id()]);

                $this->lifelineEquipmentService->updateSecurityDisasterEquipmentData(
                    $lifelineEquipment, $validatedData, $request->all()
                );
            });

            $activeSubTab = $request->input('active_sub_tab', 'camera-lock');
            
            // サブタブに応じて適切なハッシュフラグメントを設定
            $hashFragment = $activeSubTab === 'fire-disaster' ? 'fire-disaster' : 'camera-lock';
            
            return redirect(route('facilities.show', $facility) . '#' . $hashFragment)
                ->with('success', '防犯・防災情報を更新しました。')
                ->with('activeTab', 'security-disaster')
                ->with('activeSubTab', $activeSubTab);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'この施設の防犯・防災情報を更新する権限がありません。');
        } catch (Exception $e) {
            Log::error('Security disaster update failed', [
                'facility_id' => $facility->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', '防犯・防災情報の更新に失敗しました。')->withInput();
        }
    }

    /**
     * Download security disaster equipment file.
     */
    public function downloadFile(Facility $facility, string $type)
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            $securityDisasterEquipment = $facility->getSecurityDisasterEquipment();
            if (!$securityDisasterEquipment) {
                abort(404, '防犯・防災設備情報が見つかりません。');
            }

            $cameraLockInfo = $securityDisasterEquipment->security_systems['camera_lock'] ?? [];
            $fireDisasterInfo = $securityDisasterEquipment->fire_disaster_prevention ?? [];
            $filePath = null;
            $fileName = null;

            switch ($type) {
                // 防犯カメラ・電子錠
                case 'camera_layout':
                    $filePath = $cameraLockInfo['camera']['layout_pdf_path'] ?? null;
                    $fileName = $cameraLockInfo['camera']['layout_pdf_name'] ?? null;
                    break;
                case 'lock_layout':
                    $filePath = $cameraLockInfo['lock']['layout_pdf_path'] ?? null;
                    $fileName = $cameraLockInfo['lock']['layout_pdf_name'] ?? null;
                    break;
                
                // 消防・防災
                case 'hazard_map':
                    $filePath = $fireDisasterInfo['basic_info']['hazard_map_pdf_path'] ?? null;
                    $fileName = $fireDisasterInfo['basic_info']['hazard_map_pdf_name'] ?? null;
                    break;
                case 'evacuation_route':
                    $filePath = $fireDisasterInfo['basic_info']['evacuation_route_pdf_path'] ?? null;
                    $fileName = $fireDisasterInfo['basic_info']['evacuation_route_pdf_name'] ?? null;
                    break;
                case 'fire_training_report':
                    $filePath = $fireDisasterInfo['fire_prevention']['training_report_pdf_path'] ?? null;
                    $fileName = $fireDisasterInfo['fire_prevention']['training_report_pdf_name'] ?? null;
                    break;
                case 'fire_inspection_report':
                    $filePath = $fireDisasterInfo['fire_prevention']['inspection_report_pdf_path'] ?? null;
                    $fileName = $fireDisasterInfo['fire_prevention']['inspection_report_pdf_name'] ?? null;
                    break;
                case 'practical_training_report':
                    $filePath = $fireDisasterInfo['disaster_prevention']['practical_training_report_pdf_path'] ?? null;
                    $fileName = $fireDisasterInfo['disaster_prevention']['practical_training_report_pdf_name'] ?? null;
                    break;
                case 'riding_training_report':
                    $filePath = $fireDisasterInfo['disaster_prevention']['riding_training_report_pdf_path'] ?? null;
                    $fileName = $fireDisasterInfo['disaster_prevention']['riding_training_report_pdf_name'] ?? null;
                    break;
                case 'emergency_supplies':
                    $filePath = $fireDisasterInfo['disaster_prevention']['emergency_supplies_pdf_path'] ?? null;
                    $fileName = $fireDisasterInfo['disaster_prevention']['emergency_supplies_pdf_name'] ?? null;
                    break;
                default:
                    abort(404, '指定されたファイルタイプが無効です。');
            }

            if (!$filePath) {
                abort(404, 'ファイルが見つかりません。');
            }

            $fileHandlingService = app(\App\Services\FileHandlingService::class);
            return $fileHandlingService->downloadFile($filePath, $fileName);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'このファイルにアクセスする権限がありません。');
        } catch (Exception $e) {
            Log::error('Security disaster file download failed', [
                'facility_id' => $facility->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'ファイルのダウンロードに失敗しました。');
        }
    }
}