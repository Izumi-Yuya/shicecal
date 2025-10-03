<?php

use App\Http\Controllers\AnnualConfirmationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContractsController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\LifelineEquipmentController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RepairHistoryController;
use Illuminate\Support\Facades\Route;

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

// Root redirect
Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Export Routes - All export functionality under /export prefix
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('export')->name('export.')->group(function () {
    // PDF Export Routes
    Route::prefix('pdf')->name('pdf.')->group(function () {
        Route::get('/', [ExportController::class, 'pdfIndex'])->name('index');
        Route::get('/facility/{facility}', [ExportController::class, 'generateSinglePdf'])->name('single');
        Route::get('/secure/{facility}', [ExportController::class, 'generateSecurePdf'])->name('secure');
        Route::post('/batch', [ExportController::class, 'generateBatchPdf'])->name('batch');
        Route::get('/progress/{batchId}', [ExportController::class, 'getBatchProgress'])->name('progress');
    });

    // CSV Export Routes
    Route::prefix('csv')->name('csv.')->group(function () {
        Route::get('/', [ExportController::class, 'csvIndex'])->name('index');
        Route::post('/preview', [ExportController::class, 'getFieldPreview'])->name('preview');
        Route::post('/generate', [ExportController::class, 'generateCsv'])->name('generate');

        // CSV Favorites Routes - RESTful resource pattern
        Route::prefix('favorites')->name('favorites.')->group(function () {
            Route::get('/', [ExportController::class, 'getFavorites'])->name('index');
            Route::post('/', [ExportController::class, 'saveFavorite'])->name('store');
            Route::get('/{favorite}', [ExportController::class, 'loadFavorite'])->name('show');
            Route::put('/{favorite}', [ExportController::class, 'updateFavorite'])->name('update');
            Route::delete('/{favorite}', [ExportController::class, 'deleteFavorite'])->name('destroy');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Facility Routes - All facility management including land information
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Main facility resource routes
    Route::resource('facilities', FacilityController::class);

    // Facility view mode management - with proper CSRF protection
    Route::post('/facilities/set-view-mode', [FacilityController::class, 'setViewMode'])
        ->middleware(['web', 'throttle:60,1'])
        ->name('facilities.set-view-mode');

    // Facility basic information routes
    Route::prefix('facilities/{facility}')->name('facilities.')->group(function () {
        Route::get('/edit-basic-info', [FacilityController::class, 'editBasicInfo'])->name('edit-basic-info');
        Route::put('/basic-info', [FacilityController::class, 'updateBasicInfo'])->name('update-basic-info');

        // Land Information nested routes
        Route::prefix('land-info')->name('land-info.')->group(function () {
            Route::get('/', [FacilityController::class, 'showLandInfo'])->name('show');
            Route::get('/edit', [FacilityController::class, 'editLandInfo'])->name('edit');
            Route::post('/', [FacilityController::class, 'updateLandInfo'])->name('create');
            Route::put('/', [FacilityController::class, 'updateLandInfo'])->name('update');
            Route::post('/calculate', [FacilityController::class, 'calculateLandFields'])->name('calculate');
            Route::get('/status', [FacilityController::class, 'getLandInfoStatus'])->name('status');
            Route::post('/approve', [FacilityController::class, 'approveLandInfo'])->name('approve');
            Route::post('/reject', [FacilityController::class, 'rejectLandInfo'])->name('reject');

            // Document management nested routes - RESTful pattern
            Route::prefix('documents')->name('documents.')->group(function () {
                Route::get('/', [FacilityController::class, 'getDocuments'])->name('index');
                Route::post('/', [FacilityController::class, 'uploadDocuments'])->name('store');
                Route::get('/{document}/download', [FacilityController::class, 'downloadDocument'])->name('download');
                Route::delete('/{document}', [FacilityController::class, 'deleteDocument'])->name('destroy');
            });
        });

        // Building Information nested routes
        Route::prefix('building-info')->name('building-info.')->group(function () {
            Route::get('/edit', [FacilityController::class, 'editBuildingInfo'])->name('edit');
            Route::put('/', [FacilityController::class, 'updateBuildingInfo'])->name('update');
        });

        // Lifeline Equipment nested routes
        Route::prefix('lifeline-equipment')->name('lifeline-equipment.')->group(function () {
            // Comprehensive API endpoints (must come before parameterized routes)
            Route::get('/', [LifelineEquipmentController::class, 'index'])->name('index');
            Route::put('/', [LifelineEquipmentController::class, 'bulkUpdate'])->name('bulk-update');
            Route::get('/summary', [LifelineEquipmentController::class, 'summary'])->name('summary');
            Route::get('/categories', [LifelineEquipmentController::class, 'categories'])->name('categories');
            Route::post('/multiple', [LifelineEquipmentController::class, 'getMultipleCategories'])->name('multiple');
            Route::post('/validate-consistency', [LifelineEquipmentController::class, 'validateConsistency'])->name('validate-consistency');

            // Individual category routes (must come after specific routes)
            Route::get('/{category}', [LifelineEquipmentController::class, 'show'])->name('show');
            Route::get('/{category}/edit', [LifelineEquipmentController::class, 'edit'])->name('edit');
            Route::put('/{category}', [LifelineEquipmentController::class, 'update'])->name('update');
            Route::get('/{category}/download/{type}', [LifelineEquipmentController::class, 'downloadFile'])->name('download-file');
        });

        // Security disaster equipment routes
        Route::prefix('security-disaster')->name('security-disaster.')->group(function () {
            Route::get('/edit', [\App\Http\Controllers\SecurityDisasterController::class, 'edit'])->name('edit');
            Route::put('/', [\App\Http\Controllers\SecurityDisasterController::class, 'update'])->name('update');
            Route::get('/download/{type}', [\App\Http\Controllers\SecurityDisasterController::class, 'downloadFile'])->name('download-file');
        });

        // Contracts routes
        Route::prefix('contracts')->name('contracts.')->group(function () {
            Route::get('/edit', [ContractsController::class, 'edit'])->name('edit');
            Route::put('/', [ContractsController::class, 'update'])->name('update');
        });

        // Repair history routes
        Route::prefix('repair-history')->name('repair-history.')->group(function () {
            Route::get('/', [\App\Http\Controllers\RepairHistoryController::class, 'index'])->name('index');
            Route::get('/{category}/edit', [\App\Http\Controllers\RepairHistoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [\App\Http\Controllers\RepairHistoryController::class, 'update'])->name('update');
        });

        // Drawings routes
        Route::prefix('drawings')->name('drawings.')->group(function () {
            Route::get('/edit', [\App\Http\Controllers\DrawingController::class, 'edit'])->name('edit');
            Route::put('/', [\App\Http\Controllers\DrawingController::class, 'update'])->name('update');
            Route::get('/download/{type}', [\App\Http\Controllers\DrawingController::class, 'downloadFile'])->name('download');
        });

        // Document management routes with enhanced security
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/', [\App\Http\Controllers\DocumentController::class, 'index'])->name('index');
            Route::get('/folders/{folder?}', [\App\Http\Controllers\DocumentController::class, 'show'])->name('show');
            
            // Performance optimization routes
            Route::get('/folders/{folder?}/virtual', [\App\Http\Controllers\DocumentController::class, 'showVirtual'])->name('folders.virtual');

            
            Route::get('/folder-tree', [\App\Http\Controllers\DocumentController::class, 'getFolderTree'])->name('folder-tree');
            Route::post('/folders', [\App\Http\Controllers\DocumentController::class, 'createFolder'])->name('folders.store');
            Route::put('/folders/{folder}', [\App\Http\Controllers\DocumentController::class, 'renameFolder'])->name('folders.update');
            Route::put('/folders/{folder}/move', [\App\Http\Controllers\DocumentController::class, 'moveFolder'])->name('folders.move');
            Route::get('/folders/{folder}/properties', [\App\Http\Controllers\DocumentController::class, 'getFolderProperties'])->name('folders.properties');
            Route::delete('/folders/{folder}', [\App\Http\Controllers\DocumentController::class, 'deleteFolder'])->name('folders.destroy');
            
            Route::post('/files', [\App\Http\Controllers\DocumentController::class, 'uploadFile'])->name('files.store');
            Route::get('/files/{file}/download', [\App\Http\Controllers\DocumentController::class, 'downloadFile'])->name('files.download');
            Route::get('/files/{file}/preview', [\App\Http\Controllers\DocumentController::class, 'previewFile'])->name('files.preview');
            Route::get('/files/{file}/properties', [\App\Http\Controllers\DocumentController::class, 'getFileProperties'])->name('files.properties');
            Route::put('/files/{file}/rename', [\App\Http\Controllers\DocumentController::class, 'renameFile'])->name('files.rename');
            Route::put('/files/{file}/move', [\App\Http\Controllers\DocumentController::class, 'moveFile'])->name('files.move');
            Route::delete('/files/{file}', [\App\Http\Controllers\DocumentController::class, 'deleteFile'])->name('files.destroy');
            
            Route::post('/preferences/reset', [\App\Http\Controllers\DocumentController::class, 'resetPreferences'])->name('preferences.reset');
        });

        // Facility-specific comment routes
        Route::prefix('comments')->name('comments.')->group(function () {
            Route::get('/', [CommentController::class, 'allFacilityComments'])->name('all');
            Route::get('/{section}', [CommentController::class, 'facilityComments'])->name('index');
            Route::post('/', [CommentController::class, 'store'])->name('store');
            Route::delete('/{comment}', [CommentController::class, 'destroyFacilityComment'])->name('destroy');
        });

        // Land info document download routes
        Route::get('/land-info/download/{type}', [FacilityController::class, 'downloadLandInfoPdf'])->name('land-info.download');
    });
});

/*
|--------------------------------------------------------------------------
| Comment Routes - Unified comment management system
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Comment management routes (custom routes first to avoid conflicts)
    Route::prefix('comments')->name('comments.')->group(function () {
        Route::get('/my-comments', [CommentController::class, 'myComments'])->name('my-comments');
        Route::get('/assigned', [CommentController::class, 'assigned'])->name('assigned');
        Route::get('/status-dashboard', [CommentController::class, 'statusDashboard'])->name('status-dashboard');
        Route::patch('/{comment}/status', [CommentController::class, 'updateStatus'])->name('update-status');
        Route::post('/bulk-update-status', [CommentController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
    });

    // Standard comment resource routes
    Route::resource('comments', CommentController::class);
});

/*
|--------------------------------------------------------------------------
| Notification Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('/recent', [NotificationController::class, 'getRecent'])->name('recent');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
        Route::put('/{notification}', [NotificationController::class, 'update'])->name('update');
    });
});

/*
|--------------------------------------------------------------------------
| My Page Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('my-page')->name('my-page.')->group(function () {
    Route::get('/', [MyPageController::class, 'index'])->name('index');
    Route::get('/my-comments', [CommentController::class, 'myComments'])->name('my-comments');
});

/*
|--------------------------------------------------------------------------
| Maintenance Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::resource('maintenance', MaintenanceController::class);
});

/*
|--------------------------------------------------------------------------
| Annual Confirmation Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Standard resource routes for annual confirmations
    Route::resource('annual-confirmation', AnnualConfirmationController::class);

    // Additional RESTful routes for annual confirmation actions
    Route::prefix('annual-confirmation')->name('annual-confirmation.')->group(function () {
        Route::post('/{annualConfirmation}/respond', [AnnualConfirmationController::class, 'respond'])->name('respond');
        Route::patch('/{annualConfirmation}/resolve', [AnnualConfirmationController::class, 'resolve'])->name('resolve');
        Route::get('/facilities/ajax', [AnnualConfirmationController::class, 'getFacilities'])->name('facilities');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes - Administrative functionality with middleware protection
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // User Management - placeholder routes (controllers to be implemented)
    Route::get('users', function () {
        return view('admin.users.index');
    })->name('users.index');
    Route::get('users/create', function () {
        return view('admin.users.create');
    })->name('users.create');
    Route::get('users/{user}', function ($user) {
        return view('admin.users.show', compact('user'));
    })->name('users.show');
    Route::get('users/{user}/edit', function ($user) {
        return view('admin.users.edit', compact('user'));
    })->name('users.edit');

    // System Settings - RESTful nested resource approach
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', function () {
            return view('admin.settings.index');
        })->name('index');
        Route::get('/general', function () {
            return view('admin.settings.general');
        })->name('general');
        Route::put('/general', function () {
            // Handle general settings update
        })->name('general.update');
        Route::get('/security', function () {
            return view('admin.settings.security');
        })->name('security');
        Route::put('/security', function () {
            // Handle security settings update
        })->name('security.update');
    });

    // Log Management - RESTful approach with nested resources
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', function () {
            return view('admin.logs.index');
        })->name('index');
        Route::get('/activity', function () {
            return view('admin.logs.activity');
        })->name('activity');
        Route::get('/system', function () {
            return view('admin.logs.system');
        })->name('system');
        Route::delete('/activity/{id}', function ($id) {
            // Handle activity log deletion
        })->name('activity.destroy');
        Route::delete('/system/{id}', function ($id) {
            // Handle system log deletion
        })->name('system.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Backward Compatibility Routes - Redirects for old URLs
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Legacy land-info routes redirect to new facility nested routes
    Route::prefix('land-info/{facility}')->group(function () {
        Route::get('/', function ($facility) {
            return redirect()->route('facilities.land-info.show', $facility, 301);
        })->name('land-info.show');
        Route::get('/edit', function ($facility) {
            return redirect()->route('facilities.land-info.edit', $facility, 301);
        })->name('land-info.edit');
        Route::post('/', function ($facility) {
            return redirect()->route('facilities.land-info.update', $facility, 301);
        })->name('land-info.update');
        Route::put('/', function ($facility) {
            return redirect()->route('facilities.land-info.update', $facility, 301);
        })->name('land-info.store');
        Route::post('/calculate', function ($facility) {
            return redirect()->route('facilities.land-info.calculate', $facility, 301);
        })->name('land-info.calculate');
        Route::post('/approve', function ($facility) {
            return redirect()->route('facilities.land-info.approve', $facility, 301);
        })->name('land-info.approve');
        Route::post('/reject', function ($facility) {
            return redirect()->route('facilities.land-info.reject', $facility, 301);
        })->name('land-info.reject');
    });

    // Legacy export routes redirect to new export structure
    Route::prefix('pdf-export')->group(function () {
        Route::get('/', function () {
            return redirect()->route('export.pdf.index', [], 301);
        })->name('pdf-export.index');
        Route::get('/facility/{facility}', function ($facility) {
            return redirect()->route('export.pdf.single', $facility, 301);
        })->name('pdf-export.single');
        Route::get('/secure/{facility}', function ($facility) {
            return redirect()->route('export.pdf.secure', $facility, 301);
        })->name('pdf-export.secure');
        Route::post('/batch', function () {
            return redirect()->route('export.pdf.batch', [], 301);
        })->name('pdf-export.batch');
    });

    Route::prefix('csv-export')->group(function () {
        Route::get('/', function () {
            return redirect()->route('export.csv.index', [], 301);
        })->name('csv-export.index');
        Route::post('/preview', function () {
            return redirect()->route('export.csv.preview', [], 301);
        })->name('csv-export.preview');
        Route::post('/generate', function () {
            return redirect()->route('export.csv.generate', [], 301);
        })->name('csv-export.generate');
        Route::get('/favorites', function () {
            return redirect()->route('export.csv.favorites.index', [], 301);
        })->name('csv-export.favorites');
    });

    // Legacy facility comment routes redirect to new unified structure
    Route::prefix('facility-comments/{facility}')->group(function () {
        Route::get('/{section}', function ($facility, $section) {
            return redirect()->route('facilities.comments.index', [$facility, $section], 301);
        })->name('facility-comments.index');
        Route::post('/', function ($facility) {
            return redirect()->route('facilities.comments.store', $facility, 301);
        })->name('facility-comments.store');
        Route::delete('/{comment}', function ($facility, $comment) {
            return redirect()->route('facilities.comments.destroy', [$facility, $comment], 301);
        })->name('facility-comments.destroy');
    });

    // Legacy admin routes (if any old structure existed)
    Route::get('/admin', function () {
        return redirect()->route('admin.users.index', [], 301);
    })->name('admin.index');
});

/*
|--------------------------------------------------------------------------
| Development/Debug Routes - Remove in production
|--------------------------------------------------------------------------
*/
if (app()->environment(['local', 'testing'])) {
    Route::get('/test-notifications', function () {
        if (! auth()->check()) {
            return response()->json(['error' => 'Not authenticated', 'user' => null]);
        }

        $user = auth()->user();
        $count = \App\Models\Notification::where('user_id', $user->id)->where('is_read', false)->count();

        return response()->json([
            'authenticated' => true,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'unread_count' => $count,
            'route_exists' => route('notifications.unread-count'),
        ]);
    })->middleware('auth');
}
