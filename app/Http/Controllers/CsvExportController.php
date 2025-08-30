<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CsvExportController extends Controller
{
    /**
     * Display the CSV export menu
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get facilities based on user role and permissions
        $facilities = $this->getFacilitiesForUser($user);
        
        // Get available export fields
        $availableFields = $this->getAvailableFields();
        
        return view('export.csv.index', compact('facilities', 'availableFields'));
    }

    /**
     * Get field preview data for selected facilities and fields
     */
    public function getFieldPreview(Request $request)
    {
        $facilityIds = $request->input('facility_ids', []);
        $exportFields = $request->input('export_fields', []);
        
        if (empty($facilityIds) || empty($exportFields)) {
            return response()->json([
                'success' => false,
                'message' => '施設または項目が選択されていません。'
            ]);
        }
        
        $user = Auth::user();
        
        // Get facilities that user has access to
        $facilities = $this->getFacilitiesForUser($user)
                          ->whereIn('id', $facilityIds)
                          ->take(3); // Limit preview to 3 facilities
        
        // Get available fields
        $availableFields = $this->getAvailableFields();
        
        // Filter to only requested fields
        $selectedFields = array_intersect_key($availableFields, array_flip($exportFields));
        
        // Generate preview data
        $previewData = [];
        foreach ($facilities as $facility) {
            $row = [];
            foreach ($exportFields as $field) {
                $row[$field] = $this->getFieldValue($facility, $field);
            }
            $previewData[] = $row;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'fields' => $selectedFields,
                'preview_data' => $previewData,
                'total_facilities' => count($facilityIds),
                'preview_count' => count($previewData)
            ]
        ]);
    }

    /**
     * Get facilities based on user role and access scope
     */
    private function getFacilitiesForUser(User $user)
    {
        $query = Facility::approved(); // Only show approved facilities
        
        switch ($user->role) {
            case 'admin':
            case 'editor':
                // Admin and editor can see all facilities
                break;
                
            case 'viewer':
                // Viewer role - check access scope
                if (isset($user->access_scope['type'])) {
                    switch ($user->access_scope['type']) {
                        case 'department':
                            // Department manager - filter by department
                            if (isset($user->access_scope['departments'])) {
                                $query->whereIn('department', $user->access_scope['departments']);
                            }
                            break;
                            
                        case 'region':
                            // Regional manager - filter by region/prefecture
                            if (isset($user->access_scope['regions'])) {
                                $query->where(function($q) use ($user) {
                                    foreach ($user->access_scope['regions'] as $region) {
                                        $q->orWhere('address', 'like', "%{$region}%");
                                    }
                                });
                            }
                            break;
                            
                        case 'facility':
                            // Facility specific - only their own facility
                            if (isset($user->access_scope['facility_ids'])) {
                                $query->whereIn('id', $user->access_scope['facility_ids']);
                            }
                            break;
                    }
                }
                break;
                
            default:
                // Other roles get no facilities by default
                $query->whereRaw('1 = 0');
                break;
        }
        
        return $query->orderBy('company_name')
                    ->orderBy('facility_name')
                    ->get();
    }

    /**
     * Get available fields for CSV export
     */
    private function getAvailableFields()
    {
        return [
            'company_name' => '会社名',
            'office_code' => '事業所コード',
            'designation_number' => '指定番号',
            'facility_name' => '施設名',
            'postal_code' => '郵便番号',
            'address' => '住所',
            'phone_number' => '電話番号',
            'fax_number' => 'FAX番号',
            'status' => 'ステータス',
            'approved_at' => '承認日時',
            'created_at' => '作成日時',
            'updated_at' => '更新日時',
        ];
    }

    /**
     * Get formatted field value for a facility
     */
    private function getFieldValue(Facility $facility, string $field)
    {
        switch ($field) {
            case 'status':
                return $this->getStatusLabel($facility->status);
            case 'approved_at':
                return $facility->approved_at ? $facility->approved_at->format('Y-m-d H:i:s') : '';
            case 'created_at':
                return $facility->created_at->format('Y-m-d H:i:s');
            case 'updated_at':
                return $facility->updated_at->format('Y-m-d H:i:s');
            default:
                return $facility->{$field} ?? '';
        }
    }

    /**
     * Get status label in Japanese
     */
    private function getStatusLabel(string $status): string
    {
        $statusLabels = [
            'draft' => '下書き',
            'pending_approval' => '承認待ち',
            'approved' => '承認済み',
        ];
        
        return $statusLabels[$status] ?? $status;
    }
}