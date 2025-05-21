<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DebugController;

Route::get('/', function () {
    return view('welcome');
});

// Debug routes for demonstrating errors and exceptions
Route::prefix('debug')->group(function () {
    Route::get('/', [DebugController::class, 'index'])->name('debug.index');
    Route::get('/division-by-zero', [DebugController::class, 'divisionByZero'])->name('debug.division-by-zero');
    Route::get('/undefined-variable', [DebugController::class, 'undefinedVariable'])->name('debug.undefined-variable');
    Route::get('/type-error', [DebugController::class, 'typeError'])->name('debug.type-error');
    Route::get('/out-of-bounds', [DebugController::class, 'outOfBounds'])->name('debug.out-of-bounds');
    Route::get('/logic-exception', [DebugController::class, 'logicException'])->name('debug.logic-exception');
    Route::get('/runtime-exception', [DebugController::class, 'runtimeException'])->name('debug.runtime-exception');
    Route::get('/query-exception', [DebugController::class, 'queryException'])->name('debug.query-exception');
    Route::get('/http-exception', [DebugController::class, 'httpException'])->name('debug.http-exception');
    Route::get('/memory-limit', [DebugController::class, 'memoryLimit'])->name('debug.memory-limit');
    Route::get('/parse-error-example', [DebugController::class, 'parseErrorExample'])->name('debug.parse-error-example');
    Route::get('/fatal-error', [DebugController::class, 'fatalError'])->name('debug.fatal-error');
    Route::get('/custom-exception', [DebugController::class, 'customException'])->name('debug.custom-exception');
    Route::get('/random-error', [DebugController::class, 'randomError'])->name('debug.random-error');
});
