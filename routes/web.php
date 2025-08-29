<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;

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
    if (auth()->check()) {
        return redirect()->route('home');
    }
    return redirect()->route('login');
});

// Home route (requires authentication)
Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware('auth');

// Placeholder routes for navigation (will be implemented in later tasks)
Route::middleware(['auth'])->group(function () {
    // Facilities
    Route::resource('facilities', FacilityController::class);
    
    // Maintenance History
    Route::get('/maintenance', function () {
        return view('home'); // Temporary redirect to home
    })->name('maintenance.index');
    
    // Export functions
    Route::get('/export/csv', function () {
        return view('home'); // Temporary redirect to home
    })->name('export.csv');
    
    Route::get('/export/pdf', function () {
        return view('home'); // Temporary redirect to home
    })->name('export.pdf');
    
    Route::get('/export/favorites', function () {
        return view('home'); // Temporary redirect to home
    })->name('export.favorites');
    
    // Comments
    Route::get('/comments', function () {
        return view('home'); // Temporary redirect to home
    })->name('comments.index');
    
    Route::get('/comments/assigned', function () {
        return view('home'); // Temporary redirect to home
    })->name('comments.assigned');
    
    // Approvals
    Route::get('/approvals', function () {
        return view('home'); // Temporary redirect to home
    })->name('approvals.index');
    
    // Annual Check
    Route::get('/annual-check', function () {
        return view('home'); // Temporary redirect to home
    })->name('annual-check.index');
    
    // Profile and My Page
    Route::get('/profile', function () {
        return view('home'); // Temporary redirect to home
    })->name('profile.show');
    
    Route::get('/my-page', function () {
        return view('home'); // Temporary redirect to home
    })->name('my-page');
    
    // Admin routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', App\Http\Controllers\UserController::class);
        
        Route::get('/system/settings', function () {
            return view('home'); // Temporary redirect to home
        })->name('system.settings');
        
        Route::get('/logs', function () {
            return view('home'); // Temporary redirect to home
        })->name('logs.index');
        
        Route::get('/dashboard', function () {
            return view('home'); // Temporary redirect to home
        })->name('dashboard');
    });
});

// Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Health check routes (no authentication required)
Route::get('/health', [HealthController::class, 'index'])->name('health.basic');
Route::get('/health/detailed', [HealthController::class, 'detailed'])->name('health.detailed');
