# Accessibility Implementation Guide

## Overview

This document describes the comprehensive accessibility implementation for the Facility Form Layout system. The implementation follows WCAG 2.1 AA guidelines and provides full keyboard navigation, screen reader support, and inclusive design patterns.

## Implemented Features

### 1. ARIA Attributes and Semantic HTML

#### Landmarks and Regions
- **Main content**: `<main role="main">` with proper `id="main-content"`
- **Header**: `<header role="banner">` for page header
- **Navigation**: `<nav role="navigation">` for breadcrumbs
- **Sections**: `<section role="region">` for form sections
- **Complementary**: `<aside role="complementary">` for facility info card

#### Form Accessibility
- **Form labeling**: `aria-labelledby` connects form to page title
- **Required fields**: `aria-required="true"` and visual indicators
- **Field descriptions**: `aria-describedby` for help text and validation rules
- **Error associations**: `aria-invalid="true"` and proper error message linking
- **Fieldset grouping**: Related form elements grouped with `<fieldset>` and `<legend>`

#### Interactive Elements
- **Collapsible sections**: 
  - `role="button"` for clickable headers
  - `aria-expanded` to indicate state
  - `aria-controls` to reference controlled content
  - `aria-hidden` for collapsed content
- **Live regions**: Multiple live regions for different announcement types
- **Progress indicators**: `role="progressbar"` with proper value attributes

### 2. Keyboard Navigation

#### Focus Management
- **Skip links**: Jump to main content and form sections
- **Focus trapping**: Proper focus flow within expanded sections
- **Error focus**: Automatic focus to first error field on validation failure
- **Tab order**: Logical tab sequence throughout the form

#### Keyboard Shortcuts
- **Ctrl/Cmd + S**: Save form (prevents browser default)
- **Enter/Space**: Toggle collapsible sections
- **Escape**: Collapse all expanded sections
- **Arrow keys**: Navigate within grouped elements (roving tabindex)

#### Focus Indicators
- **Enhanced visibility**: 3px solid outline with proper contrast
- **Focus-visible**: Modern focus indicators that only show for keyboard users
- **Custom styling**: Consistent focus styling across all interactive elements

### 3. Screen Reader Support

#### Live Regions
- **Polite announcements**: Form status and progress updates
- **Assertive announcements**: Critical errors and urgent messages
- **Calculation results**: Real-time calculation announcements
- **Progress updates**: Form completion milestones

#### Content Structure
- **Headings hierarchy**: Proper heading levels for screen reader navigation
- **Lists**: Proper list markup for breadcrumbs and error messages
- **Descriptions**: Comprehensive field descriptions and help text
- **Labels**: All form elements have proper labels or aria-label

#### Screen Reader Only Content
- **Skip navigation**: Hidden navigation for keyboard users
- **Field descriptions**: Additional context for complex fields
- **Status information**: Current state of collapsible sections
- **Instructions**: Keyboard shortcuts and interaction hints

### 4. Color and Contrast

#### WCAG AA Compliance
- **Text contrast**: Minimum 4.5:1 ratio for normal text
- **Large text**: Minimum 3:1 ratio for large text (18pt+)
- **Interactive elements**: Proper contrast for buttons and links
- **Error states**: High contrast error indicators

#### Color Independence
- **Error indication**: Icons and text, not just color
- **Status indicators**: Multiple visual cues beyond color
- **Focus indicators**: High contrast outlines
- **Required fields**: Asterisk symbols in addition to color

### 5. Mobile Accessibility

#### Touch Targets
- **Minimum size**: 44px minimum touch target size
- **Spacing**: Adequate spacing between interactive elements
- **Touch-friendly**: Optimized button and form control sizes

#### Mobile-Specific Features
- **Input types**: Appropriate input types for mobile keyboards
- **Input modes**: `inputmode` attributes for better keyboard selection
- **Autocomplete**: Proper autocomplete attributes for form filling
- **Viewport**: Prevents zoom on input focus (16px font size)

### 6. High Contrast and Forced Colors Support

#### High Contrast Mode
- **Enhanced borders**: Thicker borders and outlines
- **System colors**: Uses system high contrast colors
- **Forced colors**: Supports Windows High Contrast mode
- **Custom properties**: CSS custom properties for easy theming

#### Reduced Motion Support
- **Animation control**: Respects `prefers-reduced-motion`
- **Transition removal**: Disables animations when requested
- **Static alternatives**: Provides static alternatives to animated content

## Implementation Details

### CSS Classes and Utilities

```css
/* Screen reader only content */
.sr-only {
  position: absolute !important;
  width: 1px !important;
  height: 1px !important;
  padding: 0 !important;
  margin: -1px !important;
  overflow: hidden !important;
  clip: rect(0, 0, 0, 0) !important;
  white-space: nowrap !important;
  border: 0 !important;
}

/* Focusable screen reader content */
.sr-only-focusable:focus,
.sr-only-focusable:active {
  position: static !important;
  width: auto !important;
  height: auto !important;
  /* ... additional styles for visibility */
}

/* Skip links */
.skip-link {
  position: absolute;
  top: -40px;
  left: 6px;
  /* ... positioning and styling */
}

.skip-link:focus {
  top: 0;
  /* ... visible state */
}
```

