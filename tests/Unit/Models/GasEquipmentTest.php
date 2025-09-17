<?php

namespace Tests\Unit\Models;

use App\Models\Facility;
use App\Models\GasEquipment;
use App\Models\LifelineEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GasEquipmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_lifeline_equipment()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();
        $gasEquipment = GasEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $this->assertInstanceOf(LifelineEquipment::class, $gasEquipment->lifelineEquipment);
        $this->assertEquals($lifelineEquipment->id, $gasEquipment->lifelineEquipment->id);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();

        $data = [
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'gas_supplier' => '東京ガス',
                'contract_type' => '一般契約',
                'meter_number' => 'G123456789',
            ],
            'notes' => 'ガス設備の備考',
        ];

        $gasEquipment = GasEquipment::create($data);

        $this->assertEquals($lifelineEquipment->id, $gasEquipment->lifeline_equipment_id);
        $this->assertEquals('東京ガス', $gasEquipment->basic_info['gas_supplier']);
        $this->assertEquals('一般契約', $gasEquipment->basic_info['contract_type']);
        $this->assertEquals('G123456789', $gasEquipment->basic_info['meter_number']);
        $this->assertEquals('ガス設備の備考', $gasEquipment->notes);
    }

    /** @test */
    public function it_casts_basic_info_to_array()
    {
        $gasEquipment = GasEquipment::factory()->create([
            'basic_info' => [
                'gas_supplier' => '東京ガス',
                'contract_type' => '一般契約',
                'safety_inspection_date' => '2024-03-15',
            ],
        ]);

        $this->assertIsArray($gasEquipment->basic_info);
        $this->assertEquals('東京ガス', $gasEquipment->basic_info['gas_supplier']);
        $this->assertEquals('一般契約', $gasEquipment->basic_info['contract_type']);
        $this->assertEquals('2024-03-15', $gasEquipment->basic_info['safety_inspection_date']);
    }

    /** @test */
    public function it_handles_null_basic_info()
    {
        $gasEquipment = GasEquipment::factory()->create([
            'basic_info' => null,
        ]);

        $this->assertNull($gasEquipment->basic_info);
    }

    /** @test */
    public function it_handles_empty_basic_info()
    {
        $gasEquipment = GasEquipment::factory()->create([
            'basic_info' => [],
        ]);

        $this->assertIsArray($gasEquipment->basic_info);
        $this->assertEmpty($gasEquipment->basic_info);
    }

    /** @test */
    public function it_can_access_facility_through_lifeline_equipment()
    {
        $facility = Facility::factory()->create();
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $facility->id,
        ]);
        $gasEquipment = GasEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $this->assertInstanceOf(Facility::class, $gasEquipment->lifelineEquipment->facility);
        $this->assertEquals($facility->id, $gasEquipment->lifelineEquipment->facility->id);
    }

    /** @test */
    public function it_can_store_japanese_text()
    {
        $gasEquipment = GasEquipment::factory()->create([
            'basic_info' => [
                'gas_supplier' => '東京ガス株式会社',
                'contract_type' => '業務用契約',
                'safety_notes' => 'ガス漏れ検知器設置済み。定期点検実施中。',
            ],
            'notes' => '特記事項：年次安全点検は毎年4月に実施予定。緊急時連絡先は施設管理者まで。',
        ]);

        $this->assertEquals('東京ガス株式会社', $gasEquipment->basic_info['gas_supplier']);
        $this->assertEquals('業務用契約', $gasEquipment->basic_info['contract_type']);
        $this->assertEquals('ガス漏れ検知器設置済み。定期点検実施中。', $gasEquipment->basic_info['safety_notes']);
        $this->assertEquals('特記事項：年次安全点検は毎年4月に実施予定。緊急時連絡先は施設管理者まで。', $gasEquipment->notes);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $gasEquipment = new GasEquipment();
        $this->assertEquals('gas_equipment', $gasEquipment->getTable());
    }

    /** @test */
    public function it_has_timestamps()
    {
        $gasEquipment = GasEquipment::factory()->create();

        $this->assertNotNull($gasEquipment->created_at);
        $this->assertNotNull($gasEquipment->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $gasEquipment->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $gasEquipment->updated_at);
    }

    /** @test */
    public function it_updates_timestamps_on_save()
    {
        $gasEquipment = GasEquipment::factory()->create();
        $originalUpdatedAt = $gasEquipment->updated_at;

        usleep(100000); // 0.1 seconds
        
        $gasEquipment->notes = '更新された備考';
        $gasEquipment->save();

        $this->assertTrue($gasEquipment->updated_at->greaterThanOrEqualTo($originalUpdatedAt));
    }

    /** @test */
    public function it_can_update_partial_data()
    {
        $gasEquipment = GasEquipment::factory()->create([
            'basic_info' => [
                'gas_supplier' => '東京ガス',
                'contract_type' => '一般契約',
                'meter_number' => 'G123456789',
            ],
            'notes' => '既存の備考',
        ]);

        // Update only basic_info
        $gasEquipment->update([
            'basic_info' => [
                'gas_supplier' => '大阪ガス',
                'contract_type' => '業務用契約',
                'meter_number' => 'G987654321',
            ],
        ]);

        $this->assertEquals('大阪ガス', $gasEquipment->basic_info['gas_supplier']);
        $this->assertEquals('業務用契約', $gasEquipment->basic_info['contract_type']);
        $this->assertEquals('G987654321', $gasEquipment->basic_info['meter_number']);
        
        // Notes should remain unchanged
        $this->assertEquals('既存の備考', $gasEquipment->notes);
    }
}