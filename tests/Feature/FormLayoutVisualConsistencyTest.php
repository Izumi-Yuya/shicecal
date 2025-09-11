<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormLayoutVisualConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'company_name' => 'テスト会社',
            'office_code' => 'TEST001',
        ]);
    }

    /** @test */
    public function both_forms_have_identical_layout_structure()
    {
        $basicInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $landInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.land-info.edit', $this->facility));

        $basicInfoResponse->assertStatus(200);
        $landInfoResponse->assertStatus(200);

        // Both forms should have the same structural elements
        $structuralElements = [
            'facility-edit-layout',
            'facility-info-card',
            'breadcrumb',
            'form-section',
            'section-header',
            'card-body',
            'btn btn-outline-secondary', // Cancel button
            'btn btn-primary', // Submit button
        ];

        foreach ($structuralElements as $element) {
            $basicInfoResponse->assertSee($element);
            $landInfoResponse->assertSee($element);
        }
    }

    /** @test */
    public function both_forms_use_consistent_icon_system()
    {
        $basicInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $landInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.land-info.edit', $this->facility));

        // Both should use Font Awesome icons consistently
        $basicInfoResponse->assertSee('fas fa-info-circle');
        $basicInfoResponse->assertSee('fas fa-map-marker-alt');
        $basicInfoResponse->assertSee('fas fa-building');
        $basicInfoResponse->assertSee('fas fa-home');
        $basicInfoResponse->assertSee('fas fa-cogs');

        $landInfoResponse->assertSee('fas fa-map');
        $landInfoResponse->assertSee('fas fa-ruler-combined');
        $landInfoResponse->assertSee('fas fa-building');
        $landInfoResponse->assertSee('fas fa-file-contract');
        $landInfoResponse->assertSee('fas fa-user-tie');
    }

    /** @test */
    public function both_forms_have_consistent_accessibility_features()
    {
        $basicInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $landInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.land-info.edit', $this->facility));

        // Both should have proper accessibility attributes
        $accessibilityFeatures = [
            'role="region"',
            'aria-labelledby=',
            'role="group"',
            'aria-label=',
            'aria-hidden="true"',
        ];

        foreach ($accessibilityFeatures as $feature) {
            $basicInfoResponse->assertSee($feature, false);
            $landInfoResponse->assertSee($feature, false);
        }
    }

    /** @test */
    public function both_forms_have_consistent_responsive_structure()
    {
        $basicInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $landInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.land-info.edit', $this->facility));

        // Both should use Bootstrap responsive classes consistently
        $responsiveClasses = [
            'col-md-6',
            'col-12',
            'mb-3',
            'mb-4',
            'd-flex',
            'justify-content-between',
            'align-items-center',
        ];

        foreach ($responsiveClasses as $class) {
            $basicInfoResponse->assertSee($class);
            $landInfoResponse->assertSee($class);
        }
    }

    /** @test */
    public function both_forms_maintain_consistent_user_experience()
    {
        $basicInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $landInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.land-info.edit', $this->facility));

        // Both should have the same navigation elements
        $basicInfoResponse->assertSee('施設一覧');
        $basicInfoResponse->assertSee($this->facility->facility_name);
        $basicInfoResponse->assertSee('戻る');

        $landInfoResponse->assertSee('施設一覧');
        $landInfoResponse->assertSee($this->facility->facility_name);
        $landInfoResponse->assertSee('戻る');

        // Both should have consistent form actions
        $basicInfoResponse->assertSee('キャンセル');
        $basicInfoResponse->assertSee('保存');

        $landInfoResponse->assertSee('キャンセル');
        $landInfoResponse->assertSee('保存');
    }
}
