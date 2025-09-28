<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ElectricalEquipmentPdfUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create([
            'role' => 'editor',
        ]);
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function user_can_upload_pdf_inspection_report()
    {
        $this->actingAs($this->user);

        // Create a fake PDF file
        $pdfFile = UploadedFile::fake()->create('inspection_report.pdf', 1024, 'application/pdf');

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']), [
            'basic_info' => [
                'electrical_contractor' => 'テスト電気会社',
                'safety_management_company' => 'テスト保安管理会社',
                'maintenance_inspection_date' => '2024-01-15',
                'inspection_report_pdf_file' => $pdfFile,
            ],
        ]);

        $response->assertRedirect(route('facilities.show', $this->facility).'#electrical');
        $response->assertSessionHas('success', 'ライフライン設備情報を更新しました。');

        // Check that a file was stored in the electrical/inspection-reports directory
        $files = Storage::disk('public')->files('electrical/inspection-reports');
        $this->assertNotEmpty($files, 'No files found in electrical/inspection-reports directory');

        // Check that the database was updated
        $this->facility->refresh();
        $lifelineEquipment = $this->facility->getLifelineEquipmentByCategory('electrical');
        $this->assertNotNull($lifelineEquipment);

        $electricalEquipment = $lifelineEquipment->electricalEquipment;
        $this->assertNotNull($electricalEquipment);

        $basicInfo = $electricalEquipment->basic_info;
        $this->assertEquals('inspection_report.pdf', $basicInfo['inspection_report_pdf']);
        $this->assertArrayHasKey('inspection_report_pdf_path', $basicInfo);
        $this->assertStringContainsString('electrical/inspection-reports/', $basicInfo['inspection_report_pdf_path']);
    }

    /** @test */
    public function user_can_download_uploaded_pdf_inspection_report()
    {
        $this->actingAs($this->user);

        // Create lifeline equipment with PDF file
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $pdfContent = 'fake pdf content';
        $filename = 'test_inspection_report.pdf';
        $path = 'electrical/inspection-reports/'.$filename;

        Storage::disk('public')->put($path, $pdfContent);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => 'テスト電気会社',
                'inspection_report_pdf' => 'inspection_report.pdf',
                'inspection_report_pdf_path' => $path,
            ],
        ]);

        $response = $this->get(route('facilities.lifeline-equipment.download', [
            $this->facility,
            'electrical',
            'inspection_report.pdf',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function pdf_upload_validates_file_type()
    {
        $this->actingAs($this->user);

        // Create a fake non-PDF file
        $textFile = UploadedFile::fake()->create('not_a_pdf.txt', 1024, 'text/plain');

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']), [
            'basic_info' => [
                'inspection_report_pdf_file' => $textFile,
            ],
        ]);

        $response->assertSessionHasErrors('basic_info.inspection_report_pdf_file');
        $this->assertStringContainsString('PDFファイルのみアップロード可能です。',
            session('errors')->first('basic_info.inspection_report_pdf_file'));
    }

    /** @test */
    public function pdf_upload_validates_file_size()
    {
        $this->actingAs($this->user);

        // Create a fake PDF file that's too large (15MB)
        $largePdfFile = UploadedFile::fake()->create('large_inspection_report.pdf', 15360, 'application/pdf');

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']), [
            'basic_info' => [
                'inspection_report_pdf_file' => $largePdfFile,
            ],
        ]);

        $response->assertSessionHasErrors('basic_info.inspection_report_pdf_file');
        $this->assertStringContainsString('10MB以下にしてください',
            session('errors')->first('basic_info.inspection_report_pdf_file'));
    }

    /** @test */
    public function unauthorized_user_cannot_download_pdf()
    {
        // Test with unauthenticated user (no login)
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $pdfContent = 'fake pdf content';
        $filename = 'test_inspection_report.pdf';
        $path = 'electrical/inspection-reports/'.$filename;

        Storage::disk('public')->put($path, $pdfContent);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'inspection_report_pdf' => 'inspection_report.pdf',
                'inspection_report_pdf_path' => $path,
            ],
        ]);

        // Try to access without authentication
        $response = $this->get(route('facilities.lifeline-equipment.download', [
            $this->facility,
            'electrical',
            'inspection_report.pdf',
        ]));

        // Should redirect to login page
        $response->assertRedirect('/login');
    }

    /** @test */
    public function download_returns_404_for_nonexistent_file()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.lifeline-equipment.download', [
            $this->facility,
            'electrical',
            'nonexistent.pdf',
        ]));

        $response->assertNotFound();
    }
}
