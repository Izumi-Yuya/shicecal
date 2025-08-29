<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'description',
        'ip_address',
        'user_agent',
    ];

    /**
     * Disable updated_at timestamp (logs are immutable)
     */
    const UPDATED_AT = null;

    /**
     * Action types
     */
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_UPLOAD = 'upload';
    const ACTION_DOWNLOAD = 'download';
    const ACTION_EXPORT = 'export';

    /**
     * Target types
     */
    const TARGET_FACILITY = 'facility';
    const TARGET_USER = 'user';
    const TARGET_FILE = 'file';
    const TARGET_COMMENT = 'comment';
    const TARGET_MAINTENANCE = 'maintenance';
    const TARGET_SYSTEM_SETTING = 'system_setting';

    /**
     * Get the user who performed this action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for logs by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for logs by action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for logs by target type
     */
    public function scopeByTargetType($query, $targetType)
    {
        return $query->where('target_type', $targetType);
    }

    /**
     * Scope for logs by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent logs
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for logs by IP address
     */
    public function scopeByIpAddress($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Log user activity
     */
    public static function logActivity($userId, $action, $targetType = null, $targetId = null, $description = null, $ipAddress = null, $userAgent = null)
    {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'description' => $description,
            'ip_address' => $ipAddress ?: request()->ip(),
            'user_agent' => $userAgent ?: request()->userAgent(),
        ]);
    }

    /**
     * Log facility creation
     */
    public static function logFacilityCreated($userId, $facilityId, $ipAddress = null)
    {
        return static::logActivity(
            $userId,
            self::ACTION_CREATE,
            self::TARGET_FACILITY,
            $facilityId,
            '施設情報を作成しました',
            $ipAddress
        );
    }

    /**
     * Log facility update
     */
    public static function logFacilityUpdated($userId, $facilityId, $ipAddress = null)
    {
        return static::logActivity(
            $userId,
            self::ACTION_UPDATE,
            self::TARGET_FACILITY,
            $facilityId,
            '施設情報を更新しました',
            $ipAddress
        );
    }

    /**
     * Log facility approval
     */
    public static function logFacilityApproved($userId, $facilityId, $ipAddress = null)
    {
        return static::logActivity(
            $userId,
            self::ACTION_APPROVE,
            self::TARGET_FACILITY,
            $facilityId,
            '施設情報を承認しました',
            $ipAddress
        );
    }

    /**
     * Log file upload
     */
    public static function logFileUploaded($userId, $fileId, $fileName, $ipAddress = null)
    {
        return static::logActivity(
            $userId,
            self::ACTION_UPLOAD,
            self::TARGET_FILE,
            $fileId,
            "ファイル「{$fileName}」をアップロードしました",
            $ipAddress
        );
    }

    /**
     * Log file download
     */
    public static function logFileDownloaded($userId, $fileId, $fileName, $ipAddress = null)
    {
        return static::logActivity(
            $userId,
            self::ACTION_DOWNLOAD,
            self::TARGET_FILE,
            $fileId,
            "ファイル「{$fileName}」をダウンロードしました",
            $ipAddress
        );
    }

    /**
     * Log user login
     */
    public static function logUserLogin($userId, $ipAddress = null)
    {
        return static::logActivity(
            $userId,
            self::ACTION_LOGIN,
            null,
            null,
            'ログインしました',
            $ipAddress
        );
    }

    /**
     * Log user logout
     */
    public static function logUserLogout($userId, $ipAddress = null)
    {
        return static::logActivity(
            $userId,
            self::ACTION_LOGOUT,
            null,
            null,
            'ログアウトしました',
            $ipAddress
        );
    }

    /**
     * Get display name for action
     */
    public function getActionDisplayNameAttribute()
    {
        $actions = [
            self::ACTION_CREATE => '作成',
            self::ACTION_UPDATE => '更新',
            self::ACTION_DELETE => '削除',
            self::ACTION_APPROVE => '承認',
            self::ACTION_REJECT => '差戻し',
            self::ACTION_LOGIN => 'ログイン',
            self::ACTION_LOGOUT => 'ログアウト',
            self::ACTION_UPLOAD => 'アップロード',
            self::ACTION_DOWNLOAD => 'ダウンロード',
            self::ACTION_EXPORT => 'エクスポート',
        ];

        return $actions[$this->action] ?? $this->action;
    }

    /**
     * Get display name for target type
     */
    public function getTargetTypeDisplayNameAttribute()
    {
        $types = [
            self::TARGET_FACILITY => '施設',
            self::TARGET_USER => 'ユーザー',
            self::TARGET_FILE => 'ファイル',
            self::TARGET_COMMENT => 'コメント',
            self::TARGET_MAINTENANCE => '修繕履歴',
            self::TARGET_SYSTEM_SETTING => 'システム設定',
        ];

        return $types[$this->target_type] ?? $this->target_type;
    }

    /**
     * Get browser name from user agent
     */
    public function getBrowserNameAttribute()
    {
        $userAgent = $this->user_agent;
        
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        }
        
        return 'Unknown';
    }
}