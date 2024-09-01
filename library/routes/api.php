<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\UserController;
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

Route::post('/login', [AuthController::class, 'login']);
Route::post('/guest-order', [UserController::class, 'create']);
Route::post('/create-user', [AuthController::class, 'create_user_department']);
Route::get('/files', [AuthController::class,'getFile']);

Route::prefix('products')
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show_product');
    });

Route::middleware(['auth-api-token'])->group(function () {
    Route::post('/create-user/{id}', [AuthController::class, 'create_user']);
    Route::get('/user.verify/{id}', [AuthController::class, 'verify_user_existence']);

    Route::get('/logout', [AuthController::class, 'logout']);
    Route::prefix('clients')
        ->controller(UserController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'store');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
            Route::get('/orders', 'getOrders');
            Route::get('/receipts', 'getReceipts');
        });
    Route::prefix('orders')
        // ->middleware(['role:admin'])
        ->controller(OrderController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show_order_products');
            Route::get('/{id}/receipt', 'order_receipt');
            Route::post('/{id}/pay', 'order_pay');
            Route::put('/{id}', 'order_status');
            Route::delete('/{id}', 'delete_order');
            Route::post('/clients/{id}', 'create_order_client');
            Route::post('/',  'create_client');
        });
    Route::prefix('products')
        // ->middleware(['role:admin'])
        ->controller(ProductController::class)
        ->group(function () {
            Route::post('/', 'create_product');
            Route::post('/{id}', 'update_product');
            Route::delete('/{id}', 'delete_product');
        });
    Route::prefix('receipts')
        // ->middleware(['role:admin'])
        ->controller(ReceiptController::class)
        ->group(function () {
            Route::post('/', 'create_receipt');
        });
});
