<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    //
    public function sessions($id)
    {
        $user = User::find($id);
        $teacher = $user->teacher;
        $sessions = $teacher->sessions;
        foreach ($sessions as $session) {
            # code...
            $session['teacher'] = $teacher;
            $session['group'] = $session->group;
        }

        return response()->json($sessions, 200);
    }

    public function show($id)
    {
        $session = Session::find($id);
        $session['teacher'] = $session->teacher;
        $session['group'] = $session->group;
        return response()->json($session, 200);
    }

    public function  reject_session(Request $request, $id)
    {
        $explanation = $request->explanation;
        $session = Session::find($id);
        $session->state = 'rejected';
    }
    public function  approve_session($id)
    {
        $session = Session::find($id);
        $session->state = 'approved';
    }
}
