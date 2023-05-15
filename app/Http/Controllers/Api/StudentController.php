<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function sessions($id = null)
    {
        if ($id) {
            $session = Session::find($id);
            $session['teacher'] = $session->teacher->user;
            $session['group'] = $session->group;
            return response()->json($session, 200);
        } else {
            $user = User::find(Auth::user()->id);
            $student = $user->student;
            foreach ($student->groups as $group) {
                # code..
                $sessions = $group->sessions;
                $temp = [];
                foreach ($sessions as $session) {

                    if ($session->state == 'approved') {
                        $session['teacher'] = $session->teacher->user;
                        $session['group'] = $session->group;
                        array_push($temp, $session);
                    }
                }
            }
            return response()->json($sessions, 200);
        }
    }
   
}
