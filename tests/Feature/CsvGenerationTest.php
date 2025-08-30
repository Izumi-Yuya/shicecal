<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin',
            'access_scope' => null,
        ]);
        
        $this->facilities = Facility::factory()->count(3)->create([
            'status' => 'approved',
            'company_name' => 'テスト会社',
            'facility_name' => 'テスト施設',
            'address' => '東京都渋谷区',
        ]);
    }

    public function test_csv_generation_returns_correct_file()
    {
        $facilityIds = $this->facilities->take(2)->pluck('id')->toArray();
        $exportFields = ['company_name', 'facility_name', 'address'];

        $response = $this->actingAs($this->user)
                         ->post(route('csv.export.generate'), [
                             'facility_ids' => $facilityIds,
                             'export_fields' => $exportFields
                         ]);

        $response->assertStatus(200);
        
        // Check headers
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('facility_export_', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
        
        // Check content
        $content = $response->getContent();
        
        // Should start with UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        
        // Should contain headers
        $this->assertStringContainsString('会社名', $content);
        $this->assertStringContainsString('施設名', $content);
        $this->assertStringContainsString('住所', $content);
        
        // Should contain data
        $this->assertStringContainsString('テスト会社', $content);
        $this->assertStringContainsString('テスト施設', $content);
        $this->assertStringContainsString('東京都渋谷区', $content);
    }

    public function test_csv_generation_requires_facilities_and_fields()
    {
        // Test with no facilities
        $response = $this->actingAs($this->user)
                         ->post(route('csv.export.generate'), [
                             'facility_ids' => [],
                             'export_fields' => ['company_name']
                         ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => '施設または項目が選択されていません。'
        ]);

        // Test with no fields
        $response = $this->actingAs($this->user)
                         ->post(route('csv.export.generate'), [
                             'facility_ids' => [$this->facilities->first()->id],
                             'export_fields' => []
                         ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => '施設または項目が選択されていません。'
        ]);
    }

    public function test_csv_generation_respects_user_access_scope()
    {
        // Create a viewer user with limited access
        $viewerUser = User::factory()->create([
            'role' => 'viewer',
            'access_scope' => [
                'type' => 'facility',
                'facility_ids' => [$this->facilities->first()->id]
            ],
        ]);

        // Try to export facilities the user doesn't have access to
        $facilityIds = $this->facilities->pluck('id')->toArray();
        $exportFields = ['company_name', 'facility_name'];

        $response = $this->actingAs($viewerUser)
                         ->post(route('csv.export.generate'), [
                             'facility_ids' => $facilityIds,
                             'export_fields' => $exportFields
                         ]);

        $response->assertStatus(200);
        
        // Should only export facilities the user has access to
        $content = $response->getContent();
        $lines = explode("\n", $content);
        
        // Should have header + 1 data row + empty line at end = 3 lines
        $this->assertLessThanOrEqual(3, count($lines));
    }

    public function test_csv_generation_handles_no_accessible_facilities()
    {
        // Create a viewer user with no access
        $viewerUser = User::factory()->create([
            'role' => 'viewer',
            'access_scope' => [
                'type' => 'facility',
                'facility_ids' => [999999] // Non-existent facility
            ],
        ]);

        $facilityIds = $this->facilities->pluck('id')->toArray();
        $exportFields = ['company_name', 'facility_name'];

        $response = $this->actingAs($viewerUser)
                         ->post(route('csv.export.generate'), [
                             'facility_ids' => $facilityIds,
                             'export_fields' => $exportFields
                         ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => '出力可能な施設がありません。'
        ]);
    }

    public function test_csv_generation_formats_data_correctly()
    {
        // Create facility with specific data for testing
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'company_name' => 'テスト会社株式会社',
            'facility_name' => 'テスト施設名',
            'office_code' => 'TEST001',
            'address' => '東京都渋谷区テスト1-2-3',
        ]);

        $response = $this->actingAs($this->user)
                         ->post(route('csv.export.generate'), [
                             'facility_ids' => [$facility->id],
                             'export_fields' => ['company_name', 'office_code', 'facility_name', 'address', 'status', 'created_at']
                         ]);

        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check that data is properly formatted
        $this->assertStringContainsString('テスト会社株式会社', $content);
        $this->assertStringContainsString('TEST001', $content);
        $this->assertStringContainsString('テスト施設名', $content);
        $this->assertStringContainsString('東京都渋谷区テスト1-2-3', $content);
        $this->assertStringContainsString('承認済み', $content); // Status should be translated
        
        // Check date format
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $content);
    }

    public function test_csv_generation_handles_special_characters()
    {
        // Create facility with special characters
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'company_name' => 'テスト会社,株式会社"特殊文字"',
            'facility_name' => 'テスト施設\n改行あり',
            'address' => '東京都,渋谷区"テスト"1-2-3',
        ]);

        $response = $this->actingAs($this->user)
                         ->post(route('csv.export.generate'), [
                             'facility_ids' => [$facility->id],
                             'export_fields' => ['company_name', 'facility_name', 'address']
                         ]);

        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // CSV should properly escape special characters (quotes are doubled in CSV format)
        $this->assertStringContainsString('テスト会社,株式会社""特殊文字""', $content);
        $this->assertStringContainsString('テスト施設\n改行あり', $content);
        $this->assertStringContainsString('東京都,渋谷区""テスト""1-2-3', $content);
    }

    public function test_csv_generation_requires_authentication()
    {
        $response = $this->post(route('csv.export.generate'), [
            'facility_ids' => [$this->facilities->first()->id],
            'export_fields' => ['company_name']
        ]);

        $response->assertStatus(302); // Redirect to login
    }

    public function test_csv_filename_contains_timestamp()
    {
        $facilityIds = [$this->facilities->first()->id];
        $exportFields = ['company_name', 'facility_name'];

        $response = $this->actingAs($this->user)
                         ->post(route('csv.export.generate'), [
                             'facility_ids' => $facilityIds,
                             'export_fields' => $exportFields
                         ]);

        $response->assertStatus(200);
        
        $contentDisposition = $response->headers->get('Content-Disposition');
        
        // Should contain timestamp in format YYYY-MM-DD_HH-MM-SS
        $this->assertMatchesRegularExpression('/facility_export_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.csv/', $contentDisposition);
    }
}