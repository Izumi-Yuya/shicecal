/**
 * Centralized State Management with Observer Pattern
 */
import { EventEmitter } from '../EventEmitter.js';

export class StateManager extends EventEmitter {
  constructor() {
    super();
    this.state = {
      ownershipType: '',
      formData: {},
      validationErrors: {},
      calculations: {},
      ui: {
        loading: false,
        activeSection: null,
        isDirty: false
      }
    };

    this.history = [];
    this.maxHistorySize = 50;
  }

  /**
   * Update state and notify observers
   */
  setState(updates, source = 'unknown') {
    const previousState = { ...this.state };

    // Deep merge updates
    this.state = this.deepMerge(this.state, updates);

    // Add to history
    this.addToHistory(previousState, this.state, source);

    // Emit specific events for different state changes
    this.emitStateChanges(previousState, this.state);

    // Emit general state change event
    this.emit('stateChanged', {
      previous: previousState,
      current: this.state,
      source
    });
  }

  /**
   * Get current state (immutable)
   */
  getState() {
    return JSON.parse(JSON.stringify(this.state));
  }

  /**
   * Get specific state slice
   */
  getStateSlice(path) {
    return this.getNestedValue(this.state, path);
  }

  /**
   * Subscribe to specific state changes
   */
  subscribeToState(path, callback) {
    const handler = ({ current, previous }) => {
      const currentValue = this.getNestedValue(current, path);
      const previousValue = this.getNestedValue(previous, path);

      if (JSON.stringify(currentValue) !== JSON.stringify(previousValue)) {
        callback(currentValue, previousValue);
      }
    };

    return this.on('stateChanged', handler);
  }

  /**
   * Emit specific events for state changes
   */
  emitStateChanges(previous, current) {
    // Ownership type changes
    if (previous.ownershipType !== current.ownershipType) {
      this.emit('ownershipTypeChanged', {
        previous: previous.ownershipType,
        current: current.ownershipType
      });
    }

    // Form data changes
    if (JSON.stringify(previous.formData) !== JSON.stringify(current.formData)) {
      this.emit('formDataChanged', {
        previous: previous.formData,
        current: current.formData
      });
    }

    // Validation error changes
    if (JSON.stringify(previous.validationErrors) !== JSON.stringify(current.validationErrors)) {
      this.emit('validationErrorsChanged', {
        previous: previous.validationErrors,
        current: current.validationErrors
      });
    }

    // Calculation changes
    if (JSON.stringify(previous.calculations) !== JSON.stringify(current.calculations)) {
      this.emit('calculationsChanged', {
        previous: previous.calculations,
        current: current.calculations
      });
    }

    // UI state changes
    if (JSON.stringify(previous.ui) !== JSON.stringify(current.ui)) {
      this.emit('uiStateChanged', {
        previous: previous.ui,
        current: current.ui
      });
    }
  }

  /**
   * Deep merge objects
   */
  deepMerge(target, source) {
    const result = { ...target };

    for (const key in source) {
      if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
        result[key] = this.deepMerge(result[key] || {}, source[key]);
      } else {
        result[key] = source[key];
      }
    }

    return result;
  }

  /**
   * Get nested value from object using dot notation
   */
  getNestedValue(obj, path) {
    return path.split('.').reduce((current, key) => current?.[key], obj);
  }

  /**
   * Add state change to history
   */
  addToHistory(previous, current, source) {
    this.history.push({
      timestamp: Date.now(),
      previous,
      current,
      source
    });

    // Limit history size
    if (this.history.length > this.maxHistorySize) {
      this.history.shift();
    }
  }

  /**
   * Get state history
   */
  getHistory(limit = 10) {
    return this.history.slice(-limit);
  }

  /**
   * Reset state to initial values
   */
  resetState() {
    this.setState({
      ownershipType: '',
      formData: {},
      validationErrors: {},
      calculations: {},
      ui: {
        loading: false,
        activeSection: null,
        isDirty: false
      }
    }, 'reset');
  }

  /**
   * Create action creators for common state updates
   */
  createActions() {
    return {
      setOwnershipType: (ownershipType) => {
        this.setState({ ownershipType }, 'setOwnershipType');
      },

      updateFormField: (field, value) => {
        this.setState({
          formData: { ...this.state.formData, [field]: value },
          ui: { ...this.state.ui, isDirty: true }
        }, 'updateFormField');
      },

      setValidationErrors: (errors) => {
        this.setState({ validationErrors: errors }, 'setValidationErrors');
      },

      updateCalculations: (calculations) => {
        this.setState({
          calculations: { ...this.state.calculations, ...calculations }
        }, 'updateCalculations');
      },

      setLoading: (loading) => {
        this.setState({
          ui: { ...this.state.ui, loading }
        }, 'setLoading');
      },

      setActiveSection: (section) => {
        this.setState({
          ui: { ...this.state.ui, activeSection: section }
        }, 'setActiveSection');
      }
    };
  }
}