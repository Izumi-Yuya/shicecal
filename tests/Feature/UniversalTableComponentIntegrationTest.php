<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityService;
use App\Models\User;
use App\Services\TableConfigService;
use App\Services\TableDataFormatter;
use App\Services\TableViewHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniversalTableComponentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Facility $facility;
    private TableConfigService $configService;
    private TableDataFormatter $formatter;
    private TableViewHelper $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'editor']);
        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'company_name' => 'テスト会社',
            'office_code' => 'TEST001',
            'email' => 'test@example.com',
            'website_url' => 'https://example.com'
        ]);
        
        $this->configService = app(TableConfigService::class);
        $this->formatter = app(TableDataFormatter::class);
        $this->viewHelper = app(TableViewHelper::class);
    }

    public function test_universal_table_renders_key_value_pairs_layout()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $this->facility->toArray();

        // Mock the performance service to avoid optimization issues in tests
        $performanceService = $this->createMock(\App\Services\TablePerformanceService::class);
        $performanceService->method('generateOptimizedCSS')->willReturn('/* test css */');
        $performanceService->method('generateOptimizedJavaScript')->willReturn('/* test js */');

        $view = view('components.universal-table', [
            'tableId' => 'basic-info-table',
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info',
            'commentEnabled' => true,
            'responsive' => true,
            'tablePerformanceService' => $performanceService,
            'optimizations' => ['strategy' => 'full_render']
        ]);

        $html = $view->render();

        // Assert table structure
        $this->assertStringContainsString('id="basic-info-table"', $html);
        $this->assertStringContainsString('table-layout-key-value-pairs', $html);
        $this->assertStringContainsString('table-responsive', $html);
        
        // Assert data is displayed
        $this->assertStringContainsString('テスト施設', $html);
        $this->assertStringContainsString('テスト会社', $html);
        $this->assertStringContainsString('TEST001', $html);
        
        // Assert column labels are displayed
        $this->assertStringContainsString('施設名', $html);
        $this->assertStringContainsString('会社名', $html);
        $this->assertStringContainsString('事業所コード', $html);
    }

    public function test_universal_table_renders_standard_table_layout()
    {
        $this->actingAs($this->user);

        // Create test services for standard table layout
        $services = FacilityService::factory()->count(3)->create([
            'facility_id' => $this->facility->id
        ]);

        $config = $this->configService->getTableConfig('service_info');
        $data = $services->toArray();

        $view = view('components.universal-table', [
            'tableId' => 'service-info-table',
            'config' => $config,
            'data' => $data,
            'section' => 'service_info',
            'commentEnabled' => true,
            'responsive' => true
        ]);

        $html = $view->render();

        // Assert table structure
        $this->assertStringContainsString('id="service-info-table"', $html);
        $this->assertStringContainsString('table-layout-grouped-rows', $html);
        
        // Assert table headers
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('サービス種類', $html);
        $this->assertStringContainsString('有効期限', $html);
        
        // Assert table body with data
        $this->assertStringContainsString('<tbody>', $html);
        $this->assertCount(3, $services); // Verify we have test data
    }

    public function test_universal_table_handles_empty_data()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $data = []; // Empty data

        $view = view('components.universal-table', [
            'tableId' => 'empty-table',
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info',
            'commentEnabled' => false,
            'responsive' => true
        ]);

        $html = $view->render();

        // Assert table still renders with empty state
        $this->assertStringContainsString('id="empty-table"', $html);
        $this->assertStringContainsString('table', $html);
        
        // Should not contain any data
        $this->assertStringNotContainsString('テスト施設', $html);
    }

    public function test_universal_table_applies_responsive_classes()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $this->facility->toArray();

        $view = view('components.universal-table', [
            'tableId' => 'responsive-table',
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info',
            'responsive' => true
        ]);

        $html = $view->render();

        // Assert responsive classes are applied
        $this->assertStringContainsString('table-responsive', $html);
        $this->assertStringContainsString('table-responsive-lg', $html);
    }

    public function test_universal_table_can_disable_responsive()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $this->facility->toArray();

        $view = view('components.universal-table', [
            'tableId' => 'non-responsive-table',
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info',
            'responsive' => false
        ]);

        $html = $view->render();

        // Assert responsive classes are not applied
        $this->assertStringNotContainsString('table-responsive', $html);
    }

    public function test_universal_table_integrates_with_comment_system()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $this->facility->toArray();

        $view = view('components.universal-table', [
            'tableId' => 'comment-enabled-table',
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info',
            'commentEnabled' => true,
            'facility' => $this->facility
        ]);

        $html = $view->render();

        // Assert comment integration elements are present
        $this->assertStringContainsString('comment-toggle', $html);
        $this->assertStringContainsString('data-section="basic_info"', $html);
        $this->assertStringContainsString('comment-section', $html);
    }

    public function test_universal_table_can_disable_comments()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $this->facility->toArray();

        $view = view('components.universal-table', [
            'tableId' => 'comment-disabled-table',
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info',
            'commentEnabled' => false
        ]);

        $html = $view->render();

        // Assert comment elements are not present
        $this->assertStringNotContainsString('comment-toggle', $html);
        $this->assertStringNotContainsString('comment-section', $html);
    }

    public function test_universal_table_applies_custom_css_classes()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $this->facility->toArray();

        $tableClasses = $this->viewHelper->generateTableClasses($config, [
            'size' => 'sm',
            'additional_classes' => ['custom-test-class']
        ]);

        $view = view('components.universal-table', [
            'tableId' => 'custom-class-table',
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info',
            'tableClasses' => $tableClasses
        ]);

        $html = $view->render();

        // Assert custom classes are applied
        $this->assertStringContainsString('table-sm', $html);
        $this->assertStringContainsString('custom-test-class', $html);
    }

    public function test_universal_table_handles_complex_data_formatting()
    {
        $this->actingAs($this->user);

        // Create facility with complex data
        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'opening_date' => \Carbon\Carbon::parse('2020-01-01'),
            'years_in_operation' => 4,
            'capacity' => 100,
            'email' => 'test@example.com',
            'website_url' => 'example.com' // Without protocol
        ]);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $facility->toArray();

        $view = view('components.universal-table', [
            'tableId' => 'complex-data-table',
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info'
        ]);

        $html = $view->render();

        // Assert complex formatting is applied
        $this->assertMatchesRegularExpression('/\d{4}年\d{1,2}月\d{1,2}日/', $html); // Date formatting
        $this->assertStringContainsString('4年', $html); // Number with unit
        $this->assertStringContainsString('100名', $html); // Capacity with unit
        $this->assertStringContainsString('test@example.com', $html); // Email
        $this->assertStringContainsString('https://example.com', $html); // URL with protocol added
    }

    public function test_universal_table_handles_null_and_empty_values()
    {
        $this->actingAs($this->user);

        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'email' => null,
            'website_url' => '',
            'building_name' => null,
            'toll_free_number' => ''
        ]);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $facility->toArray();

        $view = view('components.universal-table', [
            'tableId' => 'empty-values-table',
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info'
        ]);

        $html = $view->render();

        // Assert empty values are handled correctly
        $this->assertStringContainsString('未設定', $html); // Default empty text
        $this->assertStringContainsString('テスト施設', $html); // Non-empty value
        
        // Assert empty value styling is applied
        $this->assertStringContainsString('text-muted', $html);
    }

    public function test_universal_table_supports_different_table_types()
    {
        $this->actingAs($this->user);

        $tableTypes = ['basic_info', 'service_info', 'land_info'];

        foreach ($tableTypes as $tableType) {
            $config = $this->configService->getTableConfig($tableType);
            
            // Use appropriate test data for each type
            $data = $tableType === 'basic_info' ? $this->facility->toArray() : [];

            $view = view('components.universal-table', [
                'tableId' => "{$tableType}-table",
                'config' => $config,
                'data' => $data,
                'section' => $tableType
            ]);

            $html = $view->render();

            // Assert table renders for each type
            $this->assertStringContainsString("id=\"{$tableType}-table\"", $html);
            $this->assertStringContainsString('table', $html);
            
            // Assert layout type is applied correctly
            $layoutType = str_replace('_', '-', $config['layout']['type']);
            $this->assertStringContainsString("table-layout-{$layoutType}", $html);
        }
    }

    public function test_universal_table_error_handling()
    {
        $this->actingAs($this->user);

        // Test with invalid config
        $invalidConfig = [
            'columns' => [], // Empty columns
            'layout' => ['type' => 'invalid_type']
        ];

        $view = view('components.universal-table', [
            'tableId' => 'error-table',
            'config' => $invalidConfig,
            'data' => [],
            'section' => 'basic_info'
        ]);

        // Should not throw an exception
        $html = $view->render();
        
        // Should still render a basic table structure
        $this->assertStringContainsString('table', $html);
        $this->assertStringContainsString('id="error-table"', $html);
    }
}