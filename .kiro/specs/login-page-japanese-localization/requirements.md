# Requirements Document

## Introduction

The login page currently displays English text ("LOGIN", "UserName", "Password") instead of Japanese text, causing the browser test to fail when looking for "ログイン" (Login) text. This feature will ensure the login page is properly localized to Japanese to match the application's target language and fix the failing BasicSetupTest.

## Requirements

### Requirement 1

**User Story:** As a Japanese user, I want the login page to display in Japanese, so that I can understand the interface in my native language.

#### Acceptance Criteria

1. WHEN a user visits the login page THEN the system SHALL display "ログイン" instead of "LOGIN" on the login button
2. WHEN a user views the login form THEN the system SHALL display "ユーザー名" instead of "UserName" for the email field label
3. WHEN a user views the login form THEN the system SHALL display "パスワード" instead of "Password" for the password field label
4. WHEN a user views the login form THEN the system SHALL use Japanese placeholder text for input fields
5. WHEN the login page loads THEN the system SHALL use the existing Japanese language translations from `lang/ja/app.php`

### Requirement 2

**User Story:** As a developer, I want the login page to pass the existing browser tests, so that the test suite validates the correct Japanese localization.

#### Acceptance Criteria

1. WHEN the BasicSetupTest runs THEN the system SHALL pass the `it_can_access_login_page` test that looks for "ログイン" text
2. WHEN the BasicSetupTest runs THEN the system SHALL pass the `it_can_login_and_access_facilities` test that clicks the "ログイン" button
3. WHEN browser tests run THEN the system SHALL maintain all existing functionality while displaying Japanese text

### Requirement 3

**User Story:** As a system administrator, I want the login page to maintain consistent localization patterns, so that the application follows Laravel best practices for internationalization.

#### Acceptance Criteria

1. WHEN displaying text on the login page THEN the system SHALL use Laravel's `__()` helper function for translations
2. WHEN referencing translation keys THEN the system SHALL use the existing keys from `lang/ja/app.php`
3. WHEN the application locale is set to Japanese THEN the system SHALL display all login page text in Japanese
4. WHEN maintaining the login form THEN the system SHALL preserve all existing CSS classes and styling