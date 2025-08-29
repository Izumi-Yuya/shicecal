<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('home');
});

// Home route
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Placeholder routes for navigation (will be implemented in later tasks)
Route::middleware(['auth'])->group(function () {
    // Facilities
    Route::get('/facilities', function () {
        return view('home'); // Temporary redirect to home
    })->name('facilities.index');
    
    Route::get('/facilities/create', function () {
        return view('home'); // Temporary redirect to home
    })->name('facilities.create');
    
    // Users (admin only)
    Route::get('/users', function () {
        return view('home'); // Temporary redirect to home
    })->name('users.index');
    
    // System settings (admin only)
    Route::get('/system/settings', function () {
        return view('home'); // Temporary redirect to home
    })->name('system.settings');
    
    // Logs (admin only)
    Route::get('/logs', function () {
        return view('home'); // Temporary redirect to home
    })->name('logs.index');
    
    // Profile
    Route::get('/profile', function () {
        return view('home'); // Temporary redirect to home
    })->name('profile.show');
    
    // My page
    Route::get('/my-page', function () {
        return view('home'); // Temporary redirect to home
    })->name('my-page');
    
    // CSV export
    Route::get('/csv/export', function () {
        return view('home'); // Temporary redirect to home
    })->name('csv.export');
    
    // Approvals
    Route::get('/approvals', function () {
        return view('home'); // Temporary redirect to home
    })->name('approvals.index');
});

// Auth routes (will be implemented in task 2.x)
Route::get('/login', function () {
    return view('home'); // Temporary redirect to home
})->name('login');

Route::post('/logout', function () {
    return redirect()->route('home'); // Temporary redirect to home
})->name('logout');
