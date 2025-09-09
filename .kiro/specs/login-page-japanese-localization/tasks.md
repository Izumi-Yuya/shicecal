# Implementation Plan

- [ ] 1. Add missing Japanese translation keys to language file
  - Add translation keys for "ユーザー名" (username/email) and any other missing login-related terms
  - Ensure consistent translation patterns with existing keys in `lang/ja/app.php`
  - _Requirements: 1.5, 3.2_

- [ ] 2. Update login page template with Japanese localization
  - Replace hardcoded "LOGIN" button text with `__('app.login')` translation call
  - Replace "UserName" label with Japanese translation using `__()` helper
  - Replace "Password" label with Japanese translation using `__()` helper
  - Update placeholder text for email and password fields to Japanese
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 3.1, 3.4_

- [ ] 3. Verify browser tests pass with localized content
  - Run `BasicSetupTest::it_can_access_login_page` to confirm it finds "ログイン" text
  - Run `BasicSetupTest::it_can_login_and_access_facilities` to confirm login button click works
  - Fix any test failures related to text changes while maintaining functionality
  - _Requirements: 2.1, 2.2, 2.3_

- [ ] 4. Test complete login functionality with Japanese interface
  - Manually test login page loads correctly with Japanese text
  - Verify form submission works with localized labels
  - Confirm error messages still display properly in Japanese
  - Validate CSS styling remains consistent with Japanese text
  - _Requirements: 1.1, 1.2, 1.3, 3.3, 3.4_