<?php

namespace Tests\Unit\Models;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentFileTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentFile $file;
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
        $this->file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $this->folder->id,
            'uploaded_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_facility()
    {
        $this->assertInstanceOf(Facility::class, $this->file->facility);
        $this->assertEquals($this->facility->id, $this->file->facility->id);
    }

    /** @test */
    public function it_belongs_to_a_folder()
    {
        $this->assertInstanceOf(DocumentFolder::class, $this->file->folder);
        $this->assertEquals($this->folder->id, $this->file->folder->id);
    }

    /** @test */
    public function it_can_exist_without_a_folder()
    {
        $rootFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'uploaded_by' => $this->user->id,
        ]);

        $this->assertNull($rootFile->folder);
    }

    /** @test */
    public function it_belongs_to_an_uploader()
    {
        $this->assertInstanceOf(User::class, $this->file->uploader);
        $this->assertEquals($this->user->id, $this->file->uploader->id);
    }

    /** @test */
    public function it_formats_file_size_correctly()
    {
        $testCases = [
            ['size' => 512, 'expected' => '512 B'],
            ['size' => 1024, 'expected' => '1.00 KB'],
            ['size' => 1536, 'expected' => '1.50 KB'],
            ['size' => 1048576, 'expected' => '1.00 MB'],
            ['size' => 1073741824, 'expected' => '1.00 GB'],
            ['size' => 0, 'expected' => '0 B'],
        ];

        foreach ($testCases as $testCase) {
            $file = DocumentFile::factory()->create([
                'facility_id' => $this->facility->id,
                'folder_id' => $this->folder->id,
                'file_size' => $testCase['size'],
                'uploaded_by' => $this->user->id,
            ]);

            $this->assertEquals($testCase['expected'], $file->getFormattedSize());
        }
    }

    /** @test */
    public function it_returns_correct_file_icon_for_different_types()
    {
        $testCases = [
            ['extension' => 'pdf', 'expected' => 'fas fa-file-pdf'],
            ['extension' => 'doc', 'expected' => 'fas fa-file-word'],
            ['extension' => 'docx', 'expected' => 'fas fa-file-word'],
            ['extension' => 'xls', 'expected' => 'fas fa-file-excel'],
            ['extension' => 'xlsx', 'expected' => 'fas fa-file-excel'],
            ['extension' => 'jpg', 'expected' => 'fas fa-file-image'],
            ['extension' => 'jpeg', 'expected' => 'fas fa-file-image'],
            ['extension' => 'png', 'expected' => 'fas fa-file-image'],
            ['extension' => 'gif', 'expected' => 'fas fa-file-image'],
            ['extension' => 'txt', 'expected' => 'fas fa-file-alt'],
            ['extension' => 'unknown', 'expected' => 'fas fa-file'],
        ];

        foreach ($testCases as $testCase) {
            $file = DocumentFile::factory()->create([
                'facility_id' => $this->facility->id,
                'folder_id' => $this->folder->id,
                'file_extension' => $testCase['extension'],
                'uploaded_by' => $this->user->id,
            ]);

            $this->assertEquals($testCase['expected'], $file->getFileIcon());
        }
    }

    /** @test */
    public function it_returns_correct_file_color_for_different_types()
    {
        $testCases = [
            ['extension' => 'pdf', 'expected' => 'text-danger'],
            ['extension' => 'doc', 'expected' => 'text-primary'],
            ['extension' => 'docx', 'expected' => 'text-primary'],
            ['extension' => 'xls', 'expected' => 'text-success'],
            ['extension' => 'xlsx', 'expected' => 'text-success'],
            ['extension' => 'jpg', 'expected' => 'text-info'],
            ['extension' => 'jpeg', 'expected' => 'text-info'],
            ['extension' => 'png', 'expected' => 'text-info'],
            ['extension' => 'gif', 'expected' => 'text-info'],
            ['extension' => 'txt', 'expected' => 'text-secondary'],
            ['extension' => 'unknown', 'expected' => 'text-muted'],
        ];

        foreach ($testCases as $testCase) {
            $file = DocumentFile::factory()->create([
                'facility_id' => $this->facility->id,
                'folder_id' => $this->folder->id,
                'file_extension' => $testCase['extension'],
                'uploaded_by' => $this->user->id,
            ]);

            $this->assertEquals($testCase['expected'], $file->getFileColor());
        }
    }

    /** @test */
    public function it_generates_correct_download_url()
    {
        $expectedUrl = route('facilities.documents.files.download', [
            'facility' => $this->facility->id,
            'file' => $this->file->id
        ]);

        $this->assertEquals($expectedUrl, $this->file->getDownloadUrl());
    }

    /** @test */
    public function it_detects_previewable_files_correctly()
    {
        $previewableExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt'];
        $nonPreviewableExtensions = ['doc', 'docx', 'xls', 'xlsx', 'zip', 'rar'];

        foreach ($previewableExtensions as $extension) {
            $file = DocumentFile::factory()->create([
                'facility_id' => $this->facility->id,
                'folder_id' => $this->folder->id,
                'file_extension' => $extension,
                'uploaded_by' => $this->user->id,
            ]);

            $this->assertTrue($file->canPreview(), "File with extension {$extension} should be previewable");
        }

        foreach ($nonPreviewableExtensions as $extension) {
            $file = DocumentFile::factory()->create([
                'facility_id' => $this->facility->id,
                'folder_id' => $this->folder->id,
                'file_extension' => $extension,
                'uploaded_by' => $this->user->id,
            ]);

            $this->assertFalse($file->canPreview(), "File with extension {$extension} should not be previewable");
        }
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DocumentFile::create([
            // Missing required fields
        ]);
    }

    /** @test */
    public function it_enforces_foreign_key_constraints()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DocumentFile::create([
            'facility_id' => 99999, // Non-existent facility
            'folder_id' => $this->folder->id,
            'original_name' => 'test.pdf',
            'stored_name' => 'stored_test.pdf',
            'file_path' => 'documents/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'uploaded_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_casts_file_size_to_integer()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $this->folder->id,
            'file_size' => '1024',
            'uploaded_by' => $this->user->id,
        ]);

        $this->assertIsInt($file->file_size);
        $this->assertEquals(1024, $file->file_size);
    }

    /** @test */
    public function it_casts_timestamps_correctly()
    {
        $this->assertInstanceOf(\Carbon\Carbon::class, $this->file->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $this->file->updated_at);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'facility_id',
            'folder_id',
            'original_name',
            'stored_name',
            'file_path',
            'file_size',
            'mime_type',
            'file_extension',
            'uploaded_by'
        ];

        $this->assertEquals($fillable, $this->file->getFillable());
    }

    /** @test */
    public function it_generates_file_display_data_correctly()
    {
        $displayData = [
            'filename' => $this->file->original_name,
            'download_url' => $this->file->getDownloadUrl(),
            'icon' => $this->file->getFileIcon(),
            'color' => $this->file->getFileColor(),
            'size' => $this->file->getFormattedSize(),
            'exists' => true,
        ];

        $this->assertEquals($displayData['filename'], $this->file->original_name);
        $this->assertEquals($displayData['download_url'], $this->file->getDownloadUrl());
        $this->assertEquals($displayData['icon'], $this->file->getFileIcon());
        $this->assertEquals($displayData['color'], $this->file->getFileColor());
        $this->assertEquals($displayData['size'], $this->file->getFormattedSize());
    }
}