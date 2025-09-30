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

    // ========================================
    // Document Management Activity Logging
    // ========================================

    /**
     * Log document folder creation.
     */
    public function logDocumentFolderCreated(int $folderId, string $folderName, int $facilityId, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'create',
            'document_folder',
            $folderId,
            "ドキュメントフォルダ「{$folderName}」を作成しました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document folder rename.
     */
    public function logDocumentFolderRenamed(int $folderId, string $oldName, string $newName, int $facilityId, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'update',
            'document_folder',
            $folderId,
            "ドキュメントフォルダ「{$oldName}」を「{$newName}」に名前変更しました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document folder deletion.
     */
    public function logDocumentFolderDeleted(int $folderId, string $folderName, int $facilityId, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'delete',
            'document_folder',
            $folderId,
            "ドキュメントフォルダ「{$folderName}」を削除しました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document folder move.
     */
    public function logDocumentFolderMoved(int $folderId, string $folderName, ?string $oldParent, ?string $newParent, int $facilityId, ?Request $request = null): ActivityLog
    {
        $oldLocation = $oldParent ?: 'ルート';
        $newLocation = $newParent ?: 'ルート';
        
        return $this->log(
            'move',
            'document_folder',
            $folderId,
            "ドキュメントフォルダ「{$folderName}」を「{$oldLocation}」から「{$newLocation}」に移動しました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document file upload.
     */
    public function logDocumentFileUploaded(int $fileId, string $fileName, ?string $folderName, int $facilityId, int $fileSize = 0, ?Request $request = null): ActivityLog
    {
        $location = $folderName ? "フォルダ「{$folderName}」" : 'ルート';
        $sizeText = $fileSize > 0 ? "（サイズ: " . $this->formatFileSize($fileSize) . "）" : '';
        
        return $this->log(
            'upload',
            'document_file',
            $fileId,
            "ドキュメントファイル「{$fileName}」を{$location}にアップロードしました{$sizeText}（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document file download.
     */
    public function logDocumentFileDownloaded(int $fileId, string $fileName, int $facilityId, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'download',
            'document_file',
            $fileId,
            "ドキュメントファイル「{$fileName}」をダウンロードしました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document file preview.
     */
    public function logDocumentFilePreviewed(int $fileId, string $fileName, int $facilityId, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'preview',
            'document_file',
            $fileId,
            "ドキュメントファイル「{$fileName}」をプレビューしました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document file deletion.
     */
    public function logDocumentFileDeleted(int $fileId, string $fileName, int $facilityId, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'delete',
            'document_file',
            $fileId,
            "ドキュメントファイル「{$fileName}」を削除しました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document file rename.
     */
    public function logDocumentFileRenamed(int $fileId, string $oldName, string $newName, int $facilityId, ?Request $request = null): ActivityLog
    {
        return $this->log(
            'update',
            'document_file',
            $fileId,
            "ドキュメントファイル「{$oldName}」を「{$newName}」に名前変更しました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document file move.
     */
    public function logDocumentFileMoved(int $fileId, string $fileName, ?string $oldFolder, ?string $newFolder, int $facilityId, ?Request $request = null): ActivityLog
    {
        $oldLocation = $oldFolder ?: 'ルート';
        $newLocation = $newFolder ?: 'ルート';
        
        return $this->log(
            'move',
            'document_file',
            $fileId,
            "ドキュメントファイル「{$fileName}」を「{$oldLocation}」から「{$newLocation}」に移動しました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document bulk operations.
     */
    public function logDocumentBulkOperation(string $operation, array $itemIds, string $itemType, int $facilityId, array $details = [], ?Request $request = null): ActivityLog
    {
        $itemCount = count($itemIds);
        $itemTypeText = $itemType === 'folder' ? 'フォルダ' : 'ファイル';
        $operationText = match($operation) {
            'delete' => '削除',
            'move' => '移動',
            'copy' => 'コピー',
            'download' => 'ダウンロード',
            default => $operation
        };
        
        $description = "{$itemCount}個のドキュメント{$itemTypeText}を{$operationText}しました（施設ID: {$facilityId}）";
        
        if (!empty($details)) {
            $detailsText = [];
            foreach ($details as $key => $value) {
                $detailsText[] = "{$key}: {$value}";
            }
            $description .= "（" . implode(', ', $detailsText) . "）";
        }
        
        return $this->log(
            "bulk_{$operation}",
            "document_{$itemType}",
            null,
            $description,
            $request
        );
    }

    /**
     * Log document access permission changes.
     */
    public function logDocumentPermissionChanged(int $targetId, string $targetType, string $permission, string $action, int $facilityId, ?Request $request = null): ActivityLog
    {
        $targetTypeText = $targetType === 'folder' ? 'フォルダ' : 'ファイル';
        $actionText = match($action) {
            'grant' => '付与',
            'revoke' => '取り消し',
            'modify' => '変更',
            default => $action
        };
        
        return $this->log(
            'permission_change',
            "document_{$targetType}",
            $targetId,
            "ドキュメント{$targetTypeText}の「{$permission}」権限を{$actionText}しました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document search operations.
     */
    public function logDocumentSearch(string $query, array $filters, int $resultCount, int $facilityId, ?Request $request = null): ActivityLog
    {
        $filterText = '';
        if (!empty($filters)) {
            $filterParts = [];
            foreach ($filters as $key => $value) {
                if (!empty($value)) {
                    $filterParts[] = "{$key}: {$value}";
                }
            }
            if (!empty($filterParts)) {
                $filterText = "（フィルタ: " . implode(', ', $filterParts) . "）";
            }
        }
        
        return $this->log(
            'search',
            'document',
            null,
            "ドキュメント検索を実行しました。クエリ: 「{$query}」{$filterText}、結果: {$resultCount}件（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document export operations.
     */
    public function logDocumentExport(string $format, array $itemIds, string $itemType, int $facilityId, ?Request $request = null): ActivityLog
    {
        $itemCount = count($itemIds);
        $itemTypeText = $itemType === 'folder' ? 'フォルダ' : 'ファイル';
        $formatText = strtoupper($format);
        
        return $this->log(
            'export',
            'document',
            null,
            "{$itemCount}個のドキュメント{$itemTypeText}を{$formatText}形式でエクスポートしました（施設ID: {$facilityId}）",
            $request
        );
    }

    /**
     * Log document system maintenance operations.
     */
    public function logDocumentMaintenance(string $operation, array $details = [], ?Request $request = null): ActivityLog
    {
        $operationText = match($operation) {
            'cleanup' => 'クリーンアップ',
            'backup' => 'バックアップ',
            'restore' => '復元',
            'migration' => 'マイグレーション',
            'optimization' => '最適化',
            default => $operation
        };
        
        $description = "ドキュメントシステムの{$operationText}を実行しました";
        
        if (!empty($details)) {
            $detailsText = [];
            foreach ($details as $key => $value) {
                $detailsText[] = "{$key}: {$value}";
            }
            $description .= "（" . implode(', ', $detailsText) . "）";
        }
        
        return $this->log(
            "maintenance_{$operation}",
            'document_system',
            null,
            $description,
            $request
        );
    }

    /**
     * Log security-related document operations.
     */
    public function logDocumentSecurityEvent(string $event, int $targetId, string $targetType, array $details = [], ?Request $request = null): ActivityLog
    {
        $targetTypeText = $targetType === 'folder' ? 'フォルダ' : 'ファイル';
        $eventText = match($event) {
            'unauthorized_access' => '不正アクセス試行',
            'suspicious_download' => '疑わしいダウンロード',
            'bulk_operation' => '大量操作',
            'permission_escalation' => '権限昇格試行',
            default => $event
        };
        
        $description = "ドキュメント{$targetTypeText}で{$eventText}を検出しました";
        
        if (!empty($details)) {
            $detailsText = [];
            foreach ($details as $key => $value) {
                $detailsText[] = "{$key}: {$value}";
            }
            $description .= "（" . implode(', ', $detailsText) . "）";
        }
        
        return $this->log(
            "security_{$event}",
            "document_{$targetType}",
            $targetId,
            $description,
            $request
        );
    }

    /**
     * Format file size for logging.
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
