<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\LevelController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TeacherController;
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



//Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'store']);
Route::post('/auth/{provider}/login', [AuthController::class, 'provider_login']);
Route::post('/auth/{provider}/register', [AuthController::class, 'provider_store']);
Route::post('/forgotpassword', [AuthController::class, 'forgotpassword']);


//Protected routes

Route::middleware(['auth:api'])->group(function () {
    Route::get('logout', [AuthController::class, 'logout']);
    Route::post('/email/verify', [AuthController::class, 'verify']);
    Route::post('/email/resent/code', [AuthController::class, 'resent_verification']);
    Route::get('/email/isverified', [AuthController::class, 'email_verified']);

});

//email verification required
Route::middleware(['auth:api', 'verified'])->group(function () {

    WebSocketsRouter::webSocket('/my-websocket', \App\CustomWebSocketHandler::class);
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(PostController::class)->group(function () {
        Route::get('posts/{id?}', 'index');
        Route::post('posts/create', 'create');
        Route::delete('posts/{id}/delete', 'delete');
        Route::put('posts/{id}/update', 'update');
    });

    Route::get('profile/{id}', [AuthController::class, 'show']);
    Route::post('profile/{id}/update', [AuthController::class, 'update']);

    /*
     *
     * for each permission you have to sepcify the role in params
     *
    */

    Route::middleware('permission:admin')->prefix('admin')->group(function () {
        Route::get('stats', [DashboardController::class, 'stats']);

        Route::get('departements/{id?}', [LevelController::class, 'departements']);
        Route::get('users/{id?}', [AdminController::class, 'users']);
        Route::get('teachers/{id?}', [AdminController::class, 'teachers']);
        Route::get('parents/{id?}', [AdminController::class, 'parents']);

        Route::post('users/create', [AdminController::class, 'store']);
        Route::delete('users/{id}/delete', [AdminController::class, 'destroy']);
        Route::put('users/update', [AdminController::class, 'update']);

        Route::get('archive', [AdminController::class, 'archive']); //not now

        Route::get('sessions/{id?}', [AdminController::class, 'sessions']);
        Route::post('sessions/create', [AdminController::class, 'create_session']);
        Route::delete('sessions/{id}/delete', [AdminController::class, 'delete_session']);
        Route::put('sessions/{id}/update', [AdminController::class, 'update_session']);

        Route::get('groups/{id?}', [AdminController::class, 'groups']);
        Route::post('groups/create', [AdminController::class, 'create_group']);
        Route::delete('groups/{id}/delete', [AdminController::class, 'delete_group']);
        Route::put('groups/{id}/update', [AdminController::class, 'update_group']);
        Route::post('groups/{id}/members/add', [AdminController::class, 'group_student_add']);
        Route::delete('groups/{id}/members/{member_key}/delete', [AdminController::class, 'group_student_remove']);
        Route::get('students/excluding/groups/{id}', [AdminController::class, 'student_notin_group']);
        
        Route::get('students/{id?}', [AdminController::class, 'students']);
        Route::get('students/{id}/groups', [AdminController::class, 'student_group']);
        Route::post('students/{student_id}/groups/{group_id}/activate', [AdminController::class, 'student_group_activate']);
        Route::post('students/{student_id}/groups/{group_id}/add', [AdminController::class, 'student_group_add']);
        Route::delete('students/{student_id}/groups/{group_id}/delete', [AdminController::class, 'student_group_remove']);
        Route::get('groups/excluding/students/{id}', [AdminController::class, 'group_notin_student']);

        Route::get('checkouts/{id?}',[CheckoutController::class,'checkout_info']);

        Route::get('levels/{id?}', [LevelController::class, 'index']);
        Route::post('levels/create', [LevelController::class, 'create']);
        Route::delete('levels/{id}/delete', [LevelController::class, 'delete']);
        Route::put('levels/{id}/update', [LevelController::class, 'update']);
    });

    Route::middleware('permission:teacher')->prefix('teacher')->group(function () {
        Route::get('sessions/{id}', [TeacherController::class, 'sessions']);
        Route::get('session/{id}', [TeacherController::class, 'show']);
        Route::put('sessions/{id}/reject', [TeacherController::class, 'reject_session']);
        Route::put('sessions/{id}/approve', [TeacherController::class, 'approve_session']);
    });

    Route::middleware('permission:supervisor')->prefix('parent')->group(function () {
        Route::get('sessions/{id?}', [ParentController::class, 'sessions']);

        Route::get('students/{id?}', [ParentController::class, 'students']);
        Route::post('students/create', [ParentController::class, 'add_student']);
        Route::put('students/{id}/update', [ParentController::class, 'update_student']);
        Route::delete('students/{id}/delete', [ParentController::class, 'delete_student']);
    });

    Route::middleware('permission:student')->prefix('student')->group(function () {
        Route::get('sessions/{id?}', [StudentController::class, 'sessions']);
    });
});
