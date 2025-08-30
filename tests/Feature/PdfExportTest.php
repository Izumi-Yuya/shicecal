<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PdfExportTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'admin'
        ]);
    }

    public function test_pdf_export_index_page_loads()
    {
        $response = $this->actingAs($this->user)
            ->get(route('pdf.export.index'));

        $response->assertStatus(200);
        $response->assertViewIs('export.pdf.index');
    }

    public function test_can_generate_single_facility_pdf()
    {
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('pdf.export.single', $facility));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_cannot_generate_pdf_for_non_approved_facility()
    {
        $facility = Facility::factory()->create([
            'status' => 'draft',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('pdf.export.single', $facility));

        $response->assertStatus(403);
    }

    public function test_can_generate_batch_pdf()
    {
        $facilities = Facility::factory()->count(2)->create([
            'status' => 'approved',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('pdf.export.batch'), [
                'facility_ids' => $facilities->pluck('id')->toArray()
            ]);

        $response->assertStatus(200);
        // For multiple facilities, it should return a ZIP file
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition'), '.zip') ||
            str_contains($response->headers->get('content-type'), 'application/pdf')
        );
    }

    public function test_batch_pdf_with_no_facilities_selected()
    {
        $response = $this->actingAs($this->user)
            ->post(route('pdf.export.batch'), [
                'facility_ids' => []
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', '出力する施設を選択してください。');
    }

    public function test_unauthenticated_user_cannot_access_pdf_export()
    {
        $response = $this->get(route('pdf.export.index'));
        
        $response->assertStatus(302); // Redirect to login
    }
}