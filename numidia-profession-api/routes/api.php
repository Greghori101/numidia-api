<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\LevelController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\DashboardController;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//headers :
//'Content-type' : 'application/json',
// Accept:application/json
// Authorization:'Bearer '+ Token for protected routes
//body:
// each route with method post
//params:
// each route with methods put or get 

//Public Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'store']);
Route::post('/auth/{provider}/login', [
    AuthController::class,
    'provider_login',
]);
Route::post('/password/forgot', [AuthController::class, 'forgotpassword']);
Route::post('/password/reset', [AuthController::class, 'restpassword']);

//Protected Routes
Route::middleware(['auth:api'])->group(function () {
    Route::get('logout', [AuthController::class, 'logout']);
    Route::post('/email/verify', [AuthController::class, 'verify']);
    Route::post('/email/resent/code', [
        AuthController::class,
        'resent_verification',
    ]);
    Route::get('/email/isverified', [AuthController::class, 'email_verified']);
});


// Verfied routes (require email verification)
Route::middleware(['auth:api', 'verified'])->group(function () {
    WebSocketsRouter::webSocket(
        '/my-websocket',
        \App\CustomWebSocketHandler::class
    );
    Route::get('profile/{id}', [AuthController::class, 'show']);
    Route::put('profile/{id}', [AuthController::class, 'update']);

    Route::controller(DashboardController::class)->group(function () {
        Route::get('/', 'index');
    });

    Route::prefix('posts')
        ->controller(PostController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
        });

    Route::prefix('users')
        ->controller(UserController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/list', 'users_list');
            Route::get('/{id}', 'show');
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });

    Route::prefix('sessions')
        ->controller(SessionController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/{id}/exception', 'except');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
    Route::prefix('levels')
        ->controller(LevelController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });

    Route::prefix('groups')
        ->controller(GroupController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}/students', 'students');
            Route::get('/{id}/students/unenrolled', 'student_notin_group');
            Route::post('/{id}/students', 'students_create');
            Route::delete(
                '/{id}/students/{student_id}',
                'students_delete'
            );
            Route::get('/{id}/sessions', 'sessions');
            Route::post('/{id}/sessions', 'sessions_create');
        });
    Route::prefix('students')
        ->controller(StudentController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
            Route::get('/{id}/groups', 'student_group');
            Route::get('/{id}/groups/unenrolled', 'group_notin_student');
            Route::post('/{student_id}/groups/{group_id}', 'student_group_add');
            Route::delete(
                '/{student_id}/groups/{group_id}',
                'student_group_remove'
            );
        });

    Route::prefix('teachers')
        ->controller(TeacherController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
            Route::post('/sessions/{id}/reject', 'reject_session');
            Route::post('/sessions/{id}/approve', 'approve_session');
        });

    Route::prefix('parents')
        ->controller(ParentController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
            Route::post('/{id}/students', 'add_student');
            Route::get('/{id}/students', 'students');
        });

    Route::prefix('notifications')
        ->controller(NotificationController::class)
        ->group(function () {
            Route::post('/send', 'send');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::get('/all', 'all');
            Route::put('/seen/{id}', 'seen');
            Route::put('/seen/all', 'seen_all');
            Route::delete('/delete/{id}', 'delete');
            Route::delete('/clear', 'delete_all');
        });

    Route::prefix('departments')
        ->controller(DepartmentController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
        });

    Route::prefix('checkouts')
        ->controller(CheckoutController::class)
        ->group(function () {
            Route::get('', 'index');
            Route::get('stats', 'get_stats');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::delete('/{id}', 'delete');

        });

    Route::prefix('receipts')
        ->controller(ReceiptController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
        });
});
