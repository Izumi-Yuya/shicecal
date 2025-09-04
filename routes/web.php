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
});
