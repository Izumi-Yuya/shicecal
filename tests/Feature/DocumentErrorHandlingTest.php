<?php

namespace Tests\Feature;

use App\Exceptions\DocumentServiceException;
use App\Models\ActivityLog;
use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use App\Services\DocumentErrorHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create();
        
        Storage::fake('public');
    }

    /** @test */
    public function it_handles_folder_creation_errors_with_proper_logging()
    {
        $this->actingAs($this->user);

        // Create a folder with the same name first
        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Test Folder',
            'parent_id' => null,
        ]);

        // Try to create another folder with the same name
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Test Folder',
            'parent_id' => null,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'error_type'
        ]);

        // Verify error logging
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'security_unauthorized_access',
            'target_type' => 'document_folder',
        ]);
    }

    /** @test */
    public function it_handles_file_upload_errors_with_proper_logging()
    {
        $this->actingAs($this->user);

        // Create a file that's too large (simulate)
        $file = UploadedFile::fake()->create('large_file.pdf', 50000); // 50MB

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'files' => [$file],
            'folder_id' => null,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'error_type'
        ]);
    }

    /** @test */
    public function it_handles_authorization_errors_properly()
    {
        // Create a user without proper permissions
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Test Folder',
        ]);

        $response->assertStatus(403);
        $response->assertJsonStructure([
            'success',
            'message',
            'error_type'
        ]);

        $responseData = $response->json();
        $this->assertEquals('authorization', $responseData['error_type']);
    }

    /** @test */
    public function it_handles_not_found_errors_properly()
    {
        $this->actingAs($this->user);

        // Try to access a non-existent folder
        $response = $this->getJson(route('facilities.documents.show', [
            $this->facility,
            999 // Non-existent folder ID
        ]));

        $response->assertStatus(404);
        $response->assertJsonStructure([
            'success',
            'message',
            'error_type'
        ]);

        $responseData = $response->json();
        $this->assertEquals('not_found', $responseData['error_type']);
    }

    /** @test */
    public function it_logs_successful_document_operations()
    {
        $this->actingAs($this->user);

        // Create a folder
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Test Folder',
            'parent_id' => null,
        ]);

        $response->assertStatus(201);

        // Verify activity logging
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'create',
            'target_type' => 'document_folder',
        ]);

        $activityLog = ActivityLog::where('user_id', $this->user->id)
            ->where('action', 'create')
            ->where('target_type', 'document_folder')
            ->first();

        $this->assertStringContainsString('Test Folder', $activityLog->description);
        $this->assertStringContainsString((string)$this->facility->id, $activityLog->description);
    }

    /** @test */
    public function it_logs_file_operations_with_detailed_information()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Test Folder',
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 1024);

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'files' => [$file],
            'folder_id' => $folder->id,
        ]);

        $response->assertStatus(201);

        // Verify detailed activity logging
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'upload',
            'target_type' => 'document_file',
        ]);

        $activityLog = ActivityLog::where('user_id', $this->user->id)
            ->where('action', 'upload')
            ->where('target_type', 'document_file')
            ->first();

        $this->assertStringContainsString('test.pdf', $activityLog->description);
        $this->assertStringContainsString('Test Folder', $activityLog->description);
        $this->assertStringContainsString((string)$this->facility->id, $activityLog->description);
        $this->assertStringContainsString('KB', $activityLog->description); // File size
    }

    /** @test */
    public function it_logs_bulk_operations()
    {
        $this->actingAs($this->user);

        // Create multiple folders
        $folders = DocumentFolder::factory()->count(3)->create([
            'facility_id' => $this->facility->id,
        ]);

        // Simulate bulk delete (this would be implemented in the controller)
        $folderIds = $folders->pluck('id')->toArray();
        
        // Mock the bulk operation logging
        $activityLogService = app(\App\Services\ActivityLogService::class);
        $activityLogService->logDocumentBulkOperation(
            'delete',
            $folderIds,
            'folder',
            $this->facility->id,
            ['count' => count($folderIds)]
        );

        // Verify bulk operation logging
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'bulk_delete',
            'target_type' => 'document_folder',
        ]);

        $activityLog = ActivityLog::where('user_id', $this->user->id)
            ->where('action', 'bulk_delete')
            ->where('target_type', 'document_folder')
            ->first();

        $this->assertStringContainsString('3個', $activityLog->description);
        $this->assertStringContainsString('削除', $activityLog->description);
    }

    /** @test */
    public function it_logs_security_events()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
        ]);

        // Mock a security event
        $activityLogService = app(\App\Services\ActivityLogService::class);
        $activityLogService->logDocumentSecurityEvent(
            'unauthorized_access',
            $folder->id,
            'folder',
            [
                'ip_address' => '192.168.1.100',
                'attempted_action' => 'delete',
                'user_agent' => 'Test Browser'
            ]
        );

        // Verify security event logging
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'security_unauthorized_access',
            'target_type' => 'document_folder',
            'target_id' => $folder->id,
        ]);

        $activityLog = ActivityLog::where('user_id', $this->user->id)
            ->where('action', 'security_unauthorized_access')
            ->first();

        $this->assertStringContainsString('不正アクセス試行', $activityLog->description);
        $this->assertStringContainsString('192.168.1.100', $activityLog->description);
    }

    /** @test */
    public function document_error_handler_analyzes_errors_correctly()
    {
        // Test network error
        $networkError = new \Exception('Connection timeout');
        $errorType = $this->callPrivateMethod(DocumentErrorHandler::class, 'determineErrorType', [$networkError]);
        
        $this->assertEquals(DocumentErrorHandler::ERROR_TYPES['NETWORK'], $errorType);

        // Test validation error
        $validationError = new \Illuminate\Validation\ValidationException(
            validator([], ['name' => 'required'])
        );
        $errorType = $this->callPrivateMethod(DocumentErrorHandler::class, 'determineErrorType', [$validationError]);
        
        $this->assertEquals(DocumentErrorHandler::ERROR_TYPES['VALIDATION'], $errorType);

        // Test authorization error
        $authError = new \Illuminate\Auth\Access\AuthorizationException('Unauthorized');
        $errorType = $this->callPrivateMethod(DocumentErrorHandler::class, 'determineErrorType', [$authError]);
        
        $this->assertEquals(DocumentErrorHandler::ERROR_TYPES['AUTHORIZATION'], $errorType);
    }

    /**
     * Helper method to call private methods for testing
     */
    private function callPrivateMethod($class, $method, $args = [])
    {
        $reflection = new \ReflectionClass($class);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    /** @test */
    public function document_service_exception_provides_detailed_context()
    {
        $exception = DocumentServiceException::duplicateName('Test Folder', 'フォルダ', [
            'facility_id' => $this->facility->id,
            'parent_id' => null
        ]);

        $this->assertEquals('DUPLICATE_NAME', $exception->getErrorCode());
        $this->assertArrayHasKey('facility_id', $exception->getContext());
        $this->assertEquals($this->facility->id, $exception->getContext()['facility_id']);
        $this->assertStringContainsString('Test Folder', $exception->getMessage());
    }

    /** @test */
    public function it_handles_storage_errors_with_proper_context()
    {
        // Mock storage full error
        $exception = DocumentServiceException::storageFull(100, 50, [
            'facility_id' => $this->facility->id,
            'operation' => 'file_upload'
        ]);

        $this->assertEquals('STORAGE_FULL', $exception->getErrorCode());
        $this->assertStringContainsString('100MB', $exception->getMessage());
        $this->assertStringContainsString('50MB', $exception->getMessage());
    }

    /** @test */
    public function it_handles_file_operation_errors_with_context()
    {
        $exception = DocumentServiceException::uploadFailed('test.pdf', 'Invalid file type', [
            'facility_id' => $this->facility->id,
            'file_size' => 1024,
            'mime_type' => 'application/octet-stream'
        ]);

        $this->assertEquals('UPLOAD_FAILED', $exception->getErrorCode());
        $this->assertStringContainsString('test.pdf', $exception->getMessage());
        $this->assertStringContainsString('Invalid file type', $exception->getMessage());
        $this->assertEquals(1024, $exception->getContext()['file_size']);
    }
}