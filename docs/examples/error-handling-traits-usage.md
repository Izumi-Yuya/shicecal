# Error Handling Traits Usage Examples

This document demonstrates how to use the newly created error handling traits in controllers and services.

## Controller Usage

### Using HandlesControllerErrors Trait

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HandlesControllerErrors;
use App\Services\FacilityService;
use Illuminate\Http\Request;

class ExampleController extends Controller
{
    use HandlesControllerErrors;

    protected $facilityService;

    public function __construct(FacilityService $facilityService)
    {
        $this->facilityService = $facilityService;
    }

    public function store(Request $request)
    {
        try {
            $facility = $this->facilityService->create($request->all());
            
            return response()->json([
                'success' => true,
                'data' => $facility,
                'message' => '施設が正常に作成されました。'
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'facility_creation');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $facility = $this->facilityService->update($id, $request->all());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $facility,
                    'message' => '施設が正常に更新されました。'
                ]);
            }
            
            return redirect()->route('facilities.show', $facility)
                ->with('success', '施設が正常に更新されました。');
        } catch (\Exception $e) {
            return $this->handleException($e, 'facility_update');
        }
    }
}
```

## Service Usage

### Using HandlesServiceErrors Trait

```php
<?php

namespace App\Services;

use App\Services\Traits\HandlesServiceErrors;
use App\Exceptions\FacilityServiceException;
use App\Models\Facility;

class ExampleFacilityService
{
    use HandlesServiceErrors;

    protected function getServiceExceptionClass(): string
    {
        return FacilityServiceException::class;
    }

    public function create(array $data): Facility
    {
        return $this->executeWithErrorHandling(function() use ($data) {
            // Validate required parameters
            $this->validateRequiredParams($data, [
                'company_name',
                'facility_name',
                'address'
            ], 'facility_creation');

            // Log the operation
            $this->logInfo('Creating new facility', [
                'company_name' => $data['company_name'],
                'facility_name' => $data['facility_name']
            ]);

            // Create the facility
            $facility = Facility::create($data);

            $this->logInfo('Facility created successfully', [
                'facility_id' => $facility->id
            ]);

            return $facility;
        }, 'create_facility', ['data' => $data]);
    }

    public function update(int $id, array $data): Facility
    {
        return $this->executeWithErrorHandling(function() use ($id, $data) {
            $facility = Facility::findOrFail($id);

            // Log the operation
            $this->logInfo('Updating facility', [
                'facility_id' => $id,
                'changes' => array_keys($data)
            ]);

            $facility->update($data);

            $this->logInfo('Facility updated successfully', [
                'facility_id' => $facility->id
            ]);

            return $facility;
        }, 'update_facility', ['id' => $id, 'data' => $data]);
    }

    public function calculateLandValue(array $landData): array
    {
        try {
            $this->validateRequiredParams($landData, [
                'site_area_tsubo',
                'purchase_price'
            ], 'land_value_calculation');

            $unitPrice = $landData['purchase_price'] / $landData['site_area_tsubo'];

            $this->logInfo('Land value calculated', [
                'site_area_tsubo' => $landData['site_area_tsubo'],
                'purchase_price' => $landData['purchase_price'],
                'unit_price' => $unitPrice
            ]);

            return [
                'unit_price_per_tsubo' => round($unitPrice),
                'total_value' => $landData['purchase_price']
            ];
        } catch (\Exception $e) {
            $this->handleAndRethrowException($e, 'land_value_calculation', $landData);
        }
    }
}
```

## Test Usage

### Using Test Traits

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;
use Tests\Traits\CreatesTestFacilities;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers, CreatesTestFacilities;

    public function test_admin_can_create_facility()
    {
        // Create and authenticate as admin
        $admin = $this->actingAsAdmin();

        // Create test data
        $facilityData = [
            'company_name' => 'テスト会社',
            'facility_name' => 'テスト施設',
            'address' => '東京都渋谷区1-1-1'
        ];

        // Make request
        $response = $this->postJson('/api/facilities', $facilityData);

        // Assert response
        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_facility_with_land_info_creation()
    {
        // Create complete facility with land info
        [$facility, $landInfo] = $this->createCompleteFacility();

        // Assert relationships
        $this->assertEquals($facility->id, $landInfo->facility_id);
        $this->assertEquals('owned', $landInfo->ownership_type);
        $this->assertTrue($facility->isApproved());
    }

    public function test_user_permissions()
    {
        // Create users with different roles
        $users = $this->createCompleteUserSet();

        // Test admin permissions
        $this->assertTrue($users['admin']->canManageSystem());
        $this->assertTrue($users['admin']->canEditLandInfo());

        // Test editor permissions
        $this->assertTrue($users['editor']->canEdit());
        $this->assertTrue($users['editor']->canEditLandInfo());

        // Test viewer permissions
        $this->assertFalse($users['viewer']->canEdit());
        $this->assertFalse($users['viewer']->canEditLandInfo());
    }
}
```

## Key Benefits

1. **Consistent Error Handling**: All controllers and services handle errors in the same way
2. **Comprehensive Logging**: All errors are logged with context information
3. **Japanese Error Messages**: User-friendly error messages in Japanese
4. **JSON/Redirect Support**: Automatic detection of API vs web requests
5. **Test Data Creation**: Easy creation of test users and facilities with various configurations
6. **Type Safety**: Proper exception types for different error scenarios

## Error Codes

The system uses the following error codes:

- `VALIDATION_ERROR`: Form validation failures
- `AUTHORIZATION_ERROR`: Permission denied
- `AUTHENTICATION_ERROR`: Login required
- `NOT_FOUND`: Resource not found
- `SERVICE_ERROR`: General service errors
- `FACILITY_SERVICE_ERROR`: Facility-specific service errors
- `EXPORT_SERVICE_ERROR`: Export-related service errors
- `VALIDATION_SERVICE_ERROR`: Service-level validation errors