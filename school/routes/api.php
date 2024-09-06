<?php

use App\Http\Controllers\Api\AmphiController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ExpensesController;
use App\Http\Controllers\Api\FeeInscriptionController;
use App\Http\Controllers\Api\FinancialController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\LevelController;
use App\Http\Controllers\Api\MarkSheetController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DawaratController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/{provider}/login', [AuthController::class, 'provider_login',]);
Route::get('/levels', [LevelController::class, 'index']);
Route::get('/teachers/all-details', [TeacherController::class, 'all_details']);
Route::get('/groups/all-details', [GroupController::class, 'all']);
Route::get('/dawarat/all-details', [DawaratController::class, 'all']);
Route::post('/create-user', [AuthController::class, 'create_user_department']);
Route::get('/files', [AuthController::class, 'getFile']);


// Verified routes (require email verification)
Route::middleware(['auth-api-token'])->group(function () {
    Route::get('/user.verify/{id}', [AuthController::class, 'verify_user_existence']);
    Route::post('/create-user/{id}', [AuthController::class, 'create_user']);


    Route::get('/profile/{id?}', [UserController::class, 'show']);
    Route::put('/profile/{id?}', [UserController::class, 'update']);

    Route::prefix('users')
        ->controller(UserController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/list', 'users_list');
            Route::get('/{id}', 'show');
            Route::put('/{id?}', 'update');
            Route::post('/', 'store');
            Route::delete('/{id}', 'delete');
            Route::post('/create/departments', 'create');
            Route::get('/switch/departments', 'verify_user');
        });
    Route::prefix('sessions')
        ->controller(SessionController::class)
        ->group(function () {
            Route::get('/details', 'all_details');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/{id}/exception', 'except');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
    Route::prefix('levels')
        ->controller(LevelController::class)
        ->group(function () {
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
    Route::prefix('groups')
        ->controller(GroupController::class)
        ->group(function () {
            Route::get('/daily', 'groups_per_day');
            Route::get('/details', 'all_details');
            Route::get('/', 'index');
            Route::get('/all', 'all');
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
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::get('/{id}/checkouts', 'student_checkouts');
            Route::get('/{id}/groups', 'student_group');
            Route::get('/{id}/tickets', 'student_tickets');
            Route::get('/{id}/groups/unenrolled', 'group_notin_student');
            Route::get('/{id}/mark_sheet', 'student_mark_sheets');
            Route::post('/{student_id}/groups', 'student_group_add');
            Route::delete('/{student_id}/groups/{group_id}', 'student_group_remove');
        });
    Route::prefix('teachers')
        ->controller(TeacherController::class)
        ->group(function () {
            Route::get('/details', 'all_details');
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/sessions/reject', 'reject_session');
            Route::post('/sessions/approve', 'approve_session');
        });
    Route::prefix('parents')
        ->controller(ParentController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/{id}/students', 'add_student');
            Route::get('/{id}/students', 'students');
        });
    Route::prefix('checkouts')
        ->controller(CheckoutController::class)
        ->group(function () {
            Route::get('', 'index');
            Route::get('/{id}', 'show');
            Route::put('/{id}', 'update');
            Route::post('/pay', 'pay_debt');
            Route::post('/pay_by_sessions', 'pay_by_sessions');
        });


    Route::prefix('user')
        ->controller(UserController::class)
        ->group(function () {
            Route::get('/students', 'students');
            Route::get('/checkouts', 'checkouts');
            Route::get('/exams', 'exams');
            Route::get('/groups', 'groups');
            Route::get('/receipts', 'receipts');
            Route::get('/sessions', 'sessions');
        });

    Route::prefix('marks')
        ->controller(MarkSheetController::class)
        ->group(function () {
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
            Route::post('/marks', 'add_mark');
            Route::put('/marks/{id}', 'update_mark');
            Route::delete('/marks/{id}', 'delete_mark');
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
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
    Route::prefix('inscription_fees')
        ->controller(FeeInscriptionController::class)
        ->group(function () {
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
            Route::get('/sessions', 'sessions');
            Route::get('/presences', 'presences');
            Route::get('/sessions/{session_id}/cancel', 'cancel_session');
            Route::get('/presence/sheets', 'presence_sheets');
            Route::post('/presence', 'create_presence');
            Route::post('/mark/presence', 'mark_presence');
            Route::post('/remove/presence', 'remove_presence');
        });

    Route::prefix('statistics')
        ->controller(FinancialController::class)
        ->group(function () {
            Route::get('/checkouts', 'checkouts');
            Route::get('/students', 'students');
            Route::get("/employees/financials", 'all_per_employee');
            Route::get('/employees/register', 'register_per_employee');
            Route::get('/employees', 'employee');
            Route::get('/expenses', 'expense');
            Route::get('/inscription_fees', 'fees');
            Route::get('/dashboard', 'stats');
            Route::get('/employee_receipts', 'get_employee_receipts');
            Route::get('/transactions', 'transactions');
            Route::get('/paid_sessions', 'paid_sessions');
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
            Route::get('/{exam}', 'show');
            Route::get('/{exam}/student/{id}', 'student_exam');
            Route::get('/student/{id}', 'student_exams');
            Route::get('/teacher/{id}', 'teacher_exams');
            Route::put('/{exam}', 'update');
        });


    Route::prefix('amphitheaters')
        ->controller(AmphiController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        });
    Route::prefix('dawarat')
        ->controller(DawaratController::class)
        ->group(function () {
            Route::get('/daily', 'groups_per_day');
            Route::get('/details', 'all_details');
            Route::get('/teachers', 'teachers');
            Route::get('/', 'index');
            Route::get('/all', 'all');
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
    Route::prefix('dawarat')
        ->controller(PresenceController::class)
        ->group(function () {
            Route::get('/sessions', 'sessions');
            Route::get('/presences', 'presences');
            Route::get('/sessions/{session_id}/cancel', 'cancel_session');
            Route::get('/presence/sheets', 'presence_sheets');
            Route::post('/presence', 'create_presence');
            Route::post('/mark/presence', 'mark_presence');
            Route::post('/remove/presence', 'remove_presence');
        });
    Route::prefix('tickets')
        ->controller(TicketController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/waiting', 'getWaiting');
            Route::get('/{id}', 'show');
            Route::post('/', 'create');
            Route::put('/{id}', 'update');
            Route::delete('/{id}/cancel', 'cancel');
            Route::post('/{id}/pay', 'pay');
            Route::delete('/{id}', 'destroy');
        });
});
