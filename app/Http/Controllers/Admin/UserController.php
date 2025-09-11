<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->isAdmin()) {
                abort(403, 'Access denied');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = User::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $active = $request->status === 'active';
            $query->where('is_active', $active);
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('department', 'like', "%{$request->department}%");
        }

        $users = $query->paginate(20);

        // Statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'editor_users' => User::where('role', 'editor')->count(),
            'viewer_users' => User::where('role', 'viewer')->count(),
            'inactive_users' => User::where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,editor,primary_responder,approver,viewer',
            'department' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'department' => $request->department,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ユーザーが正常に作成されました',
            'user' => $user,
        ]);
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => ! $user->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'ユーザーステータスが更新されました',
        ]);
    }

    public function export(Request $request)
    {
        // Export functionality would be implemented here
        return response()->json(['success' => true]);
    }

    public function bulkUpdateRole(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'role' => 'required|in:admin,editor,primary_responder,approver,viewer',
        ]);

        User::whereIn('id', $request->user_ids)->update(['role' => $request->role]);

        return response()->json([
            'success' => true,
            'message' => 'ユーザーロールが一括更新されました',
        ]);
    }
}
