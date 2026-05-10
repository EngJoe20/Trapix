<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultController;
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

// ── Authenticated dashboard & settings ────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/history', [DashboardController::class, 'history'])->name('dashboard.history');

    // AI Integrations
    Route::get('/settings/ai-integrations', [\App\Http\Controllers\AiIntegrationController::class, 'index'])->name('settings.ai-integrations');
    Route::post('/settings/ai-integrations/save', [\App\Http\Controllers\AiIntegrationController::class, 'save'])->name('settings.ai-integrations.save');
    Route::post('/settings/ai-integrations/test', [\App\Http\Controllers\AiIntegrationController::class, 'test'])->name('settings.ai-integrations.test');
    Route::delete('/settings/ai-integrations/delete', [\App\Http\Controllers\AiIntegrationController::class, 'destroy'])->name('settings.ai-integrations.delete');
});

// ── Analysis result page ──────────────────────────────────────────────────────
Route::get('/analysis/{jobId}', [\App\Http\Controllers\ResultController::class, 'show'])
    ->name('analysis.result.page');

// ── Report download (auth + guest-by-token) ───────────────────────────────────
Route::get('/analysis/{jobId}/report', [\App\Http\Controllers\ReportController::class, 'download'])
    ->name('analysis.report');

Route::get('/analysis/{jobId}/export-zip', [\App\Http\Controllers\ReportController::class, 'exportZip'])
    ->name('analysis.report.export-zip');

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

    Route::post('/analysis/{jobId}/collaboration', [\App\Http\Controllers\JobCollaborationController::class, 'update'])
        ->name('analysis.collaboration');

    // ── On-demand AI analysis (authenticated only) ────────────────────────────
    Route::middleware(['auth'])->post('/analysis/{jobId}/run-ai', [AnalysisController::class, 'runAi'])
        ->name('analysis.run-ai');

    // ── Dashboard quota ───────────────────────────────────────────────────────
    Route::get('/dashboard/quota', [DashboardController::class, 'quota'])
        ->name('dashboard.quota');

});
