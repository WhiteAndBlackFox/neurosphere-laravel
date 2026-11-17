<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GenerationController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::post('/v1/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/generations', [GenerationController::class, 'store']);
    Route::get('/generations/{id}', [GenerationController::class, 'show']);
    Route::get('/generations', [GenerationController::class, 'index']);
    Route::get('/subscription/quota', [SubscriptionController::class, 'quota']);
});
