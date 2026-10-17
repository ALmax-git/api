<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\FileSystemController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ResolveController;
use App\Http\Controllers\SalesController;
use App\Models\User;

Route::prefix('v1/')->group(function () {

    Route::get('/products/get/all', function () {
        abort_if(request()->header('X-Client-UUID') !== 'f1e2d3c4-b5a6-7890-1234-56789abcdef0', 401, 'Unauthorized');

        return response()->json([
            'products' => \App\Models\Product::all(),
        ]);
    });
    // Public routes (throttled against brute force)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('app/boot/{xnamespace}', [AuthController::class, 'boot_xapp']);
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('engine/resolve', [AuthController::class, 'resolve']);
        Route::POST('auth/register-device', [AuthController::class, 'register_device']);
        Route::POST('auth/device-activation', [AuthController::class, 'device_activation']);
    });

    // Protected routes requiring a valid Sanctum Bearer token
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/user/profile', [AuthController::class, 'profile']);
        Route::post('products/get/all', [ProductController::class, 'get_all_products']);
        Route::get('auth/verify', [AuthController::class, 'verifyToken']); // Quick ping for X-POS connection checks
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('user/new_signature', [FileSystemController::class, 'new_signature']);
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('auth/devices', [AuthController::class, 'activeDevices']);
        Route::delete('auth/devices/{tokenId}', [AuthController::class, 'revokeDevice']);
        Route::get('products/all', [AuthController::class, 'getProducts']);
        Route::get('staffs/all', [AuthController::class, 'getStaffs']);
        Route::get('resolve/{model}/{uuid}', [ResolveController::class, 'show']);
        Route::POST('new-sale', [SalesController::class, 'sale_new'])->name('sale_new');
        Route::get('sales/{token}', [SalesController::class, 'sales'])->name('account.sales');

        Route::get('realtor/rsa/{user}', function ($user) {
            $user = User::find(get_id(urldecode($user), 'user'));
            if ($user) {
                return view('api.resolve.rsa', [
                    'user' => $user,
                ]);
            } else {
                return 'Record Not Found';
            }
        })->name('api.v1.realtor.rsa');

        Route::get('realtor/pfa/{user}', function ($user) {
            $user = User::find(get_id(urldecode($user), 'user'));
            if ($user) {
                return view('api.resolve.pfa_galary', [
                    'user' => $user,
                ]);
            } else {
                return 'Record Not Found';
            }
        })->name('api.v1.realtor.pfa');
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
