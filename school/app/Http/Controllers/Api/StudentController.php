<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Group;
use App\Models\Level;
use App\Models\Student;
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
            'perPage' => ['nullable', 'integer', 'min:1'],
            'sortBy' => ['nullable', 'string'],
            'sortDirection' => ['nullable', 'string', 'in:asc,desc'],
            'search' => ['nullable', 'string'],
            'level_id' => ['nullable',],
            'gender' => ['nullable', 'string', 'in:male,female'],
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
            ->with(['level', 'user', 'fee_inscription'])
            ->when($perPage !== 'all', function ($query) use ($perPage) {
                return $query->paginate($perPage);
            }, function ($query) {
                return $query->get();
            });



        return $students;
    }

    public function show($id)
    {
        $student = Student::with(['level', 'user', 'user.receipts.employee', 'groups.teacher.user'])->find($id);
        return $student;
    }
    public function student_group_add(Request $request, $student_id)
    {
        $request->validate([
            'groups' => ['required', 'array'],
        ]);
        $student = Student::find($student_id);
        $groups = $request->groups;

        foreach ($groups as $group) {
            $group = (object) $group;

            $checkout = Checkout::create([
                'price' => $group->price_per_month / $group->nb_session * $group->rest_session,
                'discount' => $group->discount,
                'month' => $group->current_month,
                'teacher_percentage' => $group->percentage,
            ]);
            $student->groups()->attach([$group->id => [
                'first_session' => $group->current_nb_session,
                'first_month' => $group->current_month,
                'debt' => ($checkout->price - $checkout->discount),
            ]]);
            $data = ["amount" => (-$checkout->price + $checkout->discount), "user" => $student->user];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);

            $student->checkouts()->save($checkout);
            $group = Group::find($group->id);
            $group->checkouts()->save($checkout);
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/notifications', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'type' => "info",
                    'title' => "new group members",
                    'content' => "new student has been added",
                    'displayed' => false,
                    'id' => $group->teacher->user->id,
                    'department' => env('DEPARTMENT'),
                ]);
        }

        return response()->json(200);
    }
    public function student_group_remove($student_id, $group_id)
    {
        $student = Student::find($student_id);
        $group = Group::find($group_id);

        $rest_session = $group->nb_session - $group->current_nb_session + 1;


        $checkoutToRemove = Checkout::where('student_id', $student_id)
            ->where('group_id', $group->id)
            ->where('status', 'pending')
            ->where('paid_price',0)
            ->first();

        if ($checkoutToRemove) {
            $data = ["amount" => ($checkoutToRemove->price - $checkoutToRemove->discount), "user" => $student->user];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);

            $checkoutToRemove->delete();
        }

        $student->groups()->syncWithoutDetaching([$group->id => [
            'last_session' => $group->current_nb_session,
            'last_month' => $group->current_month,
            'status' => 'stopped',
            'debt' => $checkoutToRemove ? $student->groups()->where('group_id',$group_id)->pivot->debt + $checkoutToRemove->price - $checkoutToRemove->discount : $student->groups()->where('group_id',$group_id)->pivot->debt,

        ]]);

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/notifications', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'type' => "warning",
                'title' => "group members",
                'content' => "A student has been removed",
                'displayed' => false,
                'id' => $group->teacher->user->id,
                'department' => env('DEPARTMENT'),
            ]);
        return response()->json(200);
    }
    public function student_group($id)
    {
        $student = Student::find($id);
        $groups = $student->groups()->with(["teacher.user", "level"])->get();



        return response()->json($groups, 200);
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

    public function student_mark_sheets(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $mark_sheets = $student->mark_sheets()->with(['marks'])->where('level_id', 'like', '%' . $request->level_id . '%')->where('year', 'like', '%' . $request->year . '%')->where('season', 'like', '%' . $request->season . '%')->get();

        return response()->json($mark_sheets, 200);
    }
}
