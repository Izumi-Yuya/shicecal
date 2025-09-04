<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class LandCalculationService
{
    /**
     * Calculate unit price per tsubo (購入価格÷敷地面積（坪数）)
     * Requirements: 2.1, 2.2
     *
     * @param float $purchasePrice Purchase price in yen
     * @param float $areaInTsubo Area in tsubo
     * @return float|null Unit price per tsubo, null if calculation not possible
     */
    public function calculateUnitPrice(float $purchasePrice, float $areaInTsubo): ?float
    {
        if ($purchasePrice <= 0 || $areaInTsubo <= 0) {
            return null;
        }

        return round($purchasePrice / $areaInTsubo);
    }

    /**
     * Calculate contract period in years and months format (5年5ヶ月)
     * Requirements: 2.3, 2.4
     *
     * @param string $startDate Contract start date (YYYY-MM-DD format)
     * @param string $endDate Contract end date (YYYY-MM-DD format)
     * @return string Contract period in Japanese format
     */
    public function calculateContractPeriod(string $startDate, string $endDate): string
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            if ($end <= $start) {
                return '';
            }

            $diff = $start->diff($end);
            $years = $diff->y;
            $months = $diff->m;

            $result = '';
            if ($years > 0) {
                $result .= $years . '年';
            }
            if ($months > 0) {
                $result .= $months . 'ヶ月';
            }

            return $result ?: '0ヶ月';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format currency with comma separators (3桁区切りカンマ)
     * Requirements: 1.7, 1.8, 2.2
     *
     * @param float $amount Amount to format
     * @return string Formatted currency string
     */
    public function formatCurrency(float $amount): string
    {
        if ($amount == 0) {
            return '';
        }

        return number_format(round($amount), 0, '.', ',');
    }

    /**
     * Format area with unit display
     * Requirements: 2.5, 2.6
     *
     * @param float $area Area value
     * @param string $unit Unit type ('sqm' for ㎡, 'tsubo' for 坪)
     * @return string Formatted area string
     */
    public function formatArea(float $area, string $unit): string
    {
        if ($area <= 0) {
            return '';
        }

        $formattedArea = number_format($area, 2, '.', ',');

        switch ($unit) {
            case 'sqm':
                return $formattedArea . '㎡';
            case 'tsubo':
                return $formattedArea . '坪';
            default:
                throw new InvalidArgumentException("Invalid unit: {$unit}. Use 'sqm' or 'tsubo'.");
        }
    }

    /**
     * Format date in Japanese format (2000年12月12日)
     * Requirements: 3.3
     *
     * @param string $date Date string in YYYY-MM-DD format
     * @return string Japanese formatted date
     */
    public function formatJapaneseDate(string $date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            $carbon = Carbon::parse($date);
            return $carbon->format('Y年n月j日');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Convert full-width numbers to half-width numbers
     * Requirements: 4.10
     *
     * @param string $input Input string with potential full-width numbers
     * @return string String with half-width numbers
     */
    public function convertToHalfWidth(string $input): string
    {
        return mb_convert_kana($input, 'n');
    }

    /**
     * Validate and format postal code (XXX-XXXX format)
     * Requirements: 4.2, 5.2
     *
     * @param string $postalCode Postal code input
     * @return string|null Formatted postal code or null if invalid
     */
    public function formatPostalCode(string $postalCode): ?string
    {
        $cleaned = $this->convertToHalfWidth($postalCode);
        $cleaned = preg_replace('/[^0-9-]/', '', $cleaned);

        if (preg_match('/^(\d{3})-?(\d{4})$/', $cleaned, $matches)) {
            return $matches[1] . '-' . $matches[2];
        }

        return null;
    }

    /**
     * Validate and format phone number (XX-XXXX-XXXX format)
     * Requirements: 4.5, 4.6, 5.5, 5.6
     *
     * @param string $phoneNumber Phone number input
     * @return string|null Formatted phone number or null if invalid
     */
    public function formatPhoneNumber(string $phoneNumber): ?string
    {
        $cleaned = $this->convertToHalfWidth($phoneNumber);
        $cleaned = preg_replace('/[^0-9-]/', '', $cleaned);

        // Remove existing hyphens for processing
        $numbersOnly = str_replace('-', '', $cleaned);

        // Format based on common Japanese phone number patterns
        if (strlen($numbersOnly) === 10) {
            // Standard 10-digit format: 03-1234-5678
            if (preg_match('/^(0\d{1,3})(\d{4})(\d{4})$/', $numbersOnly, $matches)) {
                return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            }
        } elseif (strlen($numbersOnly) === 11) {
            // Check for toll-free numbers first (0120, 0800)
            if (preg_match('/^(0120|0800)(\d{3})(\d{4})$/', $numbersOnly, $matches)) {
                return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            }
            // Mobile numbers: 090, 080, 070
            elseif (preg_match('/^(0\d{2})(\d{4})(\d{4})$/', $numbersOnly, $matches)) {
                return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            }
        }

        return null;
    }
}
