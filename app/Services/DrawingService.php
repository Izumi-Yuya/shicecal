<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\FacilityDrawing;
use App\Services\FileHandlingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DrawingService
{
    protected FileHandlingService $fileHandlingService;

    public function __construct(FileHandlingService $fileHandlingService)
    {
        $this->fileHandlingService = $fileHandlingService;
    }

    /**
     * 施設の図面情報を取得
     */
    public function getDrawing(Facility $facility): ?FacilityDrawing
    {
        return $facility->drawing;
    }

    /**
     * 図面情報を作成または更新
     */
    public function createOrUpdateDrawing(Facility $facility, array $data, $user): FacilityDrawing
    {
        try {
            DB::beginTransaction();

            $drawing = $facility->drawing ?? new FacilityDrawing(['facility_id' => $facility->id]);

            // 建物図面の処理
            if (isset($data['building_drawings'])) {
                $this->processBuildingDrawings($drawing, $data['building_drawings']);
            }

            // 設備図面の処理
            if (isset($data['equipment_drawings'])) {
                $this->processEquipmentDrawings($drawing, $data['equipment_drawings']);
            }

            // 追加図面の処理
            if (isset($data['additional_building_drawings'])) {
                $this->processAdditionalDrawings($drawing, $data['additional_building_drawings'], 'building');
            }

            if (isset($data['additional_equipment_drawings'])) {
                $this->processAdditionalDrawings($drawing, $data['additional_equipment_drawings'], 'equipment');
            }

            // 引き渡し図面の処理
            if (isset($data['handover_drawings']) || isset($data['handover_drawings_notes'])) {
                $handoverData = [
                    'handover_drawings' => $data['handover_drawings'] ?? [],
                    'notes' => $data['handover_drawings_notes'] ?? null,
                ];
                $this->processHandoverDrawings($drawing, $handoverData);
            }

            // 備考の更新
            if (isset($data['notes'])) {
                $drawing->notes = $data['notes'];
            }

            $drawing->save();

            DB::commit();

            Log::info('Drawing updated successfully', [
                'facility_id' => $facility->id,
                'user_id' => $user->id,
                'drawing_id' => $drawing->id,
            ]);

            return $drawing;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Drawing update failed', [
                'facility_id' => $facility->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 建物図面を処理
     */
    private function processBuildingDrawings(FacilityDrawing $drawing, array $buildingDrawings): void
    {
        $buildingTypes = [
            'floor_plan' => 'building-drawings/floor-plans',
            'site_plan' => 'building-drawings/site-plans',
            'elevation' => 'building-drawings/elevations',
            'development' => 'building-drawings/developments',
            'area_calculation' => 'building-drawings/area-calculations',
        ];

        foreach ($buildingTypes as $type => $directory) {
            if (isset($buildingDrawings[$type])) {
                $this->processDrawingFile($drawing, $type, $buildingDrawings[$type], $directory);
            }

            // ファイル削除処理
            $deleteField = "delete_{$type}";
            if (isset($buildingDrawings[$deleteField]) && $buildingDrawings[$deleteField] === '1') {
                $this->deleteDrawingFile($drawing, $type);
            }
        }
    }

    /**
     * 設備図面を処理
     */
    private function processEquipmentDrawings(FacilityDrawing $drawing, array $equipmentDrawings): void
    {
        $equipmentTypes = [
            'electrical_equipment' => 'equipment-drawings/electrical',
            'lighting_equipment' => 'equipment-drawings/lighting',
            'hvac_equipment' => 'equipment-drawings/hvac',
            'plumbing_equipment' => 'equipment-drawings/plumbing',
            'kitchen_equipment' => 'equipment-drawings/kitchen',
        ];

        foreach ($equipmentTypes as $type => $directory) {
            if (isset($equipmentDrawings[$type])) {
                $this->processDrawingFile($drawing, $type, $equipmentDrawings[$type], $directory);
            }

            // ファイル削除処理
            $deleteField = "delete_{$type}";
            if (isset($equipmentDrawings[$deleteField]) && $equipmentDrawings[$deleteField] === '1') {
                $this->deleteDrawingFile($drawing, $type);
            }
        }
    }

    /**
     * 追加図面を処理
     */
    private function processAdditionalDrawings(FacilityDrawing $drawing, array $additionalDrawings, string $type): void
    {
        $field = $type === 'building' ? 'additional_building_drawings' : 'additional_equipment_drawings';
        $directory = $type === 'building' ? 'building-drawings/additional' : 'equipment-drawings/additional';
        
        $existing = $drawing->$field ?? [];

        foreach ($additionalDrawings as $key => $drawingData) {
            if (isset($drawingData['file']) && $drawingData['file'] instanceof UploadedFile) {
                // 既存ファイル削除
                if (isset($existing[$key]['path'])) {
                    $this->fileHandlingService->deleteFile($existing[$key]['path']);
                }

                // 新しいファイルアップロード
                $uploadResult = $this->handleFileUpload($drawingData['file'], $directory);
                if ($uploadResult) {
                    $existing[$key] = [
                        'title' => $drawingData['title'] ?? '',
                        'filename' => $uploadResult['filename'],
                        'path' => $uploadResult['path'],
                    ];
                }
            } elseif (isset($drawingData['title'])) {
                // タイトルのみ更新
                if (isset($existing[$key])) {
                    $existing[$key]['title'] = $drawingData['title'];
                }
            }

            // 削除処理
            if (isset($drawingData['delete']) && $drawingData['delete'] === '1') {
                if (isset($existing[$key]['path'])) {
                    $this->fileHandlingService->deleteFile($existing[$key]['path']);
                }
                unset($existing[$key]);
            }
        }

        $drawing->$field = $existing;
    }

    /**
     * 引き渡し図面を処理
     */
    private function processHandoverDrawings(FacilityDrawing $drawing, array $handoverData): void
    {
        $handoverDrawings = $handoverData['handover_drawings'] ?? [];
        $existing = $drawing->handover_drawings ?? [];

        // 施工図面一式の処理（1行目固定）
        if (isset($handoverDrawings['construction_drawings'])) {
            $this->processDrawingFile($drawing, 'construction_drawings', $handoverDrawings['construction_drawings'], 'handover-drawings/construction');
        }

        // 施工図面一式の削除処理
        if (isset($handoverDrawings['delete_construction_drawings']) && $handoverDrawings['delete_construction_drawings'] === '1') {
            $this->deleteDrawingFile($drawing, 'construction_drawings');
        }

        // 追加図面の処理（2行目以降）
        if (isset($handoverDrawings['additional'])) {
            $additionalDrawings = $existing['additional'] ?? [];

            foreach ($handoverDrawings['additional'] as $key => $drawingData) {
                if (isset($drawingData['file']) && $drawingData['file'] instanceof UploadedFile) {
                    // 既存ファイル削除
                    if (isset($additionalDrawings[$key]['path'])) {
                        $this->fileHandlingService->deleteFile($additionalDrawings[$key]['path']);
                    }

                    // 新しいファイルアップロード
                    $uploadResult = $this->handleFileUpload($drawingData['file'], 'handover-drawings/additional');
                    if ($uploadResult) {
                        $additionalDrawings[$key] = [
                            'title' => $drawingData['title'] ?? '',
                            'filename' => $uploadResult['filename'],
                            'path' => $uploadResult['path'],
                        ];
                    }
                } elseif (isset($drawingData['title'])) {
                    // タイトルのみ更新
                    if (isset($additionalDrawings[$key])) {
                        $additionalDrawings[$key]['title'] = $drawingData['title'];
                    } else {
                        $additionalDrawings[$key] = [
                            'title' => $drawingData['title'],
                            'filename' => null,
                            'path' => null,
                        ];
                    }
                }

                // 削除処理
                if (isset($drawingData['delete']) && $drawingData['delete'] === '1') {
                    if (isset($additionalDrawings[$key]['path'])) {
                        $this->fileHandlingService->deleteFile($additionalDrawings[$key]['path']);
                    }
                    unset($additionalDrawings[$key]);
                }
            }

            // handover_drawingsフィールドを更新
            $handoverDrawingsData = $drawing->handover_drawings ?? [];
            $handoverDrawingsData['additional'] = $additionalDrawings;
            $drawing->handover_drawings = $handoverDrawingsData;
        }

        // 備考の更新
        if (isset($handoverData['notes'])) {
            $handoverDrawingsData = $drawing->handover_drawings ?? [];
            $handoverDrawingsData['notes'] = $handoverData['notes'];
            $drawing->handover_drawings = $handoverDrawingsData;
        }
    }

    /**
     * 図面ファイルを処理
     */
    private function processDrawingFile(FacilityDrawing $drawing, string $type, $file, string $directory): void
    {
        if ($file instanceof UploadedFile) {
            // 既存ファイル削除
            $pathField = $type . '_path';
            if ($drawing->$pathField) {
                $this->fileHandlingService->deleteFile($drawing->$pathField);
            }

            // 新しいファイルアップロード
            $uploadResult = $this->handleFileUpload($file, $directory);
            if ($uploadResult) {
                $filenameField = $type . '_filename';
                $drawing->$filenameField = $uploadResult['filename'];
                $drawing->$pathField = $uploadResult['path'];
            }
        }
    }

    /**
     * 図面ファイルを削除
     */
    private function deleteDrawingFile(FacilityDrawing $drawing, string $type): void
    {
        $pathField = $type . '_path';
        $filenameField = $type . '_filename';

        if ($drawing->$pathField) {
            $this->fileHandlingService->deleteFile($drawing->$pathField);
            $drawing->$pathField = null;
            $drawing->$filenameField = null;
        }
    }

    /**
     * ファイルアップロード処理
     */
    private function handleFileUpload(UploadedFile $file, string $directory): ?array
    {
        try {
            $result = $this->fileHandlingService->uploadFile($file, $directory, 'pdf');
            
            return [
                'filename' => $result['filename'],
                'path' => $result['path'],
                'stored_filename' => $result['stored_filename'],
            ];
        } catch (Exception $e) {
            Log::error('Drawing file upload failed', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'directory' => $directory,
            ]);
            
            throw new Exception('図面ファイルのアップロードに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 表示用データを整形
     */
    public function formatDrawingDataForDisplay(FacilityDrawing $drawing): array
    {
        return [
            'building_drawings' => $this->formatDrawingFiles($drawing->building_drawings),
            'equipment_drawings' => $this->formatDrawingFiles($drawing->equipment_drawings),
            'handover_drawings' => $this->formatHandoverDrawings($drawing),
            'notes' => $drawing->notes,
        ];
    }

    /**
     * 図面ファイルデータを表示用に整形
     */
    private function formatDrawingFiles(array $drawings): array
    {
        $formatted = [];

        foreach ($drawings as $key => $drawing) {
            // ファイル名がある場合のみ表示用データに含める
            if (!empty($drawing['filename']) && !empty($drawing['path'])) {
                $formatted[$key] = [
                    'title' => $drawing['title'] ?? '',
                    'filename' => $drawing['filename'],
                    'path' => $drawing['path'],
                    'exists' => $this->fileHandlingService->fileExists($drawing['path']),
                    'icon' => 'fas fa-file-pdf',
                    'color' => 'text-danger',
                ];
            }
        }

        return $formatted;
    }

    /**
     * 引き渡し図面データを表示用に整形
     */
    private function formatHandoverDrawings(FacilityDrawing $drawing): array
    {
        $formatted = [];

        // 施工図面一式（1行目固定）
        if (!empty($drawing->construction_drawings_filename) && !empty($drawing->construction_drawings_path)) {
            $formatted['construction_drawings'] = [
                'title' => '施工図面一式',
                'filename' => $drawing->construction_drawings_filename,
                'path' => $drawing->construction_drawings_path,
                'exists' => $this->fileHandlingService->fileExists($drawing->construction_drawings_path),
                'icon' => 'fas fa-file-pdf',
                'color' => 'text-danger',
            ];
        }

        // 追加図面（2行目以降）
        $handoverDrawings = $drawing->handover_drawings ?? [];
        if (isset($handoverDrawings['additional']) && is_array($handoverDrawings['additional'])) {
            $formatted['additional'] = [];
            foreach ($handoverDrawings['additional'] as $key => $additionalDrawing) {
                if (!empty($additionalDrawing['filename']) && !empty($additionalDrawing['path'])) {
                    $formatted['additional'][$key] = [
                        'title' => $additionalDrawing['title'] ?? '',
                        'filename' => $additionalDrawing['filename'],
                        'path' => $additionalDrawing['path'],
                        'exists' => $this->fileHandlingService->fileExists($additionalDrawing['path']),
                        'icon' => 'fas fa-file-pdf',
                        'color' => 'text-danger',
                    ];
                }
            }
        }

        // 備考
        $formatted['notes'] = $handoverDrawings['notes'] ?? '';

        return $formatted;
    }
}