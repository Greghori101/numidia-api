<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;
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


// Route::middleware(['client'])->group(function () {
// });

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/auth/{provider}/login', [AuthController::class, 'provider_login',]);
Route::post('/password/forgot', [AuthController::class, 'forgotpassword']);
Route::post('/password/reset', [AuthController::class, 'restpassword']);
Route::post('/users/create', [AuthController::class, 'create']);
Route::get('/logout', [AuthController::class, 'logout']);
Route::post('/email/verify', [AuthController::class, 'verify']);
Route::post('/email/resent/code', [AuthController::class, 'resent_verification',]);
Route::get('/email/isverified', [AuthController::class, 'email_verified']);
Route::get('/profile/{id}', [AuthController::class, 'show']);
Route::delete('/activities/revoke/{id}', [AuthController::class, 'revoke']);
Route::delete('/activities/clear', [AuthController::class, 'clear_activities']);
Route::delete('/users', [AuthController::class, 'users']);

Route::prefix('posts')
    ->controller(PostController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
    });



Route::middleware(['auth:api'])->group(function () {
    Route::get('/verify-token', [AuthController::class, 'verify_token']);
    Route::post('/password/change', [AuthController::class, 'change_password']);
    Route::post('/picture/change/{id?}', [AuthController::class, 'change_profile_picture']);
    Route::prefix('posts')
        ->controller(PostController::class)
        ->group(function () {
            Route::post('/', 'create');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
        });

    Route::prefix('notifications')
        ->controller(NotificationController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::put('/seen/all',  'seen_all');
            Route::delete('/clear',  'delete_all');
            Route::get('/all',  'all');
            Route::post('/', 'send');
            Route::get('/{id}', 'show');
            Route::put('/seen/{id}', 'seen');
            Route::delete('/{id}', 'delete');
        });
});
