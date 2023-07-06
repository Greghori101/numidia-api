<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    //

    public function index()
    {
            $teachers = Teacher::all();
            foreach ($teachers as $teacher) {
                # code...
                $teacher["user"] = $teacher->user;
            }
        return $teachers;
    }

    public function show($id){
        $teacher = Teacher::where('id', $id)->first()->user;
        return $teacher;
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
