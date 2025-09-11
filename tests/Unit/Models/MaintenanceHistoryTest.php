<?php

namespace Tests\Unit\Models;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test maintenance history relationships.
     */
    public function test_maintenance_history_relationships()
    {
        $facility = Facility::factory()->create();
        $creator = User::factory()->create();

        $maintenance = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $creator->id,
        ]);

        // Test facility relationship
        $this->assertEquals($facility->id, $maintenance->facility->id);

        // Test creator relationship
        $this->assertEquals($creator->id, $maintenance->creator->id);
    }

    /**
     * Test maintenance history fillable attributes.
     */
    public function test_maintenance_history_fillable_attributes()
    {
        $facility = Facility::factory()->create();
        $creator = User::factory()->create();
        $maintenanceDate = now()->subDays(10)->toDateString();

        $maintenanceData = [
            'facility_id' => $facility->id,
            'maintenance_date' => $maintenanceDate,
            'content' => 'Replaced air conditioning unit',
            'cost' => 150000.50,
            'contractor' => 'ABC Maintenance Co.',
            'created_by' => $creator->id,
        ];

        $maintenance = MaintenanceHistory::create($maintenanceData);

        $this->assertEquals($facility->id, $maintenance->facility_id);
        $this->assertEquals($maintenanceDate, $maintenance->maintenance_date->toDateString());
        $this->assertEquals('Replaced air conditioning unit', $maintenance->content);
        $this->assertEquals('150000.50', $maintenance->cost);
        $this->assertEquals('ABC Maintenance Co.', $maintenance->contractor);
        $this->assertEquals($creator->id, $maintenance->created_by);
    }

    /**
     * Test maintenance history casts.
     */
    public function test_maintenance_history_casts()
    {
        $maintenanceDate = '2024-01-15';
        $maintenance = MaintenanceHistory::factory()->create([
            'maintenance_date' => $maintenanceDate,
            'cost' => 100000.75,
        ]);

        // Test maintenance_date is cast to date
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $maintenance->maintenance_date);
        $this->assertEquals($maintenanceDate, $maintenance->maintenance_date->toDateString());

        // Test cost is cast to decimal with 2 places
        $this->assertEquals('100000.75', $maintenance->cost);
    }

    /**
     * Test maintenance history scopes.
     */
    public function test_maintenance_history_scopes()
    {
        $facility1 = Facility::factory()->create();
        $facility2 = Facility::factory()->create();

        // Create maintenance histories for different facilities (create oldest first)
        $maintenance3 = MaintenanceHistory::factory()->create([
            'facility_id' => $facility2->id,
            'maintenance_date' => '2024-01-10',
            'content' => 'Electrical work',
        ]);
        $maintenance1 = MaintenanceHistory::factory()->create([
            'facility_id' => $facility1->id,
            'maintenance_date' => '2024-01-15',
            'content' => 'Air conditioning repair',
        ]);
        $maintenance2 = MaintenanceHistory::factory()->create([
            'facility_id' => $facility1->id,
            'maintenance_date' => '2024-02-20',
            'content' => 'Plumbing maintenance',
        ]);

        // Test forFacility scope
        $facility1Maintenances = MaintenanceHistory::forFacility($facility1->id)->get();
        $this->assertCount(2, $facility1Maintenances);
        $this->assertTrue($facility1Maintenances->contains($maintenance1));
        $this->assertTrue($facility1Maintenances->contains($maintenance2));

        // Test dateRange scope
        $januaryMaintenances = MaintenanceHistory::dateRange('2024-01-01', '2024-01-31')->get();
        $this->assertCount(2, $januaryMaintenances);
        $this->assertTrue($januaryMaintenances->contains($maintenance1));
        $this->assertTrue($januaryMaintenances->contains($maintenance3));

        // Test searchContent scope
        $airConditioningMaintenances = MaintenanceHistory::searchContent('air conditioning')->get();
        $this->assertCount(1, $airConditioningMaintenances);
        $this->assertTrue($airConditioningMaintenances->contains($maintenance1));

        // Test latestByDate scope (newest first)
        $latestMaintenances = MaintenanceHistory::latestByDate()->get();
        // Just test that we have 3 records and they are ordered by date (newest first)
        $this->assertCount(3, $latestMaintenances);
        $this->assertEquals('2024-02-20', $latestMaintenances->first()->maintenance_date->toDateString()); // newest
        $this->assertEquals('2024-01-10', $latestMaintenances->last()->maintenance_date->toDateString());  // oldest
    }

    /**
     * Test maintenance history with null cost.
     */
    public function test_maintenance_history_with_null_cost()
    {
        $maintenance = MaintenanceHistory::factory()->create([
            'cost' => null,
        ]);

        $this->assertNull($maintenance->cost);
    }

    /**
     * Test maintenance history with null contractor.
     */
    public function test_maintenance_history_with_null_contractor()
    {
        $maintenance = MaintenanceHistory::factory()->create([
            'contractor' => null,
        ]);

        $this->assertNull($maintenance->contractor);
    }
}
