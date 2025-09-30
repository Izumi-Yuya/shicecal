<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class RepairHistoryController extends Controller
{
    /**
     * Display the repair history index page for a specific facility.
     * Shows categorized repair history data for exterior, interior, and other maintenance types.
     * 
     * @param Facility $facility The facility for which to display the repair history
     * @return \Illuminate\View\View The repair history index view
     */
    public function index(Facility $facility)
    {
        try {
            // Authorization check
            $this->authorize('view', [MaintenanceHistory::class, $facility]);

            // Get repair history data by category
            $exteriorHistory = $facility->maintenanceHistories()
                ->byCategory('exterior')
                ->orderByDate()
                ->get()
                ->groupBy('subcategory');

            $interiorHistory = $facility->maintenanceHistories()
                ->byCategory('interior')
                ->orderByDate()
                ->get();

            $otherHistory = $facility->maintenanceHistories()
                ->byCategory('other')
                ->orderByDate()
                ->get();

            return view('facilities.repair-history.index', compact(
                'facility',
                'exteriorHistory',
                'interiorHistory',
                'otherHistory'
            ));

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'この施設の修繕履歴を閲覧する権限がありません。');
        } catch (\Exception $e) {
            Log::error('Repair history index failed', [
                'facility_id' => $facility->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            abort(500, '修繕履歴の取得に失敗しました。');
        }
    }

    /**
     * Display the form for editing repair history data by category.
     * Shows existing repair history records for the specified category with editing capabilities.
     * 
     * @param Facility $facility The facility for which to edit the repair history
     * @param string $category The repair history category (exterior, interior, other)
     * @return \Illuminate\View\View The repair history edit form view
     */
    public function edit(Facility $facility, string $category)
    {
        try {
            // Authorization check
            $this->authorize('update', [MaintenanceHistory::class, $facility]);

            // Validate category
            if (!array_key_exists($category, MaintenanceHistory::CATEGORIES)) {
                abort(404, '指定されたカテゴリが無効です。');
            }

            // Get existing repair history data for the category
            $histories = $facility->maintenanceHistories()
                ->byCategory($category)
                ->orderByDate()
                ->get();

            // Get available subcategories for the category
            $subcategories = MaintenanceHistory::getSubcategoriesForCategory($category);

            return view('facilities.repair-history.edit', compact(
                'facility',
                'category',
                'histories',
                'subcategories'
            ));

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'この施設の修繕履歴を編集する権限がありません。');
        } catch (\Exception $e) {
            Log::error('Repair history edit failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            abort(500, '修繕履歴の編集画面の表示に失敗しました。');
        }
    }

    /**
     * Update repair history data for a specific category.
     * Validates and saves repair history records with proper authorization.
     * 
     * @param Request $request The HTTP request containing the repair history data
     * @param Facility $facility The facility for which to update the repair history
     * @param string $category The repair history category to update
     * @return \Illuminate\Http\RedirectResponse Redirect response with success/error message
     */
    public function update(Request $request, Facility $facility, string $category)
    {
        try {
            // Authorization check
            $this->authorize('update', [MaintenanceHistory::class, $facility]);

            // Validate category
            if (!array_key_exists($category, MaintenanceHistory::CATEGORIES)) {
                abort(404, '指定されたカテゴリが無効です。');
            }

            // Debug: Log the incoming request data
            Log::info('Repair history update request data', [
                'facility_id' => $facility->id,
                'category' => $category,
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            // Validate request data
            $validated = $request->validate(
                $this->getValidationRules($category),
                $this->getValidationMessages()
            );

            // Debug: Log the validated data
            Log::info('Repair history validated data', [
                'facility_id' => $facility->id,
                'category' => $category,
                'validated_data' => $validated,
                'user_id' => Auth::id(),
            ]);

            // Update repair history data in a transaction
            DB::transaction(function () use ($facility, $category, $validated) {
                $this->updateRepairHistory($facility, $category, $validated);
            });

            // Determine redirect fragment based on category
            $fragment = $this->getRedirectFragment($category);
            
            // Log the redirect for debugging
            Log::info('Repair history update redirect', [
                'facility_id' => $facility->id,
                'category' => $category,
                'fragment' => $fragment,
                'user_id' => Auth::id(),
            ]);
            
            // Build URL with fragment
            $redirectUrl = route('facilities.show', $facility) . '#' . $fragment;
            
            return redirect($redirectUrl)
                ->with('success', '修繕履歴が更新されました。');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'この施設の修繕履歴を編集する権限がありません。');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Repair history update failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['error' => '修繕履歴の更新に失敗しました。エラー: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Get validation rules for the specified category.
     * 
     * @param string $category The repair history category
     * @return array The validation rules array
     */
    private function getValidationRules(string $category): array
    {
        $baseRules = [
            'histories' => 'array',
            'histories.*.id' => 'nullable|exists:maintenance_histories,id',
            'histories.*.maintenance_date' => 'required|date',
            'histories.*.contractor' => 'required|string|max:255',
            'histories.*.contact_person' => 'nullable|string|max:255',
            'histories.*.phone_number' => 'nullable|string|max:20',
            'histories.*.notes' => 'nullable|string',
        ];
        
        // Add subcategory validation based on category
        if ($category === 'exterior') {
            // For exterior category, allow free text input
            $baseRules['histories.*.subcategory'] = 'required|string|max:50';
        } elseif ($category === 'interior') {
            // For interior category, allow both English and Japanese input
            $allowedSubcategories = array_merge(
                array_keys(MaintenanceHistory::getSubcategoriesForCategory($category)), // English keys
                array_values(MaintenanceHistory::getSubcategoriesForCategory($category)), // Japanese values
                ['内装リニューアル', '内装・意匠履歴'] // Additional Japanese variations for backward compatibility
            );
            $baseRules['histories.*.subcategory'] = [
                'required',
                'string',
                Rule::in($allowedSubcategories)
            ];
        } else {
            // For other category, use hidden field
            $baseRules['histories.*.subcategory'] = 'nullable|string';
        }
        
        // Add category-specific validation rules
        if ($category === 'interior' || $category === 'other') {
            // Interior design history and other category-specific fields
            $baseRules['histories.*.content'] = 'nullable|string|max:500';
            $baseRules['histories.*.cost'] = 'nullable|numeric|min:0';
            $baseRules['histories.*.classification'] = 'nullable|string|max:100';
        }

        // Add category-specific validation rules
        if ($category === 'exterior') {
            $baseRules['histories.*.warranty_period_years'] = 'nullable|integer|min:1|max:50';
            $baseRules['histories.*.warranty_start_date'] = 'nullable|date';
            $baseRules['histories.*.warranty_end_date'] = 'nullable|date|after_or_equal:histories.*.warranty_start_date';
        }

        // Add special notes validation
        $baseRules['special_notes'] = 'nullable|string';

        return $baseRules;
    }

    /**
     * Get custom validation messages in Japanese.
     * 
     * @return array The validation messages array
     */
    private function getValidationMessages(): array
    {
        return [
            'histories.*.maintenance_date.required' => '施工日は必須項目です。',
            'histories.*.maintenance_date.date' => '施工日は有効な日付形式で入力してください。',
            'histories.*.contractor.required' => '会社名は必須項目です。',
            'histories.*.contractor.max' => '会社名は255文字以内で入力してください。',
            'histories.*.content.required' => '修繕内容は必須項目です。',
            'histories.*.content.max' => '修繕内容は500文字以内で入力してください。',
            'histories.*.cost.numeric' => '金額は数値で入力してください。',
            'histories.*.cost.min' => '金額は0以上で入力してください。',
            'histories.*.contact_person.max' => '担当者名は255文字以内で入力してください。',
            'histories.*.phone_number.max' => '連絡先は20文字以内で入力してください。',
            'histories.*.classification.max' => '区分は100文字以内で入力してください。',
            'histories.*.subcategory.required' => '種別の入力は必須です。',
            'histories.*.subcategory.in' => '選択された種別が無効です。',
            'histories.*.subcategory.max' => '種別は50文字以内で入力してください。',
            'histories.*.warranty_period_years.integer' => '保証期間は整数で入力してください。',
            'histories.*.warranty_period_years.min' => '保証期間は1年以上で入力してください。',
            'histories.*.warranty_period_years.max' => '保証期間は50年以下で入力してください。',
            'histories.*.warranty_start_date.date' => '保証開始日は有効な日付形式で入力してください。',
            'histories.*.warranty_end_date.date' => '保証終了日は有効な日付形式で入力してください。',
        ];
    }

    /**
     * Update repair history data for a facility and category.
     * 
     * @param Facility $facility The facility to update
     * @param string $category The repair history category
     * @param array $validated The validated request data
     * @return void
     */
    private function updateRepairHistory(Facility $facility, string $category, array $validated): void
    {
        $histories = $validated['histories'] ?? [];
        $userId = Auth::id();

        // Debug: Log the histories data being processed
        Log::info('Processing repair history data', [
            'facility_id' => $facility->id,
            'category' => $category,
            'histories_count' => count($histories),
            'histories_data' => $histories,
            'user_id' => $userId,
        ]);

        // Get existing histories for this category
        $existingHistories = $facility->maintenanceHistories()
            ->byCategory($category)
            ->get()
            ->keyBy('id');

        Log::info('Existing histories', [
            'facility_id' => $facility->id,
            'category' => $category,
            'existing_count' => $existingHistories->count(),
            'existing_ids' => $existingHistories->keys()->toArray(),
        ]);

        $processedIds = [];

        // Process each history record
        foreach ($histories as $index => $historyData) {
            Log::info('Processing history record', [
                'facility_id' => $facility->id,
                'category' => $category,
                'index' => $index,
                'history_data' => $historyData,
                'has_id' => !empty($historyData['id']),
                'id_value' => $historyData['id'] ?? 'null',
            ]);
            
            // Check if this is an update (has valid existing ID) or create (no ID or invalid ID)
            $isUpdate = !empty($historyData['id']) && $existingHistories->has($historyData['id']);
            
            if ($isUpdate) {
                // Update existing record
                $history = $existingHistories->get($historyData['id']);
                $preparedData = $this->prepareHistoryData($historyData, $category, $facility->id, $userId);
                Log::info('Updating existing history', [
                    'facility_id' => $facility->id,
                    'category' => $category,
                    'history_id' => $history->id,
                    'prepared_data' => $preparedData,
                ]);
                $history->update($preparedData);
                $processedIds[] = $history->id;
            } else {
                // Create new record
                $preparedData = $this->prepareHistoryData($historyData, $category, $facility->id, $userId);
                Log::info('Creating new history', [
                    'facility_id' => $facility->id,
                    'category' => $category,
                    'prepared_data' => $preparedData,
                    'reason' => empty($historyData['id']) ? 'no_id' : 'invalid_id',
                ]);
                $newHistory = MaintenanceHistory::create($preparedData);
                Log::info('New history created', [
                    'facility_id' => $facility->id,
                    'category' => $category,
                    'new_history_id' => $newHistory->id,
                ]);
                $processedIds[] = $newHistory->id;
            }
        }

        // Remove histories that were not included in the update
        $facility->maintenanceHistories()
            ->byCategory($category)
            ->whereNotIn('id', $processedIds)
            ->delete();

        // Handle special notes if applicable
        if (isset($validated['special_notes'])) {
            $this->updateSpecialNotes($facility, $category, $validated['special_notes']);
        }
    }

    /**
     * Prepare history data for database insertion/update.
     * 
     * @param array $data The raw history data
     * @param string $category The repair history category
     * @param int $facilityId The facility ID
     * @param int $userId The user ID
     * @return array The prepared data array
     */
    private function prepareHistoryData(array $data, string $category, int $facilityId, int $userId): array
    {
        $prepared = [
            'facility_id' => $facilityId,
            'category' => $category,
            'subcategory' => $category === 'other' ? 'renovation_work' : $data['subcategory'],
            'maintenance_date' => $data['maintenance_date'],
            'contractor' => $data['contractor'],
            'content' => $data['content'] ?? '修繕工事', // Default content if not provided
            'cost' => $data['cost'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'classification' => $data['classification'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
        ];

        // Add category-specific fields
        if ($category === 'exterior') {
            $prepared['warranty_period_years'] = $data['warranty_period_years'] ?? null;
            $prepared['warranty_start_date'] = $data['warranty_start_date'] ?? null;
            $prepared['warranty_end_date'] = $data['warranty_end_date'] ?? null;
        }

        return $prepared;
    }

    /**
     * Update special notes for a category (placeholder for future implementation).
     * This method is reserved for handling category-specific special notes.
     * 
     * @param Facility $facility The facility to update
     * @param string $category The repair history category
     * @param string $notes The special notes content
     * @return void
     */
    private function updateSpecialNotes(Facility $facility, string $category, string $notes): void
    {
        // カテゴリに応じた特記事項フィールドを更新
        $fieldName = match($category) {
            'exterior' => 'exterior_special_notes',
            'interior' => 'interior_special_notes',
            'other' => 'other_special_notes',
            default => 'exterior_special_notes'
        };
        
        $facility->update([
            $fieldName => $notes ?: null
        ]);
        
        Log::info('Special notes updated', [
            'facility_id' => $facility->id,
            'category' => $category,
            'field' => $fieldName,
            'notes_length' => strlen($notes),
        ]);
    }

    /**
     * Get the appropriate redirect fragment based on category.
     * 
     * @param string $category The repair history category
     * @return string The fragment identifier for redirect
     */
    private function getRedirectFragment(string $category): string
    {
        switch ($category) {
            case 'exterior':
                return 'repair-history';
            case 'interior':
                return 'interior';
            case 'other':
                return 'other';
            default:
                return 'repair-history';
        }
    }
}