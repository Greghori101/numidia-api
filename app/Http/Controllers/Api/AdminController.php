<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\Admin;
use App\Models\Checkout;
use App\Models\Level;
use App\Models\File;
use App\Models\Group;
use App\Models\Module;
use App\Models\Session;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminController extends Controller
{

    public function users($id = null)
    {
        if (!$id) {
            $users = User::all()->except(Auth::id());
        } else {
            $users = User::where('id', $id)->first();
        }
        return $users;
    }
    public function users_list(Request $request)
    {
        $users = User::find($request->ids);
        foreach($users as $user){
            $user['profile_picture'] = $user->profile_picture;
        }
        return response()->json(200, $users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_role' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
        ]);

        $content = Storage::get('default-profile-picture.jpeg');
        $extension = 'jpeg';
        $name = "profile picture";

        $code = Str::upper(Str::random(6));

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->user_role,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($code),
            'code' => $code,
        ]);

        if ($user->role == 'teacher') {
            $teacher  = new Teacher();
            $user->teacher()->save($teacher);
            $module = Module::find($request->module_id);
            $module->teachers()->save($teacher);
        } else if ($user->role == 'student') {

            $level = Level::find($request->level_id);
            $student = new Student();
            $user->student()->save($student);
            $level->students()->save($student);
        } else if ($user->role == 'admin') {
            $user->admin()->save(new Admin());
        } else if ($user->role == 'supervisor') {
            $user->supervisor()->save(new Supervisor());
        }
        $transacton = 0;
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
            // abort(400);
        }

        return response()->json(200);
    }


    public function update(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_role' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->role != $request->user_role) {
            if ($user->role == 'teacher') {
                $user->teacher()->delete();
            } else if ($user->role == 'student') {
                $user->student()->delete();
            } else if ($user->role == 'admin') {
                $user->admin()->delete();
            } else if ($user->role == 'supervisor') {
                $user->supervisor()->delete();
            }

            if ($request->user_role == 'teacher') {
                $user->teacher()->save(new Teacher());
            } else if ($request->user_role == 'student') {
                $user->student()->save(new Student());
            } else if ($request->user_role == 'admin') {
                $user->admin()->save(new Admin());
            } else if ($request->user_role == 'supervisor') {
                $user->supervisor()->save(new Supervisor());
            }
        }

        $user->name = $request->name;
        $user->role = $request->user_role;
        $user->gender = $request->gender;
        $user->phone_number = $request->phone_number;

        $user->save();

        return response()->json(200);
    }

    public function destroy($id)
    {
        $user = User::where('id', $id)->first();
        $user->forceDelete();
        return response()->json(200);
    }

    public function teachers($id = null)
    {
        if (!$id) {
            $teachers = Teacher::all();
            foreach ($teachers as $teacher) {
                # code...
                $teacher["user"] = $teacher->user;
            }
        } else {
            $teachers = Teacher::where('id', $id)->first()->user;
        }
        return $teachers;
    }

    public function parents($id = null)
    {
        if (!$id) {
            $parents = Supervisor::all();
            foreach ($parents as $key => $value) {
                # code...
                $temp = [];
                foreach ($value->students as $student) {
                    $temp[] = $student->user;
                    # code...
                }
                $parents[$key] = $value->user;

                $parents[$key]['students'] = $temp;
            }
        } else {
            $parents = Supervisor::where('id', $id)->first();
            $temp = [];
            foreach ($parents->students as $student) {
                $temp[] = $student->user;
                # code...
            }
            $parents['students'] = $temp;
        }
        return $parents;
    }

    

    




    

    
}
