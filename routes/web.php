<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FacilityController;

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
    Route::middleware(['admin'])->group(function () {
        Route::get('/users', function () {
            return view('home'); // Temporary redirect to home
        })->name('users.index');
        
        Route::get('/system/settings', function () {
            return view('home'); // Temporary redirect to home
        })->name('system.settings');
        
        Route::get('/logs', function () {
            return view('home'); // Temporary redirect to home
        })->name('logs.index');
        
        Route::get('/admin/dashboard', function () {
            return view('home'); // Temporary redirect to home
        })->name('admin.dashboard');
    });
});

// Auth routes (will be implemented in task 2.x)
Route::get('/login', function () {
    return view('home'); // Temporary redirect to home
})->name('login');

Route::post('/logout', function () {
    return redirect()->route('home'); // Temporary redirect to home
})->name('logout');
