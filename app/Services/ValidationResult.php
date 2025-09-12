<?php

namespace App\Services;

/**
 * Represents the result of a validation operation
 */
class ValidationResult
{
    /**
     * @var bool Whether the validation passed
     */
    private bool $isValid;

    /**
     * @var array Array of validation error messages
     */
    private array $errors;

    /**
     * Create a new validation result
     *
     * @param bool $isValid Whether the validation passed
     * @param array $errors Array of error messages
     */
    public function __construct(bool $isValid, array $errors = [])
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }

    /**
     * Check if validation passed
     *
     * @return bool True if validation passed
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Get validation errors
     *
     * @return array Array of error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are any errors
     *
     * @return bool True if there are errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get the first error message
     *
     * @return string|null The first error message or null if no errors
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Add an error to the result
     *
     * @param string $error The error message to add
     * @return self
     */
    public function addError(string $error): self
    {
        $this->errors[] = $error;
        $this->isValid = false;
        
        return $this;
    }

    /**
     * Convert to array representation
     *
     * @return array Array representation of the result
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors
        ];
    }
}