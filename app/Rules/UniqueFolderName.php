<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\DocumentFolder;

class UniqueFolderName implements Rule
{
    protected int $facilityId;
    protected ?int $parentId;
    protected ?int $excludeId;

    /**
     * Create a new rule instance.
     */
    public function __construct(int $facilityId, ?int $parentId = null, ?int $excludeId = null)
    {
        $this->facilityId = $facilityId;
        $this->parentId = $parentId;
        $this->excludeId = $excludeId;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        $query = DocumentFolder::where('facility_id', $this->facilityId)
            ->where('parent_id', $this->parentId)
            ->where('name', $value);

        if ($this->excludeId) {
            $query->where('id', '!=', $this->excludeId);
        }

        return !$query->exists();
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'このフォルダ名は既に存在します。別の名前を入力してください。';
    }
}