<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        
        // Find user by email
        $user = User::where('email', $credentials['email'])->first();
        
        // Check if user exists and is active
        if (!$user || !$user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'ユーザーが見つからないか、アカウントが無効です。',
            ])->withInput($request->only('email'));
        }
        
        // Verify password
        if (!Hash::check($credentials['password'], $user->password)) {
            return redirect()->route('login')->withErrors([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ])->withInput($request->only('email'));
        }
        
        // Log the user in
        Auth::login($user);
        
        // Regenerate session to prevent session fixation
        $request->session()->regenerate();
        
        // Redirect to intended page or home
        return redirect()->intended(route('home'));
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
}