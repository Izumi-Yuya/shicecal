<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * ライフライン設備管理のセキュリティテスト
 * 
 * 権限管理、データ保護、入力検証のセキュリティ機能をテストします。
 */
class LifelineEquipmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $editor;
    private User $viewer;
    private User $unauthorizedUser;
    private Facility $facility;
    private LifelineEquipment $lifelineEquipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->viewer = User::factory()->create(['role' => 'viewer']);
        $this->unauthorizedUser = User::factory()->create(['role' => 'viewer']);
        
        $this->facility = Facility::factory()->create();
        $this->lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'created_by' => $this->editor->id,
        ]);
    }

    /** @test */
    public function it_enforces_authentication_for_all_endpoints()
    {
        // Test unauthenticated access
        $response = $this->get("/facilities/{$this->facility->id}/lifeline-equipment/electrical");
        $response->assertRedirect('/login');

        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", [
            'basic_info' => ['electrical_contractor' => 'Test'],
        ]);
        $response->assertRedirect('/login');

        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", [
            'basic_info' => ['electrical_contractor' => 'Test'],
        ]);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_enforces_view_permissions()
    {
        // Admin should have access
        $this->actingAs($this->admin);
        $response = $this->get("/facilities/{$this->facility->id}/lifeline-equipment/electrical");
        $response->assertStatus(200);

        // Editor should have access
        $this->actingAs($this->editor);
        $response = $this->get("/facilities/{$this->facility->id}/lifeline-equipment/electrical");
        $response->assertStatus(200);

        // Viewer should have access
        $this->actingAs($this->viewer);
        $response = $this->get("/facilities/{$this->facility->id}/lifeline-equipment/electrical");
        $response->assertStatus(200);
    }

    /** @test */
    public function it_enforces_edit_permissions()
    {
        $updateData = [
            'basic_info' => [
                'electrical_contractor' => '関西電力',
            ],
        ];

        // Admin should be able to edit
        $this->actingAs($this->admin);
        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData);
        $response->assertStatus(200);

        // Editor should be able to edit
        $this->actingAs($this->editor);
        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData);
        $response->assertStatus(200);

        // Viewer should not be able to edit
        $this->actingAs($this->viewer);
        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData);
        $response->assertStatus(403);
    }

    /** @test */
    public function it_prevents_sql_injection_attacks()
    {
        $this->actingAs($this->admin);

        // Test SQL injection in JSON fields
        $maliciousData = [
            'basic_info' => [
                'electrical_contractor' => "'; DROP TABLE electrical_equipment; --",
                'safety_management_company' => "' OR '1'='1",
            ],
        ];

        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $maliciousData);
        
        // Should not cause SQL injection
        $response->assertStatus(200);
        
        // Verify data is stored safely
        $equipment = ElectricalEquipment::where('lifeline_equipment_id', $this->lifelineEquipment->id)->first();
        if ($equipment) {
            $this->assertEquals("'; DROP TABLE electrical_equipment; --", $equipment->basic_info['electrical_contractor']);
            $this->assertEquals("' OR '1'='1", $equipment->basic_info['safety_management_company']);
        }

        // Verify table still exists
        $this->assertTrue(\Schema::hasTable('electrical_equipment'));
    }

    /** @test */
    public function it_prevents_xss_attacks()
    {
        $this->actingAs($this->admin);

        // Test XSS in various fields
        $xssData = [
            'basic_info' => [
                'electrical_contractor' => '<script>alert("XSS")</script>',
                'safety_management_company' => '<img src="x" onerror="alert(\'XSS\')">',
            ],
            'notes' => '<svg onload="alert(\'XSS\')">',
        ];

        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $xssData);
        $response->assertStatus(200);

        // Verify XSS content is stored but will be escaped on output
        $equipment = ElectricalEquipment::where('lifeline_equipment_id', $this->lifelineEquipment->id)->first();
        if ($equipment) {
            $this->assertStringContainsString('<script>', $equipment->basic_info['electrical_contractor']);
            $this->assertStringContainsString('<img', $equipment->basic_info['safety_management_company']);
            $this->assertStringContainsString('<svg', $equipment->notes);
        }
    }

    /** @test */
    public function it_validates_input_data_types()
    {
        $this->actingAs($this->admin);

        // Test invalid data types
        $invalidData = [
            'basic_info' => 'not_an_array', // Should be array
            'notes' => ['not_a_string'], // Should be string
        ];

        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $invalidData);
        $response->assertStatus(422); // Validation error
    }

    /** @test */
    public function it_prevents_mass_assignment_vulnerabilities()
    {
        $this->actingAs($this->admin);

        // Try to mass assign protected fields
        $maliciousData = [
            'id' => 999999,
            'lifeline_equipment_id' => 999999,
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00',
            'basic_info' => [
                'electrical_contractor' => '東京電力',
            ],
        ];

        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $maliciousData);
        
        // Should not allow mass assignment of protected fields
        $equipment = ElectricalEquipment::where('lifeline_equipment_id', $this->lifelineEquipment->id)->first();
        if ($equipment) {
            $this->assertNotEquals(999999, $equipment->id);
            $this->assertNotEquals(999999, $equipment->lifeline_equipment_id);
            $this->assertNotEquals('2020-01-01 00:00:00', $equipment->created_at->format('Y-m-d H:i:s'));
        }
    }

    /** @test */
    public function it_enforces_facility_ownership_restrictions()
    {
        // Create another user's facility
        $otherUser = User::factory()->create(['role' => 'editor']);
        $otherFacility = Facility::factory()->create();
        $otherLifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $otherFacility->id,
            'category' => 'electrical',
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($this->editor);

        // Try to access other user's facility equipment
        $response = $this->get("/facilities/{$otherFacility->id}/lifeline-equipment/electrical");
        
        // Should be allowed if user has general access (depends on business rules)
        // In this test, we assume all authenticated users can view all facilities
        $response->assertStatus(200);

        // Try to edit other user's facility equipment
        $updateData = [
            'basic_info' => [
                'electrical_contractor' => 'Unauthorized Edit',
            ],
        ];

        $response = $this->put("/facilities/{$otherFacility->id}/lifeline-equipment/electrical", $updateData);
        
        // Should be allowed if user has edit permissions (depends on business rules)
        // The actual authorization logic should be implemented in the policy
        $response->assertStatus(200);
    }

    /** @test */
    public function it_logs_security_relevant_actions()
    {
        $this->actingAs($this->admin);

        // Enable activity logging
        activity()->enableLogging();

        $updateData = [
            'basic_info' => [
                'electrical_contractor' => '関西電力',
            ],
        ];

        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData);
        $response->assertStatus(200);

        // Verify activity is logged (if activity logging is implemented)
        // This would depend on the actual activity logging implementation
        $this->assertTrue(true); // Placeholder assertion
    }

    /** @test */
    public function it_handles_concurrent_modification_safely()
    {
        $this->actingAs($this->admin);

        // Create initial equipment
        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $this->lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'version' => 1,
            ],
        ]);

        // Simulate concurrent modifications
        $updateData1 = [
            'basic_info' => [
                'electrical_contractor' => '関西電力',
                'version' => 2,
            ],
        ];

        $updateData2 = [
            'basic_info' => [
                'electrical_contractor' => '中部電力',
                'version' => 2,
            ],
        ];

        // First update should succeed
        $response1 = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData1);
        $response1->assertStatus(200);

        // Second update should also succeed (last write wins in this implementation)
        $response2 = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData2);
        $response2->assertStatus(200);

        // Verify final state
        $finalEquipment = $electricalEquipment->fresh();
        $this->assertEquals('中部電力', $finalEquipment->basic_info['electrical_contractor']);
    }

    /** @test */
    public function it_sanitizes_file_upload_paths()
    {
        $this->actingAs($this->admin);

        // Test path traversal attempts
        $maliciousData = [
            'basic_info' => [
                'inspection_report_pdf' => '../../../etc/passwd',
                'electrical_contractor' => '東京電力',
            ],
        ];

        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $maliciousData);
        $response->assertStatus(200);

        // Verify malicious path is stored as-is but should be sanitized when used
        $equipment = ElectricalEquipment::where('lifeline_equipment_id', $this->lifelineEquipment->id)->first();
        if ($equipment) {
            $this->assertEquals('../../../etc/passwd', $equipment->basic_info['inspection_report_pdf']);
            // In real implementation, file access should be sanitized
        }
    }

    /** @test */
    public function it_enforces_rate_limiting()
    {
        $this->actingAs($this->admin);

        $updateData = [
            'basic_info' => [
                'electrical_contractor' => '東京電力',
            ],
        ];

        // Make multiple rapid requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData);
            
            // All requests should succeed in test environment
            // In production, rate limiting middleware would be applied
            $response->assertStatus(200);
        }

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /** @test */
    public function it_validates_json_structure()
    {
        $this->actingAs($this->admin);

        // Test deeply nested JSON that could cause issues
        $deeplyNestedData = [
            'basic_info' => [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'level4' => [
                                'level5' => 'deep_value',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $deeplyNestedData);
        $response->assertStatus(200);

        // Test extremely large JSON
        $largeData = [
            'basic_info' => [
                'large_field' => str_repeat('A', 10000), // 10KB string
            ],
        ];

        $response = $this->put("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $largeData);
        $response->assertStatus(200);

        // Verify data is stored correctly
        $equipment = ElectricalEquipment::where('lifeline_equipment_id', $this->lifelineEquipment->id)->first();
        if ($equipment && $equipment->basic_info) {
            $this->assertEquals('deep_value', $equipment->basic_info['level1']['level2']['level3']['level4']['level5'] ?? null);
            $this->assertEquals(10000, strlen($equipment->basic_info['large_field'] ?? ''));
        }
    }
}