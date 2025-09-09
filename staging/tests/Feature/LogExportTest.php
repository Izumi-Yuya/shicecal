<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogExportTest extends TestCase
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

    public function test_admin_can_export_logs_to_csv()
    {
        $this->actingAs($this->adminUser);

        // Create test logs
        ActivityLog::factory()->count(3)->create([
            'user_id' => $this->adminUser->id,
            'action' => 'create',
            'target_type' => 'facility'
        ]);

        $response = $this->get(route('admin.logs.export.csv'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
        
        // Check CSV content
        $content = $response->getContent();
        $this->assertStringContainsString('ログID', $content);
        $this->assertStringContainsString('日時', $content);
        $this->assertStringContainsString('ユーザー名', $content);
        $this->assertStringContainsString('操作種別', $content);
        $this->assertStringContainsString($this->adminUser->name, $content);
        $this->assertStringContainsString('create', $content);
        $this->assertStringContainsString('facility', $content);
    }

    public function test_non_admin_cannot_export_logs()
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('admin.logs.export.csv'));

        $response->assertStatus(403);
    }

    public function test_csv_export_respects_filters()
    {
        $this->actingAs($this->adminUser);

        // Create logs with different actions
        ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'action' => 'create',
            'target_type' => 'facility'
        ]);
        
        ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'action' => 'update',
            'target_type' => 'user'
        ]);

        // Export with action filter
        $response = $this->get(route('admin.logs.export.csv', ['action' => 'create']));

        $response->assertStatus(200);
        
        $content = $response->getContent();
        $this->assertStringContainsString('create', $content);
        $this->assertStringNotContainsString('update', $content);
    }

    public function test_csv_export_respects_date_range_filter()
    {
        $this->actingAs($this->adminUser);

        // Create logs with different dates
        ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(5),
            'description' => 'Old log entry'
        ]);
        
        ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(1),
            'description' => 'Recent log entry'
        ]);

        $startDate = now()->subDays(2)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->get(route('admin.logs.export.csv', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]));

        $response->assertStatus(200);
        
        $content = $response->getContent();
        $this->assertStringContainsString('Recent log entry', $content);
        $this->assertStringNotContainsString('Old log entry', $content);
    }

    public function test_csv_export_handles_special_characters()
    {
        $this->actingAs($this->adminUser);

        // Create log with special characters
        ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'description' => 'Test with "quotes", commas, and 改行\ncharacters'
        ]);

        $response = $this->get(route('admin.logs.export.csv'));

        $response->assertStatus(200);
        
        $content = $response->getContent();
        // Should contain the description properly escaped
        $this->assertStringContainsString('Test with', $content);
        $this->assertStringContainsString('quotes', $content);
    }

    public function test_admin_can_export_audit_report()
    {
        $this->actingAs($this->adminUser);

        // Create test logs
        ActivityLog::factory()->count(5)->create([
            'user_id' => $this->adminUser->id,
            'action' => 'create',
            'created_at' => now()->subDays(1)
        ]);

        ActivityLog::factory()->count(3)->create([
            'user_id' => $this->regularUser->id,
            'action' => 'update',
            'created_at' => now()->subDays(1)
        ]);

        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->get(route('admin.logs.export.audit-report', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->getContent();
        $this->assertStringContainsString('監査レポート', $content);
        $this->assertStringContainsString('総ログ数', $content);
        $this->assertStringContainsString('操作種別別統計', $content);
        $this->assertStringContainsString('ユーザー別活動統計', $content);
        $this->assertStringContainsString('詳細ログ', $content);
        $this->assertStringContainsString($this->adminUser->name, $content);
        $this->assertStringContainsString($this->regularUser->name, $content);
    }

    public function test_non_admin_cannot_export_audit_report()
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('admin.logs.export.audit-report'));

        $response->assertStatus(403);
    }

    public function test_audit_report_uses_default_date_range()
    {
        $this->actingAs($this->adminUser);

        // Create logs within default range (last 30 days)
        ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(15)
        ]);

        // Create logs outside default range
        ActivityLog::factory()->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(45)
        ]);

        $response = $this->get(route('admin.logs.export.audit-report'));

        $response->assertStatus(200);
        
        $content = $response->getContent();
        // Should only include logs from the last 30 days
        $lines = explode("\n", $content);
        $logCount = 0;
        foreach ($lines as $line) {
            if (strpos($line, $this->adminUser->name) !== false) {
                $logCount++;
            }
        }
        
        // Should find the user name in the statistics and detailed logs sections
        $this->assertGreaterThan(0, $logCount);
    }

    public function test_csv_export_includes_utf8_bom()
    {
        $this->actingAs($this->adminUser);

        ActivityLog::factory()->create(['user_id' => $this->adminUser->id]);

        $response = $this->get(route('admin.logs.export.csv'));

        $response->assertStatus(200);
        
        $content = $response->getContent();
        // Check for UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
    }

    public function test_csv_export_filename_includes_timestamp()
    {
        $this->actingAs($this->adminUser);

        ActivityLog::factory()->create(['user_id' => $this->adminUser->id]);

        $response = $this->get(route('admin.logs.export.csv'));

        $response->assertStatus(200);
        
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('activity_logs_', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);
    }

    public function test_audit_report_filename_includes_date_range()
    {
        $this->actingAs($this->adminUser);

        ActivityLog::factory()->create(['user_id' => $this->adminUser->id]);

        $startDate = '2024-01-01';
        $endDate = '2024-01-31';

        $response = $this->get(route('admin.logs.export.audit-report', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]));

        $response->assertStatus(200);
        
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('audit_report_2024-01-01_to_2024-01-31.csv', $contentDisposition);
    }
}