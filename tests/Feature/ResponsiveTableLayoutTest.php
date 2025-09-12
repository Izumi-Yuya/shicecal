<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use App\Services\TableConfigService;
use App\Services\TableViewHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveTableLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Facility $facility;
    private TableConfigService $configService;
    private TableViewHelper $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'editor']);
        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'company_name' => 'テスト会社'
        ]);
        
        $this->configService = app(TableConfigService::class);
        $this->viewHelper = app(TableViewHelper::class);
    }

    public function test_responsive_css_generation_for_pc_environments()
    {
        $config = $this->configService->getTableConfig('basic_info');
        
        $responsiveCSS = $this->viewHelper->getResponsiveCSS($config);
        
        // Assert CSS contains media queries for PC breakpoints
        $this->assertStringContainsString('@media (max-width: 992px)', $responsiveCSS);
        $this->assertStringContainsString('@media (max-width: 768px)', $responsiveCSS);
        $this->assertStringContainsString('@media (max-width: 576px)', $responsiveCSS);
        
        // Assert horizontal scrolling is enabled
        $this->assertStringContainsString('overflow-x: auto', $responsiveCSS);
        $this->assertStringContainsString('-webkit-overflow-scrolling: touch', $responsiveCSS);
        
        // Assert scroll indicator is present
        $this->assertStringContainsString('横スクロールできます', $responsiveCSS);
        $this->assertStringContainsString('content:', $responsiveCSS);
    }

    public function test_table_responsive_classes_applied_correctly()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        
        // Test different responsive breakpoints
        $breakpoints = ['lg', 'md', 'sm'];
        
        foreach ($breakpoints as $breakpoint) {
            $config['layout']['responsive_breakpoint'] = $breakpoint;
            
            $tableClasses = $this->viewHelper->generateTableClasses($config);
            
            $this->assertStringContainsString("table-responsive-{$breakpoint}", $tableClasses);
        }
    }

    public function test_column_width_calculation_for_pc_screens()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'address', 'label' => 'Address']
        ];

        // Test for different PC screen sizes
        $screenSizes = [
            ['width' => 1920, 'min_width' => 150],
            ['width' => 1366, 'min_width' => 120],
            ['width' => 1024, 'min_width' => 100]
        ];

        foreach ($screenSizes as $screen) {
            $widths = $this->viewHelper->calculateColumnWidths($columns, [
                'screen_width' => $screen['width'],
                'min_column_width' => $screen['min_width']
            ]);

            $this->assertCount(4, $widths);
            
            // Each column should get 25% of screen width
            foreach ($widths as $width) {
                $expectedPixelWidth = ($screen['width'] * 25) / 100;
                
                if ($expectedPixelWidth < $screen['min_width']) {
                    // Should enforce minimum width
                    $this->assertStringContainsString("min-width: {$screen['min_width']}px", $width);
                } else {
                    // Should use percentage
                    $this->assertStringContainsString('25%', $width);
                }
            }
        }
    }

    public function test_horizontal_scrolling_behavior_in_view()
    {
        $this->actingAs($this->user);

        // Create a table with many columns to force horizontal scrolling
        $config = [
            'columns' => [
                ['key' => 'col1', 'label' => 'Column 1', 'width' => '200px'],
                ['key' => 'col2', 'label' => 'Column 2', 'width' => '200px'],
                ['key' => 'col3', 'label' => 'Column 3', 'width' => '200px'],
                ['key' => 'col4', 'label' => 'Column 4', 'width' => '200px'],
                ['key' => 'col5', 'label' => 'Column 5', 'width' => '200px'],
                ['key' => 'col6', 'label' => 'Column 6', 'width' => '200px']
            ],
            'layout' => [
                'type' => 'standard_table',
                'responsive_breakpoint' => 'lg'
            ],
            'styling' => [
                'table_class' => 'table table-bordered'
            ],
            'global_settings' => [
                'responsive' => ['enabled' => true]
            ],
            'features' => ['comments' => false]
        ];

        $data = [
            [
                'col1' => 'Data 1',
                'col2' => 'Data 2',
                'col3' => 'Data 3',
                'col4' => 'Data 4',
                'col5' => 'Data 5',
                'col6' => 'Data 6'
            ]
        ];

        $view = view('components.universal-table', [
            'tableId' => 'wide-table',
            'config' => $config,
            'data' => $data,
            'section' => 'test',
            'responsive' => true
        ]);

        $html = $view->render();

        // Assert responsive wrapper is present
        $this->assertStringContainsString('table-responsive', $html);
        $this->assertStringContainsString('table-responsive-lg', $html);
        
        // Assert table has fixed column widths that would cause overflow
        $this->assertStringContainsString('200px', $html);
        
        // Assert all columns are present
        for ($i = 1; $i <= 6; $i++) {
            $this->assertStringContainsString("Column {$i}", $html);
            $this->assertStringContainsString("Data {$i}", $html);
        }
    }

    public function test_responsive_table_with_key_value_pairs_layout()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $this->facility->toArray();

        $view = view('components.universal-table', [
            'tableId' => 'responsive-kv-table',
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info',
            'responsive' => true
        ]);

        $html = $view->render();

        // Assert key-value pairs layout with responsive classes
        $this->assertStringContainsString('table-layout-key-value-pairs', $html);
        $this->assertStringContainsString('table-responsive', $html);
        
        // Assert data is displayed in key-value format
        $this->assertStringContainsString('施設名', $html);
        $this->assertStringContainsString('テスト施設', $html);
        $this->assertStringContainsString('会社名', $html);
        $this->assertStringContainsString('テスト会社', $html);
    }

    public function test_responsive_table_with_grouped_rows_layout()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('service_info');
        
        // Mock service data with grouping
        $data = [
            ['service_type' => 'Web', 'service_name' => 'Website A', 'status' => 'active'],
            ['service_type' => 'Web', 'service_name' => 'Website B', 'status' => 'inactive'],
            ['service_type' => 'API', 'service_name' => 'API Service', 'status' => 'active']
        ];

        $view = view('components.universal-table', [
            'tableId' => 'responsive-grouped-table',
            'config' => $config,
            'data' => $data,
            'section' => 'service_info',
            'responsive' => true
        ]);

        $html = $view->render();

        // Assert grouped rows layout with responsive classes
        $this->assertStringContainsString('table-layout-grouped-rows', $html);
        $this->assertStringContainsString('table-responsive', $html);
        
        // Assert grouped data is displayed
        $this->assertStringContainsString('Web', $html);
        $this->assertStringContainsString('API', $html);
        $this->assertStringContainsString('Website A', $html);
        $this->assertStringContainsString('API Service', $html);
    }

    public function test_responsive_behavior_can_be_disabled()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $config['global_settings']['responsive']['enabled'] = false;
        
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
        
        // But table should still render normally
        $this->assertStringContainsString('table', $html);
        $this->assertStringContainsString('テスト施設', $html);
    }

    public function test_responsive_scroll_indicator_styling()
    {
        $config = $this->configService->getTableConfig('basic_info');
        
        $responsiveCSS = $this->viewHelper->getResponsiveCSS($config);
        
        // Assert scroll indicator has proper styling
        $this->assertStringContainsString('position: absolute', $responsiveCSS);
        $this->assertStringContainsString('top: 10px', $responsiveCSS);
        $this->assertStringContainsString('right: 10px', $responsiveCSS);
        $this->assertStringContainsString('background: rgba(0, 123, 255, 0.8)', $responsiveCSS);
        $this->assertStringContainsString('color: white', $responsiveCSS);
        $this->assertStringContainsString('border-radius: 4px', $responsiveCSS);
        $this->assertStringContainsString('font-size: 12px', $responsiveCSS);
        $this->assertStringContainsString('pointer-events: none', $responsiveCSS);
        $this->assertStringContainsString('z-index: 10', $responsiveCSS);
    }

    public function test_responsive_table_maintains_accessibility()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $this->facility->toArray();

        $view = view('components.universal-table', [
            'tableId' => 'accessible-responsive-table',
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info',
            'responsive' => true
        ]);

        $html = $view->render();

        // Assert accessibility attributes are maintained
        $this->assertStringContainsString('role="table"', $html);
        $this->assertStringContainsString('<th', $html); // Proper table headers
        $this->assertStringContainsString('<td', $html); // Proper table cells
        
        // Assert table structure is semantic
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('<tbody>', $html);
    }

    public function test_responsive_table_performance_with_large_datasets()
    {
        $this->actingAs($this->user);

        // Create a large dataset
        $largeData = [];
        for ($i = 1; $i <= 100; $i++) {
            $largeData[] = [
                'facility_name' => "施設 {$i}",
                'company_name' => "会社 {$i}",
                'office_code' => "CODE{$i}",
                'email' => "test{$i}@example.com"
            ];
        }

        $config = $this->configService->getTableConfig('basic_info');

        $startTime = microtime(true);

        $view = view('components.universal-table', [
            'tableId' => 'large-responsive-table',
            'config' => $config,
            'data' => $largeData,
            'section' => 'basic_info',
            'responsive' => true
        ]);

        $html = $view->render();
        
        $endTime = microtime(true);
        $renderTime = $endTime - $startTime;

        // Assert rendering completes within reasonable time (3 seconds as per requirements)
        $this->assertLessThan(3.0, $renderTime, 'Table rendering should complete within 3 seconds');
        
        // Assert table still renders correctly with large dataset
        $this->assertStringContainsString('table-responsive', $html);
        $this->assertStringContainsString('施設 1', $html);
        $this->assertStringContainsString('施設 100', $html);
    }
}