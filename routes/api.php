<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExampleController;
use App\Services\Telemetry\TracerService;
use Illuminate\Support\Facades\Http;

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


// Test tracing endpoint
Route::get('/test-trace', function (TracerService $tracer) {
    return $tracer->trace('test.operation', function ($span) use ($tracer) {
        // Add some nested operations
        $result1 = $tracer->trace('test.subroutine1', function ($childSpan) {
            sleep(1); // Simulate work
            return ['status' => 'completed-sab'];
        });

        // Make an external HTTP call
        $result2 = $tracer->traceHttpRequest(
            'GET',
            'https://jsonplaceholder.typicode.com/todos/1',
            [],
            function ($span) {
                return Http::get('https://jsonplaceholder.typicode.com/todos/1')->json();
            }
        );

        return [
            'message' => 'Trace test completed',
            'result1' => $result1,
            'result2' => $result2
        ];
    });
});

// Simple trace endpoint
Route::get('/simple-trace', function (App\Services\Telemetry\TracerService $tracer) {
    return $tracer->trace('simple.operation', function ($span) {
        // Simulate a quick operation
        usleep(100000); // 100ms
        return [
            'message' => 'Simple trace completed',
            'timestamp' => now()->toDateTimeString(),
        ];
    });
});
