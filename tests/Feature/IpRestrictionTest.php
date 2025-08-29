<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class IpRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_VIEWER,
            'department' => 'Test Department',
            'access_scope' => ['tokyo'],
            'is_active' => true,
        ]);
    }

    public function test_access_allowed_when_no_ip_restrictions_configured()
    {
        $this->actingAs($this->testUser);
        
        $response = $this->get('/home');
        $response->assertStatus(200);
    }

    public function test_access_allowed_for_whitelisted_ip()
    {
        // Set up IP restriction
        SystemSetting::create([
            'key' => 'allowed_ips',
            'value' => json_encode(['127.0.0.1', '192.168.1.100']),
            'description' => 'Allowed IP addresses',
            'updated_by' => $this->testUser->id,
        ]);

        $this->actingAs($this->testUser);
        
        // Simulate request from allowed IP
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1'
        ])->get('/home');
        
        $response->assertStatus(200);
    }

    public function test_access_denied_for_non_whitelisted_ip()
    {
        // Set up IP restriction
        SystemSetting::create([
            'key' => 'allowed_ips',
            'value' => json_encode(['192.168.1.100', '10.0.0.1']),
            'description' => 'Allowed IP addresses',
            'updated_by' => $this->testUser->id,
        ]);

        $this->actingAs($this->testUser);
        
        // Simulate request from non-allowed IP
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.1'
        ])->get('/home');
        
        $response->assertStatus(403);
    }

    public function test_cidr_notation_support()
    {
        // Set up IP restriction with CIDR notation
        SystemSetting::create([
            'key' => 'allowed_ips',
            'value' => json_encode(['192.168.1.0/24']),
            'description' => 'Allowed IP addresses',
            'updated_by' => $this->testUser->id,
        ]);

        $this->actingAs($this->testUser);
        
        // Test IP within CIDR range
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.50'
        ])->get('/home');
        
        $response->assertStatus(200);
        
        // Test IP outside CIDR range
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.2.50'
        ])->get('/home');
        
        $response->assertStatus(403);
    }

    public function test_wildcard_support()
    {
        // Set up IP restriction with wildcard
        SystemSetting::create([
            'key' => 'allowed_ips',
            'value' => json_encode(['192.168.1.*']),
            'description' => 'Allowed IP addresses',
            'updated_by' => $this->testUser->id,
        ]);

        $this->actingAs($this->testUser);
        
        // Test IP matching wildcard
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.123'
        ])->get('/home');
        
        $response->assertStatus(200);
        
        // Test IP not matching wildcard
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.2.123'
        ])->get('/home');
        
        $response->assertStatus(403);
    }

    public function test_multiple_ip_formats_in_same_setting()
    {
        // Set up IP restriction with mixed formats
        SystemSetting::create([
            'key' => 'allowed_ips',
            'value' => json_encode([
                '127.0.0.1',           // Exact IP
                '192.168.1.0/24',      // CIDR notation
                '10.0.0.*'             // Wildcard
            ]),
            'description' => 'Allowed IP addresses',
            'updated_by' => $this->testUser->id,
        ]);

        $this->actingAs($this->testUser);
        
        // Test exact IP
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1'
        ])->get('/home');
        $response->assertStatus(200);
        
        // Test CIDR range
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.50'
        ])->get('/home');
        $response->assertStatus(200);
        
        // Test wildcard
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '10.0.0.100'
        ])->get('/home');
        $response->assertStatus(200);
        
        // Test non-matching IP
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.1'
        ])->get('/home');
        $response->assertStatus(403);
    }
}