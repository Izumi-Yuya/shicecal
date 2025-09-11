<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvFieldSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'access_scope' => null,
        ]);

        $this->facilities = Facility::factory()->count(5)->create([
            'status' => 'approved',
        ]);
    }

    public function test_field_preview_returns_correct_data()
    {
        $facilityIds = $this->facilities->take(2)->pluck('id')->toArray();
        $exportFields = ['company_name', 'facility_name', 'address'];

        $response = $this->actingAs($this->user)
            ->postJson(route('csv.export.preview'), [
                'facility_ids' => $facilityIds,
                'export_fields' => $exportFields,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');

        // Check that fields are returned
        $this->assertArrayHasKey('fields', $data);
        $this->assertArrayHasKey('preview_data', $data);
        $this->assertArrayHasKey('total_facilities', $data);
        $this->assertArrayHasKey('preview_count', $data);

        // Check field labels
        $this->assertEquals('会社名', $data['fields']['company_name']);
        $this->assertEquals('施設名', $data['fields']['facility_name']);
        $this->assertEquals('住所', $data['fields']['address']);

        // Check preview data structure
        $this->assertCount(2, $data['preview_data']);
        $this->assertEquals(2, $data['total_facilities']);
        $this->assertEquals(2, $data['preview_count']);

        // Check that each row has the requested fields
        foreach ($data['preview_data'] as $row) {
            $this->assertArrayHasKey('company_name', $row);
            $this->assertArrayHasKey('facility_name', $row);
            $this->assertArrayHasKey('address', $row);
        }
    }

    public function test_field_preview_limits_to_three_facilities()
    {
        $facilityIds = $this->facilities->pluck('id')->toArray(); // All 5 facilities
        $exportFields = ['company_name', 'facility_name'];

        $response = $this->actingAs($this->user)
            ->postJson(route('csv.export.preview'), [
                'facility_ids' => $facilityIds,
                'export_fields' => $exportFields,
            ]);

        $response->assertStatus(200);

        $data = $response->json('data');

        // Should limit preview to 3 facilities even though 5 were requested
        $this->assertLessThanOrEqual(3, $data['preview_count']);
        $this->assertEquals(5, $data['total_facilities']);
    }

    public function test_field_preview_requires_facilities_and_fields()
    {
        // Test with no facilities
        $response = $this->actingAs($this->user)
            ->postJson(route('csv.export.preview'), [
                'facility_ids' => [],
                'export_fields' => ['company_name'],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => '施設または項目が選択されていません。',
        ]);

        // Test with no fields
        $response = $this->actingAs($this->user)
            ->postJson(route('csv.export.preview'), [
                'facility_ids' => [$this->facilities->first()->id],
                'export_fields' => [],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => '施設または項目が選択されていません。',
        ]);
    }

    public function test_field_preview_respects_user_access_scope()
    {
        // Create a viewer user with limited access
        $viewerUser = User::factory()->create([
            'role' => 'viewer',
            'access_scope' => [
                'type' => 'facility',
                'facility_ids' => [$this->facilities->first()->id],
            ],
        ]);

        // Try to preview facilities the user doesn't have access to
        $facilityIds = $this->facilities->pluck('id')->toArray();
        $exportFields = ['company_name', 'facility_name'];

        $response = $this->actingAs($viewerUser)
            ->postJson(route('csv.export.preview'), [
                'facility_ids' => $facilityIds,
                'export_fields' => $exportFields,
            ]);

        $response->assertStatus(200);

        $data = $response->json('data');

        // Should only return data for facilities the user has access to
        $this->assertLessThanOrEqual(1, $data['preview_count']);
    }

    public function test_field_preview_formats_dates_correctly()
    {
        $facilityIds = [$this->facilities->first()->id];
        $exportFields = ['facility_name', 'created_at', 'updated_at'];

        $response = $this->actingAs($this->user)
            ->postJson(route('csv.export.preview'), [
                'facility_ids' => $facilityIds,
                'export_fields' => $exportFields,
            ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $row = $data['preview_data'][0];

        // Check date format (should be Y-m-d H:i:s)
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $row['created_at']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $row['updated_at']);
    }

    public function test_field_preview_formats_status_correctly()
    {
        // Create facility with different status
        $facility = Facility::factory()->create([
            'status' => 'approved',
        ]);

        $facilityIds = [$facility->id];
        $exportFields = ['facility_name', 'status'];

        $response = $this->actingAs($this->user)
            ->postJson(route('csv.export.preview'), [
                'facility_ids' => $facilityIds,
                'export_fields' => $exportFields,
            ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $row = $data['preview_data'][0];

        // Status should be translated to Japanese
        $this->assertEquals('承認済み', $row['status']);
    }

    public function test_field_preview_requires_authentication()
    {
        $response = $this->postJson(route('csv.export.preview'), [
            'facility_ids' => [$this->facilities->first()->id],
            'export_fields' => ['company_name'],
        ]);

        $response->assertStatus(401);
    }

    public function test_available_fields_include_all_expected_fields()
    {
        $response = $this->actingAs($this->user)
            ->get(route('csv.export.index'));

        $availableFields = $response->viewData('availableFields');

        $expectedFields = [
            'company_name' => '会社名',
            'office_code' => '事業所コード',
            'designation_number' => '指定番号',
            'facility_name' => '施設名',
            'postal_code' => '郵便番号',
            'address' => '住所',
            'phone_number' => '電話番号',
            'fax_number' => 'FAX番号',
            'status' => 'ステータス',
            'approved_at' => '承認日時',
            'created_at' => '作成日時',
            'updated_at' => '更新日時',
        ];

        $this->assertEquals($expectedFields, $availableFields);
    }
}
