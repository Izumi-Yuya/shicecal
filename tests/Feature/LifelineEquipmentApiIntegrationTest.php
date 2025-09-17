<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->facility = Facility::factory()->create();
    }

    public function test_can_get_all_equipment_data()
    {
        $this->actingAs($this->user);

        // Create some test data
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'status' => 'active',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $response = $this->getJson(route('facilities.lifeline-equipment.index', $this->facility));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'facility_id',
                    'facility_name',
                    'equipment_data',
                    'categories',
                ],
                'meta' => [
                    'timestamp',
                    'user_id',
                    'request_id',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_can_get_multiple_categories_data()
    {
        $this->actingAs($this->user);

        $requestData = [
            'categories' => ['electrical', 'gas'],
        ];

        $response = $this->postJson(
            route('facilities.lifeline-equipment.multiple', $this->facility),
            $requestData
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'facility_id',
                    'equipment_data',
                    'requested_categories',
                ],
                'meta',
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_can_bulk_update_equipment_data()
    {
        $this->actingAs($this->user);

        $requestData = [
            'equipment_data' => [
                [
                    'category' => 'electrical',
                    'data' => [
                        'basic_info' => [
                            'electrical_contractor' => 'Test Electric Company',
                            'safety_management_company' => 'Test Safety Company',
                        ],
                    ],
                ],
                [
                    'category' => 'gas',
                    'data' => [
                        'basic_info' => [
                            'gas_supplier' => 'Test Gas Company',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->putJson(
            route('facilities.lifeline-equipment.bulk-update', $this->facility),
            $requestData
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'updated_categories',
                    'success_count',
                    'results',
                ],
                'meta',
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_can_get_equipment_summary()
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('facilities.lifeline-equipment.summary', $this->facility));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'facility_id',
                    'facility_name',
                    'summary',
                    'statistics' => [
                        'total_categories',
                        'configured_categories',
                        'completion_percentage',
                    ],
                ],
                'meta',
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_can_validate_data_consistency()
    {
        $this->actingAs($this->user);

        $requestData = [
            'equipment_data' => [
                'electrical' => [
                    'basic_info' => [
                        'electrical_contractor' => 'Company A',
                        'safety_management_company' => 'Safety Company A',
                    ],
                ],
                'gas' => [
                    'basic_info' => [
                        'gas_supplier' => 'Company B',
                        'maintenance_company' => 'Safety Company B',
                    ],
                ],
            ],
        ];

        $response = $this->postJson(
            route('facilities.lifeline-equipment.validate-consistency', $this->facility),
            $requestData
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'facility_id',
                    'validation_status',
                    'consistency_issues',
                    'warnings',
                    'recommendations',
                ],
                'meta',
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_can_get_available_categories()
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('facilities.lifeline-equipment.categories', $this->facility));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'facility_id',
                    'categories',
                    'available_statuses',
                ],
                'meta',
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Verify all expected categories are present
        $categories = $response->json('data.categories');
        $expectedCategories = ['electrical', 'gas', 'water', 'elevator', 'hvac_lighting'];
        
        foreach ($expectedCategories as $category) {
            $this->assertArrayHasKey($category, $categories);
            $this->assertArrayHasKey('name', $categories[$category]);
            $this->assertArrayHasKey('is_configured', $categories[$category]);
            $this->assertArrayHasKey('has_detailed_implementation', $categories[$category]);
        }
    }

    public function test_validates_category_parameter()
    {
        $this->actingAs($this->user);

        $requestData = [
            'categories' => ['invalid_category'],
        ];

        $response = $this->postJson(
            route('facilities.lifeline-equipment.multiple', $this->facility),
            $requestData
        );

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_validates_bulk_update_data_structure()
    {
        $this->actingAs($this->user);

        $requestData = [
            'equipment_data' => [
                [
                    'category' => 'invalid_category',
                    'data' => [],
                ],
            ],
        ];

        $response = $this->putJson(
            route('facilities.lifeline-equipment.bulk-update', $this->facility),
            $requestData
        );

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
                'meta',
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson(route('facilities.lifeline-equipment.index', $this->facility));

        $response->assertStatus(401); // Unauthorized for JSON requests
    }

    public function test_checks_authorization()
    {
        $unauthorizedUser = User::factory()->create([
            'role' => 'viewer',
        ]);

        $this->actingAs($unauthorizedUser);

        $response = $this->putJson(
            route('facilities.lifeline-equipment.bulk-update', $this->facility),
            ['equipment_data' => []]
        );

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_api_response_format_consistency()
    {
        $this->actingAs($this->user);

        $endpoints = [
            ['method' => 'get', 'route' => 'facilities.lifeline-equipment.index'],
            ['method' => 'get', 'route' => 'facilities.lifeline-equipment.summary'],
            ['method' => 'get', 'route' => 'facilities.lifeline-equipment.categories'],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->{$endpoint['method'] . 'Json'}(
                route($endpoint['route'], $this->facility)
            );

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data',
                    'meta' => [
                        'timestamp',
                        'user_id',
                        'request_id',
                    ],
                ]);

            // Verify meta data
            $meta = $response->json('meta');
            $this->assertNotEmpty($meta['timestamp']);
            $this->assertEquals($this->user->id, $meta['user_id']);
            $this->assertNotEmpty($meta['request_id']);
        }
    }
}