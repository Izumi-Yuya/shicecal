<?php

namespace Tests\Unit;

use App\Http\Controllers\FacilityController;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FacilityControllerViewModeTest extends TestCase
{
    use RefreshDatabase;

    private FacilityController $controller;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);

        $this->controller = app(FacilityController::class);
    }

    /**
     * Test setViewMode method with valid input
     *
     * @test
     */
    public function it_sets_view_mode_with_valid_input()
    {
        $request = Request::create('/facilities/set-view-mode', 'POST', [
            'view_mode' => 'table',
        ]);

        $response = $this->controller->setViewMode($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = $response->getData(true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('table', $responseData['view_mode']);
        $this->assertEquals('表示形式を変更しました。', $responseData['message']);

        // Verify session storage
        $this->assertEquals('table', session('facility_basic_info_view_mode'));
    }

    /**
     * Test setViewMode method with invalid input
     *
     * @test
     */
    public function it_rejects_invalid_view_mode()
    {
        $request = Request::create('/facilities/set-view-mode', 'POST', [
            'view_mode' => 'invalid',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->controller->setViewMode($request);
    }

    /**
     * Test setViewMode method with missing input
     *
     * @test
     */
    public function it_rejects_missing_view_mode()
    {
        $request = Request::create('/facilities/set-view-mode', 'POST', []);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->controller->setViewMode($request);
    }

    /**
     * Test getViewMode method with default fallback
     *
     * @test
     */
    public function it_returns_default_view_mode_when_not_set()
    {
        // Clear any existing session data
        session()->forget('facility_basic_info_view_mode');

        $viewMode = $this->controller->getViewMode();

        $this->assertEquals('card', $viewMode);
    }

    /**
     * Test getViewMode method with stored preference
     *
     * @test
     */
    public function it_returns_stored_view_mode_preference()
    {
        session(['facility_basic_info_view_mode' => 'table']);

        $viewMode = $this->controller->getViewMode();

        $this->assertEquals('table', $viewMode);
    }

    /**
     * Test view mode persistence across requests
     *
     * @test
     */
    public function it_persists_view_mode_across_requests()
    {
        // Set view mode to table
        $setRequest = Request::create('/facilities/set-view-mode', 'POST', [
            'view_mode' => 'table',
        ]);
        $this->controller->setViewMode($setRequest);

        // Verify persistence
        $this->assertEquals('table', $this->controller->getViewMode());

        // Change to card
        $setRequest = Request::create('/facilities/set-view-mode', 'POST', [
            'view_mode' => 'card',
        ]);
        $this->controller->setViewMode($setRequest);

        // Verify change
        $this->assertEquals('card', $this->controller->getViewMode());
    }

    /**
     * Test view mode parameter validation and sanitization
     *
     * @test
     */
    public function it_validates_view_mode_parameters()
    {
        // Test valid values
        $validModes = ['card', 'table'];

        foreach ($validModes as $mode) {
            $request = Request::create('/facilities/set-view-mode', 'POST', [
                'view_mode' => $mode,
            ]);

            $response = $this->controller->setViewMode($request);
            $responseData = $response->getData(true);

            $this->assertTrue($responseData['success']);
            $this->assertEquals($mode, $responseData['view_mode']);
        }

        // Test invalid values
        $invalidModes = ['list', 'grid', '', null, 123, true];

        foreach ($invalidModes as $mode) {
            $request = Request::create('/facilities/set-view-mode', 'POST', [
                'view_mode' => $mode,
            ]);

            try {
                $this->controller->setViewMode($request);
                $this->fail('Expected validation exception for invalid mode: '.var_export($mode, true));
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertArrayHasKey('view_mode', $e->errors());
            }
        }
    }

    /**
     * Test show method passes view mode to view
     *
     * @test
     */
    public function it_passes_view_mode_to_show_view()
    {
        $facility = Facility::factory()->create();

        // Test with card mode
        session(['facility_basic_info_view_mode' => 'card']);
        $response = $this->get(route('facilities.show', $facility));
        $response->assertViewHas('viewMode', 'card');

        // Test with table mode
        session(['facility_basic_info_view_mode' => 'table']);
        $response = $this->get(route('facilities.show', $facility));
        $response->assertViewHas('viewMode', 'table');

        // Test with no session (default)
        session()->forget('facility_basic_info_view_mode');
        $response = $this->get(route('facilities.show', $facility));
        $response->assertViewHas('viewMode', 'card');
    }

    /**
     * Test view mode constants are properly defined
     *
     * @test
     */
    public function it_has_proper_view_mode_constants()
    {
        $this->assertEquals('facility_basic_info_view_mode', FacilityController::VIEW_PREFERENCE_KEY);

        $expectedModes = [
            'card' => 'カード形式',
            'table' => 'テーブル形式',
        ];

        $this->assertEquals($expectedModes, FacilityController::VIEW_MODES);
    }

    /**
     * Test session key consistency
     *
     * @test
     */
    public function it_uses_consistent_session_key()
    {
        $request = Request::create('/facilities/set-view-mode', 'POST', [
            'view_mode' => 'table',
        ]);

        $this->controller->setViewMode($request);

        // Verify the session key matches the constant
        $this->assertTrue(session()->has(FacilityController::VIEW_PREFERENCE_KEY));
        $this->assertEquals('table', session(FacilityController::VIEW_PREFERENCE_KEY));
    }

    /**
     * Test setViewMode with invalid inputs
     *
     * @test
     *
     * @dataProvider invalidViewModeProvider
     */
    public function it_rejects_invalid_view_mode_inputs($invalidInput)
    {
        $request = $this->createViewModeRequest($invalidInput);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->controller->setViewMode($request);
    }

    /**
     * Data provider for invalid view mode inputs
     */
    public function invalidViewModeProvider(): array
    {
        return [
            'whitespace_padded' => [' table '],
            'uppercase' => ['TABLE'],
            'mixed_case' => ['Card'],
            'invalid_string' => ['invalid'],
            'empty_string' => [''],
            'numeric' => [123],
            'boolean' => [true],
            'null' => [null],
        ];
    }

    /**
     * Helper method to create view mode request
     */
    private function createViewModeRequest($viewMode): Request
    {
        return Request::create('/facilities/set-view-mode', 'POST', [
            'view_mode' => $viewMode,
        ]);
    }

    /**
     * Test getViewMode with corrupted session data
     *
     * @test
     */
    public function it_handles_corrupted_session_data()
    {
        // Set invalid session data
        session(['facility_basic_info_view_mode' => 'invalid_mode']);

        $viewMode = $this->controller->getViewMode();

        // Should return the stored value even if invalid (session retrieval doesn't validate)
        $this->assertEquals('invalid_mode', $viewMode);

        // Test with empty string session data (Laravel returns the empty string, not the default)
        session(['facility_basic_info_view_mode' => '']);

        $viewMode = $this->controller->getViewMode();

        // Laravel session helper returns empty string when that's what's stored
        $this->assertEquals('', $viewMode);

        // Test with completely missing session key
        session()->forget('facility_basic_info_view_mode');

        $viewMode = $this->controller->getViewMode();

        // Should return default when session key doesn't exist
        $this->assertEquals('card', $viewMode);
    }

    /**
     * Test setViewMode response format
     *
     * @test
     */
    public function it_returns_proper_json_response_format()
    {
        $request = Request::create('/facilities/set-view-mode', 'POST', [
            'view_mode' => 'table',
        ]);

        $response = $this->controller->setViewMode($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $responseData = $response->getData(true);

        // Verify all expected keys are present
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('view_mode', $responseData);
        $this->assertArrayHasKey('message', $responseData);

        // Verify data types
        $this->assertIsBool($responseData['success']);
        $this->assertIsString($responseData['view_mode']);
        $this->assertIsString($responseData['message']);

        // Verify values
        $this->assertTrue($responseData['success']);
        $this->assertEquals('table', $responseData['view_mode']);
        $this->assertEquals('表示形式を変更しました。', $responseData['message']);
    }

    /**
     * Test multiple rapid view mode changes
     *
     * @test
     */
    public function it_handles_multiple_rapid_view_mode_changes()
    {
        $modes = ['card', 'table', 'card', 'table', 'card'];

        foreach ($modes as $mode) {
            $request = Request::create('/facilities/set-view-mode', 'POST', [
                'view_mode' => $mode,
            ]);

            $response = $this->controller->setViewMode($request);
            $responseData = $response->getData(true);

            $this->assertTrue($responseData['success']);
            $this->assertEquals($mode, $responseData['view_mode']);
            $this->assertEquals($mode, $this->controller->getViewMode());
            $this->assertEquals($mode, session(FacilityController::VIEW_PREFERENCE_KEY));
        }
    }

    /**
     * Test view mode integration with show method
     *
     * @test
     */
    public function it_integrates_view_mode_with_show_method()
    {
        $facility = Facility::factory()->create();

        // Test that show method correctly retrieves and passes view mode
        session(['facility_basic_info_view_mode' => 'table']);

        $response = $this->get(route('facilities.show', $facility));
        $response->assertViewHas('viewMode', 'table');

        // Test with card mode
        session(['facility_basic_info_view_mode' => 'card']);

        $response = $this->get(route('facilities.show', $facility));
        $response->assertViewHas('viewMode', 'card');

        // Test with no session (should default to card)
        session()->forget('facility_basic_info_view_mode');

        $response = $this->get(route('facilities.show', $facility));
        $response->assertViewHas('viewMode', 'card');
    }

    /**
     * Test view mode constants are immutable
     *
     * @test
     */
    public function it_has_immutable_view_mode_constants()
    {
        // Test that constants exist and have expected values
        $this->assertTrue(defined('App\Http\Controllers\FacilityController::VIEW_PREFERENCE_KEY'));
        $this->assertEquals('facility_basic_info_view_mode', FacilityController::VIEW_PREFERENCE_KEY);

        // Test VIEW_MODES constant
        $viewModes = FacilityController::VIEW_MODES;
        $this->assertIsArray($viewModes);
        $this->assertCount(2, $viewModes);
        $this->assertArrayHasKey('card', $viewModes);
        $this->assertArrayHasKey('table', $viewModes);
        $this->assertEquals('カード形式', $viewModes['card']);
        $this->assertEquals('テーブル形式', $viewModes['table']);
    }
}
