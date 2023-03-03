<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LevelController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\PlanController;
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
// email:
// password:
//response :
// token,role,id

Route::post('/register', [AuthController::class, 'store']);
// name:
// role:
// email:
// gender:
// phone_number
// password:
// password_confirmation:
//response :
// token,role,id

//Public routes
Route::post('/auth/{provider}/login', [AuthController::class, 'provider_login']);
// email:
// password:
//response :
// token,role,id

Route::post('/auth/{provider}/register', [AuthController::class, 'provider_store']);
// name:
// role:
// email:
// gender:
// phone_number
// password:
// password_confirmation:
//response :
// token,role,id

Route::post('/forgotpassword', [AuthController::class, 'forgotpassword']);
// email:
//response :
// ok

Route::get('plans/{id?}', [PlanController::class, 'all']);
    //response :
    // price,duration,benefits,clients


//Protected routes

Route::middleware(['auth:api'])->group(function () {

    Route::get('logout', [AuthController::class, 'logout']);

    Route::post('/email/verify', [AuthController::class, 'verify']);
    // email:
    // code:
    //response :
    // message 
    Route::post('/email/resent/code', [AuthController::class, 'resent_verification']);
    // email:
    //response :
    // message  
    Route::get('/email/isverified', [AuthController::class, 'email_verified']);
    // email:
    //response :
    // message ok or not 
    
    Route::middleware('permission:client')->group(function () {
        Route::post('clients/choose/plan', [PlanController::class, 'choose_plan']);
        // client_id:
        // plan_id:
        //response :
        // ok
    });
});




