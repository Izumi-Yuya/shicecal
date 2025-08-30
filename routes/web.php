<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfExportController;
use App\Http\Controllers\CsvExportController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FacilityController;
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
    return view('welcome');
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
    
    // Comment Routes
    Route::prefix('comments')->name('comments.')->group(function () {
        Route::post('/', [CommentController::class, 'store'])->name('store');
        Route::get('/', [CommentController::class, 'index'])->name('index');
        Route::patch('/{comment}/status', [CommentController::class, 'updateStatus'])->name('update-status');
        Route::get('/my-comments', [CommentController::class, 'myComments'])->name('my-comments');
        Route::get('/assigned', [CommentController::class, 'assignedComments'])->name('assigned');
        Route::get('/status-dashboard', [CommentController::class, 'statusDashboard'])->name('status-dashboard');
        Route::post('/bulk-update-status', [CommentController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
    });
    
    // Notification Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])->name('unread-count');
        Route::get('/recent', [NotificationController::class, 'getRecent'])->name('recent');
    });
    
    // My Page Routes
    Route::prefix('my-page')->name('my-page.')->group(function () {
        Route::get('/', [MyPageController::class, 'index'])->name('index');
        Route::get('/my-comments', [MyPageController::class, 'myComments'])->name('my-comments');
        Route::get('/activity-summary', [MyPageController::class, 'activitySummary'])->name('activity-summary');
    });
    
    // Maintenance History Routes
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/', [MaintenanceController::class, 'index'])->name('index');
        Route::get('/create', [MaintenanceController::class, 'create'])->name('create');
        Route::post('/', [MaintenanceController::class, 'store'])->name('store');
        
        // Search Favorites Routes (must be before parameterized routes)
        Route::get('/search-favorites', [MaintenanceController::class, 'getSearchFavorites'])->name('search-favorites.index');
        Route::post('/search-favorites', [MaintenanceController::class, 'saveSearchFavorite'])->name('search-favorites.store');
        Route::get('/search-favorites/{favorite}', [MaintenanceController::class, 'loadSearchFavorite'])->name('search-favorites.show');
        Route::put('/search-favorites/{favorite}', [MaintenanceController::class, 'updateSearchFavorite'])->name('search-favorites.update');
        Route::delete('/search-favorites/{favorite}', [MaintenanceController::class, 'deleteSearchFavorite'])->name('search-favorites.destroy');
        
        Route::get('/facility/{facility}/histories', [MaintenanceController::class, 'getFacilityHistories'])->name('facility.histories');
        
        // Parameterized routes must be last
        Route::get('/{maintenanceHistory}', [MaintenanceController::class, 'show'])->name('show');
        Route::get('/{maintenanceHistory}/edit', [MaintenanceController::class, 'edit'])->name('edit');
        Route::put('/{maintenanceHistory}', [MaintenanceController::class, 'update'])->name('update');
        Route::delete('/{maintenanceHistory}', [MaintenanceController::class, 'destroy'])->name('destroy');
    });
    
    // Annual Confirmation Routes
    Route::prefix('annual-confirmation')->name('annual-confirmation.')->group(function () {
        Route::get('/', [AnnualConfirmationController::class, 'index'])->name('index');
        Route::get('/create', [AnnualConfirmationController::class, 'create'])->name('create')->middleware('role:admin');
        Route::post('/', [AnnualConfirmationController::class, 'store'])->name('store')->middleware('role:admin');
        Route::get('/facilities', [AnnualConfirmationController::class, 'getFacilities'])->name('facilities');
        Route::get('/{annualConfirmation}', [AnnualConfirmationController::class, 'show'])->name('show');
        Route::post('/{annualConfirmation}/respond', [AnnualConfirmationController::class, 'respond'])->name('respond');
        Route::patch('/{annualConfirmation}/resolve', [AnnualConfirmationController::class, 'resolve'])->name('resolve');
    });
    
    // Admin Routes (Log Management)
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::prefix('logs')->name('logs.')->group(function () {
            Route::get('/', [App\Http\Controllers\ActivityLogController::class, 'index'])->name('index');
            Route::get('/export/csv', [App\Http\Controllers\ActivityLogController::class, 'exportCsv'])->name('export.csv');
            Route::get('/export/audit-report', [App\Http\Controllers\ActivityLogController::class, 'exportAuditReport'])->name('export.audit-report');
            Route::get('/api/recent', [App\Http\Controllers\ActivityLogController::class, 'recent'])->name('recent');
            Route::get('/api/statistics', [App\Http\Controllers\ActivityLogController::class, 'statistics'])->name('statistics');
            Route::get('/{activityLog}', [App\Http\Controllers\ActivityLogController::class, 'show'])->name('show');
        });
    });
});
