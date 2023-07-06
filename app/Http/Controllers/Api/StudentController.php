<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Group;
use App\Models\Plan;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::all();
        foreach ($students as $student) {
            # code...
            $student['user'] = $student->user;
            $student['level'] = $student->level;
        }

        return $students;
    }

    public function show($id)
    {
        $student = Student::find($id);
        $student['user'] = $student->user;

        $student['level'] = $student->level;
        return $student;
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
}
