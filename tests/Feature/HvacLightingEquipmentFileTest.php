<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HvacLightingEquipmentFileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function user_can_upload_hvac_inspection_report_pdf()
    {
        Storage::fake('public');

        $this->actingAs($this->user);

        $pdfFile = UploadedFile::fake()->create('hvac_inspection_report.pdf', 1024, 'application/pdf');

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'hvac-lighting']), [
            'basic_info' => [
                'hvac' => [
                    'freon_inspector' => 'テスト空調業者',
                    'inspection_date' => '2024-01-15',
                    'target_equipment' => 'テスト機器',
                    'notes' => 'テスト備考',
                ],
                'lighting' => [
                    'manufacturer' => 'パナソニック',
                    'update_date' => '2024-01-01',
                    'warranty_period' => '5年',
                    'notes' => '照明備考',
                ],
            ],
            'inspection_report_file' => $pdfFile,
            'notes' => '全体備考',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // ファイルが保存されたことを確認
        $files = Storage::disk('public')->files('hvac/inspection-reports');
        $this->assertNotEmpty($files);

        // データベースに正しく保存されたことを確認
        $this->facility->refresh();
        $hvacLightingEquipment = $this->facility->getHvacLightingEquipment();
        $this->assertNotNull($hvacLightingEquipment);

        $basicInfo = $hvacLightingEquipment->basic_info;
        $this->assertArrayHasKey('hvac', $basicInfo);
        $this->assertArrayHasKey('inspection', $basicInfo['hvac']);
        $this->assertNotNull($basicInfo['hvac']['inspection']['inspection_report_filename']);
        $this->assertNotNull($basicInfo['hvac']['inspection']['inspection_report_path']);
    }
}