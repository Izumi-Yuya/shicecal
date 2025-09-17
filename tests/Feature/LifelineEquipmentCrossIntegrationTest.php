<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\ElevatorEquipment;
use App\Models\Facility;
use App\Models\GasEquipment;
use App\Models\HvacLightingEquipment;
use App\Models\LifelineEquipment;
use App\Models\User;
use App\Models\WaterEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 設備間連携のインテグレーションテスト
 * 
 * 複数の設備カテゴリ間の相互作用とデータ整合性をテストします。
 */
class LifelineEquipmentCrossIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $editor;
    private User $viewer;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->viewer = User::factory()->create(['role' => 'viewer']);
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function it_can_create_all_equipment_categories_for_single_facility()
    {
        $this->actingAs($this->admin);

        // Create lifeline equipment for all categories
        $categories = ['electrical', 'gas', 'water', 'elevator', 'hvac_lighting'];
        $lifelineEquipments = [];

        foreach ($categories as $category) {
            $lifelineEquipments[$category] = LifelineEquipment::factory()->create([
                'facility_id' => $this->facility->id,
                'category' => $category,
                'status' => 'active',
                'created_by' => $this->admin->id,
            ]);
        }

        // Create specific equipment for each category
        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipments['electrical']->id,
        ]);

        $gasEquipment = GasEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipments['gas']->id,
        ]);

        $waterEquipment = WaterEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipments['water']->id,
        ]);

        $elevatorEquipment = ElevatorEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipments['elevator']->id,
        ]);

        $hvacLightingEquipment = HvacLightingEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipments['hvac_lighting']->id,
        ]);

        // Verify all equipment is properly linked to the facility
        $this->assertEquals(5, $this->facility->lifelineEquipments()->count());
        
        foreach ($categories as $category) {
            $equipment = $this->facility->lifelineEquipments()
                ->where('category', $category)
                ->first();
            
            $this->assertNotNull($equipment);
            $this->assertEquals($category, $equipment->category);
            $this->assertEquals('active', $equipment->status);
        }

        // Verify specific equipment relationships
        $this->assertNotNull($lifelineEquipments['electrical']->electricalEquipment);
        $this->assertNotNull($lifelineEquipments['gas']->gasEquipment);
        $this->assertNotNull($lifelineEquipments['water']->waterEquipment);
        $this->assertNotNull($lifelineEquipments['elevator']->elevatorEquipment);
        $this->assertNotNull($lifelineEquipments['hvac_lighting']->hvacLightingEquipment);
    }

    /** @test */
    public function it_maintains_data_consistency_across_equipment_updates()
    {
        $this->actingAs($this->editor);

        // Create electrical and gas equipment
        $electricalLifeline = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'status' => 'draft',
            'created_by' => $this->editor->id,
        ]);

        $gasLifeline = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'gas',
            'status' => 'draft',
            'created_by' => $this->editor->id,
        ]);

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $electricalLifeline->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'maintenance_inspection_date' => '2024-03-15',
            ],
        ]);

        $gasEquipment = GasEquipment::factory()->create([
            'lifeline_equipment_id' => $gasLifeline->id,
            'basic_info' => [
                'gas_supplier' => '東京ガス',
                'safety_inspection_date' => '2024-03-15',
            ],
        ]);

        // Update both equipment simultaneously
        $electricalEquipment->update([
            'basic_info' => [
                'electrical_contractor' => '関西電力',
                'maintenance_inspection_date' => '2024-04-15',
            ],
        ]);

        $gasEquipment->update([
            'basic_info' => [
                'gas_supplier' => '大阪ガス',
                'safety_inspection_date' => '2024-04-15',
            ],
        ]);

        // Update lifeline equipment status
        $electricalLifeline->update(['status' => 'active', 'updated_by' => $this->editor->id]);
        $gasLifeline->update(['status' => 'active', 'updated_by' => $this->editor->id]);

        // Verify updates are consistent
        $electricalEquipment->refresh();
        $gasEquipment->refresh();
        $electricalLifeline->refresh();
        $gasLifeline->refresh();

        $this->assertEquals('関西電力', $electricalEquipment->basic_info['electrical_contractor']);
        $this->assertEquals('2024-04-15', $electricalEquipment->basic_info['maintenance_inspection_date']);
        $this->assertEquals('大阪ガス', $gasEquipment->basic_info['gas_supplier']);
        $this->assertEquals('2024-04-15', $gasEquipment->basic_info['safety_inspection_date']);
        $this->assertEquals('active', $electricalLifeline->status);
        $this->assertEquals('active', $gasLifeline->status);
        $this->assertEquals($this->editor->id, $electricalLifeline->updated_by);
        $this->assertEquals($this->editor->id, $gasLifeline->updated_by);
    }

    /** @test */
    public function it_handles_equipment_deletion_properly()
    {
        $this->actingAs($this->admin);

        // Create multiple equipment types
        $electricalLifeline = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $waterLifeline = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'water',
        ]);

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $electricalLifeline->id,
        ]);

        $waterEquipment = WaterEquipment::factory()->create([
            'lifeline_equipment_id' => $waterLifeline->id,
        ]);

        $this->assertEquals(2, $this->facility->lifelineEquipments()->count());

        // Delete electrical equipment
        $electricalEquipment->delete();
        $electricalLifeline->delete();

        // Verify only water equipment remains
        $this->assertEquals(1, $this->facility->lifelineEquipments()->count());
        $this->assertEquals('water', $this->facility->lifelineEquipments()->first()->category);
        
        // Verify water equipment is still accessible
        $remainingWaterEquipment = WaterEquipment::find($waterEquipment->id);
        $this->assertNotNull($remainingWaterEquipment);
        $this->assertEquals($waterLifeline->id, $remainingWaterEquipment->lifeline_equipment_id);
    }

    /** @test */
    public function it_enforces_unique_category_per_facility()
    {
        $this->actingAs($this->admin);

        // Create first electrical equipment
        $firstElectricalLifeline = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        // Attempt to create second electrical equipment for same facility should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);
    }

    /** @test */
    public function it_supports_bulk_equipment_operations()
    {
        $this->actingAs($this->admin);

        $categories = ['electrical', 'gas', 'water', 'elevator', 'hvac_lighting'];
        $equipmentData = [];

        // Bulk create lifeline equipment
        foreach ($categories as $category) {
            $equipmentData[$category] = LifelineEquipment::create([
                'facility_id' => $this->facility->id,
                'category' => $category,
                'status' => 'draft',
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);
        }

        // Bulk update status
        LifelineEquipment::where('facility_id', $this->facility->id)
            ->update([
                'status' => 'active',
                'updated_by' => $this->admin->id,
                'approved_by' => $this->admin->id,
                'approved_at' => now(),
            ]);

        // Verify bulk update
        $allEquipment = $this->facility->lifelineEquipments()->get();
        
        foreach ($allEquipment as $equipment) {
            $this->assertEquals('active', $equipment->status);
            $this->assertEquals($this->admin->id, $equipment->approved_by);
            $this->assertNotNull($equipment->approved_at);
        }
    }

    /** @test */
    public function it_handles_complex_equipment_relationships()
    {
        $this->actingAs($this->admin);

        // Create elevator equipment with complex data
        $elevatorLifeline = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'elevator',
        ]);

        $elevatorEquipment = ElevatorEquipment::factory()->create([
            'lifeline_equipment_id' => $elevatorLifeline->id,
            'basic_info' => [
                'manufacturer' => '三菱電機',
                'model_number' => 'EV-2024-001',
                'floors_served' => ['B1', '1F', '2F', '3F', '4F', '5F'],
            ],
            'maintenance_info' => [
                'maintenance_company' => 'エレベーター保守株式会社',
                'inspection_schedule' => [
                    'monthly' => '毎月15日',
                    'annual' => '毎年6月',
                ],
            ],
        ]);

        // Create electrical equipment that might power the elevator
        $electricalLifeline = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $electricalLifeline->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'capacity_notes' => 'エレベーター用電源含む',
            ],
            'notes' => 'エレベーター専用回路あり',
        ]);

        // Verify complex relationships work
        $this->assertEquals(2, $this->facility->lifelineEquipments()->count());
        
        $elevator = $this->facility->lifelineEquipments()
            ->where('category', 'elevator')
            ->first();
        
        $electrical = $this->facility->lifelineEquipments()
            ->where('category', 'electrical')
            ->first();

        $this->assertNotNull($elevator->elevatorEquipment);
        $this->assertNotNull($electrical->electricalEquipment);
        
        // Verify complex data structures
        $this->assertIsArray($elevator->elevatorEquipment->basic_info['floors_served']);
        $this->assertIsArray($elevator->elevatorEquipment->maintenance_info['inspection_schedule']);
        $this->assertContains('3F', $elevator->elevatorEquipment->basic_info['floors_served']);
        $this->assertEquals('毎月15日', $elevator->elevatorEquipment->maintenance_info['inspection_schedule']['monthly']);
    }

    /** @test */
    public function it_maintains_audit_trail_across_equipment_types()
    {
        $this->actingAs($this->editor);

        // Create equipment with audit trail
        $gasLifeline = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'gas',
            'status' => 'draft',
            'created_by' => $this->editor->id,
            'updated_by' => $this->editor->id,
        ]);

        $gasEquipment = GasEquipment::factory()->create([
            'lifeline_equipment_id' => $gasLifeline->id,
        ]);

        // Switch to admin for approval
        $this->actingAs($this->admin);

        $gasLifeline->update([
            'status' => 'approved',
            'updated_by' => $this->admin->id,
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Verify audit trail
        $gasLifeline->refresh();
        
        $this->assertEquals($this->editor->id, $gasLifeline->created_by);
        $this->assertEquals($this->admin->id, $gasLifeline->updated_by);
        $this->assertEquals($this->admin->id, $gasLifeline->approved_by);
        $this->assertNotNull($gasLifeline->approved_at);
        $this->assertEquals('approved', $gasLifeline->status);

        // Verify relationships to users work
        $this->assertEquals($this->editor->id, $gasLifeline->creator->id);
        $this->assertEquals($this->admin->id, $gasLifeline->updater->id);
        $this->assertEquals($this->admin->id, $gasLifeline->approver->id);
    }

    /** @test */
    public function it_handles_equipment_status_workflows()
    {
        $this->actingAs($this->editor);

        // Create multiple equipment in different statuses
        $draftEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'status' => 'draft',
            'created_by' => $this->editor->id,
        ]);

        $activeEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'gas',
            'status' => 'active',
            'created_by' => $this->editor->id,
        ]);

        $pendingEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'water',
            'status' => 'pending_approval',
            'created_by' => $this->editor->id,
        ]);

        // Test status queries
        $draftCount = $this->facility->lifelineEquipments()
            ->where('status', 'draft')
            ->count();
        
        $activeCount = $this->facility->lifelineEquipments()
            ->where('status', 'active')
            ->count();
        
        $pendingCount = $this->facility->lifelineEquipments()
            ->where('status', 'pending_approval')
            ->count();

        $this->assertEquals(1, $draftCount);
        $this->assertEquals(1, $activeCount);
        $this->assertEquals(1, $pendingCount);

        // Test status transitions
        $draftEquipment->update(['status' => 'pending_approval']);
        $pendingEquipment->update(['status' => 'approved', 'approved_by' => $this->admin->id, 'approved_at' => now()]);

        $this->assertEquals('pending_approval', $draftEquipment->fresh()->status);
        $this->assertEquals('approved', $pendingEquipment->fresh()->status);
    }
}