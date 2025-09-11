/**
 * Strategy Pattern for Field Validation
 */

export class ValidationStrategy {
  validate(value, rules) {
    throw new Error('validate method must be implemented');
  }
}

export class RequiredValidationStrategy extends ValidationStrategy {
  validate(value, rules) {
    if (rules.required && (!value || value.trim() === '')) {
      return ['このフィールドは必須です。'];
    }
    return [];
  }
}

export class NumericValidationStrategy extends ValidationStrategy {
  validate(value, rules) {
    const errors = [];

    if (!value) return errors;

    const numValue = parseFloat(value.replace(/[,\s]/g, ''));

    if (isNaN(numValue)) {
      errors.push('数値で入力してください。');
      return errors;
    }

    if (rules.min !== undefined && numValue < rules.min) {
      errors.push(`${rules.min}以上で入力してください。`);
    }

    if (rules.max !== undefined && numValue > rules.max) {
      errors.push(`${rules.max.toLocaleString()}以下で入力してください。`);
    }

    return errors;
  }
}

export class EmailValidationStrategy extends ValidationStrategy {
  validate(value, rules) {
    if (!value) return [];

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
      return ['正しいメールアドレス形式で入力してください。'];
    }

    return [];
  }
}

export class PatternValidationStrategy extends ValidationStrategy {
  validate(value, rules) {
    if (!value || !rules.pattern) return [];

    const regex = new RegExp(rules.pattern);
    if (!regex.test(value)) {
      return [rules.patternMessage || '正しい形式で入力してください。'];
    }

    return [];
  }
}

export class ValidationContext {
  constructor() {
    this.strategies = new Map();
    this.registerDefaultStrategies();
  }

  registerDefaultStrategies() {
    this.strategies.set('required', new RequiredValidationStrategy());
    this.strategies.set('numeric', new NumericValidationStrategy());
    this.strategies.set('email', new EmailValidationStrategy());
    this.strategies.set('pattern', new PatternValidationStrategy());
  }

  registerStrategy(type, strategy) {
    this.strategies.set(type, strategy);
  }

  validateField(value, rules) {
    const errors = [];

    for (const [type, strategy] of this.strategies) {
      if (rules[type] !== undefined) {
        const fieldErrors = strategy.validate(value, rules);
        errors.push(...fieldErrors);
      }
    }

    return errors;
  }
}