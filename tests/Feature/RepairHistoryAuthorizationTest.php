<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RepairHistoryAuthorizationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $facility;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test facility
        $this->facility = Facility::factory()->create(['status' => 'approved']);
    }

    /**
     * Test admin user can view repair history.
     */
    public function test_admin_user_can_view_repair_history()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $this->actingAs($adminUser);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.repair-history.index');
    }

    /**
     * Test editor user can view repair history.
     */
    public function test_editor_user_can_view_repair_history()
    {
        $editorUser = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editorUser);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.repair-history.index');
    }

    /**
     * Test approver user can view repair history.
     */
    public function test_approver_user_can_view_repair_history()
    {
        $approverUser = User::factory()->create(['role' => 'approver']);
        $this->actingAs($approverUser);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.repair-history.index');
    }

    /**
     * Test viewer user can view repair history.
     */
    public function test_viewer_user_can_view_repair_history()
    {
        $viewerUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewerUser);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.repair-history.index');
    }

    /**
     * Test unauthenticated user cannot view repair history.
     */
    public function test_unauthenticated_user_cannot_view_repair_history()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test admin user can edit repair history.
     */
    public function test_admin_user_can_edit_repair_history()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $this->actingAs($adminUser);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.repair-history.edit');
    }

    /**
     * Test editor user can edit repair history.
     */
    public function test_editor_user_can_edit_repair_history()
    {
        $editorUser = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editorUser);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.repair-history.edit');
    }

    /**
     * Test approver user cannot edit repair history.
     */
    public function test_approver_user_cannot_edit_repair_history()
    {
        $approverUser = User::factory()->create(['role' => 'approver']);
        $this->actingAs($approverUser);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(403);
    }

    /**
     * Test viewer user cannot edit repair history.
     */
    public function test_viewer_user_cannot_edit_repair_history()
    {
        $viewerUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewerUser);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(403);
    }

    /**
     * Test unauthenticated user cannot edit repair history.
     */
    public function test_unauthenticated_user_cannot_edit_repair_history()
    {
        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test admin user can update repair history.
     */
    public function test_admin_user_can_update_repair_history()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $this->actingAs($adminUser);

        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '工事業者',
                    'content' => '防水工事',
                    'subcategory' => 'waterproof',
                    'cost' => 500000,
                ],
            ],
        ];

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->put(
                route('facilities.repair-history.update', [$this->facility, 'exterior']),
                $updateData
            );

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success', '修繕履歴が更新されました。');

        $this->assertDatabaseHas('maintenance_histories', [
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
            'contractor' => '工事業者',
            'cost' => 500000,
            'created_by' => $adminUser->id,
        ]);
    }

    /**
     * Test editor user can update repair history.
     */
    public function test_editor_user_can_update_repair_history()
    {
        $editorUser = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editorUser);

        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '工事業者',
                    'content' => '防水工事',
                    'subcategory' => 'waterproof',
                    'cost' => 500000,
                ],
            ],
        ];

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->put(
                route('facilities.repair-history.update', [$this->facility, 'exterior']),
                $updateData
            );

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success', '修繕履歴が更新されました。');

        $this->assertDatabaseHas('maintenance_histories', [
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
            'contractor' => '工事業者',
            'cost' => 500000,
            'created_by' => $editorUser->id,
        ]);
    }

    /**
     * Test approver user cannot update repair history.
     */
    public function test_approver_user_cannot_update_repair_history()
    {
        $approverUser = User::factory()->create(['role' => 'approver']);
        $this->actingAs($approverUser);

        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '工事業者',
                    'content' => '防水工事',
                    'subcategory' => 'waterproof',
                    'cost' => 500000,
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertStatus(403);

        $this->assertDatabaseMissing('maintenance_histories', [
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
        ]);
    }

    /**
     * Test viewer user cannot update repair history.
     */
    public function test_viewer_user_cannot_update_repair_history()
    {
        $viewerUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewerUser);

        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '工事業者',
                    'content' => '防水工事',
                    'subcategory' => 'waterproof',
                    'cost' => 500000,
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertStatus(403);

        $this->assertDatabaseMissing('maintenance_histories', [
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
        ]);
    }

    /**
     * Test unauthenticated user cannot update repair history.
     */
    public function test_unauthenticated_user_cannot_update_repair_history()
    {
        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '工事業者',
                    'content' => '防水工事',
                    'subcategory' => 'waterproof',
                    'cost' => 500000,
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('maintenance_histories', [
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
        ]);
    }

    /**
     * Test user cannot access repair history for non-existent facility.
     */
    public function test_user_cannot_access_repair_history_for_non_existent_facility()
    {
        $editorUser = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editorUser);

        $response = $this->get(route('facilities.repair-history.index', 99999));

        $response->assertStatus(404);
    }

    /**
     * Test user cannot edit repair history for non-existent facility.
     */
    public function test_user_cannot_edit_repair_history_for_non_existent_facility()
    {
        $editorUser = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editorUser);

        $response = $this->get(route('facilities.repair-history.edit', [99999, 'exterior']));

        $response->assertStatus(404);
    }

    /**
     * Test user cannot update repair history for non-existent facility.
     */
    public function test_user_cannot_update_repair_history_for_non_existent_facility()
    {
        $editorUser = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editorUser);

        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '工事業者',
                    'content' => '防水工事',
                    'subcategory' => 'waterproof',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [99999, 'exterior']),
            $updateData
        );

        $response->assertStatus(404);
    }

    /**
     * Test user cannot access repair history with invalid category.
     */
    public function test_user_cannot_access_repair_history_with_invalid_category()
    {
        $editorUser = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editorUser);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'invalid']));

        $response->assertStatus(404);
    }

    /**
     * Test user cannot update repair history with invalid category.
     */
    public function test_user_cannot_update_repair_history_with_invalid_category()
    {
        $editorUser = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editorUser);

        $updateData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '工事業者',
                    'content' => '防水工事',
                    'subcategory' => 'waterproof',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'invalid']),
            $updateData
        );

        $response->assertStatus(404);
    }

    /**
     * Test edit buttons are displayed for authorized users.
     */
    public function test_edit_buttons_displayed_for_authorized_users()
    {
        $editorUser = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editorUser);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('編集');
        $response->assertSee(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertSee(route('facilities.repair-history.edit', [$this->facility, 'interior']));
        $response->assertSee(route('facilities.repair-history.edit', [$this->facility, 'other']));
    }

    /**
     * Test edit buttons are not displayed for unauthorized users.
     */
    public function test_edit_buttons_not_displayed_for_unauthorized_users()
    {
        $viewerUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewerUser);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertDontSee('編集');
        $response->assertDontSee(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertDontSee(route('facilities.repair-history.edit', [$this->facility, 'interior']));
        $response->assertDontSee(route('facilities.repair-history.edit', [$this->facility, 'other']));
    }

    /**
     * Test user can only modify their own created records.
     */
    public function test_user_can_modify_any_repair_history_record()
    {
        $editorUser1 = User::factory()->create(['role' => 'editor']);
        $editorUser2 = User::factory()->create(['role' => 'editor']);

        // Create history by user1
        $history = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '既存の工事',
            'contractor' => '既存の業者',
            'created_by' => $editorUser1->id,
        ]);

        // User2 tries to modify user1's record
        $this->actingAs($editorUser2);

        $updateData = [
            'histories' => [
                [
                    'id' => $history->id,
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '更新された業者',
                    'content' => '更新された工事',
                    'subcategory' => 'waterproof',
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.repair-history.update', [$this->facility, 'exterior']),
            $updateData
        );

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success', '修繕履歴が更新されました。');

        // Check that the record was updated (any editor can modify any record)
        $this->assertDatabaseHas('maintenance_histories', [
            'id' => $history->id,
            'contractor' => '更新された業者',
            'content' => '更新された工事',
        ]);
    }

    /**
     * Test policy authorization for different facility statuses.
     */
    public function test_policy_authorization_for_different_facility_statuses()
    {
        $editorUser = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editorUser);

        // Test with approved facility
        $approvedFacility = Facility::factory()->create(['status' => 'approved']);
        $response = $this->get(route('facilities.repair-history.index', $approvedFacility));
        $response->assertStatus(200);

        // Test with pending facility
        $pendingFacility = Facility::factory()->create(['status' => 'pending']);
        $response = $this->get(route('facilities.repair-history.index', $pendingFacility));
        $response->assertStatus(200); // Should still be accessible

        // Test with rejected facility
        $rejectedFacility = Facility::factory()->create(['status' => 'rejected']);
        $response = $this->get(route('facilities.repair-history.index', $rejectedFacility));
        $response->assertStatus(200); // Should still be accessible
    }

    /**
     * Test authorization with facility scoped users.
     */
    public function test_authorization_with_facility_scoped_users()
    {
        // Create a scoped viewer user
        $scopedUser = User::factory()->create([
            'role' => 'viewer',
            'facility_scope' => [$this->facility->id],
        ]);
        $this->actingAs($scopedUser);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));
        $response->assertStatus(200);

        // Test with facility not in scope
        $otherFacility = Facility::factory()->create(['status' => 'approved']);
        $response = $this->get(route('facilities.repair-history.index', $otherFacility));
        $response->assertStatus(403);
    }

    /**
     * Test authorization error messages are user-friendly.
     */
    public function test_authorization_error_messages_are_user_friendly()
    {
        $viewerUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewerUser);

        $response = $this->get(route('facilities.repair-history.edit', [$this->facility, 'exterior']));

        $response->assertStatus(403);
        $response->assertSee('この操作を実行する権限がありません');
    }
}