<?php

namespace Tests\Unit\Models;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectricalEquipmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_lifeline_equipment()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();
        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $this->assertInstanceOf(LifelineEquipment::class, $electricalEquipment->lifelineEquipment);
        $this->assertEquals($lifelineEquipment->id, $electricalEquipment->lifelineEquipment->id);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();

        $data = [
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '保安管理会社',
            ],
            'pas_info' => [
                'availability' => '有',
                'details' => 'PAS詳細情報',
            ],
            'cubicle_info' => [
                'availability' => '有',
                'equipment_list' => [],
            ],
            'generator_info' => [
                'availability' => '無',
                'equipment_list' => [],
            ],
            'notes' => '備考情報',
        ];

        $electricalEquipment = ElectricalEquipment::create($data);

        $this->assertEquals($lifelineEquipment->id, $electricalEquipment->lifeline_equipment_id);
        $this->assertEquals('東京電力', $electricalEquipment->basic_info['electrical_contractor']);
        $this->assertEquals('保安管理会社', $electricalEquipment->basic_info['safety_management_company']);
        $this->assertEquals('有', $electricalEquipment->pas_info['availability']);
        $this->assertEquals('PAS詳細情報', $electricalEquipment->pas_info['details']);
        $this->assertEquals('有', $electricalEquipment->cubicle_info['availability']);
        $this->assertEquals('無', $electricalEquipment->generator_info['availability']);
        $this->assertEquals('備考情報', $electricalEquipment->notes);
    }

    /** @test */
    public function it_casts_json_fields_to_arrays()
    {
        $electricalEquipment = ElectricalEquipment::factory()->create([
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '保安管理会社',
            ],
            'pas_info' => [
                'availability' => '有',
                'details' => 'PAS詳細情報',
            ],
            'cubicle_info' => [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'equipment_number' => '001',
                        'manufacturer' => '三菱電機',
                        'model_year' => '2024',
                    ],
                ],
            ],
            'generator_info' => [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'equipment_number' => '001',
                        'manufacturer' => 'ヤンマー',
                        'model_year' => '2023',
                    ],
                ],
            ],
        ]);

        $this->assertIsArray($electricalEquipment->basic_info);
        $this->assertIsArray($electricalEquipment->pas_info);
        $this->assertIsArray($electricalEquipment->cubicle_info);
        $this->assertIsArray($electricalEquipment->generator_info);

        $this->assertEquals('東京電力', $electricalEquipment->basic_info['electrical_contractor']);
        $this->assertEquals('有', $electricalEquipment->pas_info['availability']);
        $this->assertIsArray($electricalEquipment->cubicle_info['equipment_list']);
        $this->assertIsArray($electricalEquipment->generator_info['equipment_list']);
    }

    /** @test */
    public function it_handles_null_json_fields()
    {
        $electricalEquipment = ElectricalEquipment::factory()->create([
            'basic_info' => null,
            'pas_info' => null,
            'cubicle_info' => null,
            'generator_info' => null,
        ]);

        $this->assertNull($electricalEquipment->basic_info);
        $this->assertNull($electricalEquipment->pas_info);
        $this->assertNull($electricalEquipment->cubicle_info);
        $this->assertNull($electricalEquipment->generator_info);
    }

    /** @test */
    public function it_handles_empty_json_fields()
    {
        $electricalEquipment = ElectricalEquipment::factory()->create([
            'basic_info' => [],
            'pas_info' => [],
            'cubicle_info' => [],
            'generator_info' => [],
        ]);

        $this->assertIsArray($electricalEquipment->basic_info);
        $this->assertIsArray($electricalEquipment->pas_info);
        $this->assertIsArray($electricalEquipment->cubicle_info);
        $this->assertIsArray($electricalEquipment->generator_info);
        $this->assertEmpty($electricalEquipment->basic_info);
        $this->assertEmpty($electricalEquipment->pas_info);
        $this->assertEmpty($electricalEquipment->cubicle_info);
        $this->assertEmpty($electricalEquipment->generator_info);
    }

    /** @test */
    public function it_can_store_complex_equipment_lists()
    {
        $cubicleEquipmentList = [
            [
                'equipment_number' => '001',
                'manufacturer' => '三菱電機',
                'model_year' => '2024',
                'update_date' => '2024-01-15',
            ],
            [
                'equipment_number' => '002',
                'manufacturer' => '東芝',
                'model_year' => '2023',
                'update_date' => '2024-01-16',
            ],
        ];

        $generatorEquipmentList = [
            [
                'equipment_number' => 'G001',
                'manufacturer' => 'ヤンマー',
                'model_year' => '2022',
                'update_date' => '2024-01-17',
            ],
        ];

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'cubicle_info' => [
                'availability' => '有',
                'details' => 'キュービクル詳細情報',
                'equipment_list' => $cubicleEquipmentList,
            ],
            'generator_info' => [
                'availability' => '有',
                'availability_details' => '発電機詳細情報',
                'equipment_list' => $generatorEquipmentList,
            ],
        ]);

        $this->assertEquals($cubicleEquipmentList, $electricalEquipment->cubicle_info['equipment_list']);
        $this->assertEquals($generatorEquipmentList, $electricalEquipment->generator_info['equipment_list']);
        $this->assertEquals('キュービクル詳細情報', $electricalEquipment->cubicle_info['details']);
        $this->assertEquals('発電機詳細情報', $electricalEquipment->generator_info['availability_details']);
    }

    /** @test */
    public function it_can_update_partial_data()
    {
        $electricalEquipment = ElectricalEquipment::factory()->create([
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '保安管理会社',
                'maintenance_inspection_date' => '2024-01-15',
            ],
            'pas_info' => [
                'availability' => '有',
                'details' => '既存PAS情報',
            ],
        ]);

        // Update only basic_info
        $electricalEquipment->update([
            'basic_info' => [
                'electrical_contractor' => '関西電力',
                'safety_management_company' => '新保安管理会社',
                'maintenance_inspection_date' => '2024-02-15',
            ],
        ]);

        $this->assertEquals('関西電力', $electricalEquipment->basic_info['electrical_contractor']);
        $this->assertEquals('新保安管理会社', $electricalEquipment->basic_info['safety_management_company']);
        $this->assertEquals('2024-02-15', $electricalEquipment->basic_info['maintenance_inspection_date']);
        
        // PAS info should remain unchanged
        $this->assertEquals('有', $electricalEquipment->pas_info['availability']);
        $this->assertEquals('既存PAS情報', $electricalEquipment->pas_info['details']);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $electricalEquipment = new ElectricalEquipment();
        $this->assertEquals('electrical_equipment', $electricalEquipment->getTable());
    }

    /** @test */
    public function it_has_timestamps()
    {
        $electricalEquipment = ElectricalEquipment::factory()->create();

        $this->assertNotNull($electricalEquipment->created_at);
        $this->assertNotNull($electricalEquipment->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $electricalEquipment->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $electricalEquipment->updated_at);
    }

    /** @test */
    public function it_updates_timestamps_on_save()
    {
        $electricalEquipment = ElectricalEquipment::factory()->create();
        $originalUpdatedAt = $electricalEquipment->updated_at;

        // Use usleep for microsecond precision
        usleep(100000); // 0.1 seconds
        
        $electricalEquipment->notes = '更新された備考';
        $electricalEquipment->save();

        $this->assertTrue($electricalEquipment->updated_at->greaterThanOrEqualTo($originalUpdatedAt));
    }

    /** @test */
    public function it_can_access_facility_through_lifeline_equipment()
    {
        $facility = Facility::factory()->create();
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $facility->id,
        ]);
        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $this->assertInstanceOf(Facility::class, $electricalEquipment->lifelineEquipment->facility);
        $this->assertEquals($facility->id, $electricalEquipment->lifelineEquipment->facility->id);
    }

    /** @test */
    public function it_can_store_japanese_text()
    {
        $electricalEquipment = ElectricalEquipment::factory()->create([
            'basic_info' => [
                'electrical_contractor' => '東京電力パワーグリッド株式会社',
                'safety_management_company' => '電気保安協会',
            ],
            'pas_info' => [
                'availability' => '有',
                'details' => 'パワーコンディショナー設置済み。定期点検実施中。',
            ],
            'notes' => '特記事項：年次点検は毎年3月に実施予定。緊急連絡先は施設管理者まで。',
        ]);

        $this->assertEquals('東京電力パワーグリッド株式会社', $electricalEquipment->basic_info['electrical_contractor']);
        $this->assertEquals('電気保安協会', $electricalEquipment->basic_info['safety_management_company']);
        $this->assertEquals('パワーコンディショナー設置済み。定期点検実施中。', $electricalEquipment->pas_info['details']);
        $this->assertEquals('特記事項：年次点検は毎年3月に実施予定。緊急連絡先は施設管理者まで。', $electricalEquipment->notes);
    }
}