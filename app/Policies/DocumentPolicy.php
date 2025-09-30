<?php

namespace App\Policies;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any documents for a facility.
     * 
     * Requirements: 9.1 - ユーザーがドキュメントタブにアクセスするとき、
     * システムはユーザーが適切な施設アクセス権限を持っていることを確認する
     */
    public function viewAny(User $user, Facility $facility): bool
    {
        // Check if user can access the facility
        if (!$user->canAccessFacility($facility->id)) {
            return false;
        }

        // Role-based access control
        switch ($user->role) {
            case 'admin':
            case 'editor':
            case 'primary_responder':
            case 'approver':
                return true;

            case 'viewer':
                // Viewers can only see facilities within their access scope
                return $user->canAccessFacility($facility->id);

            default:
                return false;
        }
    }

    /**
     * Determine whether the user can view a specific document or folder.
     * 
     * Requirements: 9.6 - ドキュメントを表示するとき、システムは
     * ユーザーがアクセス権限を持っているファイルのみを表示する
     */
    public function view(User $user, $model): bool
    {
        if ($model instanceof DocumentFile) {
            return $user->canViewFacility($model->facility_id);
        } elseif ($model instanceof DocumentFolder) {
            return $user->canViewFacility($model->facility_id);
        }
        
        return false;
    }

    /**
     * Determine whether the user can view a specific folder.
     * 
     * Requirements: 9.6 - ドキュメントを表示するとき、システムは
     * ユーザーがアクセス権限を持っているファイルのみを表示する
     */
    public function viewFolder(User $user, DocumentFolder $documentFolder): bool
    {
        // Check if user can access the facility that owns this folder
        return $user->canViewFacility($documentFolder->facility_id);
    }

    /**
     * Determine whether the user can create documents or folders.
     * 
     * Requirements: 9.2 - ユーザーがファイルをアップロードしようとするとき、
     * システムは施設の編集権限をチェックする
     */
    public function create(User $user, $model): bool
    {
        if ($model instanceof Facility) {
            // Creating in a facility
            return $user->canEdit() && $user->canEditFacility($model->id);
        } elseif (is_string($model) && class_exists($model)) {
            // Creating a specific model type - need facility context
            return $user->canEdit();
        }
        
        return false;
    }

    /**
     * Determine whether the user can create folders in a facility.
     * 
     * Requirements: 9.2 - ユーザーがファイルをアップロードしようとするとき、
     * システムは施設の編集権限をチェックする
     */
    public function createFolder(User $user, Facility $facility): bool
    {
        // Only users with edit permissions can create folders
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canEditFacility($facility->id);
    }

    /**
     * Determine whether the user can update documents or folders.
     * 
     * Requirements: 9.2 - ユーザーがファイルをアップロードしようとするとき、
     * システムは施設の編集権限をチェックする
     */
    public function update(User $user, $model): bool
    {
        if ($model instanceof DocumentFile) {
            return $user->canEdit() && $user->canEditFacility($model->facility_id);
        } elseif ($model instanceof DocumentFolder) {
            return $user->canEdit() && $user->canEditFacility($model->facility_id);
        }
        
        return false;
    }

    /**
     * Determine whether the user can update/rename a folder.
     * 
     * Requirements: 9.2 - ユーザーがファイルをアップロードしようとするとき、
     * システムは施設の編集権限をチェックする
     */
    public function updateFolder(User $user, DocumentFolder $documentFolder): bool
    {
        // Only users with edit permissions can update folders
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canEditFacility($documentFolder->facility_id);
    }

    /**
     * Determine whether the user can delete documents or folders.
     * 
     * Requirements: 9.3 - ユーザーがファイルやフォルダを削除しようとするとき、
     * システムは適切な管理権限を要求する
     */
    public function delete(User $user, $model): bool
    {
        if ($model instanceof DocumentFile) {
            return $user->canEdit() && $user->canEditFacility($model->facility_id);
        } elseif ($model instanceof DocumentFolder) {
            return $this->deleteFolder($user, $model);
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete a folder.
     * 
     * Requirements: 9.3 - ユーザーがファイルやフォルダを削除しようとするとき、
     * システムは適切な管理権限を要求する
     */
    public function deleteFolder(User $user, DocumentFolder $documentFolder): bool
    {
        // Only admins and users with edit permissions can delete folders
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        if (!$user->canEditFacility($documentFolder->facility_id)) {
            return false;
        }

        // Additional check: only administrators can delete non-empty folders
        // (this will be enforced in the service layer as well)
        if ($documentFolder->hasChildren() || $documentFolder->getFileCount() > 0) {
            return $user->isAdmin();
        }

        return true;
    }

    /**
     * Determine whether the user can upload files to a facility.
     * 
     * Requirements: 9.2 - ユーザーがファイルをアップロードしようとするとき、
     * システムは施設の編集権限をチェックする
     */
    public function uploadFile(User $user, Facility $facility): bool
    {
        // Only users with edit permissions can upload files
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canEditFacility($facility->id);
    }

    /**
     * Determine whether the user can download a file.
     * 
     * Requirements: 8.4 - ファイルにアクセスするとき、システムは
     * ユーザー権限に基づく適切な認可を強制する
     */
    public function downloadFile(User $user, DocumentFile $documentFile): bool
    {
        // Check if user can access the facility that owns this file
        return $user->canViewFacility($documentFile->facility_id);
    }

    /**
     * Determine whether the user can delete a file.
     * 
     * Requirements: 9.3 - ユーザーがファイルやフォルダを削除しようとするとき、
     * システムは適切な管理権限を要求する
     */
    public function deleteFile(User $user, DocumentFile $documentFile): bool
    {
        // Only users with edit permissions can delete files
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canEditFacility($documentFile->facility_id);
    }

    /**
     * Determine whether the user can move files between folders.
     * 
     * Requirements: 9.2 - ユーザーがファイルをアップロードしようとするとき、
     * システムは施設の編集権限をチェックする
     */
    public function moveFile(User $user, DocumentFile $documentFile): bool
    {
        // Only users with edit permissions can move files
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canEditFacility($documentFile->facility_id);
    }

    /**
     * Determine whether the user can move folders.
     * 
     * Requirements: 9.2 - ユーザーがファイルをアップロードしようとするとき、
     * システムは施設の編集権限をチェックする
     */
    public function moveFolder(User $user, DocumentFolder $documentFolder): bool
    {
        // Only users with edit permissions can move folders
        if (!$user->canEdit()) {
            return false;
        }

        // Must have access to the facility
        return $user->canEditFacility($documentFolder->facility_id);
    }

    /**
     * Determine whether the user can view audit logs for documents.
     * 
     * Requirements: 9.4 - 不正アクセスが試行されるとき、システムは
     * 適切なエラーメッセージを表示し、試行をログに記録する
     */
    public function viewAuditLogs(User $user, Facility $facility): bool
    {
        // Only admins and approvers can view audit logs
        if (!in_array($user->role, ['admin', 'approver'])) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can manage document settings for a facility.
     * This includes setting storage quotas, file type restrictions, etc.
     */
    public function manageSettings(User $user, Facility $facility): bool
    {
        // Only admins can manage document settings
        if (!$user->isAdmin()) {
            return false;
        }

        // Must have access to the facility
        return $user->canAccessFacility($facility->id);
    }

    /**
     * Determine whether the user can export document lists.
     */
    public function export(User $user, Facility $facility): bool
    {
        // All users who can view documents can export (filtered by their access scope)
        return $this->viewAny($user, $facility);
    }

    /**
     * Determine whether the user can preview a file.
     * Same permissions as viewing a file.
     */
    public function previewFile(User $user, DocumentFile $documentFile): bool
    {
        return $this->view($user, $documentFile);
    }

    /**
     * Helper method to check if user has management permissions for a facility.
     * This is used for operations that require higher privileges.
     */
    protected function hasManagementPermissions(User $user, int $facilityId): bool
    {
        // Management permissions require edit access and facility access
        return $user->canEdit() && $user->canEditFacility($facilityId);
    }

    /**
     * Helper method to check if user has administrative permissions for a facility.
     * This is used for operations that require the highest privileges.
     */
    protected function hasAdministrativePermissions(User $user, int $facilityId): bool
    {
        // Administrative permissions require admin role and facility access
        return $user->isAdmin() && $user->canAccessFacility($facilityId);
    }
}