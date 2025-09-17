<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Log user activity.
     */
    public function log(
        string $action,
        string $targetType,
        ?int $targetId = null,
        string $description = '',
        ?Request $request = null
    ): ActivityLog {
        $request = $request ?: request();

        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? '',
            'created_at' => now(),
        ]);
    }

    /**
     * Log user login activity.
     */
    public function logLogin(int $userId, ?Request $request = null): ActivityLog
    {
        $request = $request ?: request();

        return ActivityLog::create([
            'user_id' => $userId,
            'action' => 'login',
            'target_type' => 'user',
            'target_id' => $userId,
            'description' => 'ユーザーがログインしました',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? '',
            'created_at' => now(),
        ]);
    }

    /**
     * Log user logout activity.
     */
    public function logLogout(int $userId, ?Request $request = null): ActivityLog
    {
        $request = $request ?: request();

        return ActivityLog::create([
            'user_id' => $userId,
            'action' => 'logout',
            'target_type' => 'user',
            'target_id' => $userId,
            'description' => 'ユーザーがログアウトしました',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? '',
            'created_at' => now(),
        ]);
    }

    /**
     * Log facility creation.
     */
    public function logFacilityCreated(int $facilityId, string $facilityName, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'create',
            'facility',
            $facilityId,
            "施設「{$facilityName}」を作成しました",
            $request
        );
    }

    /**
     * Log facility update.
     */
    public function logFacilityUpdated(int $facilityId, string $facilityName, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'update',
            'facility',
            $facilityId,
            "施設「{$facilityName}」を更新しました",
            $request
        );
    }

    /**
     * Log facility deletion.
     */
    public function logFacilityDeleted(int $facilityId, string $facilityName, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'delete',
            'facility',
            $facilityId,
            "施設「{$facilityName}」を削除しました",
            $request
        );
    }

    /**
     * Log facility approval.
     */
    public function logFacilityApproved(int $facilityId, string $facilityName, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'approve',
            'facility',
            $facilityId,
            "施設「{$facilityName}」を承認しました",
            $request
        );
    }

    /**
     * Log facility rejection.
     */
    public function logFacilityRejected(int $facilityId, string $facilityName, string $reason, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'reject',
            'facility',
            $facilityId,
            "施設「{$facilityName}」を差戻しました。理由: {$reason}",
            $request
        );
    }

    /**
     * Log file upload.
     */
    public function logFileUploaded(int $fileId, string $fileName, int $facilityId, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'upload',
            'file',
            $fileId,
            "ファイル「{$fileName}」をアップロードしました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log file download.
     */
    public function logFileDownloaded(int $fileId, string $fileName, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'download',
            'file',
            $fileId,
            "ファイル「{$fileName}」をダウンロードしました",
            $request
        );
    }

    /**
     * Log CSV export.
     */
    public function logCsvExported(array $facilityIds, array $fields, ?Request $request = null): ActivityLog
    {
        $facilityCount = count($facilityIds);
        $fieldCount = count($fields);

        return $this->log(
            'export_csv',
            'facility',
            null,
            "CSV出力を実行しました（施設数: {$facilityCount}、項目数: {$fieldCount}）",
            $request
        );
    }

    /**
     * Log PDF export.
     */
    public function logPdfExported(array $facilityIds, ?Request $request = null): ActivityLog
    {
        $facilityCount = count($facilityIds);

        return $this->log(
            'export_pdf',
            'facility',
            null,
            "PDF出力を実行しました（施設数: {$facilityCount}）",
            $request
        );
    }

    /**
     * Log comment creation.
     */
    public function logCommentCreated(int $commentId, int $facilityId, string $fieldName, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'create',
            'comment',
            $commentId,
            "施設ID {$facilityId} の「{$fieldName}」にコメントを投稿しました",
            $request
        );
    }

    /**
     * Log comment status update.
     */
    public function logCommentStatusUpdated(int $commentId, string $oldStatus, string $newStatus, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'update_status',
            'comment',
            $commentId,
            "コメントのステータスを「{$oldStatus}」から「{$newStatus}」に変更しました",
            $request
        );
    }

    /**
     * Log user creation.
     */
    public function logUserCreated(int $userId, string $email, string $role, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'create',
            'user',
            $userId,
            "ユーザー「{$email}」を作成しました（ロール: {$role}）",
            $request
        );
    }

    /**
     * Log user update.
     */
    public function logUserUpdated(int $userId, string $email, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'update',
            'user',
            $userId,
            "ユーザー「{$email}」を更新しました",
            $request
        );
    }

    /**
     * Log user deletion.
     */
    public function logUserDeleted(int $userId, string $email, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'delete',
            'user',
            $userId,
            "ユーザー「{$email}」を削除しました",
            $request
        );
    }

    /**
     * Log system settings update.
     */
    public function logSystemSettingUpdated(string $key, string $oldValue, string $newValue, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'update',
            'system_setting',
            null,
            "システム設定「{$key}」を「{$oldValue}」から「{$newValue}」に変更しました",
            $request
        );
    }

    /**
     * Log file deletion.
     */
    public function logFileDeleted(int $fileId, string $fileName, int $facilityId, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'delete',
            'file',
            $fileId,
            "ファイル「{$fileName}」を削除しました（施設ID: {$facilityId}）",
            $request
        );
    }
}
