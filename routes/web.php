<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfExportController;
use App\Http\Controllers\CsvExportController;

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

// Basic login route for testing
Route::get('/login', function () {
    return response('Login page', 200);
})->name('login');

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
    });
});
