/**
 * State Management Store for Land Info System
 * Implements Redux-like pattern for predictable state management
 */
export class LandInfoStore {
  constructor(initialState = {}) {
    this.state = {
      ownershipType: '',
      sectionVisibility: {},
      formData: {},
      validationErrors: {},
      calculations: {},
      ui: {
        loading: false,
        activeSection: null,
        isDirty: false
      },
      ...initialState
    };

    this.listeners = new Set();
    this.middleware = [];
    this.actionHistory = [];
    this.maxHistorySize = 50;
  }

  /**
   * Get current state (immutable)
   * @returns {Object}
   */
  getState() {
    return Object.freeze(JSON.parse(JSON.stringify(this.state)));
  }

  /**
   * Subscribe to state changes
   * @param {Function} listener 
   * @returns {Function} Unsubscribe function
   */
  subscribe(listener) {
    this.listeners.add(listener);
    return () => this.listeners.delete(listener);
  }

  /**
   * Add middleware for action processing
   * @param {Function} middleware 
   */
  addMiddleware(middleware) {
    this.middleware.push(middleware);
  }

  /**
   * Dispatch action to update state
   * @param {Object} action 
   * @returns {Object} New state
   */
  dispatch(action) {
    // Validate action
    if (!action || typeof action.type !== 'string') {
      throw new Error('Action must have a type property');
    }

    // Apply middleware
    let processedAction = action;
    for (const middleware of this.middleware) {
      processedAction = middleware(processedAction, this.getState());
      if (!processedAction) break;
    }

    if (!processedAction) return this.getState();

    // Store previous state for history
    const previousState = this.getState();

    // Apply reducer
    const newState = this.reducer(this.state, processedAction);

    // Update state if changed
    if (newState !== this.state) {
      this.state = newState;

      // Add to history
      this.addToHistory({
        action: processedAction,
        previousState,
        newState: this.getState(),
        timestamp: Date.now()
      });

      // Notify listeners
      this.notifyListeners(processedAction, previousState, this.getState());
    }

    return this.getState();
  }

  /**
   * Root reducer for state updates
   * @param {Object} state 
   * @param {Object} action 
   * @returns {Object}
   */
  reducer(state, action) {
    switch (action.type) {
      case 'SET_OWNERSHIP_TYPE':
        return this.ownershipTypeReducer(state, action);

      case 'UPDATE_SECTION_VISIBILITY':
        return this.sectionVisibilityReducer(state, action);

      case 'UPDATE_FORM_DATA':
        return this.formDataReducer(state, action);

      case 'SET_VALIDATION_ERRORS':
        return this.validationReducer(state, action);

      case 'UPDATE_CALCULATIONS':
        return this.calculationsReducer(state, action);

      case 'SET_UI_STATE':
        return this.uiReducer(state, action);

      case 'RESET_STATE':
        return this.resetReducer(state, action);

      default:
        return state;
    }
  }

  /**
   * Ownership type reducer
   * @param {Object} state 
   * @param {Object} action 
   * @returns {Object}
   */
  ownershipTypeReducer(state, action) {
    return {
      ...state,
      ownershipType: action.payload.ownershipType,
      ui: {
        ...state.ui,
        isDirty: true
      }
    };
  }

  /**
   * Section visibility reducer
   * @param {Object} state 
   * @param {Object} action 
   * @returns {Object}
   */
  sectionVisibilityReducer(state, action) {
    return {
      ...state,
      sectionVisibility: {
        ...state.sectionVisibility,
        ...action.payload.visibility
      }
    };
  }

  /**
   * Form data reducer
   * @param {Object} state 
   * @param {Object} action 
   * @returns {Object}
   */
  formDataReducer(state, action) {
    const { field, value, batch } = action.payload;

    if (batch) {
      return {
        ...state,
        formData: {
          ...state.formData,
          ...batch
        },
        ui: {
          ...state.ui,
          isDirty: true
        }
      };
    }

    return {
      ...state,
      formData: {
        ...state.formData,
        [field]: value
      },
      ui: {
        ...state.ui,
        isDirty: true
      }
    };
  }

  /**
   * Validation reducer
   * @param {Object} state 
   * @param {Object} action 
   * @returns {Object}
   */
  validationReducer(state, action) {
    const { field, errors, clearAll } = action.payload;

    if (clearAll) {
      return {
        ...state,
        validationErrors: {}
      };
    }

    if (field) {
      const newErrors = { ...state.validationErrors };
      if (errors && errors.length > 0) {
        newErrors[field] = errors;
      } else {
        delete newErrors[field];
      }

      return {
        ...state,
        validationErrors: newErrors
      };
    }

    return {
      ...state,
      validationErrors: action.payload.errors || {}
    };
  }

