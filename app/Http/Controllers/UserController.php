<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::query();
        
        // Search by email
        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        
        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        // Filter by status (active/inactive)
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }
        
        // Order by created_at desc by default
        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Get available roles for filter dropdown
        $roles = [
            User::ROLE_ADMIN => '管理者',
            User::ROLE_EDITOR => '編集者',
            User::ROLE_PRIMARY_RESPONDER => '一次対応者',
            User::ROLE_APPROVER => '承認者',
            User::ROLE_VIEWER => '閲覧者',
        ];
        
        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $roles = [
            User::ROLE_ADMIN => '管理者',
            User::ROLE_EDITOR => '編集者',
            User::ROLE_PRIMARY_RESPONDER => '一次対応者',
            User::ROLE_APPROVER => '承認者',
            User::ROLE_VIEWER => '閲覧者',
        ];
        
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage
     */
    public function store(Request $request)
    {
        // Create user without validation as per requirements
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'department' => $request->department,
            'access_scope' => $request->access_scope ? json_decode($request->access_scope, true) : null,
            'is_active' => $request->has('is_active'),
        ];
        
        User::create($userData);
        
        return redirect()->route('admin.users.index')
                        ->with('success', 'ユーザーが正常に作成されました。');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        // Load relationships for detailed view
        $user->load([
            'createdFacilities',
            'updatedFacilities', 
            'approvedFacilities',
            'uploadedFiles',
            'postedComments',
            'assignedComments',
            'maintenanceHistories',
            'exportFavorites',
            'activityLogs' => function($query) {
                $query->latest()->limit(10);
            }
        ]);
        
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $roles = [
            User::ROLE_ADMIN => '管理者',
            User::ROLE_EDITOR => '編集者',
            User::ROLE_PRIMARY_RESPONDER => '一次対応者',
            User::ROLE_APPROVER => '承認者',
            User::ROLE_VIEWER => '閲覧者',
        ];
        
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage
     */
    public function update(Request $request, User $user)
    {
        // Update user without validation as per requirements
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'department' => $request->department,
            'access_scope' => $request->access_scope ? json_decode($request->access_scope, true) : null,
            'is_active' => $request->has('is_active'),
        ];
        
        // Only update password if provided
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }
        
        $user->update($userData);
        
        return redirect()->route('admin.users.index')
                        ->with('success', 'ユーザー情報が正常に更新されました。');
    }

    /**
     * Remove the specified user from storage
     */
    public function destroy(User $user)
    {
        // Soft delete by setting is_active to false
        $user->update(['is_active' => false]);
        
        return redirect()->route('admin.users.index')
                        ->with('success', 'ユーザーが正常に削除されました。');
    }
}