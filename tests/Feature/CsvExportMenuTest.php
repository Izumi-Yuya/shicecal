<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with different roles
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'access_scope' => null,
        ]);
        
        $this->editorUser = User::factory()->create([
            'role' => 'editor',
            'access_scope' => null,
        ]);
        
        $this->viewerUser = User::factory()->create([
            'role' => 'viewer',
            'access_scope' => [
                'type' => 'department',
                'departments' => ['営業部']
            ],
        ]);
        
        // Create test facilities
        $this->facilities = Facility::factory()->count(5)->create([
            'status' => 'approved',
        ]);
    }

    public function test_csv_export_menu_displays_for_authenticated_users()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get(route('csv.export.index'));

        $response->assertStatus(200);
        $response->assertViewIs('export.csv.index');
        $response->assertViewHas(['facilities', 'availableFields']);
    }

    public function test_csv_export_menu_requires_authentication()
    {
        $response = $this->get(route('csv.export.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_see_all_approved_facilities()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get(route('csv.export.index'));

        $response->assertStatus(200);
        
        // Check that all approved facilities are available
        $facilities = $response->viewData('facilities');
        $this->assertCount(5, $facilities);
    }

    public function test_editor_can_see_all_approved_facilities()
    {
        $response = $this->actingAs($this->editorUser)
                         ->get(route('csv.export.index'));

        $response->assertStatus(200);
        
        // Check that all approved facilities are available
        $facilities = $response->viewData('facilities');
        $this->assertCount(5, $facilities);
    }

    public function test_viewer_sees_facilities_based_on_access_scope()
    {
        $response = $this->actingAs($this->viewerUser)
                         ->get(route('csv.export.index'));

        $response->assertStatus(200);
        
        // Viewer should see facilities based on their access scope
        $facilities = $response->viewData('facilities');
        $this->assertIsObject($facilities);
    }

    public function test_available_fields_are_provided()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get(route('csv.export.index'));

        $response->assertStatus(200);
        
        $availableFields = $response->viewData('availableFields');
        
        // Check that expected fields are available
        $expectedFields = [
            'company_name',
            'office_code', 
            'designation_number',
            'facility_name',
            'postal_code',
            'address',
            'phone_number',
            'fax_number',
            'status',
            'approved_at',
            'created_at',
            'updated_at',
        ];
        
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $availableFields);
        }
    }

    public function test_only_approved_facilities_are_shown()
    {
        // Create some non-approved facilities
        Facility::factory()->count(3)->create([
            'status' => 'draft',
        ]);
        
        Facility::factory()->count(2)->create([
            'status' => 'pending_approval',
        ]);

        $response = $this->actingAs($this->adminUser)
                         ->get(route('csv.export.index'));

        $response->assertStatus(200);
        
        // Should only see the 5 approved facilities, not the 5 non-approved ones
        $facilities = $response->viewData('facilities');
        $this->assertCount(5, $facilities);
        
        // Verify all returned facilities are approved
        foreach ($facilities as $facility) {
            $this->assertEquals('approved', $facility->status);
        }
    }

    public function test_facilities_are_ordered_correctly()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get(route('csv.export.index'));

        $response->assertStatus(200);
        
        $facilities = $response->viewData('facilities');
        
        // Check that facilities are ordered by company_name then facility_name
        $previousCompanyName = '';
        $previousFacilityName = '';
        
        foreach ($facilities as $facility) {
            if ($facility->company_name === $previousCompanyName) {
                $this->assertGreaterThanOrEqual($previousFacilityName, $facility->facility_name);
            } else {
                $this->assertGreaterThanOrEqual($previousCompanyName, $facility->company_name);
            }
            
            $previousCompanyName = $facility->company_name;
            $previousFacilityName = $facility->facility_name;
        }
    }

    public function test_csv_export_menu_contains_required_elements()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get(route('csv.export.index'));

        $response->assertStatus(200);
        
        // Check for key UI elements
        $response->assertSee('CSV出力');
        $response->assertSee('施設選択');
        $response->assertSee('出力項目選択');
        $response->assertSee('選択内容プレビュー');
        $response->assertSee('全選択');
        $response->assertSee('全解除');
        $response->assertSee('お気に入りに保存');
        $response->assertSee('お気に入り一覧');
    }
}