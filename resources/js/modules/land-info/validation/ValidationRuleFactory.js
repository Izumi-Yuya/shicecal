/**
 * Factory for creating validation rules from server configuration
 * Eliminates duplication between PHP and JavaScript validation
 */
export class ValidationRuleFactory {
  constructor(serverConfig) {
    this.config = serverConfig;
  }

  /**
   * Create JavaScript validation rules from server configuration
   * @param {string} ownershipType 
   * @returns {Object}
   */
  createRulesForOwnershipType(ownershipType) {
    const rules = { ...this.config.base_rules };

    if (this.config.conditional_rules[ownershipType]) {
      Object.assign(rules, this.config.conditional_rules[ownershipType]);
    }

    return this.transformServerRulesToClientRules(rules);
  }

  /**
   * Transform Laravel validation rules to client-side format
   * @param {Object} serverRules 
   * @returns {Object}
   */
  transformServerRulesToClientRules(serverRules) {
    const clientRules = {};

    Object.entries(serverRules).forEach(([field, rules]) => {
      clientRules[field] = this.parseServerRule(rules);
    });

    return clientRules;
  }

  /**
   * Parse individual server rule to client format
   * @param {Array} serverRule 
   * @returns {Object}
   */
  parseServerRule(serverRule) {
    const clientRule = {};

    serverRule.forEach(rule => {
      if (rule === 'required') {
        clientRule.required = true;
      } else if (rule === 'numeric') {
        clientRule.type = 'number';
      } else if (rule === 'email') {
        clientRule.type = 'email';
      } else if (rule.startsWith('min:')) {
        clientRule.min = parseFloat(rule.split(':')[1]);
      } else if (rule.startsWith('max:')) {
        clientRule.max = parseFloat(rule.split(':')[1]);
      } else if (rule.startsWith('required_without:')) {
        clientRule.required_without = rule.split(':')[1];
      }
    });

    return clientRule;
  }

  /**
   * Get validation configuration from server
   * @returns {Promise<Object>}
   */
  static async fetchServerConfig() {
    try {
      const response = await fetch('/api/land-info/validation-config');
      return await response.json();
    } catch (error) {
      console.error('Failed to fetch validation config:', error);
      return this.getFallbackConfig();
    }
  }

  /**
   * Fallback configuration if server is unavailable
   * @returns {Object}
   */
  static getFallbackConfig() {
    return {
      base_rules: {
        ownership_type: ['required'],
        parking_spaces: ['numeric', 'min:0', 'max:9999999999']
      },
      conditional_rules: {
        owned: {
          purchase_price: ['required', 'numeric', 'min:0', 'max:999999999999999']
        },
        leased: {
          monthly_rent: ['required', 'numeric', 'min:0', 'max:999999999999999']
        }
      }
    };
  }
}