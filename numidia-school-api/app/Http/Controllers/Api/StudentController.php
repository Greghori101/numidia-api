<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Group;
use App\Models\Level;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StudentController extends Controller
{
    public function all()
    {
        $students = Student::with(['user'])->get();
        return response()->json($students, 200);
    }
    public function index(Request $request)
    {
        $request->validate([
            'perPage' => ['integer', 'min:1'],
            'sortBy' => ['string'],
            'sortDirection' => ['string', 'in:asc,desc'],
            'search' => ['string'],
            'level_id' => ['integer'],
            'gender' => ['string', 'in:male,female'],
        ]);
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
            ->with(['level', 'fee_inscription'])
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

    public function student_group_add(Request $request, $student_id)
    {
        $request->validate([
            'groups' => ['required', 'array'],
        ]);
        $student = Student::find($student_id);
        $user = User::find($request->user_id);
        $groups = $request->groups;

        foreach ($groups as $group) {
            $group = (object) $group;

            $student->groups()->attach($group->id);
            $student->user->wallet->balance -= $group->price;

            $checkout = Checkout::create([
                'price' => $group->price,
                'date' => Carbon::now(),
                'nb_session' => $group->rest_session,
            ]);

            $user->checkouts()->save($checkout);
            $student->checkouts()->save($checkout);
            $group = Group::find($group->id);
            $group->checkouts()->save($checkout);
            $student->user->wallet->save();
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/notifications', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'type' => "info",
                    'title' => "new group members",
                    'content' => "new student has been added",
                    'displayed' => false,
                    'id' => $group->teacher->user->id,
                    'department' => env('DEPARTEMENT'),
                ]);
        }

        return response()->json(200);
    }
    public function student_group($id)
    {

        $student = Student::find($id);
        $groups = $student->groups()->with(["teacher.user", "level"])->get();



        return response()->json($groups, 200);
    }
    public function student_group_remove($student_id, $group_id)
    {
        $student = Student::find($student_id);
        $student->groups()->detach($group_id);

        $group = Group::find($group_id);

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/notifications', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'type' => "warning",
                'title' => "group members",
                'content' => "a student has been removed",
                'displayed' => false,
                'id' => $group->teacher->user->id,
                'department' => env('DEPARTEMENT'),
            ]);
        return response()->json(200);
    }
    public function group_notin_student($id)
    {
        $student = Student::find($id);
        $level = $student->level;

        $groups = $level->groups()
            ->whereNotIn('id',  $student->groups->modelKeys())
            ->with("level", "teacher.user")
            ->get();
        foreach ($groups as $group) {
            # code...
            $group['price'] = $group->price_per_month / $group->nb_session * $group->rest_session;
        }
        return response()->json($groups, 200);
    }

    public  function student_checkouts($id)
    {
        $checkouts = Checkout::query()
            ->with(['group.teacher.user'])
            ->when($id, function ($q) use ($id) {
                return $q->where('student_id', 'like', "%$id%");
            })->get();
        return response()->json($checkouts, 200);
    }
}
