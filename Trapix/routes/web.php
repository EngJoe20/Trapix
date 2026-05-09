<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Public pages ─────────────────────────────────────────────────────────────
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/pricing', function () {
    return view('pricing');
})->name('pricing');

Route::get('/docs', function () {
    return view('docs');
})->name('docs');

// ── Analysis (public — quota enforced in middleware/controller) ───────────────
Route::get('/analyze', function () {
    return view('analyze');
})->name('analyze');

// ── Authenticated dashboard ───────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/history', [DashboardController::class, 'history'])->name('dashboard.history');
});

// ── Report download (auth + guest-by-token) ───────────────────────────────────
Route::get('/analysis/{jobId}/report', [ReportController::class, 'download'])
    ->name('analysis.report');

// ── Auth routes (Breeze) ──────────────────────────────────────────────────────
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api')->name('api.')->group(function () {
    // ── Analysis jobs ─────────────────────────────────────────────────────────
    Route::post('/analysis', [AnalysisController::class, 'createJob'])
        ->middleware(['throttle:10,1'])  // 10 uploads per minute
        ->name('analysis.create');

    Route::get('/analysis/{id}', [AnalysisController::class, 'status'])
        ->name('analysis.status');

    Route::get('/analysis/{id}/result', [AnalysisController::class, 'result'])
        ->name('analysis.result');

    Route::get('/analysis/{jobId}/report', [ReportController::class, 'download'])
        ->name('analysis.report');

    // ── Dashboard quota ───────────────────────────────────────────────────────
    Route::get('/dashboard/quota', [DashboardController::class, 'quota'])
        ->name('dashboard.quota');
});
