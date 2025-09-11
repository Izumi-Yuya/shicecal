/**
 * Facade Pattern for Land Info System
 * Provides simplified interface to complex subsystem
 */
import { LandInfoManager } from './LandInfoManager.js';
import { OwnershipTypeChangeCommand } from './commands/OwnershipTypeChangeCommand.js';

export class LandInfoFacade {
  constructor(options = {}) {
    this.manager = new LandInfoManager(options);
    this.commands = new Map();
    this.commandHistory = [];

    this.initializeCommands();
  }

  /**
   * Initialize command objects
   */
  initializeCommands() {
    this.commands.set('ownershipChange',
      new OwnershipTypeChangeCommand(
        this.manager.sectionManager,
        this.manager.validator,
        this.manager.calculator
      )
    );
  }

  /**
   * Simplified API for ownership type changes
   * @param {string} ownershipType 
   */
  async changeOwnershipType(ownershipType) {
    const command = this.commands.get('ownershipChange');

    if (!command.canExecute(ownershipType)) {
      throw new Error(`Invalid ownership type: ${ownershipType}`);
    }

    try {
      const result = command.execute(ownershipType);
      this.commandHistory.push({ command: 'ownershipChange', result });

      // Emit event for external listeners
      this.emit('ownershipTypeChanged', result);

      return result;
    } catch (error) {
      this.manager.errorHandler.handleError(error, 'LandInfoFacade.changeOwnershipType');
      throw error;
    }
  }

  /**
   * Simplified API for calculations
   */
  async performCalculations() {
    try {
      const results = await Promise.all([
        this.manager.calculateUnitPrice(),
        this.manager.calculateContractPeriod()
      ]);

      return {
        unitPrice: results[0],
        contractPeriod: results[1]
      };
    } catch (error) {
      this.manager.errorHandler.handleError(error, 'LandInfoFacade.performCalculations');
      throw error;
    }
  }

  /**
   * Simplified API for form validation
   */
  validateForm() {
    return this.manager.validator.validateForm();
  }

  /**
   * Undo last command
   */
  undo() {
    const lastCommand = this.commandHistory.pop();
    if (!lastCommand) return false;

    const command = this.commands.get(lastCommand.command);
    return command.undo();
  }

  /**
   * Get system metrics
   */
  getMetrics() {
    return this.manager.getMetrics();
  }

  /**
   * Event emission for external integration
   */
  emit(eventName, data) {
    if (typeof window.dispatchEvent === 'function') {
      window.dispatchEvent(new CustomEvent(`landInfo:${eventName}`, { detail: data }));
    }
  }

  /**
   * Cleanup resources
   */
  destroy() {
    this.commands.clear();
    this.commandHistory = [];
    this.manager.destroy();
  }
}