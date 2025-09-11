<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Log successful login
            $this->activityLogService->logLogin(Auth::id(), $request);

            return redirect()->intended('/facilities');
        }

        return back()->withErrors([
            'email' => 'ログイン情報が正しくありません。',
        ])->onlyInput('email');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        $userId = Auth::id();

        // Log logout before actually logging out
        if ($userId) {
            $this->activityLogService->logLogout($userId, $request);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
