<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    echo view('dashboard', [
        'user' => App\Models\User::first(), 
        'plan' => null, 
        'remaining' => null, 
        'jobs' => App\Models\AnalysisJob::paginate(10)
    ])->render(); 
} catch (\Throwable $e) { 
    echo 'ERROR: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(); 
}
