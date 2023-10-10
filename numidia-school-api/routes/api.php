<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ExpensesController;
use App\Http\Controllers\Api\FeeInscriptionController;
use App\Http\Controllers\Api\FinancialController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\LevelController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'store']);
Route::post('/create-user', [AuthController::class, 'user_create']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/{provider}/login', [AuthController::class, 'provider_login',]);
Route::post('/password/forgot', [AuthController::class, 'forgotpassword']);
Route::post('/password/reset', [AuthController::class, 'restpassword']);
Route::get('levels/all', [LevelController::class, 'all']);

Route::middleware(['auth-api-token',])->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verify', [AuthController::class, 'verify']);
    Route::post('/email/resent/code', [AuthController::class, 'resent_verification',]);
    Route::get('/email/isverified', [AuthController::class, 'email_verified']);
    Route::post('/password/change', [UserController::class, 'change_password']);
    Route::delete('/activities/revoke/{id}', [AuthController::class, 'revoke']);
    Route::delete('/activities/clear', [AuthController::class, 'clear_activities']);
});

// Verfied routes (require email verification)
Route::middleware(['auth-api-token'])->group(function () {
    Route::get('/profile/{id?}', [UserController::class, 'show']);
    Route::put('/profile', [UserController::class, 'profile_update']);
    Route::post('/picture/change/{id?}', [UserController::class, 'change_profile_picture']);

    Route::controller(DashboardController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/stats', 'stats');
        });
    Route::controller(WalletController::class)
        ->group(function () {
            Route::post('/deposit', 'deposit');
            Route::post('/withdraw', 'withdraw');
        });


    Route::controller(FinancialController::class)
        ->group(function () {
            Route::get('/checkouts/stats', 'checkouts_stats');
            Route::get('/students/stats', 'students_stats');
            Route::get('/employees/register', 'register_per_employee');
            Route::get('/employees/stats', 'employee_stats');
            Route::get('/expenses/stats', 'expense_stats');
            Route::get('/inscription_fees/stats', 'fees_stats');
            Route::get("/employees/financials", 'all_per_employee');
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
            Route::get('/all', 'all');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::get('/{id}/students', 'students');
            Route::get('/{id}/students/unenrolled', 'student_notin_group');
            Route::post('/{id}/students', 'students_create');
            Route::delete('/{id}/students/{student_id}', 'students_delete');
            Route::get('/{id}/sessions', 'sessions');
            Route::post('/{id}/sessions', 'sessions_create');
        });
    Route::prefix('students')
        ->controller(StudentController::class)
        ->group(function () {
            Route::get('/all', 'all');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
            Route::get('/{id}/checkouts', 'student_checkouts');

            Route::get('/{id}/groups', 'student_group');
            Route::get('/{id}/groups/unenrolled', 'group_notin_student');
            Route::post('/{student_id}/groups', 'student_group_add');
            Route::delete('/{student_id}/groups/{group_id}', 'student_group_remove');
        });

    Route::prefix('teachers')
        ->controller(TeacherController::class)
        ->group(function () {
            Route::get('/all', 'all');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
            Route::post('/sessions/reject', 'reject_session');
        });

    Route::prefix('parents')
        ->controller(ParentController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::delete('/{id}', 'delete');
            Route::put('/{id}', 'update');
            Route::post('/{id}/students', 'add_student');
            Route::get('/{id}/students', 'students');
        });



    Route::prefix('checkouts')
        ->controller(CheckoutController::class)
        ->group(function () {
            Route::get('/all', 'all');
            Route::get('', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::post('/pay', 'pay');
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

    Route::prefix('expenses')
        ->controller(ExpensesController::class)
        ->group(function () {
            Route::get('/all', 'all');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
    Route::prefix('inscription_fees')
        ->controller(FeeInscriptionController::class)
        ->group(function () {
            Route::get('/all', 'all');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::post('/pay', 'pay');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
    Route::prefix('attendance')
        ->controller(AttendanceController::class)
        ->group(function () {
            Route::get('/students', 'students');
            Route::get('/sessions', 'sessions');
            Route::get('/presence/sheets', 'presence_sheets');
            Route::post('/presence', 'create_presence');
            Route::post('/mark/presence', 'mark_presence');
            Route::post('/remove/presence', 'remove_presence');
        });
    Route::prefix('exams')
        ->controller(ExamController::class)
        ->group(function () {
            Route::post('/', 'store');
            Route::post('/{exam}/student-answers', 'create_answers');
            Route::get('/', 'index');
            Route::delete('/{exam}/close', 'close_exam');
            Route::put('/{exam}/open', 'open_exam');
            Route::delete('/{exam}', 'delete');
            Route::get('/all', 'all');
            Route::get('/{exam}', 'show');
            Route::get('/{exam}/student/{id}', 'student_exam');
            Route::get('/student/{id}', 'student_exams');
            Route::get('/teacher/{id}', 'teacher_exams');
            Route::delete('/{exam}', 'delete');
            Route::put('/{exam}', 'update');
        });
});
