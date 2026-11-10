<?php

use App\Http\Controllers\AccountsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\BotNetController;
use App\Http\Controllers\FileSystemController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RealtorController;
use App\Http\Controllers\ResolveController;
use App\Http\Controllers\SalesController;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Models\User;
use Livewire\Attributes\Reactive;

Route::prefix('v1/')->group(function () {

    Route::post('apps/data/{xnamespace}', [AuthController::class, 'app_data']);
    // Public routes (throttled against brute force)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('app/boot/{xnamespace}', [AuthController::class, 'boot_xapp']);
        Route::post('auth/login', [AuthController::class, 'login'])->withoutMiddleware(EnsureTokenIsValid::class);
        Route::post('auth/register', [AuthController::class, 'register'])->withoutMiddleware(EnsureTokenIsValid::class);
        Route::post('engine/resolve', [AuthController::class, 'resolve']);
        Route::POST('auth/register-device', [AuthController::class, 'register_device']);
        Route::POST('device/email/activate/{device_key}', [AuthController::class, 'email_device_activation']);
        Route::POST('auth/device-activation', [AuthController::class, 'device_activation']);
        Route::post('client/store/{uuid}/devices/safecode', [BotNetController::class, 'store_find_bot']);
    });

    // Protected routes requiring a valid Sanctum Bearer token
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/user/profile', [AuthController::class, 'profile'])->withoutMiddleware(EnsureTokenIsValid::class);

        Route::post('products/get/all', [ProductController::class, 'get_all_products']);
        Route::post('products/store/{uuid}/all', [ProductController::class, 'get_my_products']);
        Route::post('products/save/{uuid}', [ProductController::class, 'save_products']);
        Route::post('products/find/{uuid}', [ProductController::class, 'find_product']);
        Route::post('products/upload/media/{uuid}', [ProductController::class, 'upload_product_media']);
        Route::post('products/delete/{uuid}', [ProductController::class, 'delete_product']);

        Route::post('client/store/sales/{uuid}/all', [SalesController::class, 'store_sales']);
        Route::post('client/staffs/{uuid}/all', [AccountsController::class, 'store_staffs']);

        Route::post('client/store/{uuid}/devices/create', [BotNetController::class, 'create_device']);
        Route::post('client/store/{uuid}/devices/all', [BotNetController::class, 'store_devices']);

        Route::get('auth/verify', [AuthController::class, 'verifyToken']); // Quick ping for X-POS connection checks
        Route::get('auth/devices', [AuthController::class, 'activeDevices']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::delete('auth/devices/{tokenId}', [AuthController::class, 'revokeDevice']);
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('user/new_signature', [FileSystemController::class, 'new_signature']);
        Route::get('products/all', [AuthController::class, 'getProducts']);
        Route::get('staffs/all', [AuthController::class, 'getStaffs']);
        Route::get('resolve/{model}/{uuid}', [ResolveController::class, 'show']);
        Route::POST('new-sale', [SalesController::class, 'sale_new'])->name('sale_new');
        Route::get('sales/{token}', [SalesController::class, 'sales'])->name('account.sales');


        Route::post('realtor/client/save', [RealtorController::class, 'save_client'])->name('realtor.client.save');
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
