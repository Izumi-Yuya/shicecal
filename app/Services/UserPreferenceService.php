<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

/**
 * ユーザー設定管理サービス
 *
 * Manages user preferences and settings with session persistence.
 * Provides a centralized way to store and retrieve user preferences
 * for document management and other features.
 */
class UserPreferenceService
{
    /**
     * ドキュメント管理設定のキープレフィックス
     */
    private const DOCUMENT_SETTINGS_PREFIX = 'document_settings';

    /**
     * デフォルトのドキュメント設定
     */
    private const DEFAULT_DOCUMENT_SETTINGS = [
        'sort_by' => 'name',
        'sort_direction' => 'asc',
        'view_mode' => 'list',
        'filter_type' => null,
        'search' => null,
    ];

    /**
     * ドキュメント管理設定を取得
     *
     * @param int $facilityId
     * @return array
     */
    public function getDocumentSettings(int $facilityId): array
    {
        $sessionKey = self::DOCUMENT_SETTINGS_PREFIX . ".facility_{$facilityId}";
        
        return array_merge(
            self::DEFAULT_DOCUMENT_SETTINGS,
            Session::get($sessionKey, [])
        );
    }

    /**
     * ドキュメント管理設定を保存
     *
     * @param int $facilityId
     * @param array $settings
     * @return void
     */
    public function saveDocumentSettings(int $facilityId, array $settings): void
    {
        $sessionKey = self::DOCUMENT_SETTINGS_PREFIX . ".facility_{$facilityId}";
        
        // 既存設定とマージ
        $currentSettings = $this->getDocumentSettings($facilityId);
        $newSettings = array_merge($currentSettings, $settings);
        
        // 不要な値をフィルタリング
        $filteredSettings = array_filter($newSettings, function ($value) {
            return $value !== null && $value !== '';
        });
        
        Session::put($sessionKey, $filteredSettings);
    }

    /**
     * 特定のドキュメント設定値を取得
     *
     * @param int $facilityId
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getDocumentSetting(int $facilityId, string $key, $default = null)
    {
        $settings = $this->getDocumentSettings($facilityId);
        return $settings[$key] ?? $default;
    }

    /**
     * 特定のドキュメント設定値を保存
     *
     * @param int $facilityId
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function saveDocumentSetting(int $facilityId, string $key, $value): void
    {
        $this->saveDocumentSettings($facilityId, [$key => $value]);
    }

    /**
     * ドキュメント管理設定をリセット
     *
     * @param int $facilityId
     * @return void
     */
    public function resetDocumentSettings(int $facilityId): void
    {
        $sessionKey = self::DOCUMENT_SETTINGS_PREFIX . ".facility_{$facilityId}";
        Session::forget($sessionKey);
    }

    /**
     * すべてのドキュメント管理設定をクリア
     *
     * @return void
     */
    public function clearAllDocumentSettings(): void
    {
        $sessionKeys = array_keys(Session::all());
        
        foreach ($sessionKeys as $key) {
            if (str_starts_with($key, self::DOCUMENT_SETTINGS_PREFIX)) {
                Session::forget($key);
            }
        }
    }

    /**
     * リクエストパラメータからドキュメント設定を抽出
     *
     * @param array $requestParams
     * @return array
     */
    public function extractDocumentSettingsFromRequest(array $requestParams): array
    {
        $settings = [];
        
        $allowedKeys = array_keys(self::DEFAULT_DOCUMENT_SETTINGS);
        
        foreach ($allowedKeys as $key) {
            if (isset($requestParams[$key])) {
                $settings[$key] = $requestParams[$key];
            }
        }
        
        return $settings;
    }

    /**
     * ドキュメント設定の妥当性をチェック
     *
     * @param array $settings
     * @return array 検証済み設定
     */
    public function validateDocumentSettings(array $settings): array
    {
        $validatedSettings = [];
        
        // sort_by の検証
        if (isset($settings['sort_by'])) {
            $allowedSortBy = ['name', 'date', 'modified', 'size', 'type'];
            if (in_array($settings['sort_by'], $allowedSortBy)) {
                $validatedSettings['sort_by'] = $settings['sort_by'];
            }
        }
        
        // sort_direction の検証
        if (isset($settings['sort_direction'])) {
            $allowedDirections = ['asc', 'desc'];
            if (in_array($settings['sort_direction'], $allowedDirections)) {
                $validatedSettings['sort_direction'] = $settings['sort_direction'];
            }
        }
        
        // view_mode の検証
        if (isset($settings['view_mode'])) {
            $allowedViewModes = ['list', 'icon'];
            if (in_array($settings['view_mode'], $allowedViewModes)) {
                $validatedSettings['view_mode'] = $settings['view_mode'];
            }
        }
        
        // filter_type の検証（文字列または null）
        if (isset($settings['filter_type'])) {
            if (is_string($settings['filter_type']) || is_null($settings['filter_type'])) {
                $validatedSettings['filter_type'] = $settings['filter_type'];
            }
        }
        
        // search の検証（文字列または null）
        if (isset($settings['search'])) {
            if (is_string($settings['search']) || is_null($settings['search'])) {
                $validatedSettings['search'] = trim($settings['search']) ?: null;
            }
        }
        
        return $validatedSettings;
    }

    /**
     * 設定の統計情報を取得
     *
     * @return array
     */
    public function getSettingsStatistics(): array
    {
        $sessionKeys = array_keys(Session::all());
        $documentSettingsCount = 0;
        $facilities = [];
        
        foreach ($sessionKeys as $key) {
            if (str_starts_with($key, self::DOCUMENT_SETTINGS_PREFIX)) {
                $documentSettingsCount++;
                
                // 施設IDを抽出
                if (preg_match('/facility_(\d+)/', $key, $matches)) {
                    $facilities[] = (int)$matches[1];
                }
            }
        }
        
        return [
            'total_document_settings' => $documentSettingsCount,
            'facilities_with_settings' => array_unique($facilities),
            'facilities_count' => count(array_unique($facilities)),
        ];
    }
}