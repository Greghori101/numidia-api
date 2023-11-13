<?php

use App\Http\Controllers\AmphiController;
use App\Http\Controllers\DawaratController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\TicketController;
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
Route::post('/create-user', [AuthController::class, 'create_user']);


Route::middleware(['auth-api-token',])->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);

    Route::prefix('amphis')
        ->controller(AmphiController::class)
        ->group(function () {
            Route::get('/all', 'all');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
    Route::prefix('dawarat')
        ->controller(DawaratController::class)
        ->group(function () {
            Route::get('/all', 'all');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
    Route::prefix('presences')
        ->controller(PresenceController::class)
        ->group(function () {
            Route::get('/all', 'all');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
    Route::prefix('tickets')
        ->controller(TicketController::class)
        ->group(function () {
            Route::get('/all', 'all');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
    Route::prefix('users')
        ->controller(UserController::class)
        ->group(function () {
            Route::get('/all', 'all');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
});
