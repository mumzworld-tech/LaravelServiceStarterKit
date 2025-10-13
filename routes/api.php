<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// OpenTelemetry test routes (from package)
if (app()->environment(['local', 'testing'])) {
    require __DIR__ . '/opentelemetry-test.php';
}

// Example API routes
Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('/health', function () {
        return response()->json(['status' => 'ok']);
    });
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Your protected API endpoints here
    });
}); 