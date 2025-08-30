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
}