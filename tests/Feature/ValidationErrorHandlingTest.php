<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ValidationErrorHandlingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with editing permissions
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        // Create a test facility
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function it_displays_form_level_errors_when_validation_fails()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('facilities.land-info.update', $this->facility), [
            'ownership_type' => '', // Required field left empty
            'parking_spaces' => 'invalid', // Invalid integer
            'site_area_sqm' => -1, // Invalid negative value
        ]);

        $response->assertSessionHasErrors([
            'ownership_type',
            'parking_spaces',
            'site_area_sqm',
        ]);

        // Follow redirect to see the form with errors
        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertSee('入力エラーがあります');
        $response->assertSee('所有形態を選択してください');
    }

    /** @test */
    public function it_displays_field_level_errors_with_proper_styling()
    {
        $this->actingAs($this->user);

        $this->put(route('facilities.land-info.update', $this->facility), [
            'ownership_type' => '',
            'management_company_email' => 'invalid-email',
            'owner_postal_code' => '123', // Invalid format
        ]);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        // Check that form fields have error classes
        $response->assertSee('is-invalid');
        $response->assertSee('invalid-feedback');
    }

    /** @test */
    public function it_shows_section_error_indicators_when_fields_have_errors()
    {
        $this->actingAs($this->user);

        $this->put(route('facilities.land-info.update', $this->facility), [
            'ownership_type' => '', // Basic info section error
            'management_company_email' => 'invalid', // Management company section error
            'owner_phone' => '123', // Owner info section error
        ]);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        // Check that sections with errors show error indicators
        $response->assertSee('fa-exclamation-triangle');
        $response->assertSee('このセクションにエラーがあります');
    }

    /** @test */
    public function it_preserves_old_input_values_after_validation_errors()
    {
        $this->actingAs($this->user);

        $inputData = [
            'ownership_type' => '', // This will cause validation error
            'parking_spaces' => '50',
            'site_area_sqm' => '100.5',
            'management_company_name' => 'Test Company',
            'owner_name' => 'Test Owner',
        ];

        $this->put(route('facilities.land-info.update', $this->facility), $inputData);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        // Check that valid input values are preserved
        $response->assertSee('value="50"', false);
        $response->assertSee('value="100.5"', false);
        $response->assertSee('value="Test Company"', false);
        $response->assertSee('value="Test Owner"', false);
    }

    /** @test */
    public function it_handles_file_upload_validation_errors()
    {
        $this->actingAs($this->user);

        // Create a fake file that's too large
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 15000); // 15MB, over 10MB limit

        $this->put(route('facilities.land-info.update', $this->facility), [
            'ownership_type' => 'owned',
            'lease_contract_pdf' => $file,
        ]);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertSee('ファイルサイズは10MB以下にしてください。');
    }

    /** @test */
    public function it_validates_conditional_fields_based_on_ownership_type()
    {
        $this->actingAs($this->user);

        // Test leased property validation
        $this->put(route('facilities.land-info.update', $this->facility), [
            'ownership_type' => 'leased',
            'contract_end_date' => '2023-01-01',
            'contract_start_date' => '2024-01-01', // End date before start date
        ]);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertSee('契約終了日は契約開始日より後の日付で入力してください');
    }

    /** @test */
    public function it_shows_success_message_when_validation_passes()
    {
        $this->actingAs($this->user);

        $validData = [
            'ownership_type' => 'owned',
            'parking_spaces' => '50',
            'site_area_sqm' => '100.5',
            'site_area_tsubo' => '30.4',
            'purchase_price' => '10000000',
            'notes' => 'Test notes',
        ];

        $response = $this->put(route('facilities.land-info.update', $this->facility), $validData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function error_field_mappings_are_correctly_configured()
    {
        $mappings = \App\Helpers\FacilityFormHelper::getLandInfoErrorFieldMappings();

        $this->assertArrayHasKey('basic_info', $mappings);
        $this->assertArrayHasKey('management_company', $mappings);
        $this->assertArrayHasKey('owner_info', $mappings);
        $this->assertArrayHasKey('documents', $mappings);

        $this->assertContains('ownership_type', $mappings['basic_info']);
        $this->assertContains('management_company_email', $mappings['management_company']);
        $this->assertContains('owner_phone', $mappings['owner_info']);
        $this->assertContains('lease_contract_pdf', $mappings['documents']);
    }

    /** @test */
    public function helper_returns_correct_error_fields_for_section()
    {
        $basicInfoFields = \App\Helpers\FacilityFormHelper::getErrorFieldsForSection('basic_info');
        $managementFields = \App\Helpers\FacilityFormHelper::getErrorFieldsForSection('management_company');

        $this->assertContains('ownership_type', $basicInfoFields);
        $this->assertContains('parking_spaces', $basicInfoFields);

        $this->assertContains('management_company_name', $managementFields);
        $this->assertContains('management_company_email', $managementFields);
    }
}
