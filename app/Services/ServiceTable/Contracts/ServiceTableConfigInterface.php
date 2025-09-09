<?php

namespace App\Services\ServiceTable\Contracts;

/**
 * Interface for service table configuration
 */
interface ServiceTableConfigInterface
{
    public function getMaxServices(): int;
    public function shouldShowEmptyRows(): bool;
    public function getEmptyValueText(): string;
    public function getColumnConfig(): array;
    public function getStylingConfig(): array;
    public function getCacheTtl(): int;
}