  /**
   * Calculations reducer
   * @param {Object} state 
   * @param {Object} action 
   * @returns {Object}
   */
  calculationsReducer(state, action) {
    return {
      ...state,
      calculations: {
        ...state.calculations,
        ...action.payload.calculations
      }
    };
  }

  /**
   * UI state reducer
   * @param {Object} state 
   * @param {Object} action 
   * @returns {Object}
   */
  uiReducer(state, action) {
    return {
      ...state,
      ui: {
        ...state.ui,
        ...action.payload.ui
      }
    };
  }

  /**
   * Reset reducer
   * @param {Object} state 
   * @param {Object} action 
   * @returns {Object}
   */
  resetReducer(state, action) {
    const { preserveFields = [] } = action.payload || {};

    const preserved = {};
    preserveFields.forEach(field => {
      if (state.formData[field] !== undefined) {
        preserved[field] = state.formData[field];
      }
    });

    return {
      ownershipType: '',
      sectionVisibility: {},
      formData: preserved,
      validationErrors: {},
      calculations: {},
      ui: {
        loading: false,
        activeSection: null,
        isDirty: false
      }
    };
  }

  /**
   * Add action to history
   * @param {Object} historyEntry 
   */
  addToHistory(historyEntry) {
    this.actionHistory.push(historyEntry);

    // Limit history size
    if (this.actionHistory.length > this.maxHistorySize) {
      this.actionHistory.shift();
    }
  }

  /**
   * Notify all listeners of state change
   * @param {Object} action 
   * @param {Object} previousState 
   * @param {Object} newState 
   */
  notifyListeners(action, previousState, newState) {
    this.listeners.forEach(listener => {
      try {
        listener(newState, previousState, action);
      } catch (error) {
        console.error('Error in store listener:', error);
      }
    });
  }

  /**
   * Get action history
   * @param {number} limit 
   * @returns {Array}
   */
  getHistory(limit = 10) {
    return this.actionHistory.slice(-limit);
  }

  /**
   * Time travel debugging - replay actions
   * @param {number} actionIndex 
   */
  replayToAction(actionIndex) {
    if (actionIndex < 0 || actionIndex >= this.actionHistory.length) {
      throw new Error('Invalid action index');
    }

    // Reset to initial state
    this.state = {
      ownershipType: '',
      sectionVisibility: {},
      formData: {},
      validationErrors: {},
      calculations: {},
      ui: {
        loading: false,
        activeSection: null,
        isDirty: false
      }
    };

    // Replay actions up to the specified index
    for (let i = 0; i <= actionIndex; i++) {
      const historyEntry = this.actionHistory[i];
      this.state = this.reducer(this.state, historyEntry.action);
    }

    // Notify listeners
    this.notifyListeners(
      { type: 'REPLAY_TO_ACTION', payload: { actionIndex } },
      {},
      this.getState()
    );
  }

  /**
   * Create action creators for common operations
   */
  static createActionCreators() {
    return {
      setOwnershipType: (ownershipType) => ({
        type: 'SET_OWNERSHIP_TYPE',
        payload: { ownershipType }
      }),

      updateSectionVisibility: (visibility) => ({
        type: 'UPDATE_SECTION_VISIBILITY',
        payload: { visibility }
      }),

      updateFormField: (field, value) => ({
        type: 'UPDATE_FORM_DATA',
        payload: { field, value }
      }),

      updateFormBatch: (batch) => ({
        type: 'UPDATE_FORM_DATA',
        payload: { batch }
      }),

      setValidationErrors: (errors) => ({
        type: 'SET_VALIDATION_ERRORS',
        payload: { errors }
      }),

      setFieldValidationError: (field, errors) => ({
        type: 'SET_VALIDATION_ERRORS',
        payload: { field, errors }
      }),

      clearValidationErrors: () => ({
        type: 'SET_VALIDATION_ERRORS',
        payload: { clearAll: true }
      }),

      updateCalculations: (calculations) => ({
        type: 'UPDATE_CALCULATIONS',
        payload: { calculations }
      }),

      setUIState: (ui) => ({
        type: 'SET_UI_STATE',
        payload: { ui }
      }),

      resetState: (preserveFields = []) => ({
        type: 'RESET_STATE',
        payload: { preserveFields }
      })
    };
  }
}