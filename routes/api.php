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
// email
// password 
// response : 
//  200 ok
// 'id'
// 'role' 
// 'profile_picture'
// 'verified'
// 'token'
Route::post('/register', [AuthController::class, 'store']);
// email
// password  confirmed
// name
// role
// phone_number
// gender
// response : 
//  200 ok
// 'id'
// 'role' 
// 'profile_picture'
// 'verified'
// 'token'
Route::post('/auth/{provider}/login', [AuthController::class, 'provider_login']);
Route::post('/auth/{provider}/register', [AuthController::class, 'provider_store']);
Route::post('/forgotpassword', [AuthController::class, 'forgotpassword']);
// email
// response :
// 200 ok


//Protected routes

Route::middleware(['auth:api'])->group(function () {
    Route::get('logout', [AuthController::class, 'logout']);
    // response :
    // 200 ok
    Route::post('/email/verify', [AuthController::class, 'verify']);
    // email
    // code
    // response :
    // 200 ok
    Route::post('/email/resent/code', [AuthController::class, 'resent_verification']);
    // email
    // response :
    // 200 ok
    Route::get('/email/isverified', [AuthController::class, 'email_verified']);
    // email
    // response :
    // verified

});

//email verification required
Route::middleware(['auth:api', 'verified'])->group(function () {

    WebSocketsRouter::webSocket('/my-websocket', \App\CustomWebSocketHandler::class);
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(PostController::class)->group(function () {
        Route::get('posts/{id?}', 'index');
        // response:
        // title
        // content
        // author
        Route::post('posts/create', 'create');
        // title
        // content
        // author
        // response : 
        // 200 ok
        Route::delete('posts/{id}/delete', 'delete');
        // response :
        // 200 ok
        Route::put('posts/{id}/update', 'update');
        // title
        // content
        // author
        // response : 
        // 200 ok
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
        // response :
        // all departements
        // name 
        Route::get('users/{id?}', [AdminController::class, 'users']);
        // response :
        // email
        // password  confirmed
        // name
        // role
        // phone_number
        // gender
        Route::get('teachers/{id?}', [AdminController::class, 'teachers']);
        // response :
        // email
        // name
        // role
        // phone_number
        // gender
        Route::get('parents/{id?}', [AdminController::class, 'parents']);
        // response :
        // user.email
        // user.name
        // user.role
        // user.phone_number
        // user.gender
        Route::post('users/create', [AdminController::class, 'store']);
        // email
        // password  confirmed
        // name
        // role
        // phone_number
        // gender
        // module_id
        // level_id
        // response : 
        // 200 ok
        Route::delete('users/{id}/delete', [AdminController::class, 'destroy']);
        // response : 
        //  200 ok
        Route::put('users/update', [AdminController::class, 'update']);
        // email
        // password  confirmed
        // name
        // role
        // phone_number
        // gender
        // response : 
        // 200 ok

        Route::get('archive', [AdminController::class, 'archive']); //not now

        Route::get('sessions/{id?}', [AdminController::class, 'sessions']);
        // response : 
        // starts_at
        // ends_at
        // state
        // teacher
        // group
        // classroom
        Route::post('sessions/create', [AdminController::class, 'create_session']);
        // starts_at
        // ends_at
        // state
        // group_id
        // teacher_id
        // classroom
        // response :
        // 200 ok
        Route::delete('sessions/{id}/delete', [AdminController::class, 'delete_session']);
        // response :
        // 200 ok
        Route::put('sessions/{id}/update', [AdminController::class, 'update_session']);
        // starts_at
        // ends_at
        // state
        // group
        // classroom
        // response :
        // 200 ok

        Route::get('groups/{id?}', [AdminController::class, 'groups']);
        // response :
        // name
        // teacher
        // level
        // memebers
        Route::post('groups/create', [AdminController::class, 'create_group']);
        // name
        // teacher
        // level
        // capacity
        // response :
        // 200 ok
        Route::delete('groups/{id}/delete', [AdminController::class, 'delete_group']);
        // response :
        // 200 ok
        Route::put('groups/{id}/update', [AdminController::class, 'update_group']);
        // name
        // teacher
        // level
        // capacity
        // response :
        // 200 ok
        Route::post('groups/{id}/members/add', [AdminController::class, 'group_student_add']);
        // members : ids of students
        // response :
        // 200 ok
        Route::delete('groups/{id}/members/{member_key}/delete', [AdminController::class, 'group_student_remove']);
        // response :
        // 200 ok
        Route::get('students/excluding/groups/{id}', [AdminController::class, 'student_notin_group']);
        // response :
        // members not in this group

        Route::get('students/{id?}', [AdminController::class, 'students']);
        // response :
        // email
        // name
        // role
        // phone_number
        // gender
        Route::get('students/{id}/groups', [AdminController::class, 'student_group']);
        // response :
        // group=> 
        //  teacher
        //  level 
        //  active
        Route::post('students/{student_id}/groups/{group_id}/activate', [AdminController::class, 'student_group_activate']);
        // price
        // nb_session
        // end_date
        // reponse :
        // 200 ok
        Route::post('students/{student_id}/groups/{group_id}/add', [AdminController::class, 'student_group_add']);
        // response :
        // 200 ok
        Route::delete('students/{student_id}/groups/{group_id}/delete', [AdminController::class, 'student_group_remove']);
        // response :
        // 200 ok
        Route::get('groups/excluding/students/{id}', [AdminController::class, 'group_notin_student']);
        // response :
        // name
        // teacher
        // level 

        Route::get('checkouts/{id?}', [CheckoutController::class, 'checkout_info']);

        Route::get('levels/{id?}', [LevelController::class, 'index']);
        // response :
        // education
        // specialty
        // year
        Route::post('levels/create', [LevelController::class, 'create']);
        // education
        // specialty
        // year
        // departement
        // response :
        // 200 ok
        Route::delete('levels/{id}/delete', [LevelController::class, 'delete']);
        // response :
        // 200 ok
        Route::put('levels/{id}/update', [LevelController::class, 'update']);
        // education
        // specialty
        // year
        // departement
        // response :
        // 200 ok

        Route::get('modules/{id?}', [LevelController::class, 'modules']);
        // response :
        // name
        // teachers
        Route::post('modules/create', [LevelController::class, 'create_module']);
        // name
        // level_id
        // response :
        // 200 ok
        Route::delete('modules/{id}/delete', [LevelController::class, 'delete_module']);
        // response :
        // 200 ok
        Route::put('modules/{id}/update', [LevelController::class, 'update_module']);
        // name
        // level_id
        // response :
        // 200 ok
    });

    Route::middleware('permission:teacher')->prefix('teacher')->group(function () {
        Route::get('sessions/{id}', [TeacherController::class, 'sessions']);
        // response : list of session belongs to teacher/ id
        // starts_at
        // ends_at
        // state
        // group
        Route::get('session/{id}', [TeacherController::class, 'show']);
        // response : session/id detail
        // starts_at
        // ends_at
        // state
        // group
        Route::put('sessions/{id}/reject', [TeacherController::class, 'reject_session']);
        // explanation
        // response :
        // 200 ok
        Route::put('sessions/{id}/approve', [TeacherController::class, 'approve_session']);
        // response :
        // 200 ok
    });

    Route::middleware('permission:supervisor')->prefix('parent')->group(function () {
        Route::get('sessions/{id?}', [ParentController::class, 'sessions']);
        // response : 
        // starts_at
        // ends_at
        // state
        // group
        Route::get('students/{id?}', [ParentController::class, 'students']);
        // response :
        // email
        // name
        // role
        // phone_number
        // gender
        Route::post('students/create', [ParentController::class, 'add_student']);
        // email
        // name
        // phone_number
        // gender
        // response : 
        // 200 ok
        Route::put('students/{id}/update', [ParentController::class, 'update_student']);
        // email
        // name
        // phone_number
        // gender
        // response : 
        // 200 ok
        Route::delete('students/{id}/delete', [ParentController::class, 'delete_student']);
        // response : 
        // 200 ok
    });

    Route::middleware('permission:student')->prefix('student')->group(function () {
        Route::get('sessions/{id?}', [StudentController::class, 'sessions']);
        // response : 
        // starts_at
        // ends_at
        // state
        // group
    });
});
