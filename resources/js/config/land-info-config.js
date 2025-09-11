/**
 * Land Information Form Configuration
 * Centralizes ownership type rules and field mappings
 */

export const OWNERSHIP_TYPES = {
    OWNED: 'owned',
    LEASED: 'leased',
    OWNED_RENTAL: 'owned_rental'
};

export const OWNERSHIP_TYPE_LABELS = {
    [OWNERSHIP_TYPES.OWNED]: '自社',
    [OWNERSHIP_TYPES.LEASED]: '賃借',
    [OWNERSHIP_TYPES.OWNED_RENTAL]: '自社（賃貸）'
};

// Section visibility rules based on ownership type
export const SECTION_VISIBILITY_RULES = {
    owned_section: [OWNERSHIP_TYPES.OWNED, OWNERSHIP_TYPES.OWNED_RENTAL],
    leased_section: [OWNERSHIP_TYPES.LEASED, OWNERSHIP_TYPES.OWNED_RENTAL],
    management_section: [OWNERSHIP_TYPES.LEASED],
    owner_section: [OWNERSHIP_TYPES.LEASED],
    file_section: [OWNERSHIP_TYPES.LEASED, OWNERSHIP_TYPES.OWNED_RENTAL]
};

// Field groups that should be cleared based on ownership type
export const FIELD_GROUPS = {
    owned: ['purchase_price', 'unit_price_display'],
    leased: [
        'monthly_rent', 'contract_start_date', 'contract_end_date',
        'auto_renewal', 'contract_period_display'
    ],
    management: [
        'management_company_name', 'management_company_postal_code',
        'management_company_address', 'management_company_building',
        'management_company_phone', 'management_company_fax',
        'management_company_email', 'management_company_url',
        'management_company_notes'
    ],
    owner: [
        'owner_name', 'owner_postal_code', 'owner_address',
        'owner_building', 'owner_phone', 'owner_fax',
        'owner_email', 'owner_url', 'owner_notes'
    ]
};

// Validation rules for different field types
export const VALIDATION_RULES = {
    currency: {
        max: 999999999999999,
        min: 0,
        pattern: /^\d{1,15}$/
    },
    area: {
        max: 99999999.99,
        min: 0,
        pattern: /^\d{1,8}(\.\d{1,2})?$/
    },
    parking: {
        max: 9999999999,
        min: 0,
        pattern: /^\d{1,10}$/
    },
    email: {
        pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    },
    url: {
        pattern: /^https?:\/\/.+/
    },
    phone: {
        pattern: /^\d{2,3}-\d{4}-\d{4}$/
    },
    postalCode: {
        pattern: /^\d{3}-\d{4}$/
    }
};

// Performance configuration
export const PERFORMANCE_CONFIG = {
    debounceDelay: 300,
    cacheMaxSize: 100,
    animationDelay: 50,
    autoSaveDelay: 5000,
    metricsLogInterval: 30000
};

// Error messages in Japanese
export const ERROR_MESSAGES = {
    ownershipTypeRequired: '所有形態を選択してください。',
    contractDateInvalid: '契約終了日は契約開始日より後の日付を入力してください。',
    emailInvalid: '正しいメールアドレス形式で入力してください。',
    urlInvalid: '正しいURL形式で入力してください。',
    fileSizeExceeded: 'ファイルサイズが大きすぎます。10MB以下のファイルを選択してください。',
    calculationWarning: {
        unitPrice: '坪単価が非常に高額です。入力内容をご確認ください。',
        contractPeriod: '契約期間が非常に長期です。入力内容をご確認ください。'
    }
};
