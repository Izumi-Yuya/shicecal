<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandInfoExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'role' => 'editor',
            'email' => 'test@example.com',
        ]);
    }

    public function test_csv_export_includes_land_information_fields()
    {
        // Create facility with land information
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'Test Facility',
            'company_name' => 'Test Company',
        ]);

        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'owned',
            'parking_spaces' => 50,
            'site_area_sqm' => 1000.50,
            'site_area_tsubo' => 302.65,
            'purchase_price' => 50000000,
            'unit_price_per_tsubo' => 165000,
            'notes' => 'Test land notes',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)
            ->post('/export/csv/generate', [
                'facility_ids' => [$facility->id],
                'export_fields' => [
                    'facility_name',
                    'land_ownership_type',
                    'land_parking_spaces',
                    'land_site_area_sqm',
                    'land_purchase_price',
                    'land_notes',
                ],
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csvContent = $response->getContent();

        // Remove BOM and parse CSV
        $csvContent = str_replace("\xEF\xBB\xBF", '', $csvContent);
        $lines = explode("\n", trim($csvContent));

        // Check header
        $header = str_getcsv($lines[0]);
        $this->assertContains('施設名', $header);
        $this->assertContains('土地所有形態', $header);
        $this->assertContains('敷地内駐車場台数', $header);
        $this->assertContains('敷地面積(㎡)', $header);
        $this->assertContains('購入金額', $header);
        $this->assertContains('土地備考', $header);

        // Check data row
        $dataRow = str_getcsv($lines[1]);
        $this->assertContains('Test Facility', $dataRow);
        $this->assertContains('自社', $dataRow);
        $this->assertContains('50', $dataRow);
        $this->assertContains('1,000.50', $dataRow);
        $this->assertContains('50,000,000', $dataRow);
        $this->assertContains('Test land notes', $dataRow);
    }

    public function test_csv_export_handles_different_ownership_types()
    {
        // Create leased facility
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'Leased Facility',
        ]);

        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'leased',
            'monthly_rent' => 500000,
            'contract_start_date' => '2020-01-01',
            'contract_end_date' => '2025-12-31',
            'contract_period_text' => '5年11ヶ月',
            'auto_renewal' => 'yes',
            'management_company_name' => 'Test Management Co.',
            'owner_name' => 'Test Owner',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)
            ->post('/export/csv/generate', [
                'facility_ids' => [$facility->id],
                'export_fields' => [
                    'facility_name',
                    'land_ownership_type',
                    'land_monthly_rent',
                    'land_contract_start_date',
                    'land_contract_end_date',
                    'land_contract_period_text',
                    'land_auto_renewal',
                    'land_management_company_name',
                    'land_owner_name',
                ],
            ]);

        $response->assertStatus(200);

        $csvContent = $response->getContent();
        $csvContent = str_replace("\xEF\xBB\xBF", '', $csvContent);
        $lines = explode("\n", trim($csvContent));

        $dataRow = str_getcsv($lines[1]);
        $this->assertContains('Leased Facility', $dataRow);
        $this->assertContains('賃借', $dataRow);
        $this->assertContains('500,000', $dataRow);
        $this->assertContains('2020/01/01', $dataRow);
        $this->assertContains('2025/12/31', $dataRow);
        $this->assertContains('5年11ヶ月', $dataRow);
        $this->assertContains('あり', $dataRow);
        $this->assertContains('Test Management Co.', $dataRow);
        $this->assertContains('Test Owner', $dataRow);
    }

    public function test_csv_export_handles_facilities_without_land_info()
    {
        // Create facility without land information
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'No Land Info Facility',
        ]);

        $response = $this->actingAs($this->user)
            ->post('/export/csv/generate', [
                'facility_ids' => [$facility->id],
                'export_fields' => [
                    'facility_name',
                    'land_ownership_type',
                    'land_parking_spaces',
                    'land_notes',
                ],
            ]);

        $response->assertStatus(200);

        $csvContent = $response->getContent();
        $csvContent = str_replace("\xEF\xBB\xBF", '', $csvContent);
        $lines = explode("\n", trim($csvContent));

        $dataRow = str_getcsv($lines[1]);
        $this->assertContains('No Land Info Facility', $dataRow);

        // Land fields should be empty
        $headerRow = str_getcsv($lines[0]);
        $ownershipIndex = array_search('土地所有形態', $headerRow);
        $parkingIndex = array_search('敷地内駐車場台数', $headerRow);
        $notesIndex = array_search('土地備考', $headerRow);

        $this->assertEquals('', $dataRow[$ownershipIndex]);
        $this->assertEquals('', $dataRow[$parkingIndex]);
        $this->assertEquals('', $dataRow[$notesIndex]);
    }

    public function test_csv_field_preview_includes_land_information()
    {
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'Preview Test Facility',
        ]);

        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'owned_rental',
            'parking_spaces' => 25,
            'monthly_rent' => 300000,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/export/csv/preview', [
                'facility_ids' => [$facility->id],
                'export_fields' => [
                    'facility_name',
                    'land_ownership_type',
                    'land_parking_spaces',
                    'land_monthly_rent',
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');

        $this->assertArrayHasKey('fields', $data);
        $this->assertArrayHasKey('preview_data', $data);

        $fields = $data['fields'];
        $this->assertEquals('施設名', $fields['facility_name']);
        $this->assertEquals('土地所有形態', $fields['land_ownership_type']);
        $this->assertEquals('敷地内駐車場台数', $fields['land_parking_spaces']);
        $this->assertEquals('家賃', $fields['land_monthly_rent']);

        $previewData = $data['preview_data'][0];
        $this->assertEquals('Preview Test Facility', $previewData['facility_name']);
        $this->assertEquals('自社（賃貸）', $previewData['land_ownership_type']);
        $this->assertEquals('25', $previewData['land_parking_spaces']);
        $this->assertEquals('300,000', $previewData['land_monthly_rent']);
    }

    public function test_pdf_export_includes_land_information()
    {
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'PDF Test Facility',
            'company_name' => 'PDF Test Company',
        ]);

        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'leased',
            'parking_spaces' => 30,
            'site_area_sqm' => 800.00,
            'site_area_tsubo' => 242.00,
            'monthly_rent' => 400000,
            'contract_start_date' => '2022-04-01',
            'contract_end_date' => '2027-03-31',
            'auto_renewal' => 'no',
            'management_company_name' => 'PDF Management Co.',
            'owner_name' => 'PDF Owner',
            'notes' => 'PDF test notes',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/export/pdf/facility/{$facility->id}?secure=0");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        // Check that the response contains PDF content
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_secure_pdf_export_includes_land_information()
    {
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'Secure PDF Test Facility',
        ]);

        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'owned',
            'purchase_price' => 75000000,
            'unit_price_per_tsubo' => 200000,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/export/pdf/secure/{$facility->id}");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        // Check that the response contains PDF content
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_export_favorites_work_with_land_fields()
    {
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'Favorite Test Facility',
        ]);

        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'leased',
            'status' => 'approved',
        ]);

        // Save favorite with land fields
        $response = $this->actingAs($this->user)
            ->postJson('/export/csv/favorites', [
                'name' => 'Land Info Export',
                'facility_ids' => [$facility->id],
                'export_fields' => [
                    'facility_name',
                    'land_ownership_type',
                    'land_parking_spaces',
                    'land_monthly_rent',
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'お気に入りを保存しました。',
        ]);

        $favoriteId = $response->json('data.id');

        // Load favorite
        $response = $this->actingAs($this->user)
            ->get("/export/csv/favorites/{$favoriteId}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');
        $this->assertEquals('Land Info Export', $data['name']);
        $this->assertEquals([$facility->id], $data['facility_ids']);
        $this->assertContains('land_ownership_type', $data['export_fields']);
        $this->assertContains('land_parking_spaces', $data['export_fields']);
        $this->assertContains('land_monthly_rent', $data['export_fields']);
    }
}
