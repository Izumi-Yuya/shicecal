<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityFormLayoutIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $facility;

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
    }

    /** @test */
    public function land_info_edit_form_loads_successfully_with_new_layout()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('facility-edit-layout');
        $response->assertSee('facility-info-card');
        $response->assertSee('form-section');
    }

    /** @test */
    public function new_layout_components_exist()
    {
        // Test that the component files exist
        $this->assertFileExists(resource_path('views/components/facility/edit-layout.blade.php'));
        $this->assertFileExists(resource_path('views/components/facility/info-card.blade.php'));
        $this->assertFileExists(resource_path('views/components/form/section.blade.php'));
        $this->assertFileExists(resource_path('views/components/form/actions.blade.php'));
    }

    /** @test */
    public function configuration_file_exists()
    {
        $this->assertFileExists(config_path('facility-form.php'));

        $config = config('facility-form');
        $this->assertIsArray($config);
        $this->assertArrayHasKey('layout', $config);
        $this->assertArrayHasKey('icons', $config);
        $this->assertArrayHasKey('colors', $config);
    }

    /** @test */
    public function css_and_javascript_files_exist()
    {
        $this->assertFileExists(resource_path('css/components/facility-form.css'));
        $this->assertFileExists(resource_path('js/modules/facility-form-layout.js'));
    }

    /** @test */
    public function form_sections_display_correctly()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check that all form sections are present
        $response->assertSee('基本情報');
        $response->assertSee('面積情報');
        $response->assertSee('自社物件情報');
        $response->assertSee('賃借物件情報');
        $response->assertSee('管理会社情報');
        $response->assertSee('オーナー情報');
        $response->assertSee('関連書類');
    }

    /** @test */
    public function accessibility_attributes_are_present()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check for accessibility attributes
        $response->assertSee('role="region"');
        $response->assertSee('aria-labelledby=');
        $response->assertSee('aria-hidden="true"');
    }

    /** @test */
    public function breadcrumb_navigation_is_correct()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check breadcrumb structure
        $response->assertSee('breadcrumb');
        $response->assertSee('施設一覧');
        $response->assertSee('テスト施設');
        $response->assertSee('土地情報編集');
        $response->assertSee('aria-current="page"', false);
    }

    /** @test */
    public function facility_info_card_displays_correctly()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check facility information is displayed
        $response->assertSee($this->facility->facility_name);
        $response->assertSee($this->facility->address);
        $response->assertSee($this->facility->building_name);
    }

    /** @test */
    public function form_actions_are_present()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.land-info.edit', $this->facility));

        $response->assertStatus(200);

        // Check that form actions are present
        $response->assertSee('キャンセル');
        $response->assertSee('保存');
        $response->assertSee('btn-outline-secondary');
        $response->assertSee('btn-primary');
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
        $response->assertSee('mb-3');
    }
}
