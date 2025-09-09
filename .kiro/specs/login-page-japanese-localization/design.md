# Design Document

## Overview

This design addresses the localization of the login page to display Japanese text instead of English, ensuring the application properly serves its Japanese user base and passes existing browser tests. The solution will leverage Laravel's existing localization infrastructure and maintain all current functionality and styling.

## Architecture

### Current State Analysis
- Login page template: `resources/views/auth/login.blade.php`
- Japanese translations available in: `lang/ja/app.php`
- Current hardcoded English text: "LOGIN", "UserName", "Password"
- Test expectation: Looking for "ログイン" text on the page

### Localization Strategy
- Use Laravel's `__()` translation helper function
- Reference existing translation keys from `lang/ja/app.php`
- Maintain backward compatibility with existing CSS classes
- Preserve all current form functionality and validation

## Components and Interfaces

### Translation Keys Usage
The following existing translation keys from `lang/ja/app.php` will be used:
- `app.login` → "ログイン" (for login button)
- Custom keys to be added if needed for form labels

### Form Field Localization
1. **Login Button**: Change from "LOGIN" to use `__('app.login')`
2. **Email Field Label**: Change from "UserName" to "ユーザー名" or "メールアドレス"
3. **Password Field Label**: Change from "Password" to use appropriate Japanese translation
4. **Placeholder Text**: Update to Japanese equivalents

### Template Structure
The login blade template will be modified to:
- Replace hardcoded English strings with translation function calls
- Maintain all existing HTML structure and CSS classes
- Preserve form validation and error handling
- Keep all JavaScript functionality intact

## Data Models

No data model changes are required for this feature. The localization will only affect the presentation layer.

## Error Handling

### Translation Fallbacks
- If translation keys are missing, Laravel will fall back to the key name
- Existing error message localization in `lang/ja/auth.php` will remain unchanged
- Form validation errors will continue to use existing Japanese translations

### Browser Compatibility
- All existing browser functionality will be preserved
- JavaScript event handlers will continue to work with localized text
- CSS styling will remain consistent across language changes

## Testing Strategy

### Browser Tests
1. **BasicSetupTest Updates**: Verify that existing tests pass with Japanese text
   - `it_can_access_login_page`: Should find "ログイン" text
   - `it_can_login_and_access_facilities`: Should successfully click Japanese login button

2. **Localization Tests**: Ensure proper translation rendering
   - Verify all form labels display in Japanese
   - Confirm button text uses correct translation
   - Validate placeholder text is localized

### Unit Tests
- Test translation key resolution
- Verify fallback behavior for missing translations
- Confirm locale-specific rendering

### Integration Tests
- Test complete login flow with Japanese interface
- Verify form submission works with localized labels
- Confirm error messages display correctly in Japanese

## Implementation Approach

### Phase 1: Template Localization
1. Update login button text to use `__('app.login')`
2. Replace "UserName" label with Japanese equivalent
3. Replace "Password" label with Japanese translation
4. Update placeholder text to Japanese

### Phase 2: Translation Key Management
1. Add any missing translation keys to `lang/ja/app.php`
2. Ensure consistent translation patterns
3. Verify all text is properly localized

### Phase 3: Testing and Validation
1. Run existing browser tests to confirm they pass
2. Perform manual testing of login functionality
3. Validate visual consistency and styling
4. Test error scenarios with Japanese text

## Security Considerations

- No security implications as this is purely a presentation layer change
- All existing authentication logic remains unchanged
- Form validation and CSRF protection are preserved
- No sensitive data exposure through localization changes

## Performance Impact

- Minimal performance impact from translation function calls
- Laravel's translation caching will handle efficiency
- No additional database queries or external API calls
- Existing page load times will be maintained