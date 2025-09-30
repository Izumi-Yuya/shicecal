<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RepairHistoryControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $facility;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user with editor role
        $this->user = User::factory()->create([
            'role' => 'editor',
        ]);
        $this->actingAs($this->user);

        // Create a test facility
        $this->facility = Facility::factory()->create(['status' => 'approved']);
    }

    /**
     * Test repair history index page displays correctly.
     */
    public function test_repair_history_index_displays_correctly()
    {
        // Create test repair history data
        $exteriorHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '屋上防水工事',
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
            'content' => 'エアコン修理',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.repair-history.index');
        $response->assertViewHas('facility');
        $response->assertViewHas('exteriorHistory');
        $response->assertViewHas('interiorHistory');
        $response->assertViewHas('otherHistory');
        $response->assertSee('屋上防水工事');
        $response->assertSee('内装リニューアル工事');
        $response->assertSee('エアコン修理');
    }

    /**
     * Test repair history index groups exterior history by subcategory.
     */
    public function test_repair_history_index_groups_exterior_by_subcategory()
    {
        // Create waterproof and painting histories
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事1',
            'created_by' => $this->user->id,
        ]);

        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'painting',
            'content' => '塗装工事1',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $exteriorHistory = $response->viewData('exteriorHistory');
        
        $this->assertArrayHasKey('waterproof', $exteriorHistory->toArray());
        $this->assertArrayHasKey('painting', $exteriorHistory->toArray());
        $this->assertCount(1, $exteriorHistory['waterproof']);
        $this->assertCount(1, $exteriorHistory['painting']);
    }

    /**
     * Test repair history index requires view permission.
     */
    public function test_repair_history_index_requires_view_permission()
    {
        // Create a user without permission
        $unauthorizedUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($unauthorizedUser);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(403);
    }

    /**
     * Test repair history edit page displays correctly.
     */
    public function test_repair_history_edit_displays_correctly()
    {
        // Create test repair history data
        $history1 = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '屋上防水工事',
            'created_by' => $this->user->id,
        ]);

        $history2 = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'painting',
            'content' => '外壁塗装工事',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.repair-history.edit');
        $response->assertViewHas('facility');
        $response->assertViewHas('category', 'exterior');
        $response->assertViewHas('histories');
        $response->assertViewHas('subcategories');
        $response->assertSee('屋上防水工事');
        $response->assertSee('外壁塗装工事');
    }

    /**
     * Test repair history edit with invalid category returns 404.
     */
    public function test_repair_history_edit_invalid_category_returns_404()
    {
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'invalid']));

        $response->assertStatus(404);
    }

    /**
     * Test repair history edit requires update permission.
     */
    public function test_repair_history_edit_requires_update_permission()
    {
        // Create a user without permission
        $unauthorizedUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($unauthorizedUser);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(403);
    }

    /**
     * Test repair history update creates new records.
     */
    public function test_repair_history_update_creates_new_records()
    {
        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '山田防水工事',
                    'content' => '屋上防水工事',
                    'cost' => 500000,
                    'subcategory' => 'waterproof',
                    'contact_person' => '山田太郎',
                    'phone_number' => '03-1234-5678',
                    'classification' => '定期点検',
                    'notes' => '特記事項なし',
                    'warranty_period_years' => 10,
                    'warranty_start_date' => '2024-01-15',
                    'warranty_end_date' => '2034-01-15',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success', '修繕履歴が更新されました。');

        $this->assertDatabaseHas('maintenance_histories', [
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '屋上防水工事',
            'contractor' => '山田防水工事',
            'cost' => 500000,
            'contact_person' => '山田太郎',
            'phone_number' => '03-1234-5678',
            'classification' => '定期点検',
            'notes' => '特記事項なし',
            'warranty_period_years' => 10,
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test repair history update modifies existing records.
     */
    public function test_repair_history_update_modifies_existing_records()
    {
        // Create existing record
        $existingHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '古い防水工事',
            'contractor' => '古い業者',
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'histories' => [
                [
                    'id' => $existingHistory->id,
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '新しい業者',
                    'content' => '新しい防水工事',
                    'cost' => 600000,
                    'subcategory' => 'waterproof',
                    'contact_person' => '田中花子',
                    'phone_number' => '03-9876-5432',
                    'classification' => '緊急修理',
                    'notes' => '更新された特記事項',
                    'warranty_period_years' => 15,
                    'warranty_start_date' => '2024-01-15',
                    'warranty_end_date' => '2039-01-15',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success', '修繕履歴が更新されました。');

        $this->assertDatabaseHas('maintenance_histories', [
            'id' => $existingHistory->id,
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '新しい防水工事',
            'contractor' => '新しい業者',
            'cost' => 600000,
            'contact_person' => '田中花子',
            'phone_number' => '03-9876-5432',
            'classification' => '緊急修理',
            'notes' => '更新された特記事項',
            'warranty_period_years' => 15,
        ]);
    }

    /**
     * Test repair history update removes records not included.
     */
    public function test_repair_history_update_removes_excluded_records()
    {
        // Create existing records
        $keepHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '保持する工事',
            'created_by' => $this->user->id,
        ]);

        $removeHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'painting',
            'content' => '削除される工事',
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'histories' => [
                [
                    'id' => $keepHistory->id,
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '保持する工事',
                    'subcategory' => 'waterproof',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertRedirect(route('facilities.show', $this->facility));

        // Check that the kept record still exists
        $this->assertDatabaseHas('maintenance_histories', [
            'id' => $keepHistory->id,
        ]);

        // Check that the removed record is deleted
        $this->assertDatabaseMissing('maintenance_histories', [
            'id' => $removeHistory->id,
        ]);
    }

    /**
     * Test repair history update with invalid category returns 404.
     */
    public function test_repair_history_update_invalid_category_returns_404()
    {
        $updateData = [
            'histories' => [],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'invalid']),
            $updateData
        );

        $response->assertStatus(404);
    }

    /**
     * Test repair history update requires update permission.
     */
    public function test_repair_history_update_requires_update_permission()
    {
        // Create a user without permission
        $unauthorizedUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($unauthorizedUser);

        $updateData = [
            'histories' => [],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertStatus(403);
    }

    /**
     * Test repair history update validation for required fields.
     */
    public function test_repair_history_update_validates_required_fields()
    {
        $updateData = [
            'histories' => [
                [
                    // Missing required fields
                    'subcategory' => 'waterproof',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertSessionHasErrors([
            'histories.0.maintenance_date',
            'histories.0.contractor',
            'histories.0.content',
        ]);
    }

    /**
     * Test repair history update validation for date fields.
     */
    public function test_repair_history_update_validates_date_fields()
    {
        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => 'invalid-date',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                    'warranty_start_date' => 'invalid-date',
                    'warranty_end_date' => '2024-01-01', // Before start date
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertSessionHasErrors([
            'histories.0.maintenance_date',
            'histories.0.warranty_start_date',
        ]);
    }

    /**
     * Test repair history update validation for numeric fields.
     */
    public function test_repair_history_update_validates_numeric_fields()
    {
        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                    'cost' => 'invalid-number',
                    'warranty_period_years' => 'invalid-number',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertSessionHasErrors([
            'histories.0.cost',
            'histories.0.warranty_period_years',
        ]);
    }

    /**
     * Test repair history update validation for subcategory.
     */
    public function test_repair_history_update_validates_subcategory()
    {
        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'invalid-subcategory',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertSessionHasErrors([
            'histories.0.subcategory',
        ]);
    }

    /**
     * Test repair history update validation for string length limits.
     */
    public function test_repair_history_update_validates_string_lengths()
    {
        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => str_repeat('a', 256), // Too long
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                    'contact_person' => str_repeat('b', 256), // Too long
                    'phone_number' => str_repeat('c', 21), // Too long
                    'classification' => str_repeat('d', 101), // Too long
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertSessionHasErrors([
            'histories.0.contractor',
            'histories.0.contact_person',
            'histories.0.phone_number',
            'histories.0.classification',
        ]);
    }

    /**
     * Test repair history update for interior category (no warranty fields).
     */
    public function test_repair_history_update_interior_category()
    {
        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '内装業者',
                    'content' => '内装リニューアル工事',
                    'cost' => 800000,
                    'subcategory' => 'renovation',
                    'contact_person' => '佐藤次郎',
                    'phone_number' => '03-5555-6666',
                    'classification' => '改修工事',
                    'notes' => '内装工事の特記事項',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'interior']),
            $updateData
        );

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success', '修繕履歴が更新されました。');

        $this->assertDatabaseHas('maintenance_histories', [
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'content' => '内装リニューアル工事',
            'contractor' => '内装業者',
            'cost' => 800000,
            'contact_person' => '佐藤次郎',
            'phone_number' => '03-5555-6666',
            'classification' => '改修工事',
            'notes' => '内装工事の特記事項',
            'warranty_period_years' => null, // No warranty for interior
            'warranty_start_date' => null,
            'warranty_end_date' => null,
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test repair history update for other category.
     */
    public function test_repair_history_update_other_category()
    {
        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '設備業者',
                    'content' => 'エアコン修理',
                    'cost' => 50000,
                    'subcategory' => 'renovation_work',
                    'contact_person' => '鈴木美咲',
                    'phone_number' => '03-7777-8888',
                    'classification' => '緊急修理',
                    'notes' => 'エアコン修理の特記事項',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'other']),
            $updateData
        );

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success', '修繕履歴が更新されました。');

        $this->assertDatabaseHas('maintenance_histories', [
            'facility_id' => $this->facility->id,
            'category' => 'other',
            'subcategory' => 'renovation_work',
            'content' => 'エアコン修理',
            'contractor' => '設備業者',
            'cost' => 50000,
            'contact_person' => '鈴木美咲',
            'phone_number' => '03-7777-8888',
            'classification' => '緊急修理',
            'notes' => 'エアコン修理の特記事項',
            'warranty_period_years' => null, // No warranty for other
            'warranty_start_date' => null,
            'warranty_end_date' => null,
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test repair history update with empty histories array.
     */
    public function test_repair_history_update_with_empty_histories()
    {
        // Create existing records
        $existingHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'histories' => [],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertRedirect(route('facilities.show', $this->facility));

        // Check that existing records are deleted
        $this->assertDatabaseMissing('maintenance_histories', [
            'id' => $existingHistory->id,
        ]);
    }

    /**
     * Test repair history update handles database transaction rollback on error.
     */
    public function test_repair_history_update_handles_transaction_rollback()
    {
        // Create invalid data that will cause a database error
        $updateData = [
            'histories' => [
                [
                    'id' => 99999, // Non-existent ID
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors(['error']);
    }
}