//email verification required
Route::middleware(['auth:api', 'verified'])->group(function () {

    WebSocketsRouter::webSocket('/my-websocket', \App\CustomWebSocketHandler::class);
    //for create new chanel for notifications

    Route::controller(DashboardController::class)->group(function () {

        Route::get('/', 'index');
        //dashboard for news and statisctics
    });
    

    Route::controller(PostController::class)->group(function(){
        Route::get('posts/{id?}', 'index');
        //response :
        // title,content,created_at,author

        Route::post('posts/create', 'create');
        //title,content,author
        //response :
        // ok

        Route::delete('posts/{id}/delete','delete');
        //response :
        // ok

        Route::put('posts/{id}/update','update');
        //title,content,author
        //response :
        // ok 
    });

    Route::get('profile/{id}', [AuthController::class, 'show']);
    //get the user data
    //response :
    // name,email,gender,profile_picture,profile_picture_src,role  

    Route::post('profile/{id}/update', [AuthController::class, 'update']);
    //modify user data
    //the data heer has to be form data
    // name:
    // gender:
    // phone_number
    // password:
    // password_confirmation:
    // profile_picture: containing the binary picture
    //response :
    // name,email,gender,profile_picture,role 


    /*
     *
     * for each permission you have to sepcify the role in params
     *
    */

    Route::middleware('permission:admin')->prefix('admin')->group(function () {
        Route::get('stats', [DashboardController::class, 'stats']);


        Route::get('departements/{id?}', [LevelController::class, 'departements']);
        //response :
        // name

       

        Route::get('users/{id?}', [AdminController::class, 'users']);
        //get users or secific user
        //response :
        // id,name,email,gender,role 

        Route::get('teachers/{id?}', [AdminController::class, 'teachers']);
        //get teachers or secific teacher
        //response :
        // id,name,email,gender,role 
        Route::get('students/{id?}', [AdminController::class, 'students']);
        //get students or secific student
        //response :
        // id,name,email,gender,role 
        Route::get('parents/{id?}', [AdminController::class, 'parents']);
        //get parent or secific parent
        //response :
        // id,name,email,gender,role 

        Route::post('users/create', [AdminController::class, 'store']);
        //create new user
        // name:
        // user_role:
        // phone_number
        // email:
        // gender:
        //response :
        // ok 

        Route::delete('users/{id}/delete', [AdminController::class, 'destroy']);
        //delete user(id)
        //response :
        // ok


        Route::put('users/update', [AdminController::class, 'update']);
        //modify user
        // name:
        // user_role:
        // phone_number
        // email:
        // gender:
        //response :
        // ok

        Route::get('archive', [AdminController::class, 'archive']); //not now

        Route::get('sessions/{id?}', [AdminController::class, 'sessions']);
        //response :
        // starts_at,ends_at,classroom,teacher,group

        Route::post('sessions/create', [AdminController::class, 'create_session']);
        //classrom:
        //starts_at
        //ends_at:
        //teacher_id: the host of the session
        //group_id: group's id:
        //response :
        // ok

        Route::delete('sessions/{id}/delete', [AdminController::class, 'delete_session']);
        //response :
        // ok

        Route::put('sessions/{id}/update', [AdminController::class, 'update_session']);
        //classrom:
        //starts_at:
        //ends_at:
        //teacher_id: the host of the session:
        //group_id: group's id:
        //response :
        // ok 

       
        Route::post('plans/create', [PlanController::class, 'create']);
        //price:
        //duration
        //benefits:
        //level_id:id of the level
        //teacher_id:id of the teacher
        //response :
        // ok

        Route::get('plans/{id?}', [PlanController::class, 'index']);
    //response :
    // price,duration,benefits,clients


        Route::delete('plans/{id}/delete', [PlanController::class, 'delete']);
        //response :
        // ok

        Route::put('plans/{id}/update', [PlanController::class, 'update']);
        //price:
        //duration
        //benefits:
        //level_id:id of the level
        //teacher_id:id of the teacher

        //response :
        // ok 

        Route::get('groups/{id?}', [AdminController::class, 'groups']);
        //response :
        // name,members,capacity,teacher,level

        Route::post('groups/create', [AdminController::class, 'create_group']);
        //name:
        //capacity
        //level_id:id of the level
        //teacher_id:id of the teacher

        //response :
        // ok

        Route::delete('groups/{id}/delete', [AdminController::class, 'delete_group']);
        //response :
        // ok

        Route::put('groups/{id}/update', [AdminController::class, 'update_group']);
        //name:
        //capacity:
        //level_id:id of the level
        //teacher_id:id of the teacher


        //response :
        // ok 
        Route::post('groups/{id}/students', [AdminController::class, 'group_student']);

        //students:ids of the students

        //response :
        // ok 


        Route::get('levels/{id?}', [LevelController::class, 'index']);
        //response :
        // departement,education,specialty,year

        Route::post('levels/create', [LevelController::class, 'create']);
        //education:
        //specialty:
        //year:
        //departement_id: departement
        //response :
        // ok 

        Route::delete('levels/{id}/delete', [LevelController::class, 'delete']);
        //response :
        // ok

        Route::put('levels/{id}/update', [LevelController::class, 'update']);
        //education:
        //speciality
        //year:
        //departement_id: departement
        //response :
        // ok 
    });

    Route::middleware('permission:teacher')->prefix('teacher')->group(function () {

        Route::get('sessions/{id}', [TeacherController::class, 'sessions']);
        //id: represent the teacher 
        //response :
        // starts_at,ends_at,classroom,teacher,group

        Route::get('session/{id}', [TeacherController::class, 'show']);
        //id: represent the session
        //response :
        // starts_at,ends_at,classroom,teacher,group

        Route::put('sessions/{id}/reject', [TeacherController::class, 'reject_session']);
        //explanation: the reason why reject the session
        //response :
        // ok

        Route::put('sessions/{id}/approve', [TeacherController::class, 'approve_session']);
        //response :
        // ok
    });
    


    Route::middleware('permission:supervisor')->prefix('parent')->group(function () {

        Route::get('sessions/{id?}', [ParentController::class, 'sessions']);
        //id: represent the parent 
        //response :
        // starts_at,ends_at,classroom,teacher,group


        Route::get('students/{id?}', [ParentController::class, 'students']);
        //get students or secific student that belongs to the parent
        //response :
        // id,name,email,gender,role 

        Route::post('students/create', [ParentController::class, 'add_student']);
        //create new student
        // name:
        // phone_number:
        // email:
        // gender:
        //response :
        // ok 

        Route::put('students/{id}/update', [ParentController::class, 'update_student']);
        //update new student
        // name:
        // phone_number:
        // email:
        // gender:
        //response :
        // ok

        Route::delete('students/{id}/delete', [ParentController::class, 'delete_student']);
        //response :
        // ok 
    });

    Route::middleware('permission:student')->prefix('student')->group(function () {

        Route::get('sessions/{id?}', [StudentController::class, 'sessions']);
        //response :
        // starts_at,ends_at,classroom,teacher,group

    });
});
