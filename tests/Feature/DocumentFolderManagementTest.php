<?php

namespace Tests\Feature;

use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DocumentFolderManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin', // Give admin role for testing
        ]);
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function user_can_create_folder_with_enhanced_modal()
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Test Folder',
            'parent_id' => null,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'folder' => [
                'id',
                'name',
                'path',
                'created_at',
                'creator'
            ]
        ]);

        $this->assertDatabaseHas('document_folders', [
            'facility_id' => $this->facility->id,
            'name' => 'Test Folder',
            'parent_id' => null,
        ]);
    }

    /** @test */
    public function user_can_create_nested_folder()
    {
        $this->actingAs($this->user);

        // Create parent folder
        $parentFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Parent Folder',
            'parent_id' => null,
        ]);

        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Child Folder',
            'parent_id' => $parentFolder->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('document_folders', [
            'facility_id' => $this->facility->id,
            'name' => 'Child Folder',
            'parent_id' => $parentFolder->id,
        ]);
    }

    /** @test */
    public function user_can_rename_folder_with_enhanced_modal()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Original Name',
        ]);

        $response = $this->putJson(route('facilities.documents.folders.update', [$this->facility, $folder]), [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'フォルダ名を変更しました。',
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $folder->id,
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function folder_name_validation_works()
    {
        $this->actingAs($this->user);

        // Test empty name
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);

        // Test name too long
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function user_can_delete_empty_folder()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Empty Folder',
        ]);

        $response = $this->deleteJson(route('facilities.documents.folders.destroy', [$this->facility, $folder]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'フォルダを削除しました。',
        ]);

        $this->assertDatabaseMissing('document_folders', [
            'id' => $folder->id,
        ]);
    }

    /** @test */
    public function user_can_get_folder_properties()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Test Folder',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson(route('facilities.documents.folders.properties', [$this->facility, $folder]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'type',
                'name',
                'file_count',
                'created_at',
                'updated_at',
                'creator',
                'path'
            ]
        ]);

        $response->assertJson([
            'success' => true,
            'data' => [
                'type' => 'folder',
                'name' => 'Test Folder',
                'file_count' => 0,
                'creator' => $this->user->name,
            ]
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_manage_folders()
    {
        $otherUser = User::factory()->create([
            'role' => 'viewer', // Give viewer role which should not have edit permissions
        ]);
        $this->actingAs($otherUser);

        // Test create
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Test Folder',
        ]);

        $response->assertStatus(403);

        // Test rename
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $response = $this->putJson(route('facilities.documents.folders.update', [$this->facility, $folder]), [
            'name' => 'New Name',
        ]);

        $response->assertStatus(403);

        // Test delete
        $response = $this->deleteJson(route('facilities.documents.folders.destroy', [$this->facility, $folder]));

        $response->assertStatus(403);
    }

    /** @test */
    public function folder_operations_are_logged()
    {
        $this->actingAs($this->user);

        // Create folder
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Test Folder',
        ]);

        $response->assertStatus(201);

        // Check activity log
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'document_folder_created',
        ]);
    }

    /** @test */
    public function duplicate_folder_names_in_same_directory_are_prevented()
    {
        $this->actingAs($this->user);

        // Create first folder
        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Duplicate Name',
            'parent_id' => null,
        ]);

        // Try to create second folder with same name
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Duplicate Name',
            'parent_id' => null,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}