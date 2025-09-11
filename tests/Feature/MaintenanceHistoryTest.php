<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceSearchFavorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MaintenanceHistoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user and authenticate
        $this->user = User::factory()->create([
            'role' => 'editor',
        ]);
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_display_maintenance_history_index()
    {
        // Create test data
        $facility = Facility::factory()->create(['status' => 'approved']);
        $maintenanceHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('maintenance.index'));

        $response->assertStatus(200);
        $response->assertViewIs('maintenance.index');
        $response->assertViewHas('maintenanceHistories');
        $response->assertViewHas('facilities');
        $response->assertSee($maintenanceHistory->content);
    }

    /** @test */
    public function it_can_filter_maintenance_histories_by_facility()
    {
        $facility1 = Facility::factory()->create(['status' => 'approved']);
        $facility2 = Facility::factory()->create(['status' => 'approved']);

        $history1 = MaintenanceHistory::factory()->create([
            'facility_id' => $facility1->id,
            'created_by' => $this->user->id,
            'content' => 'Facility 1 maintenance',
        ]);

        $history2 = MaintenanceHistory::factory()->create([
            'facility_id' => $facility2->id,
            'created_by' => $this->user->id,
            'content' => 'Facility 2 maintenance',
        ]);

        $response = $this->get(route('maintenance.index', ['facility_id' => $facility1->id]));

        $response->assertStatus(200);
        $response->assertSee('Facility 1 maintenance');
        $response->assertDontSee('Facility 2 maintenance');
    }

    /** @test */
    public function it_can_filter_maintenance_histories_by_date_range()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);

        $oldHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $this->user->id,
            'maintenance_date' => '2023-01-01',
            'content' => 'Old maintenance',
        ]);

        $newHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $this->user->id,
            'maintenance_date' => '2024-01-01',
            'content' => 'New maintenance',
        ]);

        $response = $this->get(route('maintenance.index', [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]));

        $response->assertStatus(200);
        $response->assertSee('New maintenance');
        $response->assertDontSee('Old maintenance');
    }

    /** @test */
    public function it_can_search_maintenance_histories_by_content()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);

        $history1 = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $this->user->id,
            'content' => 'Air conditioning repair',
        ]);

        $history2 = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $this->user->id,
            'content' => 'Plumbing maintenance',
        ]);

        $response = $this->get(route('maintenance.index', ['search' => 'air conditioning']));

        $response->assertStatus(200);
        $response->assertSee('Air conditioning repair');
        $response->assertDontSee('Plumbing maintenance');
    }

    /** @test */
    public function it_can_display_create_maintenance_form()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);

        $response = $this->get(route('maintenance.create'));

        $response->assertStatus(200);
        $response->assertViewIs('maintenance.create');
        $response->assertViewHas('facilities');
        $response->assertSee($facility->facility_name);
    }

    /** @test */
    public function it_can_create_maintenance_history()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);

        $maintenanceData = [
            'facility_id' => $facility->id,
            'maintenance_date' => '2024-01-15',
            'content' => 'Test maintenance work',
            'cost' => 50000.00,
            'contractor' => 'Test Contractor Co.',
        ];

        $response = $this->post(route('maintenance.store'), $maintenanceData);

        $response->assertRedirect(route('maintenance.index'));
        $response->assertSessionHas('success', '修繕履歴を登録しました。');

        $this->assertDatabaseHas('maintenance_histories', [
            'facility_id' => $facility->id,
            'content' => 'Test maintenance work',
            'cost' => 50000.00,
            'contractor' => 'Test Contractor Co.',
            'created_by' => $this->user->id,
        ]);

        $maintenanceHistory = MaintenanceHistory::where('content', 'Test maintenance work')->first();
        $this->assertEquals('2024-01-15', $maintenanceHistory->maintenance_date->format('Y-m-d'));
    }

    /** @test */
    public function it_can_create_maintenance_history_without_optional_fields()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);

        $maintenanceData = [
            'facility_id' => $facility->id,
            'maintenance_date' => '2024-01-15',
            'content' => 'Test maintenance work',
            // cost and contractor are optional
        ];

        $response = $this->post(route('maintenance.store'), $maintenanceData);

        $response->assertRedirect(route('maintenance.index'));

        $this->assertDatabaseHas('maintenance_histories', [
            'facility_id' => $facility->id,
            'content' => 'Test maintenance work',
            'cost' => null,
            'contractor' => null,
            'created_by' => $this->user->id,
        ]);

        $maintenanceHistory = MaintenanceHistory::where('content', 'Test maintenance work')->first();
        $this->assertEquals('2024-01-15', $maintenanceHistory->maintenance_date->format('Y-m-d'));
    }

    /** @test */
    public function it_validates_required_fields_when_creating_maintenance_history()
    {
        $response = $this->post(route('maintenance.store'), []);

        $response->assertSessionHasErrors(['facility_id', 'maintenance_date', 'content']);
    }

    /** @test */
    public function it_validates_facility_exists_when_creating_maintenance_history()
    {
        $maintenanceData = [
            'facility_id' => 999999, // Non-existent facility
            'maintenance_date' => '2024-01-15',
            'content' => 'Test maintenance work',
        ];

        $response = $this->post(route('maintenance.store'), $maintenanceData);

        $response->assertSessionHasErrors(['facility_id']);
    }

    /** @test */
    public function it_can_display_maintenance_history_details()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);
        $maintenanceHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('maintenance.show', $maintenanceHistory));

        $response->assertStatus(200);
        $response->assertViewIs('maintenance.show');
        $response->assertViewHas('maintenanceHistory');
        $response->assertSee($maintenanceHistory->content);
        $response->assertSee($facility->facility_name);
    }

    /** @test */
    public function it_can_display_edit_maintenance_form()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);
        $maintenanceHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('maintenance.edit', $maintenanceHistory));

        $response->assertStatus(200);
        $response->assertViewIs('maintenance.edit');
        $response->assertViewHas('maintenanceHistory');
        $response->assertViewHas('facilities');
        $response->assertSee($maintenanceHistory->content);
    }

    /** @test */
    public function it_can_update_maintenance_history()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);
        $maintenanceHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'facility_id' => $facility->id,
            'maintenance_date' => '2024-02-15',
            'content' => 'Updated maintenance work',
            'cost' => 75000.00,
            'contractor' => 'Updated Contractor Co.',
        ];

        $response = $this->put(route('maintenance.update', $maintenanceHistory), $updateData);

        $response->assertRedirect(route('maintenance.show', $maintenanceHistory));
        $response->assertSessionHas('success', '修繕履歴を更新しました。');

        $this->assertDatabaseHas('maintenance_histories', [
            'id' => $maintenanceHistory->id,
            'facility_id' => $facility->id,
            'content' => 'Updated maintenance work',
            'cost' => 75000.00,
            'contractor' => 'Updated Contractor Co.',
        ]);

        $maintenanceHistory->refresh();
        $this->assertEquals('2024-02-15', $maintenanceHistory->maintenance_date->format('Y-m-d'));
    }

    /** @test */
    public function it_can_delete_maintenance_history()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);
        $maintenanceHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->delete(route('maintenance.destroy', $maintenanceHistory));

        $response->assertRedirect(route('maintenance.index'));
        $response->assertSessionHas('success', '修繕履歴を削除しました。');

        $this->assertDatabaseMissing('maintenance_histories', [
            'id' => $maintenanceHistory->id,
        ]);
    }

    /** @test */
    public function it_can_get_facility_histories_via_ajax()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);
        $history1 = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $this->user->id,
        ]);
        $history2 = MaintenanceHistory::factory()->create([
            'facility_id' => $facility->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('maintenance.facility.histories', $facility));

        $response->assertStatus(200);
        $responseData = $response->json();

        // Check that we have 2 histories
        $this->assertCount(2, $responseData);

        // Check that both histories are present (order may vary based on maintenance_date)
        $historyIds = collect($responseData)->pluck('id')->toArray();
        $this->assertContains($history1->id, $historyIds);
        $this->assertContains($history2->id, $historyIds);

        // Check that content is present
        $historyContents = collect($responseData)->pluck('content')->toArray();
        $this->assertContains($history1->content, $historyContents);
        $this->assertContains($history2->content, $historyContents);
    }

    /** @test */
    public function it_can_save_search_conditions_as_favorite()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);

        $favoriteData = [
            'name' => 'My Search Favorite',
            'facility_id' => $facility->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'search_content' => 'air conditioning',
        ];

        $response = $this->postJson(route('maintenance.search-favorites.store'), $favoriteData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '検索条件を保存しました。',
        ]);

        $this->assertDatabaseHas('maintenance_search_favorites', [
            'user_id' => $this->user->id,
            'name' => 'My Search Favorite',
            'facility_id' => $facility->id,
            'search_content' => 'air conditioning',
        ]);

        $favorite = MaintenanceSearchFavorite::where('name', 'My Search Favorite')->first();
        $this->assertEquals('2024-01-01', $favorite->start_date->format('Y-m-d'));
        $this->assertEquals('2024-12-31', $favorite->end_date->format('Y-m-d'));
    }

    /** @test */
    public function it_can_save_search_conditions_with_null_values()
    {
        $favoriteData = [
            'name' => 'Simple Search',
            'facility_id' => null,
            'start_date' => null,
            'end_date' => null,
            'search_content' => null,
        ];

        $response = $this->postJson(route('maintenance.search-favorites.store'), $favoriteData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '検索条件を保存しました。',
        ]);

        $this->assertDatabaseHas('maintenance_search_favorites', [
            'user_id' => $this->user->id,
            'name' => 'Simple Search',
            'facility_id' => null,
            'start_date' => null,
            'end_date' => null,
            'search_content' => null,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_saving_search_favorite()
    {
        $response = $this->postJson(route('maintenance.search-favorites.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_date_range_when_saving_search_favorite()
    {
        $favoriteData = [
            'name' => 'Invalid Date Range',
            'start_date' => '2024-12-31',
            'end_date' => '2024-01-01', // End date before start date
        ];

        $response = $this->postJson(route('maintenance.search-favorites.store'), $favoriteData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function it_can_load_search_favorite()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);
        $favorite = MaintenanceSearchFavorite::factory()->create([
            'user_id' => $this->user->id,
            'facility_id' => $facility->id,
        ]);

        $response = $this->getJson(route('maintenance.search-favorites.show', $favorite));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'favorite' => [
                'id' => $favorite->id,
                'name' => $favorite->name,
                'facility_id' => $facility->id,
            ],
        ]);
    }

    /** @test */
    public function it_prevents_loading_other_users_search_favorite()
    {
        $otherUser = User::factory()->create(['role' => 'editor']);
        $favorite = MaintenanceSearchFavorite::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson(route('maintenance.search-favorites.show', $favorite));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'アクセス権限がありません。',
        ]);
    }

    /** @test */
    public function it_can_update_search_favorite()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);
        $favorite = MaintenanceSearchFavorite::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $updateData = [
            'name' => 'Updated Search Favorite',
            'facility_id' => $facility->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-06-30',
            'search_content' => 'updated search',
        ];

        $response = $this->putJson(route('maintenance.search-favorites.update', $favorite), $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '検索条件を更新しました。',
        ]);

        $this->assertDatabaseHas('maintenance_search_favorites', [
            'id' => $favorite->id,
            'user_id' => $this->user->id,
            'name' => 'Updated Search Favorite',
            'facility_id' => $facility->id,
            'search_content' => 'updated search',
        ]);

        $favorite->refresh();
        $this->assertEquals('2024-06-01', $favorite->start_date->format('Y-m-d'));
        $this->assertEquals('2024-06-30', $favorite->end_date->format('Y-m-d'));
    }

    /** @test */
    public function it_prevents_updating_other_users_search_favorite()
    {
        $otherUser = User::factory()->create(['role' => 'editor']);
        $favorite = MaintenanceSearchFavorite::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $updateData = [
            'name' => 'Hacked Favorite',
        ];

        $response = $this->putJson(route('maintenance.search-favorites.update', $favorite), $updateData);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'アクセス権限がありません。',
        ]);
    }

    /** @test */
    public function it_can_delete_search_favorite()
    {
        $favorite = MaintenanceSearchFavorite::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson(route('maintenance.search-favorites.destroy', $favorite));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '検索条件を削除しました。',
        ]);

        $this->assertDatabaseMissing('maintenance_search_favorites', [
            'id' => $favorite->id,
        ]);
    }

    /** @test */
    public function it_prevents_deleting_other_users_search_favorite()
    {
        $otherUser = User::factory()->create(['role' => 'editor']);
        $favorite = MaintenanceSearchFavorite::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->deleteJson(route('maintenance.search-favorites.destroy', $favorite));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'アクセス権限がありません。',
        ]);

        $this->assertDatabaseHas('maintenance_search_favorites', [
            'id' => $favorite->id,
        ]);
    }

    /** @test */
    public function it_can_get_users_search_favorites()
    {
        $otherUser = User::factory()->create(['role' => 'editor']);

        // Create favorites for current user
        $userFavorite1 = MaintenanceSearchFavorite::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Favorite 1',
        ]);
        $userFavorite2 = MaintenanceSearchFavorite::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Favorite 2',
        ]);

        // Create favorite for other user (should not be returned)
        MaintenanceSearchFavorite::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Favorite',
        ]);

        $response = $this->getJson(route('maintenance.search-favorites.index'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $favorites = $response->json('favorites');
        $this->assertCount(2, $favorites);

        $favoriteNames = collect($favorites)->pluck('name')->toArray();
        $this->assertContains('User Favorite 1', $favoriteNames);
        $this->assertContains('User Favorite 2', $favoriteNames);
        $this->assertNotContains('Other User Favorite', $favoriteNames);
    }

    /** @test */
    public function it_displays_search_favorites_in_index_view()
    {
        $facility = Facility::factory()->create(['status' => 'approved']);
        $favorite = MaintenanceSearchFavorite::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Favorite',
            'facility_id' => $facility->id,
        ]);

        $response = $this->get(route('maintenance.index'));

        $response->assertStatus(200);
        $response->assertViewHas('searchFavorites');
        $response->assertSee('Test Favorite');
    }

    /** @test */
    public function it_can_combine_search_filters_with_favorites()
    {
        $facility1 = Facility::factory()->create(['status' => 'approved']);
        $facility2 = Facility::factory()->create(['status' => 'approved']);

        // Create maintenance histories
        $history1 = MaintenanceHistory::factory()->create([
            'facility_id' => $facility1->id,
            'created_by' => $this->user->id,
            'maintenance_date' => '2024-01-15',
            'content' => 'Air conditioning repair',
        ]);

        $history2 = MaintenanceHistory::factory()->create([
            'facility_id' => $facility2->id,
            'created_by' => $this->user->id,
            'maintenance_date' => '2024-02-15',
            'content' => 'Plumbing maintenance',
        ]);

        // Create and load a favorite that should match only history1
        $favorite = MaintenanceSearchFavorite::factory()->create([
            'user_id' => $this->user->id,
            'facility_id' => $facility1->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'search_content' => 'air conditioning',
        ]);

        // Apply the search conditions from the favorite
        $response = $this->get(route('maintenance.index', [
            'facility_id' => $favorite->facility_id,
            'start_date' => $favorite->start_date,
            'end_date' => $favorite->end_date,
            'search' => $favorite->search_content,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Air conditioning repair');
        $response->assertDontSee('Plumbing maintenance');
    }
}
