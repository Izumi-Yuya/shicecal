<?php

namespace App\Helpers;

class ActivityLogHelper
{
    /**
     * Get the Bootstrap badge color for an action.
     *
     * @param string $action
     * @return string
     */
    public static function getActionBadgeColor(string $action): string
    {
        $colors = [
            'login' => 'success',
            'logout' => 'secondary',
            'create' => 'primary',
            'update' => 'info',
            'delete' => 'danger',
            'approve' => 'success',
            'reject' => 'warning',
            'export_csv' => 'info',
            'export_pdf' => 'info',
            'download' => 'secondary',
            'upload' => 'primary',
            'update_status' => 'warning',
            'view' => 'light',
            'access' => 'secondary',
        ];
        
        return $colors[$action] ?? 'secondary';
    }

    /**
     * Get the human-readable action name.
     *
     * @param string $action
     * @return string
     */
    public static function getActionName(string $action): string
    {
        $names = [
            'login' => 'ログイン',
            'logout' => 'ログアウト',
            'create' => '作成',
            'update' => '更新',
            'delete' => '削除',
            'view' => '閲覧',
            'download' => 'ダウンロード',
            'upload' => 'アップロード',
            'export_csv' => 'CSV出力',
            'export_pdf' => 'PDF出力',
            'approve' => '承認',
            'reject' => '差戻し',
            'update_status' => 'ステータス更新',
            'access' => 'アクセス',
        ];

        return $names[$action] ?? $action;
    }

    /**
     * Get the human-readable target type name.
     *
     * @param string $targetType
     * @return string
     */
    public static function getTargetTypeName(string $targetType): string
    {
        $names = [
            'user' => 'ユーザー',
            'facility' => '施設',
            'file' => 'ファイル',
            'comment' => 'コメント',
            'maintenance_history' => '修繕履歴',
            'annual_confirmation' => '年次確認',
            'notification' => '通知',
            'system_setting' => 'システム設定',
            'system' => 'システム',
        ];

        return $names[$targetType] ?? $targetType;
    }
}