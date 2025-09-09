<?php

namespace Tests\Unit\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ActivityLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityLogService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ActivityLogService();
        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    /**
     * Test basic log method.
     */
    public function test_log_creates_activity_log()
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $log = $this->service->log(
            'test_action',
            'test_target',
            123,
            'Test description',
            $request
        );

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals('test_action', $log->action);
        $this->assertEquals('test_target', $log->target_type);
        $this->assertEquals(123, $log->target_id);
        $this->assertEquals('Test description', $log->description);
        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->created_at);
    }

    /**
     * Test login logging.
     */
    public function test_log_login()
    {
        $request = Request::create('/login', 'POST');
        
        $log = $this->service->logLogin($this->user->id, $request);

        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals('login', $log->action);
        $this->assertEquals('user', $log->target_type);
        $this->assertEquals($this->user->id, $log->target_id);
        $this->assertEquals('ユーザーがログインしました', $log->description);
    }

    /**
     * Test logout logging.
     */
    public function test_log_logout()
    {
        $request = Request::create('/logout', 'POST');
        
        $log = $this->service->logLogout($this->user->id, $request);

        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals('logout', $log->action);
        $this->assertEquals('user', $log->target_type);
        $this->assertEquals($this->user->id, $log->target_id);
        $this->assertEquals('ユーザーがログアウトしました', $log->description);
    }

    /**
     * Test facility creation logging.
     */
    public function test_log_facility_created()
    {
        $facilityId = 123;
        $facilityName = 'Test Facility';
        
        $log = $this->service->logFacilityCreated($facilityId, $facilityName);

        $this->assertEquals('create', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertEquals($facilityId, $log->target_id);
        $this->assertEquals("施設「{$facilityName}」を作成しました", $log->description);
    }

    /**
     * Test facility update logging.
     */
    public function test_log_facility_updated()
    {
        $facilityId = 123;
        $facilityName = 'Test Facility';
        
        $log = $this->service->logFacilityUpdated($facilityId, $facilityName);

        $this->assertEquals('update', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertEquals($facilityId, $log->target_id);
        $this->assertEquals("施設「{$facilityName}」を更新しました", $log->description);
    }

    /**
     * Test facility deletion logging.
     */
    public function test_log_facility_deleted()
    {
        $facilityId = 123;
        $facilityName = 'Test Facility';
        
        $log = $this->service->logFacilityDeleted($facilityId, $facilityName);

        $this->assertEquals('delete', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertEquals($facilityId, $log->target_id);
        $this->assertEquals("施設「{$facilityName}」を削除しました", $log->description);
    }

    /**
     * Test facility approval logging.
     */
    public function test_log_facility_approved()
    {
        $facilityId = 123;
        $facilityName = 'Test Facility';
        
        $log = $this->service->logFacilityApproved($facilityId, $facilityName);

        $this->assertEquals('approve', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertEquals($facilityId, $log->target_id);
        $this->assertEquals("施設「{$facilityName}」を承認しました", $log->description);
    }

    /**
     * Test facility rejection logging.
     */
    public function test_log_facility_rejected()
    {
        $facilityId = 123;
        $facilityName = 'Test Facility';
        $reason = 'Incomplete information';
        
        $log = $this->service->logFacilityRejected($facilityId, $facilityName, $reason);

        $this->assertEquals('reject', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertEquals($facilityId, $log->target_id);
        $this->assertEquals("施設「{$facilityName}」を差戻しました。理由: {$reason}", $log->description);
    }

    /**
     * Test file upload logging.
     */
    public function test_log_file_uploaded()
    {
        $fileId = 456;
        $fileName = 'test.pdf';
        $facilityId = 123;
        
        $log = $this->service->logFileUploaded($fileId, $fileName, $facilityId);

        $this->assertEquals('upload', $log->action);
        $this->assertEquals('file', $log->target_type);
        $this->assertEquals($fileId, $log->target_id);
        $this->assertEquals("ファイル「{$fileName}」をアップロードしました（施設ID: {$facilityId}）", $log->description);
    }

    /**
     * Test file download logging.
     */
    public function test_log_file_downloaded()
    {
        $fileId = 456;
        $fileName = 'test.pdf';
        
        $log = $this->service->logFileDownloaded($fileId, $fileName);

        $this->assertEquals('download', $log->action);
        $this->assertEquals('file', $log->target_type);
        $this->assertEquals($fileId, $log->target_id);
        $this->assertEquals("ファイル「{$fileName}」をダウンロードしました", $log->description);
    }

    /**
     * Test CSV export logging.
     */
    public function test_log_csv_exported()
    {
        $facilityIds = [1, 2, 3];
        $fields = ['name', 'address', 'phone'];
        
        $log = $this->service->logCsvExported($facilityIds, $fields);

        $this->assertEquals('export_csv', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertNull($log->target_id);
        $this->assertEquals('CSV出力を実行しました（施設数: 3、項目数: 3）', $log->description);
    }

    /**
     * Test PDF export logging.
     */
    public function test_log_pdf_exported()
    {
        $facilityIds = [1, 2];
        
        $log = $this->service->logPdfExported($facilityIds);

        $this->assertEquals('export_pdf', $log->action);
        $this->assertEquals('facility', $log->target_type);
        $this->assertNull($log->target_id);
        $this->assertEquals('PDF出力を実行しました（施設数: 2）', $log->description);
    }

    /**
     * Test comment creation logging.
     */
    public function test_log_comment_created()
    {
        $commentId = 789;
        $facilityId = 123;
        $fieldName = 'facility_name';
        
        $log = $this->service->logCommentCreated($commentId, $facilityId, $fieldName);

        $this->assertEquals('create', $log->action);
        $this->assertEquals('comment', $log->target_type);
        $this->assertEquals($commentId, $log->target_id);
        $this->assertEquals("施設ID {$facilityId} の「{$fieldName}」にコメントを投稿しました", $log->description);
    }

    /**
     * Test comment status update logging.
     */
    public function test_log_comment_status_updated()
    {
        $commentId = 789;
        $oldStatus = 'pending';
        $newStatus = 'in_progress';
        
        $log = $this->service->logCommentStatusUpdated($commentId, $oldStatus, $newStatus);

        $this->assertEquals('update_status', $log->action);
        $this->assertEquals('comment', $log->target_type);
        $this->assertEquals($commentId, $log->target_id);
        $this->assertEquals("コメントのステータスを「{$oldStatus}」から「{$newStatus}」に変更しました", $log->description);
    }

    /**
     * Test user creation logging.
     */
    public function test_log_user_created()
    {
        $userId = 999;
        $email = 'test@example.com';
        $role = 'editor';
        
        $log = $this->service->logUserCreated($userId, $email, $role);

        $this->assertEquals('create', $log->action);
        $this->assertEquals('user', $log->target_type);
        $this->assertEquals($userId, $log->target_id);
        $this->assertEquals("ユーザー「{$email}」を作成しました（ロール: {$role}）", $log->description);
    }

    /**
     * Test system setting update logging.
     */
    public function test_log_system_setting_updated()
    {
        $key = 'approval_enabled';
        $oldValue = 'false';
        $newValue = 'true';
        
        $log = $this->service->logSystemSettingUpdated($key, $oldValue, $newValue);

        $this->assertEquals('update', $log->action);
        $this->assertEquals('system_setting', $log->target_type);
        $this->assertNull($log->target_id);
        $this->assertEquals("システム設定「{$key}」を「{$oldValue}」から「{$newValue}」に変更しました", $log->description);
    }
}