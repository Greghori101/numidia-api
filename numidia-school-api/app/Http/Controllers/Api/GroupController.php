<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Group;
use App\Models\Level;
use App\Models\Session;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GroupController extends Controller
{

    public function all()
    {
        $groups  = Group::with(['level', 'teacher.user'])->get();
        return response()->json($groups, 200);
    }
    public function index(Request $request)
    {
        $perPage = $request->query('perPage', 10);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $search = $request->query('search', "");
        $levelId = $request->query('level_id');
        $teacherId = $request->query('teacher_id');

        $query = Group::query();
        $level = Level::find($levelId);
        $teacher = Teacher::find($teacherId);

        if ($level) {
            $query = $level->groups();
        }
        if ($teacher) {
            $query = $query->where('teacher_id', $teacherId);
        }

        $groups = $query
            ->whereRaw('LOWER(name) LIKE ?', ["%$search%"])
            ->orderBy($sortBy, $sortDirection)
            ->with(['level', 'teacher.user'])
            ->when($perPage !== 'all', function ($query) use ($perPage) {
                return $query->paginate($perPage);
            }, function ($query) {
                return $query->get();
            });
        return response()->json($groups, 200);
    }

    public function show($id)
    {
        $group = Group::with(['teacher.user', 'level', 'students.user'])->find($id);
        $group->students->each(function ($student) {
            $student['active'] = $student->user->wallet->balance >= 0;
        });
        return response()->json($group, 200);
    }

    public function create(Request $request)
    {
        $request->validate([
            'teacher_id' => ['required'],
            'level_id' => ['required'],
            'name' => ['required', 'string'],
            'capacity' => ['required', 'integer'],
            'nb_session' => ['required', 'integer'],
        ]);

        $teacher = Teacher::find($request->teacher_id);
        $level = Level::find($request->level_id);
        $group = Group::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'price_per_month' => $request->price_per_month,
            'nb_session' => $request->nb_session,
        ]);
        $teacher->groups()->save($group);
        $level->groups()->save($group);

        return response()->json($group, 200);
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
            'price_per_month' => $request->price_per_month,

            'capacity' => ['required', 'integer'],
            'nb_session' => ['required', 'integer'],
        ]);
        $group = Group::find($id);
        $group->delete();
        $teacher = Teacher::find($request->teacher_id);
        $level = Level::find($request->level_id);
        $group = Group::create([
            'name' => $request->name,
            'price_per_month' => $request->price_per_month,

            'capacity' => $request->capacity,
            'nb_session' => $request->capacity,
        ]);
        $teacher->groups()->save($group);
        $level->groups()->save($group);

        $group->save();

        return response()->json(200);
    }

    public function students_create(Request $request, $id)
    {
        $group = Group::find($id);
        foreach ($request->students as $studentId) {
            if (!$group->students->contains($studentId)) {
                $student = Student::find($studentId);
                $student->user->wallet->balance = $student->user->wallet->balance - $group->price_per_month;

                $checkout = Checkout::create([
                    'price' => $group->price_per_month,
                    'date' => Carbon::now(),
                ]);
                $student->checkouts()->save($checkout);
                $group->checkouts()->save($checkout);
                $student->user->wallet->save();
            }
        }
        if ($request->students) {
            $group->students()->sync($request->students);
        } else {
            $group->students()->detach();
        }
        return response()->json(200);
    }
    public function students_delete(Request $request, $id, $student_id)
    {
        $group = Group::find($id);
        $group->students()->detach($student_id);
        return response()->json(200);
    }
    public function student_notin_group($id)
    {
        $group = Group::find($id);
        $level = $group->level;

        $students = $level
            ->students()
            ->whereNotIn('id', $group->students->modelKeys())
            ->get();
        foreach ($students as $student) {
            # code...
            $student['user'] = $student->user;
        }
        return response()->json($students, 200);
    }
    public function students($id)
    {
        $group = Group::with(['students.user'])->find($id);
        $students = $group->students;
        return response()->json($students, 200);
    }

    public function sessions($id)
    {
        $group = Group::with(['sessions.exceptions'])->find($id);
        $sessions = $group->sessions;
        return response()->json($sessions, 200);
    }
    public function sessions_create(Request $request, $id)
    {
        $group = Group::find($id);
        $session = Session::create([
            "classroom" => $request->classroom,
            "starts_at" => $request->starts_at,
            "ends_at" => $request->ends_at,
            "repeating" => $request->repeating,
        ]);
        $group->sessions()->save($session);

        return response()->json($session, 200);
    }
}
