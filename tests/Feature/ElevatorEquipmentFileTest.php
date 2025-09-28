<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ElevatorEquipmentFileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'editor',
            'access_scope' => 'all',
        ]);
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function it_can_upload_inspection_report_pdf()
    {
        Storage::fake('public');

        $this->actingAs($this->user);

        $pdfFile = UploadedFile::fake()->create('inspection_report.pdf', 1024, 'application/pdf');

        $data = [
            'basic_info' => [
                'availability' => '有',
                'inspection' => [
                    'maintenance_contractor' => 'テスト保守会社',
                    'inspection_date' => '2024-01-15',
                ],
            ],
            'inspection_report_file' => $pdfFile,
            'notes' => 'ファイルアップロードテスト',
        ];

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'elevator']), $data);

        $response->assertRedirect(route('facilities.show', $this->facility).'#elevator');
        $response->assertSessionHas('success');

        // ファイルが保存されているか確認
        $files = Storage::disk('public')->files('elevator/inspection-reports');
        $this->assertNotEmpty($files);

        // データベースにファイル情報が保存されているか確認
        $elevatorEquipment = $this->facility->fresh()->getElevatorEquipment();
        $this->assertNotNull($elevatorEquipment);
        $this->assertEquals('inspection_report.pdf', $elevatorEquipment->basic_info['inspection']['inspection_report_filename']);
        $this->assertNotNull($elevatorEquipment->basic_info['inspection']['inspection_report_path']);
    }

    /** @test */
    public function it_can_download_inspection_report_pdf()
    {
        Storage::fake('public');

        $this->actingAs($this->user);

        // テストファイルを作成
        $pdfContent = 'fake pdf content';
        $filename = 'test_inspection_report.pdf';
        $path = 'elevator/inspection-reports/'.$filename;

        Storage::disk('public')->put($path, $pdfContent);

        // エレベーター設備データを作成
        $lifelineEquipment = $this->facility->lifelineEquipment()->create(['category' => 'elevator']);
        $elevatorEquipment = $lifelineEquipment->elevatorEquipment()->create([
            'basic_info' => [
                'availability' => '有',
                'inspection' => [
                    'maintenance_contractor' => 'テスト保守会社',
                    'inspection_date' => '2024-01-15',
                    'inspection_report_filename' => 'inspection_report.pdf',
                    'inspection_report_path' => $path,
                ],
            ],
            'notes' => 'ダウンロードテスト',
        ]);

        $response = $this->get(route('facilities.lifeline-equipment.download-file', [
            $this->facility,
            'elevator',
            'inspection_report',
        ]));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=inspection_report.pdf');
    }

    /** @test */
    public function it_can_remove_inspection_report_pdf()
    {
        Storage::fake('public');

        $this->actingAs($this->user);

        // 既存のファイルを作成
        $pdfContent = 'fake pdf content';
        $filename = 'existing_report.pdf';
        $path = 'elevator/inspection-reports/'.$filename;

        Storage::disk('public')->put($path, $pdfContent);

        // エレベーター設備データを作成
        $lifelineEquipment = $this->facility->lifelineEquipment()->create(['category' => 'elevator']);
        $elevatorEquipment = $lifelineEquipment->elevatorEquipment()->create([
            'basic_info' => [
                'availability' => '有',
                'inspection' => [
                    'maintenance_contractor' => 'テスト保守会社',
                    'inspection_date' => '2024-01-15',
                    'inspection_report_filename' => 'existing_report.pdf',
                    'inspection_report_path' => $path,
                ],
            ],
            'notes' => 'ファイル削除テスト',
        ]);

        $data = [
            'basic_info' => [
                'availability' => '有',
                'inspection' => [
                    'maintenance_contractor' => 'テスト保守会社',
                    'inspection_date' => '2024-01-15',
                ],
            ],
            'remove_inspection_report' => '1',
            'notes' => 'ファイル削除テスト',
        ];

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'elevator']), $data);

        $response->assertRedirect(route('facilities.show', $this->facility).'#elevator');
        $response->assertSessionHas('success');

        // ファイルが削除されているか確認
        Storage::disk('public')->assertMissing($path);

        // データベースからファイル情報が削除されているか確認
        $elevatorEquipment = $this->facility->fresh()->getElevatorEquipment();
        $this->assertNull($elevatorEquipment->basic_info['inspection']['inspection_report_filename']);
        $this->assertNull($elevatorEquipment->basic_info['inspection']['inspection_report_path']);
    }

    /** @test */
    public function it_replaces_old_file_when_uploading_new_one()
    {
        Storage::fake('public');

        $this->actingAs($this->user);

        // 既存のファイルを作成
        $oldPdfContent = 'old pdf content';
        $oldFilename = 'old_report.pdf';
        $oldPath = 'elevator/inspection-reports/'.$oldFilename;

        Storage::disk('public')->put($oldPath, $oldPdfContent);

        // エレベーター設備データを作成
        $lifelineEquipment = $this->facility->lifelineEquipment()->create(['category' => 'elevator']);
        $elevatorEquipment = $lifelineEquipment->elevatorEquipment()->create([
            'basic_info' => [
                'availability' => '有',
                'inspection' => [
                    'maintenance_contractor' => 'テスト保守会社',
                    'inspection_date' => '2024-01-15',
                    'inspection_report_filename' => 'old_report.pdf',
                    'inspection_report_path' => $oldPath,
                ],
            ],
            'notes' => 'ファイル置換テスト',
        ]);

        // 新しいファイルをアップロード
        $newPdfFile = UploadedFile::fake()->create('new_report.pdf', 1024, 'application/pdf');

        $data = [
            'basic_info' => [
                'availability' => '有',
                'inspection' => [
                    'maintenance_contractor' => 'テスト保守会社',
                    'inspection_date' => '2024-01-15',
                ],
            ],
            'inspection_report_file' => $newPdfFile,
            'notes' => 'ファイル置換テスト',
        ];

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'elevator']), $data);

        $response->assertRedirect(route('facilities.show', $this->facility).'#elevator');
        $response->assertSessionHas('success');

        // 古いファイルが削除されているか確認
        Storage::disk('public')->assertMissing($oldPath);

        // 新しいファイルが保存されているか確認
        $files = Storage::disk('public')->files('elevator/inspection-reports');
        $this->assertNotEmpty($files);

        // データベースに新しいファイル情報が保存されているか確認
        $elevatorEquipment = $this->facility->fresh()->getElevatorEquipment();
        $this->assertEquals('new_report.pdf', $elevatorEquipment->basic_info['inspection']['inspection_report_filename']);
        $this->assertNotNull($elevatorEquipment->basic_info['inspection']['inspection_report_path']);
        $this->assertNotEquals($oldPath, $elevatorEquipment->basic_info['inspection']['inspection_report_path']);
    }

    /** @test */
    public function it_validates_pdf_file_type()
    {
        Storage::fake('public');

        $this->actingAs($this->user);

        $invalidFile = UploadedFile::fake()->create('document.txt', 1024, 'text/plain');

        $data = [
            'basic_info' => [
                'availability' => '有',
                'inspection' => [
                    'maintenance_contractor' => 'テスト保守会社',
                    'inspection_date' => '2024-01-15',
                ],
            ],
            'inspection_report_file' => $invalidFile,
            'notes' => 'バリデーションテスト',
        ];

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'elevator']), $data);

        $response->assertSessionHasErrors('inspection_report_file');
    }

    /** @test */
    public function it_validates_file_size_limit()
    {
        Storage::fake('public');

        $this->actingAs($this->user);

        // 10MBを超えるファイルを作成
        $largeFile = UploadedFile::fake()->create('large_report.pdf', 11 * 1024, 'application/pdf');

        $data = [
            'basic_info' => [
                'availability' => '有',
                'inspection' => [
                    'maintenance_contractor' => 'テスト保守会社',
                    'inspection_date' => '2024-01-15',
                ],
            ],
            'inspection_report_file' => $largeFile,
            'notes' => 'ファイルサイズテスト',
        ];

        $response = $this->put(route('facilities.lifeline-equipment.update', [$this->facility, 'elevator']), $data);

        $response->assertSessionHasErrors('inspection_report_file');
    }

    /** @test */
    public function unauthorized_user_cannot_download_file()
    {
        Storage::fake('public');

        // ファイルを作成
        $pdfContent = 'fake pdf content';
        $filename = 'test_inspection_report.pdf';
        $path = 'elevator/inspection-reports/'.$filename;

        Storage::disk('public')->put($path, $pdfContent);

        // エレベーター設備データを作成
        $lifelineEquipment = $this->facility->lifelineEquipment()->create(['category' => 'elevator']);
        $elevatorEquipment = $lifelineEquipment->elevatorEquipment()->create([
            'basic_info' => [
                'availability' => '有',
                'inspection' => [
                    'maintenance_contractor' => 'テスト保守会社',
                    'inspection_date' => '2024-01-15',
                    'inspection_report_filename' => 'inspection_report.pdf',
                    'inspection_report_path' => $path,
                ],
            ],
            'notes' => '認証テスト',
        ]);

        // 認証なしでアクセス
        $response = $this->get(route('facilities.lifeline-equipment.download-file', [
            $this->facility,
            'elevator',
            'inspection_report',
        ]));

        $response->assertStatus(302); // リダイレクト（ログインページへ）
    }
}
