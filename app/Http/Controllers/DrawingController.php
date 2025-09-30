<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\FacilityDrawing;
use App\Services\DrawingService;
use App\Services\ActivityLogService;
use App\Services\FileHandlingService;
use App\Http\Requests\DrawingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class DrawingController extends Controller
{
    protected DrawingService $drawingService;
    protected ActivityLogService $activityLogService;
    protected FileHandlingService $fileHandlingService;

    public function __construct(
        DrawingService $drawingService,
        ActivityLogService $activityLogService,
        FileHandlingService $fileHandlingService
    ) {
        $this->drawingService = $drawingService;
        $this->activityLogService = $activityLogService;
        $this->fileHandlingService = $fileHandlingService;
    }

    /**
     * 図面編集画面を表示
     */
    public function edit(Facility $facility)
    {
        try {
            $this->authorize('update', [FacilityDrawing::class, $facility]);

            $drawing = $this->drawingService->getDrawing($facility);
            $drawingsData = [];
            
            if ($drawing) {
                $drawingsData = $this->drawingService->formatDrawingDataForDisplay($drawing);
            }

            return view('facilities.drawings.edit', compact('facility', 'drawingsData'));

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->route('facilities.show', $facility)
                ->with('error', 'この施設の図面を編集する権限がありません。');
        }
    }

    /**
     * 図面データを更新
     */
    public function update(DrawingRequest $request, Facility $facility)
    {
        try {
            $this->authorize('update', [FacilityDrawing::class, $facility]);

            $validated = $request->validated();
            $user = auth()->user();

            $drawing = $this->drawingService->createOrUpdateDrawing($facility, $validated, $user);

            $this->activityLogService->logFacilityUpdated(
                $facility->id,
                $facility->facility_name . ' - 図面',
                $request
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '図面を更新しました。',
                    'drawing' => $drawing,
                ]);
            }

            return redirect()
                ->route('facilities.show', $facility)
                ->with('success', '図面を更新しました。')
                ->with('activeTab', 'drawings');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この施設の図面を編集する権限がありません。'
                ], 403);
            }

            return redirect()->back()
                ->with('error', 'この施設の図面を編集する権限がありません。');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withErrors($e->validator)->withInput();

        } catch (Exception $e) {
            Log::error('Drawing update failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'システムエラーが発生しました。'
                ], 500);
            }

            return back()
                ->with('error', 'システムエラーが発生しました。管理者にお問い合わせください。')
                ->withInput();
        }
    }

    /**
     * 図面ファイルをダウンロード
     */
    public function downloadFile(Facility $facility, string $type)
    {
        try {
            $this->authorize('view', [FacilityDrawing::class, $facility]);

            $drawing = $facility->drawing;
            if (!$drawing) {
                abort(404, '図面情報が見つかりません。');
            }

            $filePath = null;
            $fileName = null;

            // 建物図面のチェック
            $buildingDrawings = $drawing->building_drawings;
            if (isset($buildingDrawings[$type])) {
                $filePath = $buildingDrawings[$type]['path'];
                $fileName = $buildingDrawings[$type]['filename'];
            }

            // 設備図面のチェック
            if (!$filePath) {
                $equipmentDrawings = $drawing->equipment_drawings;
                if (isset($equipmentDrawings[$type])) {
                    $filePath = $equipmentDrawings[$type]['path'];
                    $fileName = $equipmentDrawings[$type]['filename'];
                }
            }

            // 引き渡し図面のチェック
            if (!$filePath) {
                // 施工図面一式（1行目固定）
                if ($type === 'construction_drawings') {
                    $filePath = $drawing->construction_drawings_path;
                    $fileName = $drawing->construction_drawings_filename;
                }
                // 追加引き渡し図面（2行目以降）
                elseif (strpos($type, 'handover_additional_') === 0) {
                    $index = str_replace('handover_additional_', '', $type);
                    $handoverDrawings = $drawing->handover_drawings ?? [];
                    if (isset($handoverDrawings['additional'][$index])) {
                        $additionalDrawing = $handoverDrawings['additional'][$index];
                        $filePath = $additionalDrawing['path'] ?? null;
                        $fileName = $additionalDrawing['filename'] ?? null;
                    }
                }
            }

            if (!$filePath) {
                abort(404, '指定された図面ファイルが見つかりません。');
            }

            return $this->fileHandlingService->downloadFile($filePath, $fileName);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'この図面ファイルにアクセスする権限がありません。');
        } catch (Exception $e) {
            Log::error('Drawing file download failed', [
                'facility_id' => $facility->id,
                'type' => $type,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            abort(500, 'ファイルのダウンロードに失敗しました。');
        }
    }
}