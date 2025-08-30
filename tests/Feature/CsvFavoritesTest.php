<?php

namespace Tests\Feature;

use App\Models\ExportFavorite;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvFavoritesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin',
            'access_scope' => null,
        ]);
        
        $this->facilities = Facility::factory()->count(3)->create([
            'status' => 'approved',
        ]);
    }

    public function test_user_can_save_favorite()
    {
        $facilityIds = $this->facilities->take(2)->pluck('id')->toArray();
        $exportFields = ['company_name', 'facility_name', 'address'];

        $response = $this->actingAs($this->user)
                         ->postJson(route('csv.export.favorites.store'), [
                             'name' => 'テストお気に入り',
                             'facility_ids' => $facilityIds,
                             'export_fields' => $exportFields
                         ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'お気に入りを保存しました。'
        ]);

        $this->assertDatabaseHas('export_favorites', [
            'user_id' => $this->user->id,
            'name' => 'テストお気に入り',
        ]);

        $favorite = ExportFavorite::where('user_id', $this->user->id)->first();
        $this->assertEquals($facilityIds, $favorite->facility_ids);
        $this->assertEquals($exportFields, $favorite->export_fields);
    }

    public function test_user_cannot_save_favorite_with_duplicate_name()
    {
        // Create existing favorite
        ExportFavorite::factory()->create([
            'user_id' => $this->user->id,
            'name' => '既存のお気に入り',
        ]);

        $response = $this->actingAs($this->user)
                         ->postJson(route('csv.export.favorites.store'), [
                             'name' => '既存のお気に入り',
                             'facility_ids' => [$this->facilities->first()->id],
                             'export_fields' => ['company_name']
                         ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'この名前のお気に入りは既に存在します。'
        ]);
    }

    public function test_user_can_get_their_favorites()
    {
        $favorites = ExportFavorite::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Create favorite for another user (should not be returned)
        $otherUser = User::factory()->create();
        ExportFavorite::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
                         ->getJson(route('csv.export.favorites.index'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
        
        // Check that all returned favorites belong to the user
        foreach ($data as $favorite) {
            $this->assertEquals($this->user->id, $favorite['user_id']);
        }
    }

    public function test_user_can_load_favorite()
    {
        $facilityIds = $this->facilities->take(2)->pluck('id')->toArray();
        $exportFields = ['company_name', 'facility_name'];
        
        $favorite = ExportFavorite::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'テストお気に入り',
            'facility_ids' => $facilityIds,
            'export_fields' => $exportFields,
        ]);

        $response = $this->actingAs($this->user)
                         ->getJson(route('csv.export.favorites.show', $favorite->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $favorite->id,
                'name' => 'テストお気に入り',
                'facility_ids' => $facilityIds,
                'export_fields' => $exportFields,
                'original_facility_count' => 2,
                'accessible_facility_count' => 2,
            ]
        ]);
    }

    public function test_user_cannot_load_other_users_favorite()
    {
        $otherUser = User::factory()->create();
        $favorite = ExportFavorite::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
                         ->getJson(route('csv.export.favorites.show', $favorite->id));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'お気に入りが見つかりません。'
        ]);
    }

    public function test_user_can_update_favorite_name()
    {
        $favorite = ExportFavorite::factory()->create([
            'user_id' => $this->user->id,
            'name' => '古い名前',
        ]);

        $response = $this->actingAs($this->user)
                         ->putJson(route('csv.export.favorites.update', $favorite->id), [
                             'name' => '新しい名前'
                         ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'お気に入り名を更新しました。'
        ]);

        $favorite->refresh();
        $this->assertEquals('新しい名前', $favorite->name);
    }

    public function test_user_cannot_update_favorite_to_duplicate_name()
    {
        $favorite1 = ExportFavorite::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'お気に入り1',
        ]);

        $favorite2 = ExportFavorite::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'お気に入り2',
        ]);

        $response = $this->actingAs($this->user)
                         ->putJson(route('csv.export.favorites.update', $favorite2->id), [
                             'name' => 'お気に入り1'
                         ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'この名前のお気に入りは既に存在します。'
        ]);
    }

    public function test_user_can_delete_favorite()
    {
        $favorite = ExportFavorite::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
                         ->deleteJson(route('csv.export.favorites.destroy', $favorite->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'お気に入りを削除しました。'
        ]);

        $this->assertDatabaseMissing('export_favorites', [
            'id' => $favorite->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_favorite()
    {
        $otherUser = User::factory()->create();
        $favorite = ExportFavorite::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
                         ->deleteJson(route('csv.export.favorites.destroy', $favorite->id));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'お気に入りが見つかりません。'
        ]);

        // Favorite should still exist
        $this->assertDatabaseHas('export_favorites', [
            'id' => $favorite->id,
        ]);
    }

    public function test_favorite_filters_inaccessible_facilities_on_load()
    {
        // Create a viewer user with limited access
        $viewerUser = User::factory()->create([
            'role' => 'viewer',
            'access_scope' => [
                'type' => 'facility',
                'facility_ids' => [$this->facilities->first()->id]
            ],
        ]);

        // Create favorite with facilities the user no longer has access to
        $allFacilityIds = $this->facilities->pluck('id')->toArray();
        $favorite = ExportFavorite::factory()->create([
            'user_id' => $viewerUser->id,
            'facility_ids' => $allFacilityIds,
            'export_fields' => ['company_name'],
        ]);

        $response = $this->actingAs($viewerUser)
                         ->getJson(route('csv.export.favorites.show', $favorite->id));

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals(3, $data['original_facility_count']);
        $this->assertEquals(1, $data['accessible_facility_count']);
        $this->assertCount(1, $data['facility_ids']);
        $this->assertEquals([$this->facilities->first()->id], $data['facility_ids']);
    }

    public function test_save_favorite_validates_facility_access()
    {
        // Create a viewer user with limited access
        $viewerUser = User::factory()->create([
            'role' => 'viewer',
            'access_scope' => [
                'type' => 'facility',
                'facility_ids' => [$this->facilities->first()->id]
            ],
        ]);

        // Try to save favorite with facilities the user doesn't have access to
        $allFacilityIds = $this->facilities->pluck('id')->toArray();

        $response = $this->actingAs($viewerUser)
                         ->postJson(route('csv.export.favorites.store'), [
                             'name' => 'テストお気に入り',
                             'facility_ids' => $allFacilityIds,
                             'export_fields' => ['company_name']
                         ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'アクセス権限のない施設が含まれています。'
        ]);
    }

    public function test_favorites_require_authentication()
    {
        $response = $this->getJson(route('csv.export.favorites.index'));
        $response->assertStatus(401);

        $response = $this->postJson(route('csv.export.favorites.store'), []);
        $response->assertStatus(401);

        $response = $this->getJson(route('csv.export.favorites.show', 1));
        $response->assertStatus(401);

        $response = $this->putJson(route('csv.export.favorites.update', 1), []);
        $response->assertStatus(401);

        $response = $this->deleteJson(route('csv.export.favorites.destroy', 1));
        $response->assertStatus(401);
    }
}