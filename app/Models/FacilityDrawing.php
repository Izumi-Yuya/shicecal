<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityDrawing extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        // 建物図面
        'floor_plan_filename',
        'floor_plan_path',
        'site_plan_filename',
        'site_plan_path',
        'elevation_filename',
        'elevation_path',
        'development_filename',
        'development_path',
        'area_calculation_filename',
        'area_calculation_path',
        // 設備図面
        'electrical_equipment_filename',
        'electrical_equipment_path',
        'lighting_equipment_filename',
        'lighting_equipment_path',
        'hvac_equipment_filename',
        'hvac_equipment_path',
        'plumbing_equipment_filename',
        'plumbing_equipment_path',
        'kitchen_equipment_filename',
        'kitchen_equipment_path',
        // 追加図面
        'additional_building_drawings',
        'additional_equipment_drawings',
        // 引き渡し図面
        'construction_drawings_filename',
        'construction_drawings_path',
        'handover_drawings',
        // 備考
        'notes',
    ];

    protected $casts = [
        'additional_building_drawings' => 'array',
        'additional_equipment_drawings' => 'array',
        'handover_drawings' => 'array',
    ];

    /**
     * 施設とのリレーション
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * 建物図面データを取得
     */
    public function getBuildingDrawingsAttribute(): array
    {
        $baseDrawings = [
            'floor_plan' => [
                'title' => '平面図',
                'filename' => $this->floor_plan_filename,
                'path' => $this->floor_plan_path,
            ],
            'site_plan' => [
                'title' => '配置図',
                'filename' => $this->site_plan_filename,
                'path' => $this->site_plan_path,
            ],
            'elevation' => [
                'title' => '立面図',
                'filename' => $this->elevation_filename,
                'path' => $this->elevation_path,
            ],
            'development' => [
                'title' => '展開図',
                'filename' => $this->development_filename,
                'path' => $this->development_path,
            ],
            'area_calculation' => [
                'title' => '求積図',
                'filename' => $this->area_calculation_filename,
                'path' => $this->area_calculation_path,
            ],
        ];

        // 追加図面をマージ
        $additionalDrawings = $this->additional_building_drawings ?? [];
        
        return array_merge($baseDrawings, $additionalDrawings);
    }

    /**
     * 設備図面データを取得
     */
    public function getEquipmentDrawingsAttribute(): array
    {
        $baseDrawings = [
            'electrical_equipment' => [
                'title' => '電気設備図面',
                'filename' => $this->electrical_equipment_filename,
                'path' => $this->electrical_equipment_path,
            ],
            'lighting_equipment' => [
                'title' => '電灯設備図面',
                'filename' => $this->lighting_equipment_filename,
                'path' => $this->lighting_equipment_path,
            ],
            'hvac_equipment' => [
                'title' => '空調設備図面',
                'filename' => $this->hvac_equipment_filename,
                'path' => $this->hvac_equipment_path,
            ],
            'plumbing_equipment' => [
                'title' => '給排水衛生設備図面',
                'filename' => $this->plumbing_equipment_filename,
                'path' => $this->plumbing_equipment_path,
            ],
            'kitchen_equipment' => [
                'title' => '厨房設備図面',
                'filename' => $this->kitchen_equipment_filename,
                'path' => $this->kitchen_equipment_path,
            ],
        ];

        // 追加図面をマージ
        $additionalDrawings = $this->additional_equipment_drawings ?? [];
        
        return array_merge($baseDrawings, $additionalDrawings);
    }

    /**
     * 引き渡し図面データを取得
     */
    public function getHandoverDrawingsDataAttribute(): array
    {
        $handoverDrawings = $this->handover_drawings ?? [];
        
        return [
            'construction_drawings' => [
                'title' => '施工図面一式',
                'filename' => $this->construction_drawings_filename,
                'path' => $this->construction_drawings_path,
            ],
            'additional' => $handoverDrawings['additional'] ?? [],
            'notes' => $handoverDrawings['notes'] ?? '',
        ];
    }

    /**
     * 建物図面を更新
     */
    public function updateBuildingDrawing(string $type, ?string $filename, ?string $path): void
    {
        $filenameField = $type . '_filename';
        $pathField = $type . '_path';
        
        $this->update([
            $filenameField => $filename,
            $pathField => $path,
        ]);
    }

    /**
     * 設備図面を更新
     */
    public function updateEquipmentDrawing(string $type, ?string $filename, ?string $path): void
    {
        $filenameField = $type . '_filename';
        $pathField = $type . '_path';
        
        $this->update([
            $filenameField => $filename,
            $pathField => $path,
        ]);
    }

    /**
     * 追加建物図面を追加
     */
    public function addAdditionalBuildingDrawing(string $key, string $title, string $filename, string $path): void
    {
        $additional = $this->additional_building_drawings ?? [];
        $additional[$key] = [
            'title' => $title,
            'filename' => $filename,
            'path' => $path,
        ];
        
        $this->update(['additional_building_drawings' => $additional]);
    }

    /**
     * 追加設備図面を追加
     */
    public function addAdditionalEquipmentDrawing(string $key, string $title, string $filename, string $path): void
    {
        $additional = $this->additional_equipment_drawings ?? [];
        $additional[$key] = [
            'title' => $title,
            'filename' => $filename,
            'path' => $path,
        ];
        
        $this->update(['additional_equipment_drawings' => $additional]);
    }

    /**
     * 追加図面を削除
     */
    public function removeAdditionalDrawing(string $type, string $key): void
    {
        $field = $type === 'building' ? 'additional_building_drawings' : 'additional_equipment_drawings';
        $additional = $this->$field ?? [];
        
        if (isset($additional[$key])) {
            unset($additional[$key]);
            $this->update([$field => $additional]);
        }
    }
}