<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacilityFormLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $facility;

    protected $landInfo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user with admin role
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        // Create test facility
        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'address' => '東京都渋谷区テスト住所1-2-3',
            'building_name' => 'テストビル',
        ]);

        // Create test land info
        $this->landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'site_area_sqm' => 1000.50,
            'site_area_tsubo' => 300.25,
            'monthly_rent' => 500000,
            'purchase_price' => 50000000,
        ]);
    }

    /** @test */
    public function land_info_edit_form_uses_new_layout_components()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check that the new layout components are being used
        $response->assertViewHas('facility');
        $response->assertSee('facility-edit-layout');
        $response->assertSee('facility-info-card');
        $response->assertSee('form-section');
        $response->assertSee('form-actions');

        // Check that facility information is displayed
        $response->assertSee($this->facility->facility_name);
        $response->assertSee($this->facility->address);
        $response->assertSee($this->facility->building_name);
    }

    /** @test */
    public function land_info_edit_form_displays_all_sections()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check that all form sections are present
        $response->assertSee('基本情報'); // Basic info section
        $response->assertSee('面積情報'); // Area info section
        $response->assertSee('自社物件情報'); // Own property section
        $response->assertSee('賃借物件情報'); // Rental property section
        $response->assertSee('管理会社情報'); // Management company section
        $response->assertSee('オーナー情報'); // Owner info section
        $response->assertSee('関連書類'); // Related documents section

        // Check that section icons are present
        $response->assertSee('fas fa-map');
        $response->assertSee('fas fa-ruler-combined');
        $response->assertSee('fas fa-building');
        $response->assertSee('fas fa-file-contract');
        $response->assertSee('fas fa-user-tie');
        $response->assertSee('fas fa-file-pdf');
        $response->assertSee('fas fa-sticky-note');
    }

    /** @test */
    public function land_info_form_submission_works_with_new_layout()
    {
        $this->actingAs($this->user);

        $updateData = [
            'site_area_sqm' => 1200.75,
            'site_area_tsubo' => 363.50,
            'monthly_rent' => 600000,
            'purchase_price' => 60000000,
            'ownership_type' => 'owned',
            'unit_price_per_tsubo' => 165000,
            'contract_start_date' => '2023-04-01',
            'contract_end_date' => '2025-03-31',
            'management_company_name' => 'テスト管理会社',
            'management_company_phone' => '03-1234-5678',
            'management_company_email' => 'test@management.com',
            'owner_name' => 'テストオーナー',
            'owner_phone' => '090-1234-5678',
            'owner_email' => 'owner@test.com',
        ];

        $response = $this->put(
            route('facilities.land-info.update', $this->facility),
            $updateData
        );

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success');

        // Verify data was updated
        $this->landInfo->refresh();
        $this->assertEquals(1200.75, $this->landInfo->site_area_sqm);
        $this->assertEquals(363.50, $this->landInfo->site_area_tsubo);
        $this->assertEquals(600000, $this->landInfo->monthly_rent);
        $this->assertEquals(60000000, $this->landInfo->purchase_price);
    }

    /** @test */
    public function form_validation_errors_display_correctly_with_new_layout()
    {
        $this->actingAs($this->user);

        // Submit form with invalid data
        $invalidData = [
            'site_area_sqm' => 'invalid',
            'site_area_tsubo' => -100,
            'monthly_rent' => 'not_a_number',
            'management_company_email' => 'invalid-email',
            'owner_email' => 'also-invalid',
        ];

        $response = $this->put(
            route('facilities.land-info.update', $this->facility),
            $invalidData
        );

        $response->assertSessionHasErrors([
            'site_area_sqm',
            'site_area_tsubo',
            'monthly_rent',
            'management_company_email',
            'owner_email',
        ]);

        // Check that error display structure is correct
        $response = $this->get(route('facilities.land-info.edit', $this->facility));
        $response->assertSee('alert-danger'); // Error alert
        $response->assertSee('invalid-feedback'); // Field-level errors
    }

    /** @test */
    public function form_actions_work_correctly()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check that form actions are present
        $response->assertSee('キャンセル'); // Cancel button
        $response->assertSee('保存'); // Save button
        $response->assertSee('btn-outline-secondary'); // Cancel button class
        $response->assertSee('btn-primary'); // Save button class
        $response->assertSee('fas fa-save'); // Save icon

        // Check that cancel button links to facility show page
        $response->assertSee(route('facilities.show', $this->facility));
    }

    /** @test */
    public function responsive_design_classes_are_applied()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check for responsive classes
        $response->assertSee('container-fluid');
        $response->assertSee('col-md-6');
        $response->assertSee('col-lg-6');
        $response->assertSee('col-xl-4');
        $response->assertSee('d-flex');
        $response->assertSee('mb-3');
    }

    /** @test */
    public function accessibility_attributes_are_present()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check for accessibility attributes
        $response->assertSee('aria-labelledby=');
        $response->assertSee('aria-expanded=');
        $response->assertSee('aria-hidden=');
        $response->assertSee('aria-required="true"');
    }

    /** @test */
    public function file_upload_section_works_with_new_layout()
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('test-document.pdf', 1000, 'application/pdf');

        $response = $this->post(
            route('facilities.land-info.files.store', $this->facility),
            [
                'file' => $file,
                'document_type' => 'contract',
                'description' => 'テスト契約書',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify file was stored
        Storage::disk('local')->assertExists('land-info/'.$file->hashName());
    }

    /** @test */
    public function breadcrumb_navigation_is_correct()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check breadcrumb structure
        $response->assertSee('breadcrumb');
        $response->assertSee('ホーム');
        $response->assertSee('施設一覧');
        $response->assertSee('施設詳細');
        $response->assertSee('土地情報編集');
        $response->assertSee('aria-current="page"');
    }

    /** @test */
    public function collapsible_sections_have_correct_attributes()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check collapsible section attributes
        $response->assertSee('data-collapsible="true"');
        $response->assertSee('collapse-icon');
        $response->assertSee('fa-chevron-up');
        $response->assertSee('section-header');
    }

    /** @test */
    public function form_preserves_data_on_validation_error()
    {
        $this->actingAs($this->user);

        $partialData = [
            'site_area_sqm' => 1500.25,
            'site_area_tsubo' => 'invalid', // This will cause validation error
            'monthly_rent' => 700000,
            'management_company_name' => 'テスト管理会社名',
        ];

        $response = $this->put(
            route('facilities.land-info.update', $this->facility),
            $partialData
        );

        $response->assertSessionHasErrors(['site_area_tsubo']);

        // Check that valid data is preserved in the form
        $response = $this->get(route('facilities.land-info.edit', $this->facility));
        $response->assertSee('1500.25'); // site_area_sqm should be preserved
        $response->assertSee('700000'); // monthly_rent should be preserved
        $response->assertSee('テスト管理会社名'); // management_company_name should be preserved
    }

    /** @test */
    public function currency_fields_display_with_proper_formatting()
    {
        $this->actingAs($this->user);

        // Update land info with large currency values
        $this->landInfo->update([
            'monthly_rent' => 1500000,
            'purchase_price' => 75000000,
            'unit_price_per_tsubo' => 250000,
        ]);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check that currency fields have proper classes for formatting
        $response->assertSee('currency-input');
        $response->assertSee('data-currency="true"');
    }

    /** @test */
    public function form_sections_can_be_collapsed_and_expanded()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check that sections have collapsible functionality
        $response->assertSee('data-collapsible="true"');
        $response->assertSee('aria-expanded="true"');
        $response->assertSee('collapse-icon');

        // Check that JavaScript for collapsible sections is loaded
        $response->assertSee('facility-form-layout.js');
    }

    /** @test */
    public function unauthorized_users_cannot_access_edit_form()
    {
        $viewerUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewerUser);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(403);
    }

    /** @test */
    public function form_layout_is_consistent_across_different_data_states()
    {
        $this->actingAs($this->user);

        // Test with minimal data
        $minimalLandInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'site_area_sqm' => null,
            'site_area_tsubo' => null,
            'monthly_rent' => null,
        ]);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));
        $response->assertStatus(200);
        $response->assertSee('facility-edit-layout');

        // Test with complete data
        $completeLandInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'site_area_sqm' => 1000,
            'site_area_tsubo' => 300,
            'monthly_rent' => 500000,
            'management_company_name' => 'Complete Management Co.',
            'owner_name' => 'Complete Owner',
        ]);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));
        $response->assertStatus(200);
        $response->assertSee('facility-edit-layout');
        $response->assertSee('Complete Management Co.');
        $response->assertSee('Complete Owner');
    }

    /** @test */
    public function css_and_javascript_assets_are_loaded()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check that required CSS is loaded
        $response->assertSee('facility-form.css');
        $response->assertSee('land-info.css');

        // Check that required JavaScript is loaded
        $response->assertSee('facility-form-layout.js');
        $response->assertSee('land-info.js');
    }

    /** @test */
    public function form_maintains_existing_functionality()
    {
        $this->actingAs($this->user);

        // Test that all existing form functionality still works
        $updateData = [
            'ownership_type' => 'leased',
            'contract_start_date' => '2023-01-01',
            'contract_end_date' => '2025-12-31',
            'monthly_rent' => 800000,
            'purchase_price' => 80000000,
            'unit_price_per_tsubo' => 200000,
        ];

        $response = $this->put(
            route('facilities.land-info.update', $this->facility),
            $updateData
        );

        $response->assertRedirect(route('facilities.show', $this->facility));
        $response->assertSessionHas('success');

        // Verify all data was saved correctly
        $this->landInfo->refresh();
        $this->assertEquals('leased', $this->landInfo->ownership_type);
        $this->assertEquals('2023-01-01', $this->landInfo->contract_start_date->format('Y-m-d'));
        $this->assertEquals('2025-12-31', $this->landInfo->contract_end_date->format('Y-m-d'));
        $this->assertEquals(800000, $this->landInfo->monthly_rent);
        $this->assertEquals(80000000, $this->landInfo->purchase_price);
        $this->assertEquals(200000, $this->landInfo->unit_price_per_tsubo);
    }
}
