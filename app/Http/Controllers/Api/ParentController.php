<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\File;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ParentController extends Controller
{
    public function sessions($id = null)
    {

        if ($id) {
            $session = Session::find($id);
            $session['teacher'] = $session->teacher;
            $session['group'] = $session->group;
            return response()->json($session, 200);
        } else {
            $user = User::find(Auth::user()->id);
            $supervisor = $user->supervisor;
            $all_sessions = [];
            foreach ($supervisor->students as $student) {

                foreach ($student->groups as $group) {
                    # code..
                    $sessions = $group->sessions;
                    $temp = [];
                    foreach ($sessions as $session) {

                        if ($session->state == 'approved') {
                            $session['teacher'] = $session->teacher->user;
                            array_push($temp, $session);
                        }
                    }
                }
                $all_sessions[$student->id]['sessions'] = $temp;
                $all_sessions[$student->id]['group'] = $group;
            }


            return response()->json($all_sessions, 200);
        }
    }

    public function add_student(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $supervisor = $user->supervisor();
        $password = Str::random(10);

        $content = Storage::get('default-profile-picture.jpeg');
        $extension = 'jpeg';
        $name = "profile picture";

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'student',
            'gender' => $request->gender,
            'code' => Str::random(10),
            'password' => Hash::make($password),
        ]);

        $student = new Student();
        $user->student()->save($student);
        $supervisor->students()->save($student);

        $user->profile_picture()->save(new File([
            'name' => $name,
            'content' => base64_encode($content),
            'extension' => $extension,
        ]));

        // $user->refresh();

        try {
            //code...
            $data = [
                'name' => $user->name,
                'email' => $user->email,
                'code' => $user->code,
            ];
            Mail::to($user)->send(new VerifyEmail($data));
        } catch (\Throwable $th) {
            //throw $th;
            abort(400);
        }

        return response()->json(200);
    }

    public function students($id = null)
    {
        if (!$id) {
            $user = User::find(Auth::user()->id);
            $supervisor = $user->supervisor;
            $students = $supervisor->students;
            foreach ($students as $key => $value) {
                # code...
                $students[$key] = $value->user;
            }
        } else {
            $students = Student::where('id', $id)->first()->user();
        }
        return $students;
    }


    public function update_student(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_role' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        $user->name = $request->name;
        $user->role = $request->user_role;
        $user->gender = $request->gender;

        $user->save();

        return response()->json(200);
    }

    public function delete_student($id)
    {
        $user = User::where('id', $id)->first();
        $user->forceDelete();
        return response()->json(200);
    }
}
