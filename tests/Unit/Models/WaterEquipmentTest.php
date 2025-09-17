<?php

namespace Tests\Unit\Models;

use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\WaterEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaterEquipmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_lifeline_equipment()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();
        $waterEquipment = WaterEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $this->assertInstanceOf(LifelineEquipment::class, $waterEquipment->lifelineEquipment);
        $this->assertEquals($lifelineEquipment->id, $waterEquipment->lifelineEquipment->id);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();

        $data = [
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'water_supplier' => '東京都水道局',
                'contract_type' => '上水道',
                'meter_number' => 'W123456789',
            ],
            'notes' => '水道設備の備考',
        ];

        $waterEquipment = WaterEquipment::create($data);

        $this->assertEquals($lifelineEquipment->id, $waterEquipment->lifeline_equipment_id);
        $this->assertEquals('東京都水道局', $waterEquipment->basic_info['water_supplier']);
        $this->assertEquals('上水道', $waterEquipment->basic_info['contract_type']);
        $this->assertEquals('W123456789', $waterEquipment->basic_info['meter_number']);
        $this->assertEquals('水道設備の備考', $waterEquipment->notes);
    }

    /** @test */
    public function it_casts_basic_info_to_array()
    {
        $waterEquipment = WaterEquipment::factory()->create([
            'basic_info' => [
                'water_supplier' => '東京都水道局',
                'contract_type' => '上水道',
                'quality_inspection_date' => '2024-03-15',
            ],
        ]);

        $this->assertIsArray($waterEquipment->basic_info);
        $this->assertEquals('東京都水道局', $waterEquipment->basic_info['water_supplier']);
        $this->assertEquals('上水道', $waterEquipment->basic_info['contract_type']);
        $this->assertEquals('2024-03-15', $waterEquipment->basic_info['quality_inspection_date']);
    }

    /** @test */
    public function it_handles_null_basic_info()
    {
        $waterEquipment = WaterEquipment::factory()->create([
            'basic_info' => null,
        ]);

        $this->assertNull($waterEquipment->basic_info);
    }

    /** @test */
    public function it_handles_empty_basic_info()
    {
        $waterEquipment = WaterEquipment::factory()->create([
            'basic_info' => [],
        ]);

        $this->assertIsArray($waterEquipment->basic_info);
        $this->assertEmpty($waterEquipment->basic_info);
    }

    /** @test */
    public function it_can_access_facility_through_lifeline_equipment()
    {
        $facility = Facility::factory()->create();
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $facility->id,
        ]);
        $waterEquipment = WaterEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $this->assertInstanceOf(Facility::class, $waterEquipment->lifelineEquipment->facility);
        $this->assertEquals($facility->id, $waterEquipment->lifelineEquipment->facility->id);
    }

    /** @test */
    public function it_can_store_japanese_text()
    {
        $waterEquipment = WaterEquipment::factory()->create([
            'basic_info' => [
                'water_supplier' => '東京都水道局',
                'contract_type' => '上水道・工業用水道',
                'quality_notes' => '水質検査結果良好。定期点検実施中。',
            ],
            'notes' => '特記事項：年次水質検査は毎年5月に実施予定。緊急時連絡先は施設管理者まで。',
        ]);

        $this->assertEquals('東京都水道局', $waterEquipment->basic_info['water_supplier']);
        $this->assertEquals('上水道・工業用水道', $waterEquipment->basic_info['contract_type']);
        $this->assertEquals('水質検査結果良好。定期点検実施中。', $waterEquipment->basic_info['quality_notes']);
        $this->assertEquals('特記事項：年次水質検査は毎年5月に実施予定。緊急時連絡先は施設管理者まで。', $waterEquipment->notes);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $waterEquipment = new WaterEquipment();
        $this->assertEquals('water_equipment', $waterEquipment->getTable());
    }

    /** @test */
    public function it_has_timestamps()
    {
        $waterEquipment = WaterEquipment::factory()->create();

        $this->assertNotNull($waterEquipment->created_at);
        $this->assertNotNull($waterEquipment->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $waterEquipment->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $waterEquipment->updated_at);
    }

    /** @test */
    public function it_updates_timestamps_on_save()
    {
        $waterEquipment = WaterEquipment::factory()->create();
        $originalUpdatedAt = $waterEquipment->updated_at;

        usleep(100000); // 0.1 seconds
        
        $waterEquipment->notes = '更新された備考';
        $waterEquipment->save();

        $this->assertTrue($waterEquipment->updated_at->greaterThanOrEqualTo($originalUpdatedAt));
    }

    /** @test */
    public function it_can_update_partial_data()
    {
        $waterEquipment = WaterEquipment::factory()->create([
            'basic_info' => [
                'water_supplier' => '東京都水道局',
                'contract_type' => '上水道',
                'meter_number' => 'W123456789',
            ],
            'notes' => '既存の備考',
        ]);

        // Update only basic_info
        $waterEquipment->update([
            'basic_info' => [
                'water_supplier' => '大阪市水道局',
                'contract_type' => '工業用水道',
                'meter_number' => 'W987654321',
            ],
        ]);

        $this->assertEquals('大阪市水道局', $waterEquipment->basic_info['water_supplier']);
        $this->assertEquals('工業用水道', $waterEquipment->basic_info['contract_type']);
        $this->assertEquals('W987654321', $waterEquipment->basic_info['meter_number']);
        
        // Notes should remain unchanged
        $this->assertEquals('既存の備考', $waterEquipment->notes);
    }

    /** @test */
    public function it_can_store_complex_water_system_data()
    {
        $waterEquipment = WaterEquipment::factory()->create([
            'basic_info' => [
                'water_supplier' => '東京都水道局',
                'contract_type' => '上水道',
                'meter_number' => 'W123456789',
                'pressure_info' => [
                    'normal_pressure' => '0.3MPa',
                    'max_pressure' => '0.5MPa',
                    'measurement_date' => '2024-03-15',
                ],
                'quality_info' => [
                    'ph_level' => '7.2',
                    'chlorine_level' => '0.5mg/L',
                    'test_date' => '2024-03-10',
                ],
            ],
        ]);

        $this->assertIsArray($waterEquipment->basic_info['pressure_info']);
        $this->assertIsArray($waterEquipment->basic_info['quality_info']);
        $this->assertEquals('0.3MPa', $waterEquipment->basic_info['pressure_info']['normal_pressure']);
        $this->assertEquals('7.2', $waterEquipment->basic_info['quality_info']['ph_level']);
    }
}