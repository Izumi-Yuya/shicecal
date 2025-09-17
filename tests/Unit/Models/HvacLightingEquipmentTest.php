<?php

namespace Tests\Unit\Models;

use App\Models\Facility;
use App\Models\HvacLightingEquipment;
use App\Models\LifelineEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HvacLightingEquipmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_lifeline_equipment()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();
        $hvacLightingEquipment = HvacLightingEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $this->assertInstanceOf(LifelineEquipment::class, $hvacLightingEquipment->lifelineEquipment);
        $this->assertEquals($lifelineEquipment->id, $hvacLightingEquipment->lifelineEquipment->id);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();

        $data = [
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'hvac_system' => 'セントラル空調システム',
                'lighting_system' => 'LED照明システム',
                'maintenance_company' => '空調設備保守株式会社',
            ],
            'notes' => '空調・照明設備の備考',
        ];

        $hvacLightingEquipment = HvacLightingEquipment::create($data);

        $this->assertEquals($lifelineEquipment->id, $hvacLightingEquipment->lifeline_equipment_id);
        $this->assertEquals('セントラル空調システム', $hvacLightingEquipment->basic_info['hvac_system']);
        $this->assertEquals('LED照明システム', $hvacLightingEquipment->basic_info['lighting_system']);
        $this->assertEquals('空調設備保守株式会社', $hvacLightingEquipment->basic_info['maintenance_company']);
        $this->assertEquals('空調・照明設備の備考', $hvacLightingEquipment->notes);
    }

    /** @test */
    public function it_casts_basic_info_to_array()
    {
        $hvacLightingEquipment = HvacLightingEquipment::factory()->create([
            'basic_info' => [
                'hvac_system' => 'セントラル空調システム',
                'lighting_system' => 'LED照明システム',
                'last_maintenance_date' => '2024-03-15',
            ],
        ]);

        $this->assertIsArray($hvacLightingEquipment->basic_info);
        $this->assertEquals('セントラル空調システム', $hvacLightingEquipment->basic_info['hvac_system']);
        $this->assertEquals('LED照明システム', $hvacLightingEquipment->basic_info['lighting_system']);
        $this->assertEquals('2024-03-15', $hvacLightingEquipment->basic_info['last_maintenance_date']);
    }

    /** @test */
    public function it_handles_null_basic_info()
    {
        $hvacLightingEquipment = HvacLightingEquipment::factory()->create([
            'basic_info' => null,
        ]);

        $this->assertNull($hvacLightingEquipment->basic_info);
    }

    /** @test */
    public function it_handles_empty_basic_info()
    {
        $hvacLightingEquipment = HvacLightingEquipment::factory()->create([
            'basic_info' => [],
        ]);

        $this->assertIsArray($hvacLightingEquipment->basic_info);
        $this->assertEmpty($hvacLightingEquipment->basic_info);
    }

    /** @test */
    public function it_can_access_facility_through_lifeline_equipment()
    {
        $facility = Facility::factory()->create();
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $facility->id,
        ]);
        $hvacLightingEquipment = HvacLightingEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $this->assertInstanceOf(Facility::class, $hvacLightingEquipment->lifelineEquipment->facility);
        $this->assertEquals($facility->id, $hvacLightingEquipment->lifelineEquipment->facility->id);
    }

    /** @test */
    public function it_can_store_japanese_text()
    {
        $hvacLightingEquipment = HvacLightingEquipment::factory()->create([
            'basic_info' => [
                'hvac_system' => 'セントラル空調システム（冷暖房完備）',
                'lighting_system' => 'LED照明システム（調光機能付き）',
                'maintenance_notes' => '定期メンテナンス実施中。省エネ対応済み。',
            ],
            'notes' => '特記事項：年次点検は毎年7月に実施予定。緊急時連絡先は施設管理者まで。',
        ]);

        $this->assertEquals('セントラル空調システム（冷暖房完備）', $hvacLightingEquipment->basic_info['hvac_system']);
        $this->assertEquals('LED照明システム（調光機能付き）', $hvacLightingEquipment->basic_info['lighting_system']);
        $this->assertEquals('定期メンテナンス実施中。省エネ対応済み。', $hvacLightingEquipment->basic_info['maintenance_notes']);
        $this->assertEquals('特記事項：年次点検は毎年7月に実施予定。緊急時連絡先は施設管理者まで。', $hvacLightingEquipment->notes);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $hvacLightingEquipment = new HvacLightingEquipment();
        $this->assertEquals('hvac_lighting_equipment', $hvacLightingEquipment->getTable());
    }

    /** @test */
    public function it_has_timestamps()
    {
        $hvacLightingEquipment = HvacLightingEquipment::factory()->create();

        $this->assertNotNull($hvacLightingEquipment->created_at);
        $this->assertNotNull($hvacLightingEquipment->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $hvacLightingEquipment->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $hvacLightingEquipment->updated_at);
    }

    /** @test */
    public function it_updates_timestamps_on_save()
    {
        $hvacLightingEquipment = HvacLightingEquipment::factory()->create();
        $originalUpdatedAt = $hvacLightingEquipment->updated_at;

        usleep(100000); // 0.1 seconds
        
        $hvacLightingEquipment->notes = '更新された備考';
        $hvacLightingEquipment->save();

        $this->assertTrue($hvacLightingEquipment->updated_at->greaterThanOrEqualTo($originalUpdatedAt));
    }

    /** @test */
    public function it_can_update_partial_data()
    {
        $hvacLightingEquipment = HvacLightingEquipment::factory()->create([
            'basic_info' => [
                'hvac_system' => 'セントラル空調システム',
                'lighting_system' => 'LED照明システム',
                'maintenance_company' => '空調設備保守株式会社',
            ],
            'notes' => '既存の備考',
        ]);

        // Update only basic_info
        $hvacLightingEquipment->update([
            'basic_info' => [
                'hvac_system' => 'パッケージ空調システム',
                'lighting_system' => '蛍光灯照明システム',
                'maintenance_company' => '新空調設備保守株式会社',
            ],
        ]);

        $this->assertEquals('パッケージ空調システム', $hvacLightingEquipment->basic_info['hvac_system']);
        $this->assertEquals('蛍光灯照明システム', $hvacLightingEquipment->basic_info['lighting_system']);
        $this->assertEquals('新空調設備保守株式会社', $hvacLightingEquipment->basic_info['maintenance_company']);
        
        // Notes should remain unchanged
        $this->assertEquals('既存の備考', $hvacLightingEquipment->notes);
    }

    /** @test */
    public function it_can_store_complex_hvac_lighting_data()
    {
        $hvacLightingEquipment = HvacLightingEquipment::factory()->create([
            'basic_info' => [
                'hvac_system' => [
                    'type' => 'セントラル空調システム',
                    'manufacturer' => 'ダイキン工業',
                    'model' => 'VRV-X7',
                    'capacity' => '50HP',
                    'installation_date' => '2024-01-15',
                ],
                'lighting_system' => [
                    'type' => 'LED照明システム',
                    'manufacturer' => 'パナソニック',
                    'control_system' => '調光制御システム',
                    'energy_saving' => '省エネ対応',
                ],
                'maintenance_info' => [
                    'maintenance_company' => '空調設備保守株式会社',
                    'contract_type' => 'フルメンテナンス契約',
                    'last_maintenance' => '2024-03-15',
                    'next_maintenance' => '2024-06-15',
                ],
            ],
        ]);

        $this->assertIsArray($hvacLightingEquipment->basic_info['hvac_system']);
        $this->assertIsArray($hvacLightingEquipment->basic_info['lighting_system']);
        $this->assertIsArray($hvacLightingEquipment->basic_info['maintenance_info']);
        
        $this->assertEquals('ダイキン工業', $hvacLightingEquipment->basic_info['hvac_system']['manufacturer']);
        $this->assertEquals('調光制御システム', $hvacLightingEquipment->basic_info['lighting_system']['control_system']);
        $this->assertEquals('フルメンテナンス契約', $hvacLightingEquipment->basic_info['maintenance_info']['contract_type']);
    }
}