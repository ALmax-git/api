<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;

Route::prefix('v1/')->group(function () {
    // Public routes (throttled against brute force)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/login', [AuthController::class, 'login']);
        // Route::POST('auth/register-device', [AuthController::class, 'register_device']);
        // Route::POST('auth/device-activation', [AuthController::class, 'device_activation']);
    });

    // Protected routes requiring a valid Sanctum Bearer token
    // Route::middleware('auth:sanctum')->group(function () {
    //     Route::get('auth/me', [AuthController::class, 'me']);
    //     Route::get('auth/verify', [AuthController::class, 'verifyToken']); // Quick ping for X-POS connection checks
    //     Route::post('auth/logout', [AuthController::class, 'logout']);
    //     Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);
    //     Route::get('auth/devices', [AuthController::class, 'activeDevices']);
    //     Route::delete('auth/devices/{tokenId}', [AuthController::class, 'revokeDevice']);
    //     Route::get('products/all', [AuthController::class, 'getProducts']);
    //     Route::get('staffs/all', [AuthController::class, 'getStaffs']);
    // });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
