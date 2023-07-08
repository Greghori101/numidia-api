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
// Authorization:'Bearer '+ Token this for protected routes
//body:
// each route has its own required body

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

Route::middleware(['auth:api', 'verified'])->group(function () {
    WebSocketsRouter::webSocket(
        '/my-websocket',
        \App\CustomWebSocketHandler::class
    );
    Route::get('profile/{id}', [AuthController::class, 'show']);
    Route::post('profile/{id}/update', [AuthController::class, 'update']);

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
            Route::put('/', 'update');
            Route::delete('/{id}', 'delete');
        });

    Route::prefix('sessions')
        ->controller(SessionController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
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
            Route::post('/{student_id}/groups/{group_id}', 'student_group_add');
            Route::delete(
                '/{student_id}/groups/{group_id}',
                'student_group_remove'
            );
            Route::get('/{id}/groups/unenrolled', 'group_notin_student');
        });

    Route::prefix('teachers')
        ->controller(TeacherController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
            Route::post('/{id}/reject_session', 'reject_session');
            Route::post('/{id}/approve_session', 'approve_session');
        });
    Route::prefix('parents')
        ->controller(ParentController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
            Route::post('/add_student', 'add_student');
            Route::get('/students', 'students');
        });

    Route::prefix('notifications')
        ->controller(NotificationController::class)
        ->group(function () {
            Route::post('/send', 'send');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::get('/all', 'all');
            Route::put('/seen/{id}', 'seen');
            Route::put('/seen_all', 'seen_all');
            Route::delete('/delete/{id}', 'delete');
            Route::delete('/delete_all', 'delete_all');
        });
    Route::prefix('departments')
        ->controller(DepartmentController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
        });
    Route::prefix('levels')
        ->controller(LevelController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/departments', 'departments');
            Route::get('/departments/{id}', 'departments');
        });

    Route::prefix('groups')
        ->controller(GroupController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}/students', 'student_group');
            Route::get('/{id}/students/unenrolled', 'student_notin_group');
            Route::post('/{id}/students', 'group_student_add');
            Route::delete(
                '/{id}/students/{student_id}',
                'group_student_remove'
            );
        });

    Route::prefix('checkouts')
        ->controller(CheckoutController::class)
        ->group(function () {
            Route::get('/{id}/info', 'checkout_info');
            Route::get('', 'index');
            Route::get('/{id}', 'show');
            Route::get('/all', 'all');
            Route::post('/', 'create');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
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
