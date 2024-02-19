<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Level;
use App\Models\Session;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{

    public function stats(Request $request)
    {
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->get(env('AUTH_API') . '/api/wallet/'.$request->user["id"]);

        $parents = Supervisor::all()->count();
        $students = Student::all()->count();
        $teachers = Teacher::all()->count();
        $users = User::all()->count();
        $groups = Group::all()->count();
        $sessions = Session::all()->count();
        $financials = $response->json();
        $departments = 3;
        $levels = Level::all()->count();
        $data = [
            'financials' => $financials,
            'departments' => $departments,
            'levels' => $levels,
            'users' => $users,
            'students' => $students,
            'parents' => $parents,
            'teachers' => $teachers,
            'groups' => $groups,
            'sessions' => $sessions,
        ];
        return response()->json($data, 200);
    }
}
