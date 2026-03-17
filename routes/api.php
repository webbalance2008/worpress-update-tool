<?php

use App\Http\Controllers\AgentApiController;
use App\Http\Middleware\VerifyAgentHmac;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent API Routes
|--------------------------------------------------------------------------
|
| These routes are called by the WordPress agent plugin.
| Registration uses a one-time token. All other endpoints use HMAC auth.
|
*/

Route::prefix('agent')->group(function () {

    // Registration does NOT use HMAC — uses one-time token instead
    Route::post('/register', [AgentApiController::class, 'register']);

    // All other agent endpoints require HMAC signature verification
    Route::middleware(VerifyAgentHmac::class)->group(function () {
        Route::post('/heartbeat', [AgentApiController::class, 'heartbeat']);
        Route::post('/sync', [AgentApiController::class, 'sync']);
        Route::post('/update-result', [AgentApiController::class, 'updateResult']);
        Route::post('/error-report', [AgentApiController::class, 'errorReport']);
    });
});
