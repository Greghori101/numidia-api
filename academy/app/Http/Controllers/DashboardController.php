<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Level;
use App\Models\Post;
use App\Models\Session;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function stats(Request $request)
    {

        $parents = Supervisor::all()->count();
        $students = Student::all()->count();
        $teachers = Teacher::all()->count();
        $users = User::all()->count();
        $news = 0;
        $groups = Group::all()->count();
        $sessions = Session::all()->count();
        $finicials = User::where("role", 'admin')->first()->wallet->balance;
        $departments = 3;
        $levels = Level::all()->count();
        $data = [
            'finicials' => $finicials,
            'departments' => $departments,
            'levels' => $levels,
            'users' => $users,
            'students' => $students,
            'parents' => $parents,
            'teachers' => $teachers,
            'groups' => $groups,
            'sessions' => $sessions,
            'news' => $news,
        ];
        return response()->json($data, 200);
    }
}
