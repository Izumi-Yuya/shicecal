<?php

namespace Tests\Unit\Models;

use App\Models\ExportFavorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportFavoriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test export favorite relationships.
     */
    public function test_export_favorite_relationships()
    {
        $user = User::factory()->create();
        $favorite = ExportFavorite::factory()->create(['user_id' => $user->id]);

        // Test user relationship
        $this->assertEquals($user->id, $favorite->user->id);
    }

    /**
     * Test export favorite fillable attributes.
     */
    public function test_export_favorite_fillable_attributes()
    {
        $user = User::factory()->create();
        
        $favoriteData = [
            'user_id' => $user->id,
            'name' => 'My Favorite Export',
            'facility_ids' => [1, 2, 3],
            'export_fields' => ['facility_name', 'address', 'phone_number'],
        ];

        $favorite = ExportFavorite::create($favoriteData);

        $this->assertEquals($user->id, $favorite->user_id);
        $this->assertEquals('My Favorite Export', $favorite->name);
        $this->assertEquals([1, 2, 3], $favorite->facility_ids);
        $this->assertEquals(['facility_name', 'address', 'phone_number'], $favorite->export_fields);
    }

    /**
     * Test export favorite casts.
     */
    public function test_export_favorite_casts()
    {
        $favorite = ExportFavorite::factory()->create([
            'facility_ids' => [1, 2, 3, 4],
            'export_fields' => ['name', 'address', 'phone'],
        ]);

        // Test facility_ids is cast to array
        $this->assertIsArray($favorite->facility_ids);
        $this->assertEquals([1, 2, 3, 4], $favorite->facility_ids);

        // Test export_fields is cast to array
        $this->assertIsArray($favorite->export_fields);
        $this->assertEquals(['name', 'address', 'phone'], $favorite->export_fields);
    }

    /**
     * Test export favorite with empty arrays.
     */
    public function test_export_favorite_with_empty_arrays()
    {
        $favorite = ExportFavorite::factory()->create([
            'facility_ids' => [],
            'export_fields' => [],
        ]);

        $this->assertIsArray($favorite->facility_ids);
        $this->assertEmpty($favorite->facility_ids);
        
        $this->assertIsArray($favorite->export_fields);
        $this->assertEmpty($favorite->export_fields);
    }
}