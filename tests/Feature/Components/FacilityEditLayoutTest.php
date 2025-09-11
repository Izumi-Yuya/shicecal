<?php

namespace Tests\Feature\Components;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityEditLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        // Create a test facility with correct field names
        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'address' => '東京都渋谷区テスト住所1-2-3',
            'building_name' => 'テストビル',
        ]);
    }

    /** @test */
    public function facility_edit_layout_component_files_exist()
    {
        // Test that the component files exist
        $this->assertFileExists(resource_path('views/components/facility/edit-layout.blade.php'));
        $this->assertFileExists(resource_path('views/components/facility/info-card.blade.php'));
        $this->assertFileExists(resource_path('views/components/form/section.blade.php'));
        $this->assertFileExists(resource_path('views/components/form/actions.blade.php'));
    }

    /** @test */
    public function facility_info_card_component_renders_facility_data()
    {
        $view = view('components.facility.info-card', [
            'facility' => $this->facility,
            'showType' => false,  // Set to false since we don't have facility_type field
        ]);

        $html = $view->render();

        // Check facility name
        $this->assertStringContainsString('テスト施設', $html);

        // Check address
        $this->assertStringContainsString('東京都渋谷区テスト住所1-2-3', $html);

        // Check building name
        $this->assertStringContainsString('テストビル', $html);
    }

    /** @test */
    public function configuration_file_exists_and_has_correct_structure()
    {
        // Test that the configuration file exists
        $this->assertFileExists(config_path('facility-form.php'));

        // Test that the configuration has the expected structure
        $config = config('facility-form');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('layout', $config);
        $this->assertArrayHasKey('icons', $config);
        $this->assertArrayHasKey('colors', $config);
        $this->assertArrayHasKey('section_colors', $config);

        // Test specific icon configurations
        $this->assertArrayHasKey('basic_info', $config['icons']);
        $this->assertArrayHasKey('land_info', $config['icons']);
        $this->assertEquals('fas fa-info-circle', $config['icons']['basic_info']);
        $this->assertEquals('fas fa-map', $config['icons']['land_info']);
    }

    /** @test */
    public function css_and_javascript_files_exist()
    {
        // Test that the CSS file exists
        $this->assertFileExists(resource_path('css/components/facility-form.css'));

        // Test that the JavaScript module exists
        $this->assertFileExists(resource_path('js/modules/facility-form-layout.js'));

        // Test that the CSS is imported in components.css
        $componentsCSS = file_get_contents(resource_path('css/components.css'));
        $this->assertStringContainsString('components/facility-form.css', $componentsCSS);

        // Test that the JS module is imported in app.js
        $appJS = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString('facility-form-layout.js', $appJS);
    }

    /** @test */
    public function form_actions_component_renders_buttons()
    {
        $cancelRoute = route('facilities.show', $this->facility);

        $view = view('components.form.actions', [
            'cancelRoute' => $cancelRoute,
            'cancelText' => 'キャンセル',
            'submitText' => '保存',
            'submitIcon' => 'fas fa-save',
        ]);

        $html = $view->render();

        // Check cancel button
        $this->assertStringContainsString('キャンセル', $html);
        $this->assertStringContainsString('btn-outline-secondary', $html);

        // Check submit button
        $this->assertStringContainsString('保存', $html);
        $this->assertStringContainsString('fas fa-save', $html);
        $this->assertStringContainsString('btn-primary', $html);
        $this->assertStringContainsString('type="submit"', $html);
    }

    /** @test */
    public function component_structure_is_correct()
    {
        // Test that component files have the expected structure
        $editLayoutContent = file_get_contents(resource_path('views/components/facility/edit-layout.blade.php'));
        $this->assertStringContainsString('@props([', $editLayoutContent);
        $this->assertStringContainsString('facility-edit-layout', $editLayoutContent);
        $this->assertStringContainsString('facility.info-card', $editLayoutContent);

        $infoCardContent = file_get_contents(resource_path('views/components/facility/info-card.blade.php'));
        $this->assertStringContainsString('@props([', $infoCardContent);
        $this->assertStringContainsString('facility-info-card', $infoCardContent);

        $sectionContent = file_get_contents(resource_path('views/components/form/section.blade.php'));
        $this->assertStringContainsString('@props([', $sectionContent);
        $this->assertStringContainsString('form-section', $sectionContent);
        $this->assertStringContainsString('role="region"', $sectionContent);

        $actionsContent = file_get_contents(resource_path('views/components/form/actions.blade.php'));
        $this->assertStringContainsString('@props([', $actionsContent);
        $this->assertStringContainsString('btn-primary', $actionsContent);
    }
}
