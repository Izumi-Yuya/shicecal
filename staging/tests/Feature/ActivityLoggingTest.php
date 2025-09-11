<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Facility;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected $activityLogService;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityLogService = new ActivityLogService;
        $this->user = User::factory()->create(['role' => 'editor']);
        $this->actingAs($this->user);
    }

    public function test_can_log_user_login()
    {
        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Test Browser',
        ]);

        $log = $this->activityLogService->logLogin($this->user->id, $request);

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals('login', $log->action);
        $this->assertEquals('user', $log->target_type);
        $this->assertEquals($this->user->id, $log->target_id);
        $this->assertEquals('ユーザーがログインしました', $log->description);
        $this->assertEquals('192.168.1.1', $log->ip_address);
        $this->assertEquals('Test Browser', $log->user_agent);
    }

    public function test_can_log_user_logout()
    {
        $request = Request::create('/logout', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Test Browser',
        ]);

        $log = $this->activityLogService->logLogout($this->user->id, $request);

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals('logout', $log->action);
        $this->assertEquals('user', $log->target_type);
        $this->assertEquals($this->user->id, $log->target_id);
        $this->assertEquals('ユーザーがログアウトしました', $log->description);
    }

    public function test_can_log_facility_creation()
    {
        $facility = Facility::factory()->create(['facility_name' => 'テスト施設']);

        $log = $this->activityLogService->logFacilityCreated(
            $facility->id,
            $facility->facility_name
        );

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals('create', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertEquals($facility->id, $log->target_id);
        $this->assertStringContainsString('テスト施設', $log->description);
        $this->assertStringContainsString('作成しました', $log->description);
    }

    public function test_can_log_facility_update()
    {
        $facility = Facility::factory()->create(['facility_name' => 'テスト施設']);

        $log = $this->activityLogService->logFacilityUpdated(
            $facility->id,
            $facility->facility_name
        );

        $this->assertEquals('update', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertEquals($facility->id, $log->target_id);
        $this->assertStringContainsString('更新しました', $log->description);
    }

    public function test_can_log_facility_deletion()
    {
        $facility = Facility::factory()->create(['facility_name' => 'テスト施設']);

        $log = $this->activityLogService->logFacilityDeleted(
            $facility->id,
            $facility->facility_name
        );

        $this->assertEquals('delete', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertEquals($facility->id, $log->target_id);
        $this->assertStringContainsString('削除しました', $log->description);
    }

    public function test_can_log_csv_export()
    {
        $facilityIds = [1, 2, 3];
        $fields = ['facility_name', 'address', 'phone_number'];

        $log = $this->activityLogService->logCsvExported($facilityIds, $fields);

        $this->assertEquals('export_csv', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertNull($log->target_id);
        $this->assertStringContainsString('CSV出力を実行しました', $log->description);
        $this->assertStringContainsString('施設数: 3', $log->description);
        $this->assertStringContainsString('項目数: 3', $log->description);
    }

    public function test_can_log_pdf_export()
    {
        $facilityIds = [1, 2];

        $log = $this->activityLogService->logPdfExported($facilityIds);

        $this->assertEquals('export_pdf', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertNull($log->target_id);
        $this->assertStringContainsString('PDF出力を実行しました', $log->description);
        $this->assertStringContainsString('施設数: 2', $log->description);
    }

    public function test_can_log_comment_creation()
    {
        $comment = Comment::factory()->create();

        $log = $this->activityLogService->logCommentCreated(
            $comment->id,
            $comment->facility_id,
            'facility_name'
        );

        $this->assertEquals('create', $log->action);
        $this->assertEquals('comment', $log->target_type);
        $this->assertEquals($comment->id, $log->target_id);
        $this->assertStringContainsString('コメントを投稿しました', $log->description);
    }

    public function test_can_log_comment_status_update()
    {
        $comment = Comment::factory()->create();

        $log = $this->activityLogService->logCommentStatusUpdated(
            $comment->id,
            'pending',
            'resolved'
        );

        $this->assertEquals('update_status', $log->action);
        $this->assertEquals('comment', $log->target_type);
        $this->assertEquals($comment->id, $log->target_id);
        $this->assertStringContainsString('pending', $log->description);
        $this->assertStringContainsString('resolved', $log->description);
    }

    public function test_activity_log_model_relationships()
    {
        $log = ActivityLog::factory()->create(['user_id' => $this->user->id]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($this->user->id, $log->user->id);
    }

    public function test_activity_log_scopes()
    {
        ActivityLog::factory()->create([
            'action' => 'create',
            'target_type' => 'user',
            'user_id' => $this->user->id,
        ]);
        ActivityLog::factory()->create([
            'action' => 'update',
            'target_type' => 'user',
            'user_id' => $this->user->id,
        ]);
        ActivityLog::factory()->create([
            'action' => 'view',
            'target_type' => 'facility',
            'user_id' => $this->user->id,
        ]);

        $createLogs = ActivityLog::byAction('create')->get();
        $facilityLogs = ActivityLog::byTargetType('facility')->get();
        $userLogs = ActivityLog::byUser($this->user->id)->get();

        $this->assertCount(1, $createLogs);
        $this->assertCount(1, $facilityLogs);
        $this->assertCount(3, $userLogs);
    }

    public function test_activity_log_date_range_scope()
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        ActivityLog::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(5),
        ]);

        ActivityLog::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(10),
        ]);

        $logsInRange = ActivityLog::byDateRange($startDate, $endDate)->get();

        $this->assertCount(1, $logsInRange);
    }

    public function test_logs_are_created_with_correct_timestamps()
    {
        $beforeLog = now()->subSecond();

        $log = $this->activityLogService->log(
            'test_action',
            'test_target',
            1,
            'Test description'
        );

        $afterLog = now()->addSecond();

        $this->assertNotNull($log->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $log->created_at);
        $this->assertTrue($log->created_at->between($beforeLog, $afterLog));
    }

    public function test_logs_capture_request_information()
    {
        $request = Request::create('/test', 'POST', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_USER_AGENT' => 'Custom User Agent',
        ]);

        $log = $this->activityLogService->log(
            'test_action',
            'test_target',
            1,
            'Test description',
            $request
        );

        $this->assertEquals('10.0.0.1', $log->ip_address);
        $this->assertEquals('Custom User Agent', $log->user_agent);
    }
}
