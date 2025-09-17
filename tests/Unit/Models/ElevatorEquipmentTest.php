<?php

namespace Tests\Unit\Models;

use App\Models\ElevatorEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElevatorEquipmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_lifeline_equipment()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();
        $elevatorEquipment = ElevatorEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $this->assertInstanceOf(LifelineEquipment::class, $elevatorEquipment->lifelineEquipment);
        $this->assertEquals($lifelineEquipment->id, $elevatorEquipment->lifelineEquipment->id);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();

        $data = [
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'manufacturer' => '三菱電機',
                'model_number' => 'EV-2024-001',
                'installation_date' => '2024-01-15',
            ],
            'maintenance_info' => [
                'maintenance_company' => 'エレベーター保守株式会社',
                'last_inspection_date' => '2024-03-15',
            ],
            'safety_info' => [
                'emergency_phone' => '有',
                'backup_power' => '有',
            ],
            'notes' => 'エレベーター設備の備考',
        ];

        $elevatorEquipment = ElevatorEquipment::create($data);

        $this->assertEquals($lifelineEquipment->id, $elevatorEquipment->lifeline_equipment_id);
        $this->assertEquals('三菱電機', $elevatorEquipment->basic_info['manufacturer']);
        $this->assertEquals('EV-2024-001', $elevatorEquipment->basic_info['model_number']);
        $this->assertEquals('2024-01-15', $elevatorEquipment->basic_info['installation_date']);
        $this->assertEquals('エレベーター保守株式会社', $elevatorEquipment->maintenance_info['maintenance_company']);
        $this->assertEquals('2024-03-15', $elevatorEquipment->maintenance_info['last_inspection_date']);
        $this->assertEquals('有', $elevatorEquipment->safety_info['emergency_phone']);
        $this->assertEquals('有', $elevatorEquipment->safety_info['backup_power']);
        $this->assertEquals('エレベーター設備の備考', $elevatorEquipment->notes);
    }

    /** @test */
    public function it_casts_json_fields_to_arrays()
    {
        $elevatorEquipment = ElevatorEquipment::factory()->create([
            'basic_info' => [
                'manufacturer' => '三菱電機',
                'model_number' => 'EV-2024-001',
                'capacity' => '1000kg',
            ],
            'maintenance_info' => [
                'maintenance_company' => 'エレベーター保守株式会社',
                'contract_type' => 'フルメンテナンス契約',
            ],
            'safety_info' => [
                'emergency_phone' => '有',
                'backup_power' => '有',
                'seismic_control' => '有',
            ],
        ]);

        $this->assertIsArray($elevatorEquipment->basic_info);
        $this->assertIsArray($elevatorEquipment->maintenance_info);
        $this->assertIsArray($elevatorEquipment->safety_info);

        $this->assertEquals('三菱電機', $elevatorEquipment->basic_info['manufacturer']);
        $this->assertEquals('1000kg', $elevatorEquipment->basic_info['capacity']);
        $this->assertEquals('フルメンテナンス契約', $elevatorEquipment->maintenance_info['contract_type']);
        $this->assertEquals('有', $elevatorEquipment->safety_info['seismic_control']);
    }

    /** @test */
    public function it_handles_null_json_fields()
    {
        $elevatorEquipment = ElevatorEquipment::factory()->create([
            'basic_info' => null,
            'maintenance_info' => null,
            'safety_info' => null,
        ]);

        $this->assertNull($elevatorEquipment->basic_info);
        $this->assertNull($elevatorEquipment->maintenance_info);
        $this->assertNull($elevatorEquipment->safety_info);
    }

    /** @test */
    public function it_handles_empty_json_fields()
    {
        $elevatorEquipment = ElevatorEquipment::factory()->create([
            'basic_info' => [],
            'maintenance_info' => [],
            'safety_info' => [],
        ]);

        $this->assertIsArray($elevatorEquipment->basic_info);
        $this->assertIsArray($elevatorEquipment->maintenance_info);
        $this->assertIsArray($elevatorEquipment->safety_info);
        $this->assertEmpty($elevatorEquipment->basic_info);
        $this->assertEmpty($elevatorEquipment->maintenance_info);
        $this->assertEmpty($elevatorEquipment->safety_info);
    }

    /** @test */
    public function it_can_access_facility_through_lifeline_equipment()
    {
        $facility = Facility::factory()->create();
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $facility->id,
        ]);
        $elevatorEquipment = ElevatorEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $this->assertInstanceOf(Facility::class, $elevatorEquipment->lifelineEquipment->facility);
        $this->assertEquals($facility->id, $elevatorEquipment->lifelineEquipment->facility->id);
    }

    /** @test */
    public function it_can_store_japanese_text()
    {
        $elevatorEquipment = ElevatorEquipment::factory()->create([
            'basic_info' => [
                'manufacturer' => '三菱電機株式会社',
                'model_number' => 'エレベーター型式EV-2024-001',
                'specifications' => '定員15名、積載量1000kg、速度60m/分',
            ],
            'maintenance_info' => [
                'maintenance_company' => 'エレベーター保守株式会社',
                'contract_details' => 'フルメンテナンス契約。月1回定期点検実施。',
            ],
            'notes' => '特記事項：年次法定点検は毎年6月に実施予定。緊急時連絡先は施設管理者まで。',
        ]);

        $this->assertEquals('三菱電機株式会社', $elevatorEquipment->basic_info['manufacturer']);
        $this->assertEquals('エレベーター型式EV-2024-001', $elevatorEquipment->basic_info['model_number']);
        $this->assertEquals('定員15名、積載量1000kg、速度60m/分', $elevatorEquipment->basic_info['specifications']);
        $this->assertEquals('フルメンテナンス契約。月1回定期点検実施。', $elevatorEquipment->maintenance_info['contract_details']);
        $this->assertEquals('特記事項：年次法定点検は毎年6月に実施予定。緊急時連絡先は施設管理者まで。', $elevatorEquipment->notes);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $elevatorEquipment = new ElevatorEquipment();
        $this->assertEquals('elevator_equipment', $elevatorEquipment->getTable());
    }

    /** @test */
    public function it_has_timestamps()
    {
        $elevatorEquipment = ElevatorEquipment::factory()->create();

        $this->assertNotNull($elevatorEquipment->created_at);
        $this->assertNotNull($elevatorEquipment->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $elevatorEquipment->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $elevatorEquipment->updated_at);
    }

    /** @test */
    public function it_updates_timestamps_on_save()
    {
        $elevatorEquipment = ElevatorEquipment::factory()->create();
        $originalUpdatedAt = $elevatorEquipment->updated_at;

        usleep(100000); // 0.1 seconds
        
        $elevatorEquipment->notes = '更新された備考';
        $elevatorEquipment->save();

        $this->assertTrue($elevatorEquipment->updated_at->greaterThanOrEqualTo($originalUpdatedAt));
    }

    /** @test */
    public function it_can_update_partial_data()
    {
        $elevatorEquipment = ElevatorEquipment::factory()->create([
            'basic_info' => [
                'manufacturer' => '三菱電機',
                'model_number' => 'EV-2024-001',
                'capacity' => '1000kg',
            ],
            'maintenance_info' => [
                'maintenance_company' => 'エレベーター保守株式会社',
                'contract_type' => 'フルメンテナンス契約',
            ],
            'notes' => '既存の備考',
        ]);

        // Update only basic_info
        $elevatorEquipment->update([
            'basic_info' => [
                'manufacturer' => '東芝エレベータ',
                'model_number' => 'EV-2024-002',
                'capacity' => '1200kg',
            ],
        ]);

        $this->assertEquals('東芝エレベータ', $elevatorEquipment->basic_info['manufacturer']);
        $this->assertEquals('EV-2024-002', $elevatorEquipment->basic_info['model_number']);
        $this->assertEquals('1200kg', $elevatorEquipment->basic_info['capacity']);
        
        // Other fields should remain unchanged
        $this->assertEquals('エレベーター保守株式会社', $elevatorEquipment->maintenance_info['maintenance_company']);
        $this->assertEquals('既存の備考', $elevatorEquipment->notes);
    }

    /** @test */
    public function it_can_store_complex_elevator_data()
    {
        $elevatorEquipment = ElevatorEquipment::factory()->create([
            'basic_info' => [
                'manufacturer' => '三菱電機',
                'model_number' => 'EV-2024-001',
                'specifications' => [
                    'capacity_persons' => 15,
                    'capacity_weight' => '1000kg',
                    'speed' => '60m/min',
                    'floors_served' => ['B1', '1F', '2F', '3F', '4F', '5F'],
                ],
                'installation_date' => '2024-01-15',
            ],
            'maintenance_info' => [
                'maintenance_company' => 'エレベーター保守株式会社',
                'contract_type' => 'フルメンテナンス契約',
                'inspection_schedule' => [
                    'monthly' => '毎月15日',
                    'annual' => '毎年6月',
                    'legal_inspection' => '毎年6月（法定点検）',
                ],
            ],
            'safety_info' => [
                'emergency_phone' => '有',
                'backup_power' => '有',
                'seismic_control' => '有',
                'fire_service' => '有',
                'safety_features' => [
                    'door_sensor' => '有',
                    'overload_protection' => '有',
                    'emergency_stop' => '有',
                ],
            ],
        ]);

        $this->assertIsArray($elevatorEquipment->basic_info['specifications']);
        $this->assertIsArray($elevatorEquipment->maintenance_info['inspection_schedule']);
        $this->assertIsArray($elevatorEquipment->safety_info['safety_features']);
        
        $this->assertEquals(15, $elevatorEquipment->basic_info['specifications']['capacity_persons']);
        $this->assertEquals('毎月15日', $elevatorEquipment->maintenance_info['inspection_schedule']['monthly']);
        $this->assertEquals('有', $elevatorEquipment->safety_info['safety_features']['door_sensor']);
    }
}