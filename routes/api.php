<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Chat
    Route::get('/chats',                [ChatController::class, 'index']);
    Route::post('/chats',               [ChatController::class, 'store']);
    Route::get('/chats/{chat}',         [ChatController::class, 'show']);
    Route::put('/chats/{chat}',         [ChatController::class, 'update']);
    Route::delete('/chats/{chat}',      [ChatController::class, 'destroy']);
    Route::post('/chats/{chat}/send',   [ChatController::class, 'send']);
});
