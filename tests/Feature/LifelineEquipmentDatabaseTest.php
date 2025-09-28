<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentDatabaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the lifeline equipment model creation and relationships.
     */
    public function test_lifeline_equipment_model_creation_and_relationships()
    {
        // Create test data
        $user = User::factory()->create();
        $facility = Facility::factory()->create();

        // Create lifeline equipment
        $lifelineEquipment = LifelineEquipment::create([
            'facility_id' => $facility->id,
            'category' => 'electrical',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        // Test basic model properties
        $this->assertInstanceOf(LifelineEquipment::class, $lifelineEquipment);
        $this->assertEquals('electrical', $lifelineEquipment->category);
        $this->assertEquals('active', $lifelineEquipment->status);
        $this->assertEquals($facility->id, $lifelineEquipment->facility_id);

        // Test relationships
        $this->assertInstanceOf(Facility::class, $lifelineEquipment->facility);
        $this->assertEquals($facility->id, $lifelineEquipment->facility->id);

        $this->assertInstanceOf(User::class, $lifelineEquipment->creator);
        $this->assertEquals($user->id, $lifelineEquipment->creator->id);

        // Test facility relationship back to lifeline equipment
        $this->assertTrue($facility->lifelineEquipment->contains($lifelineEquipment));

        // Test getting lifeline equipment by category
        $electricalEquipment = $facility->getLifelineEquipmentByCategory('electrical');
        $this->assertInstanceOf(LifelineEquipment::class, $electricalEquipment);
        $this->assertEquals($lifelineEquipment->id, $electricalEquipment->id);
    }

    /**
     * Test the electrical equipment model creation and relationships.
     */
    public function test_electrical_equipment_model_creation_and_relationships()
    {
        // Create test data
        $facility = Facility::factory()->create();
        $lifelineEquipment = LifelineEquipment::create([
            'facility_id' => $facility->id,
            'category' => 'electrical',
            'status' => 'active',
        ]);

        // Create electrical equipment
        $electricalEquipment = ElectricalEquipment::create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '○○保安管理株式会社',
                'maintenance_inspection_date' => '2024-03-15',
                'inspection_report_pdf' => 'electrical_inspection_report_2024.pdf',
            ],
            'pas_info' => [
                'availability' => '有',
                'update_date' => '2023-09-15',
            ],
            'cubicle_info' => [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'equipment_number' => '1',
                        'manufacturer' => '三菱電機',
                        'model_year' => '2020',
                        'update_date' => '2024-03-15',
                    ],
                ],
            ],
            'generator_info' => [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'equipment_number' => '1',
                        'manufacturer' => 'ヤンマー',
                        'model_year' => '2021',
                        'update_date' => '2024-03-15',
                    ],
                ],
            ],
            'notes' => '特記事項なし',
        ]);

        // Test basic model properties
        $this->assertInstanceOf(ElectricalEquipment::class, $electricalEquipment);
        $this->assertEquals($lifelineEquipment->id, $electricalEquipment->lifeline_equipment_id);

        // Test JSON field access
        $this->assertEquals('東京電力', $electricalEquipment->getBasicInfoField('electrical_contractor'));
        $this->assertEquals('有', $electricalEquipment->getPasInfoField('availability'));
        $this->assertTrue($electricalEquipment->hasPas());
        $this->assertTrue($electricalEquipment->hasCubicle());
        $this->assertTrue($electricalEquipment->hasGenerator());

        // Test equipment lists
        $cubicleList = $electricalEquipment->getCubicleEquipmentList();
        $this->assertCount(1, $cubicleList);
        $this->assertEquals('三菱電機', $cubicleList[0]['manufacturer']);

        $generatorList = $electricalEquipment->getGeneratorEquipmentList();
        $this->assertCount(1, $generatorList);
        $this->assertEquals('ヤンマー', $generatorList[0]['manufacturer']);

        // Test relationships
        $this->assertInstanceOf(LifelineEquipment::class, $electricalEquipment->lifelineEquipment);
        $this->assertEquals($lifelineEquipment->id, $electricalEquipment->lifelineEquipment->id);

        // Test lifeline equipment relationship back to electrical equipment
        $this->assertInstanceOf(ElectricalEquipment::class, $lifelineEquipment->electricalEquipment);
        $this->assertEquals($electricalEquipment->id, $lifelineEquipment->electricalEquipment->id);

        // Test facility relationship through lifeline equipment
        $electricalFromFacility = $facility->getElectricalEquipment();
        $this->assertInstanceOf(ElectricalEquipment::class, $electricalFromFacility);
        $this->assertEquals($electricalEquipment->id, $electricalFromFacility->id);
    }

    /**
     * Test the lifeline equipment status methods.
     */
    public function test_lifeline_equipment_status_methods()
    {
        $facility = Facility::factory()->create();

        // Test active status
        $activeEquipment = LifelineEquipment::create([
            'facility_id' => $facility->id,
            'category' => 'electrical',
            'status' => 'active',
        ]);
        $this->assertTrue($activeEquipment->isActive());
        $this->assertFalse($activeEquipment->isInactive());
        $this->assertFalse($activeEquipment->isDecommissioned());

        // Test inactive status
        $inactiveEquipment = LifelineEquipment::create([
            'facility_id' => $facility->id,
            'category' => 'gas',
            'status' => 'inactive',
        ]);
        $this->assertFalse($inactiveEquipment->isActive());
        $this->assertTrue($inactiveEquipment->isInactive());
        $this->assertFalse($inactiveEquipment->isDecommissioned());

        // Test decommissioned status
        $decommissionedEquipment = LifelineEquipment::create([
            'facility_id' => $facility->id,
            'category' => 'water',
            'status' => 'decommissioned',
        ]);
        $this->assertFalse($decommissionedEquipment->isActive());
        $this->assertFalse($decommissionedEquipment->isInactive());
        $this->assertTrue($decommissionedEquipment->isDecommissioned());
    }

    /**
     * Test the lifeline equipment display names.
     */
    public function test_lifeline_equipment_display_names()
    {
        $facility = Facility::factory()->create();
        $lifelineEquipment = LifelineEquipment::create([
            'facility_id' => $facility->id,
            'category' => 'electrical',
            'status' => 'active',
        ]);

        $this->assertEquals('電気', $lifelineEquipment->getCategoryDisplayName());
        $this->assertEquals('アクティブ', $lifelineEquipment->getStatusDisplayName());
    }
}
