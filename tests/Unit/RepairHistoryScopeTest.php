<?php

namespace Tests\Unit;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairHistoryScopeTest extends TestCase
{
    use RefreshDatabase;

    protected $facility;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create(['status' => 'approved']);
        $this->user = User::factory()->create(['role' => 'editor']);
    }

    /**
     * Test byCategory scope filters records correctly.
     */
    public function test_by_category_scope_filters_correctly()
    {
        // Create maintenance histories for different categories
        $exteriorHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '外装防水工事',
            'created_by' => $this->user->id,
        ]);

        $interiorHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'content' => '内装リニューアル工事',
            'created_by' => $this->user->id,
        ]);

        $otherHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'other',
            'subcategory' => 'renovation_work',
            'content' => 'その他修繕工事',
            'created_by' => $this->user->id,
        ]);

        // Test exterior category filter
        $exteriorResults = MaintenanceHistory::byCategory('exterior')->get();
        $this->assertCount(1, $exteriorResults);
        $this->assertEquals($exteriorHistory->id, $exteriorResults->first()->id);
        $this->assertEquals('外装防水工事', $exteriorResults->first()->content);

        // Test interior category filter
        $interiorResults = MaintenanceHistory::byCategory('interior')->get();
        $this->assertCount(1, $interiorResults);
        $this->assertEquals($interiorHistory->id, $interiorResults->first()->id);
        $this->assertEquals('内装リニューアル工事', $interiorResults->first()->content);

        // Test other category filter
        $otherResults = MaintenanceHistory::byCategory('other')->get();
        $this->assertCount(1, $otherResults);
        $this->assertEquals($otherHistory->id, $otherResults->first()->id);
        $this->assertEquals('その他修繕工事', $otherResults->first()->content);

        // Test non-existent category
        $nonExistentResults = MaintenanceHistory::byCategory('non_existent')->get();
        $this->assertCount(0, $nonExistentResults);
    }

    /**
     * Test bySubcategory scope filters records correctly.
     */
    public function test_by_subcategory_scope_filters_correctly()
    {
        // Create maintenance histories for different subcategories
        $waterproofHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
            'created_by' => $this->user->id,
        ]);

        $paintingHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'painting',
            'content' => '塗装工事',
            'created_by' => $this->user->id,
        ]);

        $renovationHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'content' => 'リニューアル工事',
            'created_by' => $this->user->id,
        ]);

        $designHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'design',
            'content' => '意匠工事',
            'created_by' => $this->user->id,
        ]);

        // Test waterproof subcategory filter
        $waterproofResults = MaintenanceHistory::bySubcategory('waterproof')->get();
        $this->assertCount(1, $waterproofResults);
        $this->assertEquals($waterproofHistory->id, $waterproofResults->first()->id);
        $this->assertEquals('防水工事', $waterproofResults->first()->content);

        // Test painting subcategory filter
        $paintingResults = MaintenanceHistory::bySubcategory('painting')->get();
        $this->assertCount(1, $paintingResults);
        $this->assertEquals($paintingHistory->id, $paintingResults->first()->id);
        $this->assertEquals('塗装工事', $paintingResults->first()->content);

        // Test renovation subcategory filter
        $renovationResults = MaintenanceHistory::bySubcategory('renovation')->get();
        $this->assertCount(1, $renovationResults);
        $this->assertEquals($renovationHistory->id, $renovationResults->first()->id);
        $this->assertEquals('リニューアル工事', $renovationResults->first()->content);

        // Test design subcategory filter
        $designResults = MaintenanceHistory::bySubcategory('design')->get();
        $this->assertCount(1, $designResults);
        $this->assertEquals($designHistory->id, $designResults->first()->id);
        $this->assertEquals('意匠工事', $designResults->first()->content);

        // Test non-existent subcategory
        $nonExistentResults = MaintenanceHistory::bySubcategory('non_existent')->get();
        $this->assertCount(0, $nonExistentResults);
    }

    /**
     * Test orderByDate scope orders records correctly.
     */
    public function test_order_by_date_scope_orders_correctly()
    {
        // Create maintenance histories with different dates
        $oldHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'maintenance_date' => '2024-01-01',
            'content' => '古い工事',
            'created_by' => $this->user->id,
        ]);

        $middleHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'maintenance_date' => '2024-02-15',
            'content' => '中間の工事',
            'created_by' => $this->user->id,
        ]);

        $newHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'maintenance_date' => '2024-03-30',
            'content' => '新しい工事',
            'created_by' => $this->user->id,
        ]);

        // Test descending order (default)
        $descResults = MaintenanceHistory::orderByDate()->get();
        $this->assertCount(3, $descResults);
        $this->assertEquals($newHistory->id, $descResults[0]->id);
        $this->assertEquals($middleHistory->id, $descResults[1]->id);
        $this->assertEquals($oldHistory->id, $descResults[2]->id);

        // Verify dates are in descending order
        $this->assertEquals('2024-03-30', $descResults[0]->maintenance_date->format('Y-m-d'));
        $this->assertEquals('2024-02-15', $descResults[1]->maintenance_date->format('Y-m-d'));
        $this->assertEquals('2024-01-01', $descResults[2]->maintenance_date->format('Y-m-d'));

        // Test ascending order
        $ascResults = MaintenanceHistory::orderByDate('asc')->get();
        $this->assertCount(3, $ascResults);
        $this->assertEquals($oldHistory->id, $ascResults[0]->id);
        $this->assertEquals($middleHistory->id, $ascResults[1]->id);
        $this->assertEquals($newHistory->id, $ascResults[2]->id);

        // Verify dates are in ascending order
        $this->assertEquals('2024-01-01', $ascResults[0]->maintenance_date->format('Y-m-d'));
        $this->assertEquals('2024-02-15', $ascResults[1]->maintenance_date->format('Y-m-d'));
        $this->assertEquals('2024-03-30', $ascResults[2]->maintenance_date->format('Y-m-d'));
    }

    /**
     * Test combining multiple scopes.
     */
    public function test_combining_multiple_scopes()
    {
        // Create maintenance histories for testing scope combinations
        $exteriorWaterproof1 = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'maintenance_date' => '2024-01-15',
            'content' => '古い防水工事',
            'created_by' => $this->user->id,
        ]);

        $exteriorWaterproof2 = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'maintenance_date' => '2024-03-15',
            'content' => '新しい防水工事',
            'created_by' => $this->user->id,
        ]);

        $exteriorPainting = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'painting',
            'maintenance_date' => '2024-02-15',
            'content' => '塗装工事',
            'created_by' => $this->user->id,
        ]);

        $interiorRenovation = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'maintenance_date' => '2024-02-20',
            'content' => '内装工事',
            'created_by' => $this->user->id,
        ]);

        // Test combining byCategory and bySubcategory
        $exteriorWaterproofResults = MaintenanceHistory::byCategory('exterior')
            ->bySubcategory('waterproof')
            ->get();
        
        $this->assertCount(2, $exteriorWaterproofResults);
        $this->assertTrue($exteriorWaterproofResults->contains($exteriorWaterproof1));
        $this->assertTrue($exteriorWaterproofResults->contains($exteriorWaterproof2));
        $this->assertFalse($exteriorWaterproofResults->contains($exteriorPainting));
        $this->assertFalse($exteriorWaterproofResults->contains($interiorRenovation));

        // Test combining byCategory, bySubcategory, and orderByDate
        $exteriorWaterproofOrderedResults = MaintenanceHistory::byCategory('exterior')
            ->bySubcategory('waterproof')
            ->orderByDate('desc')
            ->get();
        
        $this->assertCount(2, $exteriorWaterproofOrderedResults);
        $this->assertEquals($exteriorWaterproof2->id, $exteriorWaterproofOrderedResults[0]->id); // Newer first
        $this->assertEquals($exteriorWaterproof1->id, $exteriorWaterproofOrderedResults[1]->id); // Older second

        // Test combining all three scopes with facility relationship
        $facilityExteriorWaterproofResults = $this->facility->maintenanceHistories()
            ->byCategory('exterior')
            ->bySubcategory('waterproof')
            ->orderByDate('asc')
            ->get();
        
        $this->assertCount(2, $facilityExteriorWaterproofResults);
        $this->assertEquals($exteriorWaterproof1->id, $facilityExteriorWaterproofResults[0]->id); // Older first
        $this->assertEquals($exteriorWaterproof2->id, $facilityExteriorWaterproofResults[1]->id); // Newer second
    }

    /**
     * Test scopes work with empty results.
     */
    public function test_scopes_work_with_empty_results()
    {
        // Test scopes with no matching records
        $noExteriorResults = MaintenanceHistory::byCategory('exterior')->get();
        $this->assertCount(0, $noExteriorResults);

        $noWaterproofResults = MaintenanceHistory::bySubcategory('waterproof')->get();
        $this->assertCount(0, $noWaterproofResults);

        $noOrderedResults = MaintenanceHistory::orderByDate()->get();
        $this->assertCount(0, $noOrderedResults);

        // Test combining scopes with no matching records
        $noCombinedResults = MaintenanceHistory::byCategory('exterior')
            ->bySubcategory('waterproof')
            ->orderByDate()
            ->get();
        $this->assertCount(0, $noCombinedResults);
    }

    /**
     * Test scopes work with different facilities.
     */
    public function test_scopes_work_with_different_facilities()
    {
        $facility2 = Facility::factory()->create(['status' => 'approved']);

        // Create maintenance histories for different facilities
        $facility1History = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '施設1の防水工事',
            'created_by' => $this->user->id,
        ]);

        $facility2History = MaintenanceHistory::factory()->create([
            'facility_id' => $facility2->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '施設2の防水工事',
            'created_by' => $this->user->id,
        ]);

        // Test that scopes return records from both facilities
        $allExteriorResults = MaintenanceHistory::byCategory('exterior')->get();
        $this->assertCount(2, $allExteriorResults);
        $this->assertTrue($allExteriorResults->contains($facility1History));
        $this->assertTrue($allExteriorResults->contains($facility2History));

        // Test that facility relationship + scopes work correctly
        $facility1ExteriorResults = $this->facility->maintenanceHistories()
            ->byCategory('exterior')
            ->get();
        $this->assertCount(1, $facility1ExteriorResults);
        $this->assertTrue($facility1ExteriorResults->contains($facility1History));
        $this->assertFalse($facility1ExteriorResults->contains($facility2History));

        $facility2ExteriorResults = $facility2->maintenanceHistories()
            ->byCategory('exterior')
            ->get();
        $this->assertCount(1, $facility2ExteriorResults);
        $this->assertTrue($facility2ExteriorResults->contains($facility2History));
        $this->assertFalse($facility2ExteriorResults->contains($facility1History));
    }

    /**
     * Test scopes handle null values correctly.
     */
    public function test_scopes_handle_null_values_correctly()
    {
        // Create maintenance history with default category and null subcategory
        $defaultCategoryHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'other', // Use default category
            'subcategory' => null,
            'maintenance_date' => '2024-01-15',
            'content' => 'デフォルトカテゴリの工事',
            'created_by' => $this->user->id,
        ]);

        $validCategoryHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'maintenance_date' => '2024-02-15',
            'content' => '正常なカテゴリの工事',
            'created_by' => $this->user->id,
        ]);

        // Test byCategory scope with different categories
        $exteriorResults = MaintenanceHistory::byCategory('exterior')->get();
        $this->assertCount(1, $exteriorResults);
        $this->assertTrue($exteriorResults->contains($validCategoryHistory));
        $this->assertFalse($exteriorResults->contains($defaultCategoryHistory));

        $otherResults = MaintenanceHistory::byCategory('other')->get();
        $this->assertCount(1, $otherResults);
        $this->assertTrue($otherResults->contains($defaultCategoryHistory));
        $this->assertFalse($otherResults->contains($validCategoryHistory));

        // Test bySubcategory scope with null values
        $waterproofResults = MaintenanceHistory::bySubcategory('waterproof')->get();
        $this->assertCount(1, $waterproofResults);
        $this->assertTrue($waterproofResults->contains($validCategoryHistory));
        $this->assertFalse($waterproofResults->contains($defaultCategoryHistory));

        $nullSubcategoryResults = MaintenanceHistory::bySubcategory(null)->get();
        $this->assertCount(1, $nullSubcategoryResults);
        $this->assertTrue($nullSubcategoryResults->contains($defaultCategoryHistory));
        $this->assertFalse($nullSubcategoryResults->contains($validCategoryHistory));

        // Test orderByDate scope works with different categories
        $orderedResults = MaintenanceHistory::orderByDate()->get();
        $this->assertCount(2, $orderedResults);
        $this->assertEquals($validCategoryHistory->id, $orderedResults[0]->id); // Newer first
        $this->assertEquals($defaultCategoryHistory->id, $orderedResults[1]->id); // Older second
    }

    /**
     * Test scopes are chainable and return query builder.
     */
    public function test_scopes_are_chainable_and_return_query_builder()
    {
        // Test that scopes return query builder instances
        $query1 = MaintenanceHistory::byCategory('exterior');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query1);

        $query2 = MaintenanceHistory::bySubcategory('waterproof');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query2);

        $query3 = MaintenanceHistory::orderByDate();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query3);

        // Test that scopes can be chained
        $chainedQuery = MaintenanceHistory::byCategory('exterior')
            ->bySubcategory('waterproof')
            ->orderByDate('desc');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $chainedQuery);

        // Test that chained scopes can be executed
        $results = $chainedQuery->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
    }
}