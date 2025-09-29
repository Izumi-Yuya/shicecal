<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractRequest;
use App\Models\Facility;
use App\Models\FacilityContract;
use App\Services\ActivityLogService;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContractsController extends Controller
{
    protected ContractService $contractService;
    protected ActivityLogService $activityLogService;

    public function __construct(ContractService $contractService, ActivityLogService $activityLogService)
    {
        $this->contractService = $contractService;
        $this->activityLogService = $activityLogService;
    }

    public function edit(Request $request, Facility $facility)
    {
        try {
            // Log user and facility information for authorization debugging
            $user = auth()->user();
            Log::info('Contracts edit access attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'facility_id' => $facility->id,
                'canEdit' => $user->canEdit(),
                'canAccessFacility' => $user->canAccessFacility($facility->id),
                'canEditFacility' => $user->canEditFacility($facility->id),
            ]);

            // Check authorization using policy
            $this->authorize('update', [FacilityContract::class, $facility]);

            $contract = $this->contractService->getContract($facility);
            $contractsData = [];
            
            if ($contract) {
                $contractsData = $this->contractService->formatContractDataForDisplay($contract);
            }

            // Get the active sub-tab from the request parameter
            $activeSubTab = $request->get('sub_tab', 'others');

            return view('facilities.contracts.edit', compact('facility', 'contractsData', 'activeSubTab'));

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->route('facilities.show', $facility)
                ->with('error', 'この施設の契約書を編集する権限がありません。');
        } catch (\Exception $e) {
            Log::error('Contracts edit page failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('facilities.show', $facility)
                ->with('error', 'システムエラーが発生しました。');
        }
    }

    public function update(ContractRequest $request, Facility $facility)
    {
        try {
            // Check authorization using policy
            $this->authorize('update', [FacilityContract::class, $facility]);

            $validated = $request->validated();
            $user = auth()->user();

            // Update contract data via service
            $contract = $this->contractService->createOrUpdateContract($facility, $validated, $user);

            // Log the activity
            $this->activityLogService->logFacilityUpdated(
                $facility->id,
                $facility->facility_name . ' - 契約書',
                $request
            );

            // Get the active sub-tab
            $activeSubTab = $request->input('active_sub_tab', 'others');

            // Return appropriate response based on request type
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '契約書を更新しました。',
                    'contract' => $contract,
                ]);
            }

            return redirect()
                ->route('facilities.show', $facility)
                ->with('success', '契約書を更新しました。')
                ->with('activeTab', 'contracts')
                ->with('activeSubTab', $activeSubTab);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この施設の契約書を更新する権限がありません。',
                ], 403);
            }

            return redirect()->route('facilities.show', $facility)
                ->with('error', 'この施設の契約書を更新する権限がありません。');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '入力内容に誤りがあります。',
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Contracts update failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'システムエラーが発生しました。',
                ], 500);
            }

            return back()
                ->with('error', 'システムエラーが発生しました。')
                ->withInput();
        }
    }
}