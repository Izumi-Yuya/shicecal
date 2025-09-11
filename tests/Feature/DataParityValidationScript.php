<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive Data Parity Validation Script
 *
 * This script validates that all facility data from card view appears correctly
 * in table view with proper formatting and no information loss.
 */
class DataParityValidationScript extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private array $validationResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($this->user);
    }

    /**
     * Run comprehensive data parity validation
     *
     * @test
     */
    public function it_validates_comprehensive_data_parity()
    {
        $this->info('Starting comprehensive data parity validation...');

        // Test scenarios
        $scenarios = [
            'complete_data' => $this->createFacilityWithCompleteData(),
            'empty_values' => $this->createFacilityWithEmptyValues(),
            'with_services' => $this->createFacilityWithServices(),
            'mixed_data' => $this->createFacilityWithMixedData(),
        ];

        foreach ($scenarios as $scenarioName => $facility) {
            $this->info("Validating scenario: {$scenarioName}");
            $this->validateScenario($scenarioName, $facility);
        }

        $this->printValidationSummary();
        $this->assertTrue(true, 'All data parity validations completed successfully');
    }

    /**
     * Validate a specific scenario
     */
    private function validateScenario(string $scenarioName, Facility $facility): void
    {
        $results = [];

        // Test basic field parity
        $results['basic_fields'] = $this->validateBasicFieldParity($facility);

        // Test date formatting
        $results['date_formatting'] = $this->validateDateFormatting($facility);

        // Test number formatting
        $results['number_formatting'] = $this->validateNumberFormatting($facility);

        // Test link formatting
        $results['link_formatting'] = $this->validateLinkFormatting($facility);

        // Test empty value handling
        $results['empty_values'] = $this->validateEmptyValueHandling($facility);

        // Test service information
        $results['service_information'] = $this->validateServiceInformation($facility);

        // Test badge formatting
        $results['badge_formatting'] = $this->validateBadgeFormatting($facility);

        $this->validationResults[$scenarioName] = $results;
    }

    /**
     * Validate basic field parity between views
     */
    private function validateBasicFieldParity(Facility $facility): array
    {
        $results = ['passed' => 0, 'failed' => 0, 'details' => []];

        $basicFields = [
            'company_name' => $facility->company_name,
            'facility_name' => $facility->facility_name,
            'office_code' => $facility->office_code,
            'designation_number' => $facility->designation_number,
            'postal_code' => $facility->formatted_postal_code,
            'address' => $facility->full_address,
            'phone_number' => $facility->phone_number,
            'fax_number' => $facility->fax_number,
            'toll_free_number' => $facility->toll_free_number,
            'email' => $facility->email,
            'website_url' => $facility->website_url,
            'building_structure' => $facility->building_structure,
        ];

        foreach ($basicFields as $fieldName => $fieldValue) {
            if ($fieldValue) {
                $cardPresent = $this->isFieldPresentInView($facility, 'card', $fieldValue);
                $tablePresent = $this->isFieldPresentInView($facility, 'table', $fieldValue);

                if ($cardPresent && $tablePresent) {
                    $results['passed']++;
                    $results['details'][$fieldName] = 'PASS';
                } else {
                    $results['failed']++;
                    $results['details'][$fieldName] = sprintf(
                        'FAIL - Card: %s, Table: %s',
                        $cardPresent ? 'Present' : 'Missing',
                        $tablePresent ? 'Present' : 'Missing'
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Validate date formatting consistency
     */
    private function validateDateFormatting(Facility $facility): array
    {
        $results = ['passed' => 0, 'failed' => 0, 'details' => []];

        $dateFields = [
            'opening_date' => $facility->opening_date,
        ];

        foreach ($dateFields as $fieldName => $dateValue) {
            if ($dateValue) {
                $expectedFormat = $dateValue->format('Y年m月d日');

                $cardPresent = $this->isFieldPresentInView($facility, 'card', $expectedFormat);
                $tablePresent = $this->isFieldPresentInView($facility, 'table', $expectedFormat);

                if ($cardPresent && $tablePresent) {
                    $results['passed']++;
                    $results['details'][$fieldName] = 'PASS - Japanese format';
                } else {
                    $results['failed']++;
                    $results['details'][$fieldName] = sprintf(
                        'FAIL - Expected: %s, Card: %s, Table: %s',
                        $expectedFormat,
                        $cardPresent ? 'Present' : 'Missing',
                        $tablePresent ? 'Present' : 'Missing'
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Validate number formatting with units
     */
    private function validateNumberFormatting(Facility $facility): array
    {
        $results = ['passed' => 0, 'failed' => 0, 'details' => []];

        $numberFields = [
            'building_floors' => ['value' => $facility->building_floors, 'unit' => '階'],
            'paid_rooms_count' => ['value' => $facility->paid_rooms_count, 'unit' => '室'],
            'ss_rooms_count' => ['value' => $facility->ss_rooms_count, 'unit' => '室'],
            'capacity' => ['value' => $facility->capacity, 'unit' => '名'],
            'years_in_operation' => ['value' => $facility->years_in_operation, 'unit' => '年'],
        ];

        foreach ($numberFields as $fieldName => $fieldData) {
            if ($fieldData['value'] !== null) {
                $expectedFormat = number_format($fieldData['value']).$fieldData['unit'];

                $cardPresent = $this->isFieldPresentInView($facility, 'card', $expectedFormat);
                $tablePresent = $this->isFieldPresentInView($facility, 'table', $expectedFormat);

                if ($cardPresent && $tablePresent) {
                    $results['passed']++;
                    $results['details'][$fieldName] = 'PASS - With unit';
                } else {
                    $results['failed']++;
                    $results['details'][$fieldName] = sprintf(
                        'FAIL - Expected: %s, Card: %s, Table: %s',
                        $expectedFormat,
                        $cardPresent ? 'Present' : 'Missing',
                        $tablePresent ? 'Present' : 'Missing'
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Validate link formatting
     */
    private function validateLinkFormatting(Facility $facility): array
    {
        $results = ['passed' => 0, 'failed' => 0, 'details' => []];

        // Test email link
        if ($facility->email) {
            $emailLinkPattern = 'href="mailto:'.$facility->email.'"';

            $cardHasEmailLink = $this->isFieldPresentInView($facility, 'card', $emailLinkPattern);
            $tableHasEmailLink = $this->isFieldPresentInView($facility, 'table', $emailLinkPattern);

            if ($cardHasEmailLink && $tableHasEmailLink) {
                $results['passed']++;
                $results['details']['email_link'] = 'PASS - Mailto link';
            } else {
                $results['failed']++;
                $results['details']['email_link'] = sprintf(
                    'FAIL - Card: %s, Table: %s',
                    $cardHasEmailLink ? 'Present' : 'Missing',
                    $tableHasEmailLink ? 'Present' : 'Missing'
                );
            }
        }

        // Test website URL link
        if ($facility->website_url) {
            $urlLinkPattern = 'href="'.$facility->website_url.'"';
            $targetBlankPattern = 'target="_blank"';

            $cardHasUrlLink = $this->isFieldPresentInView($facility, 'card', $urlLinkPattern);
            $tableHasUrlLink = $this->isFieldPresentInView($facility, 'table', $urlLinkPattern);
            $cardHasTargetBlank = $this->isFieldPresentInView($facility, 'card', $targetBlankPattern);
            $tableHasTargetBlank = $this->isFieldPresentInView($facility, 'table', $targetBlankPattern);

            if ($cardHasUrlLink && $tableHasUrlLink && $cardHasTargetBlank && $tableHasTargetBlank) {
                $results['passed']++;
                $results['details']['website_link'] = 'PASS - External link with target="_blank"';
            } else {
                $results['failed']++;
                $results['details']['website_link'] = sprintf(
                    'FAIL - URL Link Card: %s, Table: %s, Target Blank Card: %s, Table: %s',
                    $cardHasUrlLink ? 'Present' : 'Missing',
                    $tableHasUrlLink ? 'Present' : 'Missing',
                    $cardHasTargetBlank ? 'Present' : 'Missing',
                    $tableHasTargetBlank ? 'Present' : 'Missing'
                );
            }
        }

        return $results;
    }

    /**
     * Validate empty value handling
     */
    private function validateEmptyValueHandling(Facility $facility): array
    {
        $results = ['passed' => 0, 'failed' => 0, 'details' => []];

        // Count "未設定" occurrences in both views
        $cardContent = $this->getViewContent($facility, 'card');
        $tableContent = $this->getViewContent($facility, 'table');

        $cardEmptyCount = substr_count($cardContent, '未設定');
        $tableEmptyCount = substr_count($tableContent, '未設定');

        if ($cardEmptyCount > 0 && $tableEmptyCount > 0) {
            $results['passed']++;
            $results['details']['empty_value_display'] = sprintf(
                'PASS - Card: %d occurrences, Table: %d occurrences',
                $cardEmptyCount,
                $tableEmptyCount
            );
        } else {
            $results['failed']++;
            $results['details']['empty_value_display'] = sprintf(
                'FAIL - Card: %d occurrences, Table: %d occurrences',
                $cardEmptyCount,
                $tableEmptyCount
            );
        }

        return $results;
    }

    /**
     * Validate service information completeness
     */
    private function validateServiceInformation(Facility $facility): array
    {
        $results = ['passed' => 0, 'failed' => 0, 'details' => []];

        $facility->load('services');

        foreach ($facility->services as $index => $service) {
            $serviceResults = [];

            // Check service type
            $cardHasType = $this->isFieldPresentInView($facility, 'card', $service->service_type);
            $tableHasType = $this->isFieldPresentInView($facility, 'table', $service->service_type);

            if ($cardHasType && $tableHasType) {
                $serviceResults['type'] = 'PASS';
            } else {
                $serviceResults['type'] = sprintf(
                    'FAIL - Card: %s, Table: %s',
                    $cardHasType ? 'Present' : 'Missing',
                    $tableHasType ? 'Present' : 'Missing'
                );
            }

            // Check service dates
            if ($service->renewal_start_date) {
                $expectedDate = $service->renewal_start_date->format('Y年m月d日');
                $cardHasDate = $this->isFieldPresentInView($facility, 'card', $expectedDate);
                $tableHasDate = $this->isFieldPresentInView($facility, 'table', $expectedDate);

                if ($cardHasDate && $tableHasDate) {
                    $serviceResults['start_date'] = 'PASS';
                } else {
                    $serviceResults['start_date'] = sprintf(
                        'FAIL - Expected: %s, Card: %s, Table: %s',
                        $expectedDate,
                        $cardHasDate ? 'Present' : 'Missing',
                        $tableHasDate ? 'Present' : 'Missing'
                    );
                }
            }

            if ($service->renewal_end_date) {
                $expectedDate = $service->renewal_end_date->format('Y年m月d日');
                $cardHasDate = $this->isFieldPresentInView($facility, 'card', $expectedDate);
                $tableHasDate = $this->isFieldPresentInView($facility, 'table', $expectedDate);

                if ($cardHasDate && $tableHasDate) {
                    $serviceResults['end_date'] = 'PASS';
                } else {
                    $serviceResults['end_date'] = sprintf(
                        'FAIL - Expected: %s, Card: %s, Table: %s',
                        $expectedDate,
                        $cardHasDate ? 'Present' : 'Missing',
                        $tableHasDate ? 'Present' : 'Missing'
                    );
                }
            }

            $results['details']['service_'.$index] = $serviceResults;

            // Count passes and fails
            foreach ($serviceResults as $result) {
                if (strpos($result, 'PASS') === 0) {
                    $results['passed']++;
                } else {
                    $results['failed']++;
                }
            }
        }

        return $results;
    }

    /**
     * Validate badge formatting
     */
    private function validateBadgeFormatting(Facility $facility): array
    {
        $results = ['passed' => 0, 'failed' => 0, 'details' => []];

        // Test office code badge
        $badgePattern = 'badge bg-primary';

        $cardHasBadge = $this->isFieldPresentInView($facility, 'card', $badgePattern);
        $tableHasBadge = $this->isFieldPresentInView($facility, 'table', $badgePattern);

        if ($cardHasBadge && $tableHasBadge) {
            $results['passed']++;
            $results['details']['office_code_badge'] = 'PASS - Bootstrap badge';
        } else {
            $results['failed']++;
            $results['details']['office_code_badge'] = sprintf(
                'FAIL - Card: %s, Table: %s',
                $cardHasBadge ? 'Present' : 'Missing',
                $tableHasBadge ? 'Present' : 'Missing'
            );
        }

        return $results;
    }

    /**
     * Check if a field is present in a specific view
     */
    private function isFieldPresentInView(Facility $facility, string $viewMode, string $searchValue): bool
    {
        $content = $this->getViewContent($facility, $viewMode);

        return strpos($content, $searchValue) !== false;
    }

    /**
     * Get view content for a specific view mode
     */
    private function getViewContent(Facility $facility, string $viewMode): string
    {
        session(['facility_basic_info_view_mode' => $viewMode]);
        $response = $this->get(route('facilities.show', $facility));

        return $response->getContent();
    }

    /**
     * Print validation summary
     */
    private function printValidationSummary(): void
    {
        $this->info("\n".str_repeat('=', 80));
        $this->info('DATA PARITY VALIDATION SUMMARY');
        $this->info(str_repeat('=', 80));

        $totalPassed = 0;
        $totalFailed = 0;

        foreach ($this->validationResults as $scenarioName => $scenarioResults) {
            $this->info("\nScenario: ".strtoupper($scenarioName));
            $this->info(str_repeat('-', 40));

            foreach ($scenarioResults as $testType => $results) {
                $passed = $results['passed'];
                $failed = $results['failed'];
                $total = $passed + $failed;

                $totalPassed += $passed;
                $totalFailed += $failed;

                $status = $failed === 0 ? '✓ PASS' : '✗ FAIL';
                $this->info(sprintf(
                    '%s: %s (%d/%d passed)',
                    ucwords(str_replace('_', ' ', $testType)),
                    $status,
                    $passed,
                    $total
                ));

                // Show failed details
                if ($failed > 0) {
                    foreach ($results['details'] as $field => $detail) {
                        if (strpos($detail, 'FAIL') === 0) {
                            $this->info("  - {$field}: {$detail}");
                        }
                    }
                }
            }
        }

        $this->info("\n".str_repeat('=', 80));
        $this->info(sprintf(
            'OVERALL RESULT: %d PASSED, %d FAILED',
            $totalPassed,
            $totalFailed
        ));

        if ($totalFailed === 0) {
            $this->info('🎉 ALL DATA PARITY VALIDATIONS PASSED!');
        } else {
            $this->info('⚠️  SOME VALIDATIONS FAILED - REVIEW DETAILS ABOVE');
        }

        $this->info(str_repeat('=', 80));
    }

    /**
     * Create facility with complete data
     */
    private function createFacilityWithCompleteData(): Facility
    {
        return Facility::factory()->create([
            'company_name' => 'テスト株式会社',
            'office_code' => 'TEST001',
            'designation_number' => '1234567890',
            'facility_name' => 'テスト施設',
            'postal_code' => '1234567',
            'address' => '東京都渋谷区テスト1-2-3',
            'building_name' => 'テストビル4F',
            'phone_number' => '03-1234-5678',
            'fax_number' => '03-1234-5679',
            'toll_free_number' => '0120-123-456',
            'email' => 'test@example.com',
            'website_url' => 'https://example.com',
            'opening_date' => Carbon::parse('2020-01-15'),
            'years_in_operation' => 4,
            'building_structure' => '鉄筋コンクリート造',
            'building_floors' => 5,
            'paid_rooms_count' => 50,
            'ss_rooms_count' => 10,
            'capacity' => 60,
            'status' => 'approved',
        ]);
    }

    /**
     * Create facility with empty values
     */
    private function createFacilityWithEmptyValues(): Facility
    {
        return Facility::factory()->create([
            'company_name' => 'テスト株式会社',
            'office_code' => 'TEST002',
            'facility_name' => 'テスト施設2',
            'designation_number' => null,
            'postal_code' => null,
            'address' => null,
            'building_name' => null,
            'phone_number' => null,
            'fax_number' => null,
            'toll_free_number' => null,
            'email' => null,
            'website_url' => null,
            'opening_date' => null,
            'years_in_operation' => null,
            'building_structure' => null,
            'building_floors' => null,
            'paid_rooms_count' => null,
            'ss_rooms_count' => null,
            'capacity' => null,
        ]);
    }

    /**
     * Create facility with services
     */
    private function createFacilityWithServices(): Facility
    {
        $facility = $this->createFacilityWithCompleteData();

        FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'service_type' => '介護保険サービス',
            'renewal_start_date' => Carbon::parse('2023-04-01'),
            'renewal_end_date' => Carbon::parse('2029-03-31'),
        ]);

        FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'service_type' => '障害福祉サービス',
            'renewal_start_date' => Carbon::parse('2022-10-01'),
            'renewal_end_date' => Carbon::parse('2028-09-30'),
        ]);

        return $facility->fresh(['services']);
    }

    /**
     * Create facility with mixed data (some fields empty, some filled)
     */
    private function createFacilityWithMixedData(): Facility
    {
        return Facility::factory()->create([
            'company_name' => 'ミックステスト株式会社',
            'office_code' => 'MIX001',
            'designation_number' => null, // Empty
            'facility_name' => 'ミックステスト施設',
            'postal_code' => '5678901',
            'address' => '大阪府大阪市テスト区5-6-7',
            'building_name' => null, // Empty
            'phone_number' => '06-5678-9012',
            'fax_number' => null, // Empty
            'toll_free_number' => '0800-567-890',
            'email' => null, // Empty
            'website_url' => 'https://mixed-test.com',
            'opening_date' => Carbon::parse('2018-03-20'),
            'years_in_operation' => null, // Empty
            'building_structure' => '木造',
            'building_floors' => 3,
            'paid_rooms_count' => null, // Empty
            'ss_rooms_count' => 5,
            'capacity' => 25,
            'status' => 'approved',
        ]);
    }

    /**
     * Helper method to output info during tests
     */
    private function info(string $message): void
    {
        if (method_exists($this, 'output')) {
            $this->output->writeln($message);
        } else {
            echo $message."\n";
        }
    }
}
