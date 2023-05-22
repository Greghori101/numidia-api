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

    public function students($id = null)
    {
        if (!$id) {
            $students = Student::all();
            foreach ($students as $student) {
                # code...
                $student['user'] = $student->user;
                $student['level'] = $student->level;
            }
        } else {
            $students = Student::find($id);
            $students['user'] = $students->user;

            $students['level'] = $students->level;

        }
        return $students;
    }

    public function groups($id = null)
    {
        if (!$id) {
            $groups = Group::all();
            foreach ($groups as $group) {
                # code...
                $group['teacher'] = $group->teacher->user;
                $group['level'] = $group->level;
            }
        } else {
            $groups = Group::find($id);
            $groups['teacher'] = $groups->teacher->user;
            $groups['level'] = $groups->level;
            $members = $groups->students;
            foreach ($members as $member) {
                # code...
                $member['active'] = $member->pivot->active;
                $member = $member->user;
            }
            $groups['members'] = $members;
        }

        return response()->json($groups, 200);
    }

    public function create_group(Request $request)
    {

        $request->validate([
            'teacher_id' => ['required'],
            'level_id' => ['required'],
            'name' => ['required', 'string'],
            'capacity' => ['required', 'integer'],
        ]);

        $teacher = Teacher::find($request->teacher_id);
        $level = Level::find($request->level_id);
        $group = Group::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
        ]);
        $teacher->groups()->save($group);
        $level->groups()->save($group);

        return response()->json(200);
    }

    public function delete_group($id)
    {

        $group = Group::find($id);

        $group->delete();

        return response()->json(200);
    }

    public function update_group(Request $request, $id)
    {

        $request->validate([
            'teacher_id' => ['required'],
            'level_id' => ['required'],
            'name' => ['required', 'string'],
            'capacity' => ['required', 'integer'],

        ]);
        $group = Group::find($id);
        $group->delete();
        $teacher = Teacher::find($request->teacher_id);
        $level = Level::find($request->level_id);
        $group = Group::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
        ]);
        $teacher->groups()->save($group);
        $level->groups()->save($group);




        $group->save();

        return response()->json(200);
    }

    public function group_student_add(Request $request, $id)
    {

        $group = Group::find($id);
        if ($request->students) {
            $group->students()->sync($request->students);
        } else {
            $group->students()->detach();
        }
        return response()->json(200);
    }
    public function group_student_remove(Request $request, $id, $member_key)
    {

        $group = Group::find($id);
        $group->students()->detach($member_key);
        return response()->json(200);
    }
    public function student_notin_group($id)
    {
        $group = Group::find($id);
        $level = $group->level;

        $students = $level->students()
            ->whereNotIn('id',  $group->students->modelKeys())
            ->get();
        foreach ($students as $student) {
            # code...
            $student['user'] = $student->user;
        }
        return response()->json($students, 200);
    }

    public function student_group_add($student_id, $group_id)
    {

        $student = Student::find($student_id);
        $student->groups()->attach($group_id);
        return response()->json(200);
    }
    public function student_group($id)
    {

        $student = Student::find($id);
        $groups = $student->groups;
        foreach ($groups as $group) {
            # code...
            $group['teacher'] = $group->teacher->user;
            $group['level'] = $group->level;
            $group['active'] = $group->pivot->active;
        }
        return response()->json($groups, 200);
    }
    public function student_group_remove($student_id, $group_id)
    {

        $student = Student::find($student_id);
        $student->groups()->detach($group_id);
        return response()->json(200);
    }
    public function group_notin_student($id)
    {
        $student = Student::find($id);
        $level = $student->level;

        $groups = $level->groups()
            ->whereNotIn('id',  $student->groups->modelKeys())
            ->get();
        foreach ($groups as $group) {
            # code...
            $group['teacher'] = $group->teacher->user;
            $group['level'] = $group->level;
        }
        return response()->json($groups, 200);
    }

    public function student_group_activate(Request $request, $student_id, $group_id)
    {
        $group =  Student::find($student_id)->groups()->find($group_id);
        if (!$group) {
            $student = Student::find($student_id);
            $student->groups()->attach($group_id);
            $group = Student::find($student_id)->groups()->find($group_id);
        }
        $pivot = $group->pivot;
        $pivot->active = true;
        $pivot->activated_at = Carbon::now()->toDateTimeString();
        $checkout = Checkout::create([
            'price' => $request->price,
            'nb_session' => $request->nb_session,
            'total' => $request->nb_session * $request->price,
            'end_date' => $request->end_date,
        ]);

        $checkout->student()->associate(Student::find($student_id));
        $checkout->group()->associate(Group::find($group_id));

        $pivot->save();
        return response()->json(200);
    }




    public function sessions($id = null)
    {
        if ($id) {
            $session = Session::find($id);
            $session['teacher'] = $session->teacher->user;
            $session['group'] = $session->group;
            return response()->json($session, 200);
        } else {
            $sessions = Session::all();
            foreach ($sessions as  $session) {
                # code...
                $session['teacher'] = $session->teacher->user;
                $session['group'] = $session->group;
            }

            return response()->json($sessions, 200);
        }
    }

    public function create_session(Request $request)
    {
        $session = Session::create([
            'classroom' => $request->classroom,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
        ]);

        $group = Group::find($request->group_id);
        $teacher = Teacher::find($request->teacher_id);
        $session->group()->associate($group)->save();
        $session->teacher()->associate($teacher)->save();


        return response()->json(200);
    }

    public function delete_session($id)
    {

        $session = Session::find($id);

        $session->delete();

        return response()->json(200);
    }

    public function update_session(Request $request, $id)
    {

        $session = Session::find($id);

        $session->update([
            'classroom' => $request->classroom,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
        ]);

        $session->group()->associate(Group::find($request->group_id));
        $session->teacher()->associate(Teacher::find($request->teacher_id));

        $session->save();

        return response()->json(200);
    }


    public function archive()
    {

        return response()->json(200);
    }
}
