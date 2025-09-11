<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles facility view mode preferences
 * Extracted from FacilityController for better separation of concerns
 */
class FacilityViewModeController extends Controller
{
    // View mode session management constants
    const VIEW_PREFERENCE_KEY = 'facility_basic_info_view_mode';

    const VIEW_MODES = [
        'card' => 'カード形式',
        'table' => 'テーブル形式',
    ];

    /**
     * Set view mode preference via AJAX
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'view_mode' => 'required|in:card,table',
        ]);

        session([self::VIEW_PREFERENCE_KEY => $validated['view_mode']]);

        return response()->json([
            'success' => true,
            'view_mode' => $validated['view_mode'],
            'message' => '表示形式を変更しました。',
        ]);
    }

    /**
     * Get current view mode preference with 'card' as default
     */
    public function show(): string
    {
        return session(self::VIEW_PREFERENCE_KEY, 'card');
    }
}
