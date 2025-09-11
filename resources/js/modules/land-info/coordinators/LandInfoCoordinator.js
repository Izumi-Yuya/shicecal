/**
 * Main Coordinator for Land Info System
 * Orchestrates interactions between modules without handling implementation details
 */
export class LandInfoCoordinator {
  constructor(modules) {
    this.modules = modules;
    this.eventBus = modules.eventBus;
    this.setupModuleCommunication();
  }

  setupModuleCommunication() {
    // Ownership type changes
    this.eventBus.on('ownershipTypeChanged', (data) => {
      this.modules.sectionManager.updateVisibility(data.ownershipType);
      this.modules.validator.clearHiddenSectionErrors(data.ownershipType);
      this.modules.calculator.recalculateAll();
    });

    // Field changes
    this.eventBus.on('fieldChanged', (data) => {
      this.modules.validator.validateField(data.fieldId);
      if (data.triggerCalculation) {
        this.modules.calculator.scheduleCalculation(data.fieldId);
      }
    });

    // Form submission
    this.eventBus.on('formSubmitting', (data) => {
      const validation = this.modules.validator.validateAll();
      if (!validation.isValid) {
        data.preventDefault();
        this.modules.ui.showValidationErrors(validation.errors);
      }
    });
  }

  destroy() {
    this.eventBus.removeAllListeners();
    Object.values(this.modules).forEach(module => {
      if (module.destroy) module.destroy();
    });
  }
}