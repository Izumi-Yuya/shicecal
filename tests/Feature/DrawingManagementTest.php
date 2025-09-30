<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityDrawing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DrawingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function user_can_view_drawings_tab()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertOk();
        $response->assertSee('図面');
        $response->assertSee('建物図面');
        $response->assertSee('設備図面');
    }

    /** @test */
    public function user_can_access_drawings_edit_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.drawings.edit', $this->facility));

        $response->assertOk();
        $response->assertSee('図面編集');
        $response->assertSee('平面図');
        $response->assertSee('電気設備図面');
        $response->assertSee('竣工図面一式');
    }

    /** @test */
    public function user_can_upload_building_drawing()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $pdfFile = UploadedFile::fake()->create('floor_plan.pdf', 1024, 'application/pdf');

        $response = $this->put(route('facilities.drawings.update', $this->facility), [
            'building_drawings' => [
                'floor_plan' => $pdfFile,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('activeTab', 'drawings');

        $this->assertDatabaseHas('facility_drawings', [
            'facility_id' => $this->facility->id,
            'floor_plan_filename' => 'floor_plan.pdf',
        ]);

        // ファイルが保存されたことを確認（ディレクトリ構造は実装に依存）
        $allFiles = Storage::disk('public')->allFiles();
        $this->assertNotEmpty($allFiles);
    }

    /** @test */
    public function user_can_upload_equipment_drawing()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $pdfFile = UploadedFile::fake()->create('electrical.pdf', 1024, 'application/pdf');

        $response = $this->put(route('facilities.drawings.update', $this->facility), [
            'equipment_drawings' => [
                'electrical_equipment' => $pdfFile,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('facility_drawings', [
            'facility_id' => $this->facility->id,
            'electrical_equipment_filename' => 'electrical.pdf',
        ]);
    }

    /** @test */
    public function user_can_upload_completion_drawing()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $pdfFile = UploadedFile::fake()->create('completion.pdf', 1024, 'application/pdf');

        $response = $this->put(route('facilities.drawings.update', $this->facility), [
            'completion_drawings' => $pdfFile,
            'completion_drawings_notes' => 'テスト竣工図面備考',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('facility_drawings', [
            'facility_id' => $this->facility->id,
            'completion_drawings_filename' => 'completion.pdf',
            'completion_drawings_notes' => 'テスト竣工図面備考',
        ]);
    }

    /** @test */
    public function user_can_add_notes()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('facilities.drawings.update', $this->facility), [
            'notes' => 'これは図面に関する備考です。',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('facility_drawings', [
            'facility_id' => $this->facility->id,
            'notes' => 'これは図面に関する備考です。',
        ]);
    }

    /** @test */
    public function user_can_download_drawing_file()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        // テストファイル作成
        $pdfContent = 'fake pdf content';
        $filename = 'test_floor_plan.pdf';
        $path = 'building-drawings/floor-plans/' . $filename;
        
        Storage::disk('public')->put($path, $pdfContent);

        // 図面データ作成
        FacilityDrawing::create([
            'facility_id' => $this->facility->id,
            'floor_plan_filename' => $filename,
            'floor_plan_path' => $path,
        ]);

        $response = $this->get(route('facilities.drawings.download', [
            $this->facility, 
            'floor_plan'
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function user_can_delete_drawing_file()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        // 既存の図面データ作成
        $drawing = FacilityDrawing::create([
            'facility_id' => $this->facility->id,
            'floor_plan_filename' => 'existing_floor_plan.pdf',
            'floor_plan_path' => 'building-drawings/floor-plans/existing_floor_plan.pdf',
        ]);

        $response = $this->put(route('facilities.drawings.update', $this->facility), [
            'building_drawings' => [
                'delete_floor_plan' => '1',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $drawing->refresh();
        $this->assertNull($drawing->floor_plan_filename);
        $this->assertNull($drawing->floor_plan_path);
    }

    /** @test */
    public function validation_rejects_non_pdf_files()
    {
        $this->actingAs($this->user);

        $txtFile = UploadedFile::fake()->create('document.txt', 1024, 'text/plain');

        $response = $this->put(route('facilities.drawings.update', $this->facility), [
            'building_drawings' => [
                'floor_plan' => $txtFile,
            ],
        ]);

        $response->assertSessionHasErrors('building_drawings.floor_plan');
    }

    /** @test */
    public function validation_rejects_oversized_files()
    {
        $this->actingAs($this->user);

        $largePdfFile = UploadedFile::fake()->create('large.pdf', 15000, 'application/pdf'); // 15MB

        $response = $this->put(route('facilities.drawings.update', $this->facility), [
            'building_drawings' => [
                'floor_plan' => $largePdfFile,
            ],
        ]);

        $response->assertSessionHasErrors('building_drawings.floor_plan');
    }

    /** @test */
    public function unauthorized_user_cannot_edit_drawings()
    {
        $unauthorizedUser = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $this->actingAs($unauthorizedUser);

        $response = $this->get(route('facilities.drawings.edit', $this->facility));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function drawings_display_correctly_on_facility_page()
    {
        $this->actingAs($this->user);

        // テスト図面データ作成
        FacilityDrawing::create([
            'facility_id' => $this->facility->id,
            'floor_plan_filename' => 'test_floor_plan.pdf',
            'floor_plan_path' => 'building-drawings/floor-plans/test_floor_plan.pdf',
            'electrical_equipment_filename' => 'test_electrical.pdf',
            'electrical_equipment_path' => 'equipment-drawings/electrical/test_electrical.pdf',
            'completion_drawings_filename' => 'test_completion.pdf',
            'completion_drawings_path' => 'completion-drawings/test_completion.pdf',
            'completion_drawings_notes' => 'テスト竣工図面備考',
            'notes' => 'テスト備考',
        ]);

        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertOk();
        $response->assertSee('test_floor_plan.pdf');
        $response->assertSee('test_electrical.pdf');
        $response->assertSee('test_completion.pdf');
        $response->assertSee('テスト竣工図面備考');
        $response->assertSee('テスト備考');
    }
}