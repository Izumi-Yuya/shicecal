<?php

namespace App\Constants;

/**
 * Constants for facility-related functionality
 */
class FacilityConstants
{
    // View Modes
    public const VIEW_MODE_CARD = 'card';

    public const VIEW_MODE_TABLE = 'table';

    public const VIEW_MODE_SESSION_KEY = 'facility_basic_info_view_mode';

    public const DEFAULT_VIEW_MODE = self::VIEW_MODE_CARD;

    // Status Values
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    // Comment Sections
    public const SECTION_BASIC_INFO = 'basic_info';

    public const SECTION_CONTACT_INFO = 'contact_info';

    public const SECTION_BUILDING_INFO = 'building_info';

    public const SECTION_FACILITY_INFO = 'facility_info';

    public const SECTION_SERVICE_INFO = 'service_info';

    // Display Constants
    public const EMPTY_VALUE_PLACEHOLDER = '未設定';

    public const DATE_FORMAT_JAPANESE = 'Y年m月d日';

    public const POSTAL_CODE_FORMAT = '/^(\d{3})(\d{4})$/';

    // Validation Limits
    public const MAX_COMMENT_LENGTH = 500;

    public const MIN_COMMENT_LENGTH = 1;

    public const MAX_COMPANY_NAME_LENGTH = 255;

    public const MAX_FACILITY_NAME_LENGTH = 255;

    public const MAX_OFFICE_CODE_LENGTH = 50;

    // Cache TTL (in seconds)
    public const CACHE_TTL_SHORT = 300;    // 5 minutes

    public const CACHE_TTL_MEDIUM = 1800;  // 30 minutes

    public const CACHE_TTL_LONG = 3600;    // 1 hour

    // File Upload
    public const MAX_FILE_SIZE_KB = 10240; // 10MB

    public const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png'];

    public const ALLOWED_DOCUMENT_TYPES = ['pdf'];

    // Pagination
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 100;

    /**
     * Get all available view modes
     */
    public static function getViewModes(): array
    {
        return [
            self::VIEW_MODE_CARD => 'カード形式',
            self::VIEW_MODE_TABLE => 'テーブル形式',
        ];
    }

    /**
     * Get all available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => '下書き',
            self::STATUS_PENDING => '承認待ち',
            self::STATUS_APPROVED => '承認済み',
            self::STATUS_REJECTED => '却下',
        ];
    }

    /**
     * Get all comment sections
     */
    public static function getCommentSections(): array
    {
        return [
            self::SECTION_BASIC_INFO => '基本情報',
            self::SECTION_CONTACT_INFO => '連絡先情報',
            self::SECTION_BUILDING_INFO => '建物情報',
            self::SECTION_FACILITY_INFO => '施設情報',
            self::SECTION_SERVICE_INFO => 'サービス情報',
        ];
    }

    /**
     * Check if view mode is valid
     */
    public static function isValidViewMode(string $mode): bool
    {
        return in_array($mode, [self::VIEW_MODE_CARD, self::VIEW_MODE_TABLE], true);
    }

    /**
     * Check if status is valid
     */
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ], true);
    }

    /**
     * Check if comment section is valid
     */
    public static function isValidCommentSection(string $section): bool
    {
        return array_key_exists($section, self::getCommentSections());
    }
}
