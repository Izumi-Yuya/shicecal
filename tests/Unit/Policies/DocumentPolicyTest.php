<?php

namespace Tests\Unit\Policies;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use App\Policies\DocumentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentPolicy $policy;
    protected User $adminUser;
    protected User $editorUser;
    protected User $viewerUser;
    protected User $unauthorizedUser;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new DocumentPolicy();
        
        // Create users with different permissions
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->editorUser = User::factory()->create(['role' => 'editor']);
        $this->viewerUser = User::factory()->create(['role' => 'viewer']);
        $this->unauthorizedUser = User::factory()->create(['role' => 'viewer']);
        
        $this->facility = Facility::factory()->create();

        // Mock user permissions
        $this->mockUserPermissions();
    }

    protected function mockUserPermissions(): void
    {
        // Admin user - can do everything
        $this->adminUser->shouldReceive('canViewFacility')
            ->with($this->facility->id)
            ->andReturn(true);
        $this->adminUser->shouldReceive('canEditFacility')
            ->with($this->facility->id)
            ->andReturn(true);

        // Editor user - can view and edit
        $this->editorUser->shouldReceive('canViewFacility')
            ->with($this->facility->id)
            ->andReturn(true);
        $this->editorUser->shouldReceive('canEditFacility')
            ->with($this->facility->id)
            ->andReturn(true);

        // Viewer user - can only view
        $this->viewerUser->shouldReceive('canViewFacility')
            ->with($this->facility->id)
            ->andReturn(true);
        $this->viewerUser->shouldReceive('canEditFacility')
            ->with($this->facility->id)
            ->andReturn(false);

        // Unauthorized user - cannot view or edit
        $this->unauthorizedUser->shouldReceive('canViewFacility')
            ->with($this->facility->id)
            ->andReturn(false);
        $this->unauthorizedUser->shouldReceive('canEditFacility')
            ->with($this->facility->id)
            ->andReturn(false);
    }

    /** @test */
    public function admin_can_view_any_documents()
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser, $this->facility));
    }

    /** @test */
    public function editor_can_view_any_documents()
    {
        $this->assertTrue($this->policy->viewAny($this->editorUser, $this->facility));
    }

    /** @test */
    public function viewer_can_view_any_documents()
    {
        $this->assertTrue($this->policy->viewAny($this->viewerUser, $this->facility));
    }

    /** @test */
    public function unauthorized_user_cannot_view_any_documents()
    {
        $this->assertFalse($this->policy->viewAny($this->unauthorizedUser, $this->facility));
    }

    /** @test */
    public function admin_can_view_specific_folder()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->view($this->adminUser, $folder));
    }

    /** @test */
    public function editor_can_view_specific_folder()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->view($this->editorUser, $folder));
    }

    /** @test */
    public function viewer_can_view_specific_folder()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->view($this->viewerUser, $folder));
    }

    /** @test */
    public function unauthorized_user_cannot_view_specific_folder()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertFalse($this->policy->view($this->unauthorizedUser, $folder));
    }

    /** @test */
    public function admin_can_create_folders()
    {
        $this->assertTrue($this->policy->create($this->adminUser, $this->facility));
    }

    /** @test */
    public function editor_can_create_folders()
    {
        $this->assertTrue($this->policy->create($this->editorUser, $this->facility));
    }

    /** @test */
    public function viewer_cannot_create_folders()
    {
        $this->assertFalse($this->policy->create($this->viewerUser, $this->facility));
    }

    /** @test */
    public function unauthorized_user_cannot_create_folders()
    {
        $this->assertFalse($this->policy->create($this->unauthorizedUser, $this->facility));
    }

    /** @test */
    public function admin_can_update_folders()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->update($this->adminUser, $folder));
    }

    /** @test */
    public function editor_can_update_folders()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->update($this->editorUser, $folder));
    }

    /** @test */
    public function viewer_cannot_update_folders()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertFalse($this->policy->update($this->viewerUser, $folder));
    }

    /** @test */
    public function unauthorized_user_cannot_update_folders()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertFalse($this->policy->update($this->unauthorizedUser, $folder));
    }

    /** @test */
    public function admin_can_delete_empty_folders()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->delete($this->adminUser, $folder));
    }

    /** @test */
    public function editor_can_delete_empty_folders()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->delete($this->editorUser, $folder));
    }

    /** @test */
    public function viewer_cannot_delete_folders()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertFalse($this->policy->delete($this->viewerUser, $folder));
    }

    /** @test */
    public function unauthorized_user_cannot_delete_folders()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertFalse($this->policy->delete($this->unauthorizedUser, $folder));
    }

    /** @test */
    public function admin_cannot_delete_non_empty_folders()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        // Add a file to make folder non-empty
        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $folder->id,
        ]);

        $this->assertFalse($this->policy->delete($this->adminUser, $folder));
    }

    /** @test */
    public function admin_can_view_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->viewFile($this->adminUser, $file));
    }

    /** @test */
    public function editor_can_view_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->viewFile($this->editorUser, $file));
    }

    /** @test */
    public function viewer_can_view_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->viewFile($this->viewerUser, $file));
    }

    /** @test */
    public function unauthorized_user_cannot_view_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertFalse($this->policy->viewFile($this->unauthorizedUser, $file));
    }

    /** @test */
    public function admin_can_upload_files()
    {
        $this->assertTrue($this->policy->upload($this->adminUser, $this->facility));
    }

    /** @test */
    public function editor_can_upload_files()
    {
        $this->assertTrue($this->policy->upload($this->editorUser, $this->facility));
    }

    /** @test */
    public function viewer_cannot_upload_files()
    {
        $this->assertFalse($this->policy->upload($this->viewerUser, $this->facility));
    }

    /** @test */
    public function unauthorized_user_cannot_upload_files()
    {
        $this->assertFalse($this->policy->upload($this->unauthorizedUser, $this->facility));
    }

    /** @test */
    public function admin_can_delete_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->deleteFile($this->adminUser, $file));
    }

    /** @test */
    public function editor_can_delete_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->deleteFile($this->editorUser, $file));
    }

    /** @test */
    public function viewer_cannot_delete_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertFalse($this->policy->deleteFile($this->viewerUser, $file));
    }

    /** @test */
    public function unauthorized_user_cannot_delete_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertFalse($this->policy->deleteFile($this->unauthorizedUser, $file));
    }

    /** @test */
    public function admin_can_download_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->download($this->adminUser, $file));
    }

    /** @test */
    public function editor_can_download_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->download($this->editorUser, $file));
    }

    /** @test */
    public function viewer_can_download_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertTrue($this->policy->download($this->viewerUser, $file));
    }

    /** @test */
    public function unauthorized_user_cannot_download_files()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        $this->assertFalse($this->policy->download($this->unauthorizedUser, $file));
    }

    /** @test */
    public function policy_handles_different_facility_correctly()
    {
        $otherFacility = Facility::factory()->create();
        
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $otherFacility->id,
        ]);

        // Mock permissions for other facility
        $this->adminUser->shouldReceive('canViewFacility')
            ->with($otherFacility->id)
            ->andReturn(false);

        $this->assertFalse($this->policy->view($this->adminUser, $folder));
    }

    /** @test */
    public function policy_validates_folder_belongs_to_facility()
    {
        $otherFacility = Facility::factory()->create();
        
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $otherFacility->id,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $otherFacility->id,
            'folder_id' => $folder->id,
        ]);

        // Mock permissions for other facility
        $this->adminUser->shouldReceive('canViewFacility')
            ->with($otherFacility->id)
            ->andReturn(false);

        $this->assertFalse($this->policy->viewFile($this->adminUser, $file));
    }
}