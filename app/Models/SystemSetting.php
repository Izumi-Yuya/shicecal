<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'description',
        'updated_by',
    ];

    /**
     * System setting keys
     */
    const KEY_APPROVAL_ENABLED = 'approval_enabled';
    const KEY_ALLOWED_IPS = 'allowed_ips';
    const KEY_MAX_FILE_SIZE = 'max_file_size';
    const KEY_NOTIFICATION_EMAIL = 'notification_email';

    /**
     * Get the user who last updated this setting
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for setting by key
     */
    public function scopeByKey($query, $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Get setting value by key
     */
    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key
     */
    public static function setValue($key, $value, $description = null, $updatedBy = null)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
                'updated_by' => $updatedBy,
            ]
        );
    }

    /**
     * Check if approval is enabled
     */
    public static function isApprovalEnabled()
    {
        return static::getValue(self::KEY_APPROVAL_ENABLED, false) === 'true';
    }

    /**
     * Get allowed IP addresses
     */
    public static function getAllowedIps()
    {
        $ips = static::getValue(self::KEY_ALLOWED_IPS, '');
        return $ips ? explode(',', $ips) : [];
    }

    /**
     * Get maximum file size in bytes
     */
    public static function getMaxFileSize()
    {
        return (int) static::getValue(self::KEY_MAX_FILE_SIZE, 10485760); // 10MB default
    }

    /**
     * Get notification email address
     */
    public static function getNotificationEmail()
    {
        return static::getValue(self::KEY_NOTIFICATION_EMAIL, '');
    }

    /**
     * Enable approval system
     */
    public static function enableApproval($updatedBy = null)
    {
        return static::setValue(self::KEY_APPROVAL_ENABLED, 'true', '承認機能の有効化', $updatedBy);
    }

    /**
     * Disable approval system
     */
    public static function disableApproval($updatedBy = null)
    {
        return static::setValue(self::KEY_APPROVAL_ENABLED, 'false', '承認機能の無効化', $updatedBy);
    }

    /**
     * Set allowed IP addresses
     */
    public static function setAllowedIps(array $ips, $updatedBy = null)
    {
        $ipString = implode(',', $ips);
        return static::setValue(self::KEY_ALLOWED_IPS, $ipString, '許可IPアドレス設定', $updatedBy);
    }

    /**
     * Set maximum file size
     */
    public static function setMaxFileSize($sizeInBytes, $updatedBy = null)
    {
        return static::setValue(self::KEY_MAX_FILE_SIZE, $sizeInBytes, 'ファイル最大サイズ設定', $updatedBy);
    }

    /**
     * Set notification email
     */
    public static function setNotificationEmail($email, $updatedBy = null)
    {
        return static::setValue(self::KEY_NOTIFICATION_EMAIL, $email, '通知メールアドレス設定', $updatedBy);
    }

    /**
     * Get human readable value
     */
    public function getHumanValueAttribute()
    {
        switch ($this->key) {
            case self::KEY_APPROVAL_ENABLED:
                return $this->value === 'true' ? '有効' : '無効';
            case self::KEY_MAX_FILE_SIZE:
                return $this->formatFileSize((int) $this->value);
            default:
                return $this->value;
        }
    }

    /**
     * Format file size for display
     */
    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}