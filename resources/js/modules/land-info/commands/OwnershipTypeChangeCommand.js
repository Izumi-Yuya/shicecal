/**
 * Command Pattern for Ownership Type Changes
 * Encapsulates ownership type change logic as executable commands
 */
export class OwnershipTypeChangeCommand {
  constructor(sectionManager, validator, calculator) {
    this.sectionManager = sectionManager;
    this.validator = validator;
    this.calculator = calculator;
    this.previousState = null;
  }

  /**
   * Execute ownership type change
   * @param {string} newOwnershipType 
   */
  execute(newOwnershipType) {
    // Store previous state for undo capability
    this.previousState = {
      ownershipType: this.sectionManager.getOwnershipTypeValue(),
      visibleSections: this.sectionManager.getVisibleSections()
    };

    // Execute the change
    this.sectionManager.updateSectionVisibility(newOwnershipType);
    this.validator.clearValidationErrors();
    this.calculator.clearCache();

    return {
      success: true,
      previousState: this.previousState,
      newState: {
        ownershipType: newOwnershipType,
        visibleSections: this.sectionManager.getVisibleSections()
      }
    };
  }

  /**
   * Undo the ownership type change
   */
  undo() {
    if (!this.previousState) return false;

    this.sectionManager.updateSectionVisibility(this.previousState.ownershipType);
    return true;
  }

  /**
   * Check if command can be executed
   * @param {string} newOwnershipType 
   */
  canExecute(newOwnershipType) {
    const validTypes = ['owned', 'leased', 'owned_rental'];
    return validTypes.includes(newOwnershipType);
  }
}