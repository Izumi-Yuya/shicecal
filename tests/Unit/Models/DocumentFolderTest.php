<?php

namespace Tests\Unit\Models;

use App\Models\DocumentFolder;
use App\Models\DocumentFile;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentFolderTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentFolder $folder;
    protected Facility $facility;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->facility = Facility::factory()->create();
        $this->user = User::factory()->create();
        $this->folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_facility()
    {
        $this->assertInstanceOf(Facility::class, $this->folder->facility);
        $this->assertEquals($this->facility->id, $this->folder->facility->id);
    }

    /** @test */
    public function it_belongs_to_a_creator()
    {
        $this->assertInstanceOf(User::class, $this->folder->creator);
        $this->assertEquals($this->user->id, $this->folder->creator->id);
    }

    /** @test */
    public function it_can_have_a_parent_folder()
    {
        $parentFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        $childFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => $parentFolder->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(DocumentFolder::class, $childFolder->parent);
        $this->assertEquals($parentFolder->id, $childFolder->parent->id);
    }

    /** @test */
    public function it_can_have_child_folders()
    {
        $childFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => $this->folder->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($this->folder->children->contains($childFolder));
        $this->assertCount(1, $this->folder->children);
    }

    /** @test */
    public function it_can_have_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $this->folder->id,
            'uploaded_by' => $this->user->id,
        ]);

        $this->assertTrue($this->folder->files->contains($file));
        $this->assertCount(1, $this->folder->files);
    }

    /** @test */
    public function it_generates_correct_full_path_for_root_folder()
    {
        $rootFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => null,
            'name' => 'Documents',
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals('Documents', $rootFolder->getFullPath());
    }

    /** @test */
    public function it_generates_correct_full_path_for_nested_folder()
    {
        $parentFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => null,
            'name' => 'Contracts',
            'created_by' => $this->user->id,
        ]);

        $childFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => $parentFolder->id,
            'name' => 'Maintenance',
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals('Contracts/Maintenance', $childFolder->getFullPath());
    }

    /** @test */
    public function it_detects_if_folder_has_children()
    {
        $this->assertFalse($this->folder->hasChildren());

        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => $this->folder->id,
            'created_by' => $this->user->id,
        ]);

        $this->folder->refresh();
        $this->assertTrue($this->folder->hasChildren());
    }

    /** @test */
    public function it_counts_files_correctly()
    {
        $this->assertEquals(0, $this->folder->getFileCount());

        DocumentFile::factory()->count(3)->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $this->folder->id,
            'uploaded_by' => $this->user->id,
        ]);

        $this->folder->refresh();
        $this->assertEquals(3, $this->folder->getFileCount());
    }

    /** @test */
    public function it_calculates_total_size_correctly()
    {
        $this->assertEquals(0, $this->folder->getTotalSize());

        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $this->folder->id,
            'file_size' => 1024,
            'uploaded_by' => $this->user->id,
        ]);

        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $this->folder->id,
            'file_size' => 2048,
            'uploaded_by' => $this->user->id,
        ]);

        $this->folder->refresh();
        $this->assertEquals(3072, $this->folder->getTotalSize());
    }

    /** @test */
    public function it_can_be_deleted_when_empty()
    {
        $this->assertTrue($this->folder->canDelete());
    }

    /** @test */
    public function it_cannot_be_deleted_when_has_files()
    {
        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $this->folder->id,
            'uploaded_by' => $this->user->id,
        ]);

        $this->folder->refresh();
        $this->assertFalse($this->folder->canDelete());
    }

    /** @test */
    public function it_cannot_be_deleted_when_has_subfolders()
    {
        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => $this->folder->id,
            'created_by' => $this->user->id,
        ]);

        $this->folder->refresh();
        $this->assertFalse($this->folder->canDelete());
    }

    /** @test */
    public function it_updates_path_when_name_changes()
    {
        $this->folder->update(['name' => 'New Name']);
        $this->assertEquals('New Name', $this->folder->path);
    }

    /** @test */
    public function it_updates_nested_paths_when_parent_name_changes()
    {
        $childFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => $this->folder->id,
            'name' => 'Child',
            'created_by' => $this->user->id,
        ]);

        $this->folder->update(['name' => 'New Parent Name']);
        $childFolder->refresh();

        $this->assertEquals('New Parent Name/Child', $childFolder->getFullPath());
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DocumentFolder::create([
            // Missing required fields
        ]);
    }

    /** @test */
    public function it_enforces_foreign_key_constraints()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DocumentFolder::create([
            'facility_id' => 99999, // Non-existent facility
            'name' => 'Test Folder',
            'path' => 'Test Folder',
            'created_by' => $this->user->id,
        ]);
    }
}