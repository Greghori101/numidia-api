<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Group;
use App\Models\Level;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('perPage', 10);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $search = $request->query('search', "");
        $levelId = $request->query('level_id');
        $gender = $request->query('gender', '');

        $query = Student::query();
        $level = Level::find($levelId);
        if ($level) {

            $query = $level->students();
        }

        $students = $query->join('users', 'students.user_id', '=', 'users.id')
            ->select('students.*', "users.$sortBy as sorted_column")
            ->where(function ($query) use ($gender, $search) {
                $query->where('users.gender', 'like', "%$gender%")
                    ->where(function ($subQuery) use ($search) {
                        $subQuery->whereRaw('LOWER(users.name) LIKE ?', ["%$search%"])
                            ->orWhereRaw('LOWER(users.email) LIKE ?', ["%$search%"]);
                    });
            })
            ->orderByRaw("LOWER(sorted_column) $sortDirection")
            ->with('level')
            ->when($perPage !== 'all', function ($query) use ($perPage) {
                return $query->paginate($perPage);
            }, function ($query) {
                return $query->get();
            });

        $students->each(function ($student) {
            $student['active'] = $student->user->wallet->balance >= 0;
        });

        return $students;
    }

    public function show($id)
    {
        $student = Student::with(['level', 'user.wallet', 'user.profile_picture'])->find($id);
        return $student;
    }
    public function student_group_add($student_id, $group_id)
    {
        $group = Group::find($group_id);
        $student = Student::find($student_id);

        $student->groups()->attach($group_id);
        $student->user->wallet->balance = $student->user->wallet->balance - $group->price_per_month;
        $checkout = Checkout::create([
            'price' => $group->price_per_month,
            'date' => Carbon::now(),
        ]);
        $student->checkouts()->save($checkout);
        $group->checkouts()->save($checkout);
        $student->user->wallet->save();
        $student->save();
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
        $checkout = Checkout::create([
            'price' => $request->price,
            'nb_session' => $request->nb_session,
            'total' => $request->nb_session * $request->price,
            'end_date' => $request->end_date,
        ]);

        $checkout->student()->associate(Student::find($student_id));
        $checkout->group()->associate(Group::find($group_id));


        return response()->json(200);
    }
}
