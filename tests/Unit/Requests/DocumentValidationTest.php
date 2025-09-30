<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Rules\ValidFolderName;
use App\Rules\UniqueFolderName;
use App\Rules\SecureFileUpload;
use App\Models\Facility;
use App\Models\DocumentFolder;
use App\Models\User;

class DocumentValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function valid_folder_name_rule_passes_for_valid_names()
    {
        $rule = new ValidFolderName();
        
        $validNames = [
            'Valid Folder',
            'フォルダ名',
            'Folder123',
            'My-Folder_Name',
            'Test (1)',
        ];

        foreach ($validNames as $name) {
            $this->assertTrue($rule->passes('name', $name), "Name '{$name}' should be valid");
        }
    }

    /** @test */
    public function valid_folder_name_rule_fails_for_invalid_names()
    {
        $rule = new ValidFolderName();
        
        $invalidNames = [
            '',
            '   ',
            'folder/name',
            'folder\\name',
            'folder:name',
            'folder*name',
            'folder?name',
            'folder"name',
            'folder<name',
            'folder>name',
            'folder|name',
            'CON',
            'PRN',
            'AUX',
            'NUL',
            'COM1',
            'LPT1',
            '.folder',
            'folder.',
            ' folder',
            'folder ',
        ];

        foreach ($invalidNames as $name) {
            $this->assertFalse($rule->passes('name', $name), "Name '{$name}' should be invalid");
        }
    }

    /** @test */
    public function unique_folder_name_rule_passes_for_unique_names()
    {
        $rule = new UniqueFolderName($this->facility->id, null);
        
        $this->assertTrue($rule->passes('name', 'Unique Folder'));
    }

    /** @test */
    public function unique_folder_name_rule_fails_for_duplicate_names()
    {
        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => null,
            'name' => 'Existing Folder',
        ]);

        $rule = new UniqueFolderName($this->facility->id, null);
        
        $this->assertFalse($rule->passes('name', 'Existing Folder'));
    }

    /** @test */
    public function unique_folder_name_rule_allows_same_name_in_different_parent()
    {
        $parentFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => null,
            'name' => 'Parent Folder',
        ]);

        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => null,
            'name' => 'Test Folder',
        ]);

        $rule = new UniqueFolderName($this->facility->id, $parentFolder->id);
        
        $this->assertTrue($rule->passes('name', 'Test Folder'));
    }

    /** @test */
    public function unique_folder_name_rule_excludes_current_folder()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => null,
            'name' => 'Test Folder',
        ]);

        $rule = new UniqueFolderName($this->facility->id, null, $folder->id);
        
        $this->assertTrue($rule->passes('name', 'Test Folder'));
    }

    /** @test */
    public function secure_file_upload_rule_passes_for_valid_files()
    {
        Storage::fake('public');
        
        $rule = new SecureFileUpload();
        
        $validFiles = [
            UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('image.jpg', 100, 'image/jpeg'),
            UploadedFile::fake()->create('document.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ];

        foreach ($validFiles as $file) {
            $this->assertTrue($rule->passes('file', $file), "File '{$file->getClientOriginalName()}' should be valid");
        }
    }

    /** @test */
    public function secure_file_upload_rule_fails_for_invalid_files()
    {
        Storage::fake('public');
        
        $rule = new SecureFileUpload();
        
        // Test with non-file input
        $this->assertFalse($rule->passes('file', 'not_a_file'));
        
        // Test with invalid extension
        $invalidFile = UploadedFile::fake()->create('script.exe', 100, 'application/octet-stream');
        $this->assertFalse($rule->passes('file', $invalidFile));
    }

    /** @test */
    public function secure_file_upload_rule_detects_dangerous_filenames()
    {
        Storage::fake('public');
        
        $rule = new SecureFileUpload();
        
        // Test with invalid extension (should fail)
        $invalidFile = UploadedFile::fake()->create('script.exe', 100, 'application/octet-stream');
        $this->assertFalse($rule->passes('file', $invalidFile));
        
        // Test with oversized file (should fail)
        $oversizedFile = UploadedFile::fake()->create('large.pdf', 20480, 'application/pdf'); // 20MB
        $this->assertFalse($rule->passes('file', $oversizedFile));
    }

    /** @test */
    public function folder_validation_works_with_validator()
    {
        $validator = Validator::make(
            ['name' => 'Valid Folder Name'],
            ['name' => ['required', new ValidFolderName(), new UniqueFolderName($this->facility->id, null)]]
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function folder_validation_fails_with_invalid_data()
    {
        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => null,
            'name' => 'Existing Folder',
        ]);

        $validator = Validator::make(
            ['name' => 'Existing Folder'],
            ['name' => ['required', new ValidFolderName(), new UniqueFolderName($this->facility->id, null)]]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
}