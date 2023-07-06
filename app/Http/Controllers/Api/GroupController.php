<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Group;
use App\Models\Level;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    
    public function index()
    {
            $groups = Group::all();
            foreach ($groups as $group) {
                # code...
                $group['teacher'] = $group->teacher->user;
                $group['level'] = $group->level;
            }
       

        return response()->json($groups, 200);
    }

    public function show($id){
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

    public function create(Request $request)
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

    public function delete($id)
    {

        $group = Group::find($id);

        $group->delete();

        return response()->json(200);
    }

    public function update(Request $request, $id)
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

}
