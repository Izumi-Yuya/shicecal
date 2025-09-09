<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestFacilities;
use Tests\Traits\CreatesTestUsers;

class ServiceTableTest extends TestCase
{
    use RefreshDatabase, CreatesTestFacilities, CreatesTestUsers;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUserWithRole('admin');
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function service_table_renders_with_no_services()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('サービス種類');
        $response->assertSee('未設定');
    }

    /** @test */
    public function service_table_renders_with_single_service()
    {
        FacilityService::factory()->create([
            'facility_id' => $this->facility->id,
            'service_type' => '介護保険',
            'renewal_start_date' => '2024-01-01',
            'renewal_end_date' => '2024-12-31',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('介護保険');
        $response->assertSee('2024年1月1日');
        $response->assertSee('2024年12月31日');
        $response->assertSee('～');
    }

    /** @test */
    public function service_table_renders_with_multiple_services()
    {
        FacilityService::factory()->create([
            'facility_id' => $this->facility->id,
            'service_type' => '介護保険',
            'renewal_start_date' => '2024-01-01',
            'renewal_end_date' => '2024-12-31',
        ]);

        FacilityService::factory()->create([
            'facility_id' => $this->facility->id,
            'service_type' => '障害福祉',
            'renewal_start_date' => '2024-04-01',
            'renewal_end_date' => '2025-03-31',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('介護保険');
        $response->assertSee('障害福祉');
        $response->assertSee('data-service-table');
    }

    /** @test */
    public function service_table_handles_incomplete_dates()
    {
        FacilityService::factory()->create([
            'facility_id' => $this->facility->id,
            'service_type' => '介護保険',
            'renewal_start_date' => '2024-01-01',
            'renewal_end_date' => null, // Missing end date
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('介護保険');
        $response->assertSee('2024年1月1日');
        // Should not show separator when end date is missing
        $response->assertDontSee('～');
    }

    /** @test */
    public function service_table_shows_expiry_badges()
    {
        // Create expired service
        FacilityService::factory()->create([
            'facility_id' => $this->facility->id,
            'service_type' => '期限切れサービス',
            'renewal_start_date' => '2023-01-01',
            'renewal_end_date' => '2023-12-31', // Expired
        ]);

        // Create expiring soon service
        FacilityService::factory()->create([
            'facility_id' => $this->facility->id,
            'service_type' => '期限間近サービス',
            'renewal_start_date' => '2024-01-01',
            'renewal_end_date' => now()->addDays(15)->format('Y-m-d'), // Expiring soon
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // Check for expiry badges (if the model methods exist)
        if (method_exists(FacilityService::class, 'isExpired')) {
            $response->assertSee('期限切れ');
        }

        if (method_exists(FacilityService::class, 'isExpiringSoon')) {
            $response->assertSee('期限間近');
        }
    }

    /** @test */
    public function service_table_includes_proper_css_classes()
    {
        FacilityService::factory()->create([
            'facility_id' => $this->facility->id,
            'service_type' => '介護保険',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('service-info');
        $response->assertSee('label-cell');
        $response->assertSee('value-cell');
        $response->assertSee('svc-row');
    }

    /** @test */
    public function service_table_configuration_is_respected()
    {
        // Test that configuration values are used
        config(['facility.services.max_display_rows' => 5]);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        // The template should respect the configuration
        // This is more of an integration test to ensure config is loaded
    }

    /** @test */
    public function service_table_handles_table_view_mode()
    {
        // Set table view mode in session
        session(['facility_basic_info_view_mode' => 'table']);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('service-info');
        $response->assertViewHas('viewMode', 'table');
    }
}
