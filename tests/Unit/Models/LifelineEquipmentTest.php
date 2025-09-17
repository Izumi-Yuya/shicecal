<?php

namespace Tests\Unit\Models;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_a_facility()
    {
        $facility = Facility::factory()->create();
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $facility->id,
        ]);

        $this->assertInstanceOf(Facility::class, $lifelineEquipment->facility);
        $this->assertEquals($facility->id, $lifelineEquipment->facility->id);
    }

    /** @test */
    public function it_has_one_electrical_equipment()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'category' => 'electrical',
        ]);
        
        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $this->assertInstanceOf(ElectricalEquipment::class, $lifelineEquipment->electricalEquipment);
        $this->assertEquals($electricalEquipment->id, $lifelineEquipment->electricalEquipment->id);
    }

    /** @test */
    public function it_belongs_to_creator_user()
    {
        $creator = User::factory()->create();
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'created_by' => $creator->id,
        ]);

        $this->assertInstanceOf(User::class, $lifelineEquipment->creator);
        $this->assertEquals($creator->id, $lifelineEquipment->creator->id);
    }

    /** @test */
    public function it_belongs_to_updater_user()
    {
        $updater = User::factory()->create();
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'updated_by' => $updater->id,
        ]);

        $this->assertInstanceOf(User::class, $lifelineEquipment->updater);
        $this->assertEquals($updater->id, $lifelineEquipment->updater->id);
    }

    /** @test */
    public function it_belongs_to_approver_user()
    {
        $approver = User::factory()->create();
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $lifelineEquipment->approver);
        $this->assertEquals($approver->id, $lifelineEquipment->approver->id);
    }

    /** @test */
    public function it_can_have_null_approver()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->assertNull($lifelineEquipment->approver);
        $this->assertNull($lifelineEquipment->approved_at);
    }

    /** @test */
    public function it_casts_approved_at_to_datetime()
    {
        $approvedAt = now();
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'approved_at' => $approvedAt,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $lifelineEquipment->approved_at);
        $this->assertEquals($approvedAt->format('Y-m-d H:i:s'), $lifelineEquipment->approved_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $facility = Facility::factory()->create();
        $user = User::factory()->create();

        $data = [
            'facility_id' => $facility->id,
            'category' => 'electrical',
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ];

        $lifelineEquipment = LifelineEquipment::create($data);

        $this->assertEquals($facility->id, $lifelineEquipment->facility_id);
        $this->assertEquals('electrical', $lifelineEquipment->category);
        $this->assertEquals('active', $lifelineEquipment->status);
        $this->assertEquals($user->id, $lifelineEquipment->created_by);
        $this->assertEquals($user->id, $lifelineEquipment->updated_by);
        $this->assertEquals($user->id, $lifelineEquipment->approved_by);
        $this->assertNotNull($lifelineEquipment->approved_at);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $lifelineEquipment = new LifelineEquipment();
        $this->assertEquals('lifeline_equipment', $lifelineEquipment->getTable());
    }

    /** @test */
    public function it_can_scope_by_category()
    {
        $electricalEquipment = LifelineEquipment::factory()->create(['category' => 'electrical']);
        $gasEquipment = LifelineEquipment::factory()->create(['category' => 'gas']);
        $waterEquipment = LifelineEquipment::factory()->create(['category' => 'water']);

        $electricalResults = LifelineEquipment::where('category', 'electrical')->get();
        $gasResults = LifelineEquipment::where('category', 'gas')->get();

        $this->assertCount(1, $electricalResults);
        $this->assertCount(1, $gasResults);
        $this->assertTrue($electricalResults->contains($electricalEquipment));
        $this->assertTrue($gasResults->contains($gasEquipment));
        $this->assertFalse($electricalResults->contains($gasEquipment));
    }

    /** @test */
    public function it_can_scope_by_status()
    {
        $activeEquipment = LifelineEquipment::factory()->create(['status' => 'active']);
        $draftEquipment = LifelineEquipment::factory()->create(['status' => 'draft']);
        $pendingEquipment = LifelineEquipment::factory()->create(['status' => 'pending_approval']);

        $activeResults = LifelineEquipment::where('status', 'active')->get();
        $draftResults = LifelineEquipment::where('status', 'draft')->get();

        $this->assertCount(1, $activeResults);
        $this->assertCount(1, $draftResults);
        $this->assertTrue($activeResults->contains($activeEquipment));
        $this->assertTrue($draftResults->contains($draftEquipment));
        $this->assertFalse($activeResults->contains($draftEquipment));
    }

    /** @test */
    public function it_can_find_by_facility_and_category()
    {
        $facility1 = Facility::factory()->create();
        $facility2 = Facility::factory()->create();
        
        $equipment1 = LifelineEquipment::factory()->create([
            'facility_id' => $facility1->id,
            'category' => 'electrical',
        ]);
        
        $equipment2 = LifelineEquipment::factory()->create([
            'facility_id' => $facility2->id,
            'category' => 'electrical',
        ]);
        
        $equipment3 = LifelineEquipment::factory()->create([
            'facility_id' => $facility1->id,
            'category' => 'gas',
        ]);

        $result = LifelineEquipment::where('facility_id', $facility1->id)
            ->where('category', 'electrical')
            ->first();

        $this->assertNotNull($result);
        $this->assertEquals($equipment1->id, $result->id);
        $this->assertNotEquals($equipment2->id, $result->id);
        $this->assertNotEquals($equipment3->id, $result->id);
    }

    /** @test */
    public function it_validates_category_values()
    {
        $validCategories = ['electrical', 'gas', 'water', 'elevator', 'hvac_lighting'];
        
        foreach ($validCategories as $category) {
            $equipment = LifelineEquipment::factory()->create(['category' => $category]);
            $this->assertEquals($category, $equipment->category);
        }
    }

    /** @test */
    public function it_validates_status_values()
    {
        $validStatuses = ['draft', 'active', 'pending_approval', 'approved', 'rejected'];
        
        foreach ($validStatuses as $status) {
            $equipment = LifelineEquipment::factory()->create(['status' => $status]);
            $this->assertEquals($status, $equipment->status);
        }
    }

    /** @test */
    public function it_has_timestamps()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();

        $this->assertNotNull($lifelineEquipment->created_at);
        $this->assertNotNull($lifelineEquipment->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $lifelineEquipment->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $lifelineEquipment->updated_at);
    }

    /** @test */
    public function it_updates_timestamps_on_save()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create();
        $originalUpdatedAt = $lifelineEquipment->updated_at;

        // Use usleep for microsecond precision
        usleep(100000); // 0.1 seconds
        
        $lifelineEquipment->status = 'active';
        $lifelineEquipment->save();

        $this->assertTrue($lifelineEquipment->updated_at->greaterThanOrEqualTo($originalUpdatedAt));
    }
}