### JavaScript API

```javascript
// Initialize with accessibility features
const facilityForm = new FacilityFormLayout({
  enableAccessibility: true,
  enableRealTimeValidation: true,
  enableMobileOptimization: true
});

// Announce to screen readers
facilityForm.announceToScreenReader('Message', 'polite|urgent');

// Focus management
facilityForm.focusFirstError();

// Progress tracking
facilityForm.updateFormProgress();
```

### Component Usage

```blade
{{-- Form section with accessibility --}}
<x-form.section 
  title="基本情報" 
  icon="fas fa-info-circle"
  collapsible="true"
  error-fields="['facility_name', 'email']">
  
  {{-- Form fields with proper labels --}}
  <div class="mb-3">
    <label for="facility_name" class="form-label required">施設名</label>
    <input type="text" 
           id="facility_name" 
           name="facility_name" 
           class="form-control" 
           required 
           aria-required="true"
           aria-describedby="facility_name_help">
    <div id="facility_name_help" class="form-text">
      施設の正式名称を入力してください
    </div>
    <div class="invalid-feedback" id="facility_name_error"></div>
  </div>
  
</x-form.section>
```

## Testing Accessibility

### Automated Testing
- **axe-core**: Automated accessibility testing
- **WAVE**: Web accessibility evaluation
- **Lighthouse**: Accessibility audit scores

### Manual Testing
- **Keyboard navigation**: Tab through all interactive elements
- **Screen reader**: Test with NVDA, JAWS, or VoiceOver
- **High contrast**: Test in Windows High Contrast mode
- **Mobile**: Test touch targets and mobile screen readers

### Test Checklist

#### Keyboard Navigation
- [ ] All interactive elements are keyboard accessible
- [ ] Tab order is logical and intuitive
- [ ] Focus indicators are visible and high contrast
- [ ] Skip links work correctly
- [ ] Keyboard shortcuts function properly

#### Screen Reader Support
- [ ] All content is announced correctly
- [ ] Form fields have proper labels and descriptions
- [ ] Error messages are associated with fields
- [ ] Live regions announce dynamic content
- [ ] Headings provide proper document structure

#### Visual Design
- [ ] Color contrast meets WCAG AA standards
- [ ] Information is not conveyed by color alone
- [ ] Focus indicators are clearly visible
- [ ] Text is readable at 200% zoom
- [ ] Touch targets are at least 44px

#### Mobile Accessibility
- [ ] Touch targets are appropriately sized
- [ ] Content reflows properly on small screens
- [ ] Mobile screen readers work correctly
- [ ] Input types trigger appropriate keyboards
- [ ] Zoom doesn't break functionality

## Browser Support

### Desktop Browsers
- **Chrome**: Full support for all features
- **Firefox**: Full support for all features
- **Safari**: Full support for all features
- **Edge**: Full support for all features

### Mobile Browsers
- **iOS Safari**: Full support with VoiceOver
- **Chrome Mobile**: Full support with TalkBack
- **Samsung Internet**: Full support with TalkBack

### Screen Readers
- **NVDA**: Full compatibility (Windows)
- **JAWS**: Full compatibility (Windows)
- **VoiceOver**: Full compatibility (macOS/iOS)
- **TalkBack**: Full compatibility (Android)

## Maintenance and Updates

### Regular Testing
- Test with each major browser update
- Verify screen reader compatibility
- Check keyboard navigation after UI changes
- Validate color contrast with design updates

### Code Reviews
- Include accessibility checks in code reviews
- Verify ARIA attributes are correct
- Ensure semantic HTML is maintained
- Check for keyboard navigation regressions

### User Feedback
- Collect feedback from users with disabilities
- Monitor accessibility support requests
- Update implementation based on user needs
- Document accessibility improvements

## Resources and References

### Guidelines and Standards
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [WebAIM Resources](https://webaim.org/)

### Testing Tools
- [axe DevTools](https://www.deque.com/axe/devtools/)
- [WAVE Web Accessibility Evaluator](https://wave.webaim.org/)
- [Lighthouse Accessibility Audit](https://developers.google.com/web/tools/lighthouse)

### Screen Readers
- [NVDA Screen Reader](https://www.nvaccess.org/)
- [JAWS Screen Reader](https://www.freedomscientific.com/products/software/jaws/)
- [VoiceOver User Guide](https://support.apple.com/guide/voiceover/)

This comprehensive accessibility implementation ensures that the Facility Form Layout system is usable by all users, regardless of their abilities or the assistive technologies they use.