/**
 * Constants for Land Info System
 * Centralized configuration and magic values
 */

export const OWNERSHIP_TYPES = Object.freeze({
  OWNED: 'owned',
  LEASED: 'leased',
  OWNED_RENTAL: 'owned_rental'
});

export const SECTION_IDS = Object.freeze({
  OWNED: 'owned_section',
  LEASED: 'leased_section',
  MANAGEMENT: 'management_section',
  OWNER: 'owner_section',
  FILE: 'file_section'
});

export const FIELD_GROUPS = Object.freeze({
  OWNED: ['purchase_price', 'unit_price_display'],
  LEASED: ['monthly_rent', 'contract_start_date', 'contract_end_date', 'auto_renewal', 'contract_period_display'],
  MANAGEMENT: [
    'management_company_name', 'management_company_postal_code',
    'management_company_address', 'management_company_building',
    'management_company_phone', 'management_company_fax',
    'management_company_email', 'management_company_url',
    'management_company_notes'
  ],
  OWNER: [
    'owner_name', 'owner_postal_code', 'owner_address',
    'owner_building', 'owner_phone', 'owner_fax',
    'owner_email', 'owner_url', 'owner_notes'
  ]
});

export const VALIDATION_RULES = Object.freeze({
  MAX_INPUT_LENGTH: 1000,
  MAX_CURRENCY_VALUE: 999999999999999,
  MAX_AREA_VALUE: 99999999.99,
  MAX_PARKING_VALUE: 9999999999,
  DEBOUNCE_DELAY: 300,
  ANIMATION_DURATION: 300
});

export const CSS_CLASSES = Object.freeze({
  HIDDEN: 'd-none',
  INVALID: 'is-invalid',
  CALCULATED: 'calculated',
  COLLAPSE: 'collapse',
  SHOW: 'show',
  FADE_IN: 'fade-in',
  HIGHLIGHT: 'show-highlight'
});

export const ARIA_ATTRIBUTES = Object.freeze({
  HIDDEN: 'aria-hidden',
  EXPANDED: 'aria-expanded',
  REQUIRED: 'aria-required'
});

export const ERROR_MESSAGES = Object.freeze({
  CALCULATION_FAILED: '計算処理でエラーが発生しました。入力値を確認してください。',
  VALIDATION_FAILED: '入力内容に問題があります。エラーメッセージを確認してください。',
  SECTION_DISPLAY_FAILED: 'セクションの表示処理でエラーが発生しました。',
  NETWORK_ERROR: 'ネットワークエラーが発生しました。接続を確認してください。',
  SYSTEM_ERROR: 'システムエラーが発生しました。管理者にお問い合わせください。'
});