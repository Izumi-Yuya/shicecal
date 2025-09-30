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
     * Test the maintenance history model relationships.
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
     * Test the maintenance history model fillable attributes.
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
     * Test the maintenance history model casting.
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
     * Test the maintenance history query scopes.
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
     * Test the maintenance history with null cost.
     */
    public function test_maintenance_history_with_null_cost()
    {
        $maintenance = MaintenanceHistory::factory()->create([
            'cost' => null,
        ]);

        $this->assertNull($maintenance->cost);
    }

    /**
     * Test the maintenance history with null contractor.
     */
    public function test_maintenance_history_with_null_contractor()
    {
        $maintenance = MaintenanceHistory::factory()->create([
            'contractor' => null,
        ]);

        $this->assertNull($maintenance->contractor);
    }

    /**
     * Test the repair history category constants.
     */
    public function test_category_constants()
    {
        $expectedCategories = [
            'exterior' => '外装',
            'interior' => '内装リニューアル',
            'other' => 'その他'
        ];

        $this->assertEquals($expectedCategories, MaintenanceHistory::CATEGORIES);
    }

    /**
     * Test the repair history subcategory constants.
     */
    public function test_subcategory_constants()
    {
        $expectedSubcategories = [
            'exterior' => [
                'waterproof' => '防水',
                'painting' => '塗装'
            ],
            'interior' => [
                'renovation' => '内装リニューアル',
                'design' => '内装・意匠'
            ],
            'other' => [
                'renovation_work' => '改修工事'
            ]
        ];

        $this->assertEquals($expectedSubcategories, MaintenanceHistory::SUBCATEGORIES);
    }

    /**
     * Test the repair history category scope.
     */
    public function test_by_category_scope()
    {
        $facility = Facility::factory()->create();

        $exteriorHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
        ]);

        $interiorHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
        ]);

        $otherHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'category' => 'other',
            'subcategory' => 'renovation_work',
        ]);

        // Test exterior category
        $exteriorResults = MaintenanceHistory::byCategory('exterior')->get();
        $this->assertCount(1, $exteriorResults);
        $this->assertTrue($exteriorResults->contains($exteriorHistory));
        $this->assertFalse($exteriorResults->contains($interiorHistory));
        $this->assertFalse($exteriorResults->contains($otherHistory));

        // Test interior category
        $interiorResults = MaintenanceHistory::byCategory('interior')->get();
        $this->assertCount(1, $interiorResults);
        $this->assertTrue($interiorResults->contains($interiorHistory));
        $this->assertFalse($interiorResults->contains($exteriorHistory));
        $this->assertFalse($interiorResults->contains($otherHistory));

        // Test other category
        $otherResults = MaintenanceHistory::byCategory('other')->get();
        $this->assertCount(1, $otherResults);
        $this->assertTrue($otherResults->contains($otherHistory));
        $this->assertFalse($otherResults->contains($exteriorHistory));
        $this->assertFalse($otherResults->contains($interiorHistory));
    }

    /**
     * Test the repair history subcategory scope.
     */
    public function test_by_subcategory_scope()
    {
        $facility = Facility::factory()->create();

        $waterproofHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
        ]);

        $paintingHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'category' => 'exterior',
            'subcategory' => 'painting',
        ]);

        // Test waterproof subcategory
        $waterproofResults = MaintenanceHistory::bySubcategory('waterproof')->get();
        $this->assertCount(1, $waterproofResults);
        $this->assertTrue($waterproofResults->contains($waterproofHistory));
        $this->assertFalse($waterproofResults->contains($paintingHistory));

        // Test painting subcategory
        $paintingResults = MaintenanceHistory::bySubcategory('painting')->get();
        $this->assertCount(1, $paintingResults);
        $this->assertTrue($paintingResults->contains($paintingHistory));
        $this->assertFalse($paintingResults->contains($waterproofHistory));
    }

    /**
     * Test the repair history order by date scope.
     */
    public function test_order_by_date_scope()
    {
        $facility = Facility::factory()->create();

        $oldHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'maintenance_date' => '2024-01-01',
        ]);

        $newHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'maintenance_date' => '2024-03-01',
        ]);

        MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'maintenance_date' => '2024-02-01',
        ]);

        // Test descending order (default)
        $descResults = MaintenanceHistory::orderByDate()->get();
        $this->assertEquals($newHistory->id, $descResults->first()->id);
        $this->assertEquals($oldHistory->id, $descResults->last()->id);

        // Test ascending order
        $ascResults = MaintenanceHistory::orderByDate('asc')->get();
        $this->assertEquals($oldHistory->id, $ascResults->first()->id);
        $this->assertEquals($newHistory->id, $ascResults->last()->id);
    }

    /**
     * Test the repair history new fillable attributes.
     */
    public function test_repair_history_fillable_attributes()
    {
        $facility = Facility::factory()->create();
        $creator = User::factory()->create();

        $repairData = [
            'facility_id' => $facility->id,
            'maintenance_date' => '2024-01-15',
            'content' => '屋上防水工事',
            'cost' => 500000.00,
            'contractor' => '山田防水工事',
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'contact_person' => '山田太郎',
            'phone_number' => '03-1234-5678',
            'classification' => '定期点検',
            'notes' => '特記事項なし',
            'warranty_period_years' => 10,
            'warranty_start_date' => '2024-01-15',
            'warranty_end_date' => '2034-01-15',
            'created_by' => $creator->id,
        ];

        $maintenance = MaintenanceHistory::create($repairData);

        $this->assertEquals('exterior', $maintenance->category);
        $this->assertEquals('waterproof', $maintenance->subcategory);
        $this->assertEquals('山田太郎', $maintenance->contact_person);
        $this->assertEquals('03-1234-5678', $maintenance->phone_number);
        $this->assertEquals('定期点検', $maintenance->classification);
        $this->assertEquals('特記事項なし', $maintenance->notes);
        $this->assertEquals(10, $maintenance->warranty_period_years);
        $this->assertEquals('2024-01-15', $maintenance->warranty_start_date->format('Y-m-d'));
        $this->assertEquals('2034-01-15', $maintenance->warranty_end_date->format('Y-m-d'));
    }

    /**
     * Test the repair history casts for new fields.
     */
    public function test_repair_history_casts()
    {
        $maintenance = MaintenanceHistory::factory()->create([
            'warranty_start_date' => '2024-01-15',
            'warranty_end_date' => '2034-01-15',
            'warranty_period_years' => 10,
        ]);

        // Test warranty dates are cast to date
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $maintenance->warranty_start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $maintenance->warranty_end_date);
        $this->assertEquals('2024-01-15', $maintenance->warranty_start_date->format('Y-m-d'));
        $this->assertEquals('2034-01-15', $maintenance->warranty_end_date->format('Y-m-d'));

        // Test warranty period is cast to integer
        $this->assertIsInt($maintenance->warranty_period_years);
        $this->assertEquals(10, $maintenance->warranty_period_years);
    }

    /**
     * Test the category label attribute.
     */
    public function test_category_label_attribute()
    {
        $exteriorHistory = MaintenanceHistory::factory()->create(['category' => 'exterior']);
        $interiorHistory = MaintenanceHistory::factory()->create(['category' => 'interior']);
        $otherHistory = MaintenanceHistory::factory()->create(['category' => 'other']);

        $this->assertEquals('外装', $exteriorHistory->category_label);
        $this->assertEquals('内装リニューアル', $interiorHistory->category_label);
        $this->assertEquals('その他', $otherHistory->category_label);

        // Test unknown category
        $unknownHistory = MaintenanceHistory::factory()->create(['category' => 'unknown']);
        $this->assertEquals('unknown', $unknownHistory->category_label);
    }

    /**
     * Test the subcategory label attribute.
     */
    public function test_subcategory_label_attribute()
    {
        $waterproofHistory = MaintenanceHistory::factory()->create([
            'category' => 'exterior',
            'subcategory' => 'waterproof'
        ]);
        $paintingHistory = MaintenanceHistory::factory()->create([
            'category' => 'exterior',
            'subcategory' => 'painting'
        ]);
        $renovationHistory = MaintenanceHistory::factory()->create([
            'category' => 'interior',
            'subcategory' => 'renovation'
        ]);

        $this->assertEquals('防水', $waterproofHistory->subcategory_label);
        $this->assertEquals('塗装', $paintingHistory->subcategory_label);
        $this->assertEquals('内装リニューアル', $renovationHistory->subcategory_label);

        // Test unknown subcategory
        $unknownHistory = MaintenanceHistory::factory()->create([
            'category' => 'exterior',
            'subcategory' => 'unknown'
        ]);
        $this->assertEquals('unknown', $unknownHistory->subcategory_label);

        // Test null category/subcategory
        $nullHistory = MaintenanceHistory::factory()->create([
            'category' => 'exterior',
            'subcategory' => null
        ]);
        $this->assertEquals(null, $nullHistory->subcategory_label);
    }

    /**
     * Test the static method that retrieves subcategories for a given category.
     */
    public function test_get_subcategories_for_category()
    {
        $exteriorSubcategories = MaintenanceHistory::getSubcategoriesForCategory('exterior');
        $expectedExterior = [
            'waterproof' => '防水',
            'painting' => '塗装'
        ];
        $this->assertEquals($expectedExterior, $exteriorSubcategories);

        $interiorSubcategories = MaintenanceHistory::getSubcategoriesForCategory('interior');
        $expectedInterior = [
            'renovation' => '内装リニューアル',
            'design' => '内装・意匠'
        ];
        $this->assertEquals($expectedInterior, $interiorSubcategories);

        $otherSubcategories = MaintenanceHistory::getSubcategoriesForCategory('other');
        $expectedOther = [
            'renovation_work' => '改修工事'
        ];
        $this->assertEquals($expectedOther, $otherSubcategories);

        // Test unknown category
        $unknownSubcategories = MaintenanceHistory::getSubcategoriesForCategory('unknown');
        $this->assertEquals([], $unknownSubcategories);
    }

    /**
     * Test the repair history with warranty information.
     */
    public function test_repair_history_with_warranty()
    {
        $maintenance = MaintenanceHistory::factory()->create([
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'warranty_period_years' => 15,
            'warranty_start_date' => '2024-01-15',
            'warranty_end_date' => '2039-01-15',
        ]);

        $this->assertEquals(15, $maintenance->warranty_period_years);
        $this->assertEquals('2024-01-15', $maintenance->warranty_start_date->format('Y-m-d'));
        $this->assertEquals('2039-01-15', $maintenance->warranty_end_date->format('Y-m-d'));
    }

    /**
     * Test the repair history without warranty information.
     */
    public function test_repair_history_without_warranty()
    {
        $maintenance = MaintenanceHistory::factory()->create([
            'category' => 'interior',
            'subcategory' => 'renovation',
            'warranty_period_years' => null,
            'warranty_start_date' => null,
            'warranty_end_date' => null,
        ]);

        $this->assertNull($maintenance->warranty_period_years);
        $this->assertNull($maintenance->warranty_start_date);
        $this->assertNull($maintenance->warranty_end_date);
    }
}
