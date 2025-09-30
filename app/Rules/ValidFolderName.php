<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidFolderName implements Rule
{
    protected array $forbiddenNames;
    protected array $forbiddenCharacters;
    protected string $failureReason = '';

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->forbiddenNames = config('facility-document.forbidden_folder_names', []);
        $this->forbiddenCharacters = config('facility-document.forbidden_folder_characters', []);
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        // Check for empty or whitespace-only names
        if (empty(trim($value))) {
            $this->failureReason = 'empty';
            return false;
        }

        // Check for forbidden characters
        foreach ($this->forbiddenCharacters as $char) {
            if (strpos($value, $char) !== false) {
                $this->failureReason = 'forbidden_character';
                return false;
            }
        }

        // Check for forbidden names (case-insensitive)
        $upperValue = strtoupper(trim($value));
        foreach ($this->forbiddenNames as $forbiddenName) {
            if ($upperValue === strtoupper($forbiddenName)) {
                $this->failureReason = 'forbidden_name';
                return false;
            }
        }

        // Check for names that start or end with dots or spaces
        if (preg_match('/^[\.\s]|[\.\s]$/', $value)) {
            $this->failureReason = 'invalid_start_end';
            return false;
        }

        // Check for control characters
        if (preg_match('/[\x00-\x1F\x7F]/', $value)) {
            $this->failureReason = 'control_character';
            return false;
        }

        // Check for excessively long names
        if (mb_strlen($value) > config('facility-document.folder_name_max_length', 255)) {
            $this->failureReason = 'too_long';
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        switch ($this->failureReason) {
            case 'empty':
                return 'フォルダ名を入力してください。';
            case 'forbidden_character':
                $chars = implode(' ', $this->forbiddenCharacters);
                return "フォルダ名に使用できない文字が含まれています。（{$chars} は使用できません）";
            case 'forbidden_name':
                return 'このフォルダ名はシステムで予約されているため使用できません。';
            case 'invalid_start_end':
                return 'フォルダ名の最初や最後にドット（.）やスペースは使用できません。';
            case 'control_character':
                return 'フォルダ名に制御文字が含まれています。';
            case 'too_long':
                $maxLength = config('facility-document.folder_name_max_length', 255);
                return "フォルダ名は{$maxLength}文字以内で入力してください。";
            default:
                return 'フォルダ名が無効です。';
        }
    }
}