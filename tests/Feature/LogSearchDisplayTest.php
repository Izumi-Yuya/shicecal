<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogSearchDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->regularUser = User::factory()->create(['role' => 'editor']);
    }

    public function test_admin_can_access_log_index()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.logs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.logs.index');
        $response->assertViewHas(['logs', 'users', 'actions', 'targetTypes', 'stats']);
    }

    public function test_non_admin_cannot_access_log_index()
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('admin.logs.index'));

        $response->assertStatus(403);
    }

    public function test_can_filter_logs_by_user()
    {
        $this->actingAs($this->adminUser);

        // Create logs for different users
        ActivityLog::factory()->create(['user_id' => $this->adminUser->id]);
        ActivityLog::factory()->create(['user_id' => $this->regularUser->id]);

        $response = $this->get(route('admin.logs.index', ['user_id' => $this->adminUser->id]));

        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        
        $this->assertEquals(1, $logs->count());
        $this->assertEquals($this->adminUser->id, $logs->first()->user_id);
    }

    public function test_can_filter_logs_by_action()
    {
        $this->actingAs($this->adminUser);

        // Create logs with different actions
        ActivityLog::factory()->create(['action' => 'create', 'user_id' => $this->adminUser->id]);
        ActivityLog::factory()->create(['action' => 'update', 'user_id' => $this->adminUser->id]);

        $response = $this->get(route('admin.logs.index', ['action' => 'create']));

        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        
        $this->assertEquals(1, $logs->count());
        $this->assertEquals('create', $logs->first()->action);
    }

    public function test_can_filter_logs_by_target_type()
    {
        $this->actingAs($this->adminUser);

        // Create logs with different target types
        ActivityLog::factory()->create(['target_type' => 'facility', 'user_id' => $this->adminUser->id]);
        ActivityLog::factory()->create(['target_type' => 'user', 'user_id' => $this->adminUser->id]);

        $response = $this->get(route('admin.logs.index', ['target_type' => 'facility']));

        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        
        $this->assertEquals(1, $logs->count());
        $this->assertEquals('facility', $logs->first()->target_type);
    }

    public function test_can_filter_logs_by_date_range()
    {
        $this->actingAs($this->adminUser);

        // Create logs with different dates
        ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(5)
        ]);
        ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(15)
        ]);

        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->get(route('admin.logs.index', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]));

        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        
        $this->assertEquals(1, $logs->count());
    }

    public function test_can_filter_logs_by_ip_address()
    {
        $this->actingAs($this->adminUser);

        // Create logs with different IP addresses
        ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1'
        ]);
        ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'ip_address' => '10.0.0.1'
        ]);

        $response = $this->get(route('admin.logs.index', ['ip_address' => '192.168']));

        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        
        $this->assertEquals(1, $logs->count());
        $this->assertStringContainsString('192.168', $logs->first()->ip_address);
    }

    public function test_can_view_log_details()
    {
        $this->actingAs($this->adminUser);

        $log = ActivityLog::factory()->create(['user_id' => $this->adminUser->id]);

        $response = $this->get(route('admin.logs.show', $log));

        $response->assertStatus(200);
        $response->assertViewIs('admin.logs.show');
        $response->assertViewHas('activityLog');
        $response->assertSee($log->description);
        $response->assertSee($log->ip_address);
    }

    public function test_non_admin_cannot_view_log_details()
    {
        $this->actingAs($this->regularUser);

        $log = ActivityLog::factory()->create(['user_id' => $this->adminUser->id]);

        $response = $this->get(route('admin.logs.show', $log));

        $response->assertStatus(403);
    }

    public function test_logs_are_paginated()
    {
        $this->actingAs($this->adminUser);

        // Create more logs than the pagination limit
        ActivityLog::factory()->count(60)->create(['user_id' => $this->adminUser->id]);

        $response = $this->get(route('admin.logs.index'));

        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        
        $this->assertEquals(50, $logs->perPage()); // Default pagination limit
        $this->assertTrue($logs->hasPages());
    }

    public function test_logs_are_ordered_by_most_recent_first()
    {
        $this->actingAs($this->adminUser);

        $oldLog = ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subHours(2)
        ]);
        
        $newLog = ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subHours(1)
        ]);

        $response = $this->get(route('admin.logs.index'));

        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        
        $this->assertEquals($newLog->id, $logs->first()->id);
        $this->assertEquals($oldLog->id, $logs->last()->id);
    }

    public function test_can_get_recent_logs_via_api()
    {
        $this->actingAs($this->adminUser);

        ActivityLog::factory()->count(15)->create(['user_id' => $this->adminUser->id]);

        $response = $this->get(route('admin.logs.recent', ['limit' => 5]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'logs' => [
                '*' => [
                    'id',
                    'action',
                    'target_type',
                    'description',
                    'created_at',
                    'user'
                ]
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertCount(5, $data['logs']);
    }

    public function test_non_admin_cannot_access_recent_logs_api()
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('admin.logs.recent'));

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Unauthorized']);
    }

    public function test_can_get_log_statistics_via_api()
    {
        $this->actingAs($this->adminUser);

        // Create various logs for statistics
        ActivityLog::factory()->create(['action' => 'create', 'user_id' => $this->adminUser->id]);
        ActivityLog::factory()->create(['action' => 'update', 'user_id' => $this->adminUser->id]);
        ActivityLog::factory()->create(['action' => 'create', 'user_id' => $this->regularUser->id]);

        $response = $this->get(route('admin.logs.statistics'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'statistics' => [
                'total_logs',
                'action_stats',
                'user_stats',
                'daily_stats'
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(3, $data['statistics']['total_logs']);
    }

    public function test_log_statistics_include_correct_data()
    {
        $this->actingAs($this->adminUser);

        // Create logs with known data
        ActivityLog::factory()->create([
            'action' => 'create',
            'user_id' => $this->adminUser->id,
            'created_at' => now()
        ]);
        
        ActivityLog::factory()->create([
            'action' => 'create',
            'user_id' => $this->adminUser->id,
            'created_at' => now()
        ]);

        $response = $this->get(route('admin.logs.statistics'));

        $data = $response->json();
        $stats = $data['statistics'];

        $this->assertEquals(2, $stats['total_logs']);
        $this->assertArrayHasKey('create', $stats['action_stats']);
        $this->assertEquals(2, $stats['action_stats']['create']);
    }

    public function test_search_filters_preserve_query_parameters()
    {
        $this->actingAs($this->adminUser);

        ActivityLog::factory()->count(60)->create(['user_id' => $this->adminUser->id, 'action' => 'create']);

        $queryParams = [
            'user_id' => $this->adminUser->id,
            'action' => 'create'
        ];

        $response = $this->get(route('admin.logs.index', $queryParams));

        $response->assertStatus(200);
        
        // Check that pagination links preserve the query parameters
        $logs = $response->viewData('logs');
        $paginationView = $logs->appends($queryParams)->links()->render();
        
        $this->assertStringContainsString('user_id=' . $this->adminUser->id, $paginationView);
        $this->assertStringContainsString('action=create', $paginationView);
    }
}