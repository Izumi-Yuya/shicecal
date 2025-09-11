<?php

namespace Tests\Support;

/**
 * Centralized test constants to avoid magic numbers and improve maintainability
 */
class TestConstants
{
    // View Mode Constants
    const VIEW_MODE_SESSION_KEY = 'facility_basic_info_view_mode';

    const CARD_VIEW_MODE = 'card';

    const TABLE_VIEW_MODE = 'table';

    const EMPTY_VALUE_PLACEHOLDER = '未設定';

    // Test Data Constants
    const TEST_COMPANY_NAME = 'テスト株式会社';

    const TEST_FACILITY_NAME = 'テスト施設';

    const TEST_OFFICE_CODE = 'TEST001';

    const TEST_EMAIL = 'test@example.com';

    const TEST_WEBSITE = 'https://example.com';

    // Browser Test Constants
    const DESKTOP_WIDTH = 1200;

    const DESKTOP_HEIGHT = 800;

    const TABLET_WIDTH = 768;

    const TABLET_HEIGHT = 1024;

    const MOBILE_WIDTH = 375;

    const MOBILE_HEIGHT = 667;

    // Performance Constants
    const MAX_RESPONSE_TIME_MS = 2000;

    const MAX_MEMORY_USAGE_MB = 128;

    // XPath Selectors
    const TABLE_CELL_SELECTOR = '//td[contains(@class, "detail-value") or not(@class)]';

    const CARD_VALUE_SELECTOR = '//span[contains(@class, "detail-value")]';

    const SERVICE_ELEMENT_SELECTOR = '//*[contains(@class, "service-card-title") or contains(@class, "svc-name")]';

    // HTTP Status Codes
    const HTTP_OK = 200;

    const HTTP_UNPROCESSABLE_ENTITY = 422;

    const HTTP_FORBIDDEN = 403;

    const HTTP_INTERNAL_SERVER_ERROR = 500;
}
