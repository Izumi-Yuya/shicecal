<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfExportController;
use App\Http\Controllers\CsvExportController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\LandInfoController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\AnnualConfirmationController;

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

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

// Export Routes
Route::middleware(['auth'])->group(function () {
    // PDF Export Routes
    Route::prefix('export/pdf')->name('pdf.export.')->group(function () {
        Route::get('/', [PdfExportController::class, 'index'])->name('index');
        Route::get('/facility/{facility}', [PdfExportController::class, 'generateSingle'])->name('single');
        Route::get('/secure/{facility}', [PdfExportController::class, 'generateSecureSingle'])->name('secure.single');
        Route::post('/batch', [PdfExportController::class, 'generateBatch'])->name('batch');
        Route::get('/progress/{batchId}', [PdfExportController::class, 'getBatchProgress'])->name('progress');
    });

    // CSV Export Routes
    Route::prefix('export/csv')->name('csv.export.')->group(function () {
        Route::get('/', [CsvExportController::class, 'index'])->name('index');
        Route::post('/preview', [CsvExportController::class, 'getFieldPreview'])->name('preview');
        Route::post('/generate', [CsvExportController::class, 'generateCsv'])->name('generate');

        // Favorites Routes
        Route::get('/favorites', [CsvExportController::class, 'getFavorites'])->name('favorites.index');
        Route::post('/favorites', [CsvExportController::class, 'saveFavorite'])->name('favorites.store');
        Route::get('/favorites/{id}', [CsvExportController::class, 'loadFavorite'])->name('favorites.show');
        Route::put('/favorites/{id}', [CsvExportController::class, 'updateFavorite'])->name('favorites.update');
        Route::delete('/favorites/{id}', [CsvExportController::class, 'deleteFavorite'])->name('favorites.destroy');
    });

    // Facility Routes
    Route::resource('facilities', FacilityController::class);
    Route::get('/facilities/{facility}/basic-info', [FacilityController::class, 'basicInfo'])->name('facilities.basic-info');
    Route::get('/facilities/{facility}/edit-basic-info', [FacilityController::class, 'editBasicInfo'])->name('facilities.edit-basic-info');
    Route::put('/facilities/{facility}/basic-info', [FacilityController::class, 'updateBasicInfo'])->name('facilities.update-basic-info');

    // Land Information Routes
    Route::prefix('facilities/{facility}/land-info')->name('facilities.land-info.')->group(function () {
        Route::get('/', [App\Http\Controllers\LandInfoController::class, 'show'])->name('show');
        Route::get('/edit', [App\Http\Controllers\LandInfoController::class, 'edit'])->name('edit');
        Route::post('/', [App\Http\Controllers\LandInfoController::class, 'update'])->name('create');
        Route::put('/', [App\Http\Controllers\LandInfoController::class, 'update'])->name('update');
        Route::post('/calculate', [App\Http\Controllers\LandInfoController::class, 'calculateFields'])->name('calculate');
        Route::get('/status', [App\Http\Controllers\LandInfoController::class, 'getStatus'])->name('status');
        Route::post('/approve', [App\Http\Controllers\LandInfoController::class, 'approve'])->name('approve');
        Route::post('/reject', [App\Http\Controllers\LandInfoController::class, 'reject'])->name('reject');

        // File management routes
        Route::post('/documents', [App\Http\Controllers\LandInfoController::class, 'uploadDocuments'])->name('documents.upload');
        Route::get('/documents', [App\Http\Controllers\LandInfoController::class, 'getDocuments'])->name('documents.index');
        Route::get('/documents/{fileId}/download', [App\Http\Controllers\LandInfoController::class, 'downloadDocument'])->name('documents.download');
        Route::delete('/documents/{fileId}', [App\Http\Controllers\LandInfoController::class, 'deleteDocument'])->name('documents.delete');
    });

    // Notification Routes
    Route::resource('notifications', NotificationController::class)->only(['index', 'show', 'update']);

    // My Page Routes
    Route::get('/my-page', [MyPageController::class, 'index'])->name('my-page.index');

    // Maintenance Routes
    Route::resource('maintenance', MaintenanceController::class);

    // Annual Confirmation Routes
    Route::resource('annual-confirmation', AnnualConfirmationController::class);

    // Comment Routes
    Route::resource('comments', CommentController::class);
    Route::get('/comments/my-comments', [CommentController::class, 'myComments'])->name('comments.my-comments');
    Route::get('/comments/assigned', [CommentController::class, 'assigned'])->name('comments.assigned');
    Route::get('/comments/status-dashboard', [CommentController::class, 'statusDashboard'])->name('comments.status-dashboard');
    Route::patch('/comments/{comment}/status', [CommentController::class, 'updateStatus'])->name('comments.update-status');
    Route::post('/comments/bulk-update-status', [CommentController::class, 'bulkUpdateStatus'])->name('comments.bulk-update-status');
    Route::get('/comments/assigned', [CommentController::class, 'assignedComments'])->name('comments.assigned');
    Route::get('/comments/status-dashboard', [CommentController::class, 'statusDashboard'])->name('comments.status-dashboard');
    Route::post('/comments/bulk-update-status', [CommentController::class, 'bulkUpdateStatus'])->name('comments.bulk-update-status');
    Route::put('/comments/{comment}/status', [CommentController::class, 'updateStatus'])->name('comments.update-status');

    // Admin Routes (for admin users only)
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        // User Management
        Route::get('/users', function () {
            return view('admin.users.index');
        })->name('users.index');
        Route::get('/users/create', function () {
            return view('admin.users.create');
        })->name('users.create');
        Route::get('/users/{user}', function () {
            return view('admin.users.show');
        })->name('users.show');
        Route::get('/users/{user}/edit', function () {
            return view('admin.users.edit');
        })->name('users.edit');

        // System Settings
        Route::get('/settings', function () {
            return view('admin.settings.index');
        })->name('settings.index');
        Route::get('/settings/general', function () {
            return view('admin.settings.general');
        })->name('settings.general');
        Route::get('/settings/security', function () {
            return view('admin.settings.security');
        })->name('settings.security');

        // Log Management
        Route::get('/logs', function () {
            return view('admin.logs.index');
        })->name('logs.index');
        Route::get('/logs/activity', function () {
            return view('admin.logs.activity');
        })->name('logs.activity');
        Route::get('/logs/system', function () {
            return view('admin.logs.system');
        })->name('logs.system');
    });

    // Additional missing routes
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
});
