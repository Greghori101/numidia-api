<?php

namespace App\Http\Controllers\Api;

// use Twilio\Rest\Client;
use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    public function all()
    {
        $users = User::all();
        return response()->json($users, 200);
    }
    public function index(Request $request)
    {
        $request->validate([
            'role' => ['string'],
            'sortBy' => ['string'],
            'sortDirection' => ['string', 'in:asc,desc'],
            'perPage' => ['integer', 'min:1'],
            'search' => ['string'],
            'gender' => ['string'],
        ]);
        $role = $request->query('role', "");
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');
        $gender = $request->query('gender', '');

        $usersQuery = User::query()->where('id', '<>', Auth::id());

        $usersQuery->when($search, function ($query) use ($search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        });

        $usersQuery->when($gender, function ($query) use ($gender) {
            return $query->where('gender', $gender);
        });
        $usersQuery->when($role, function ($query) use ($role) {
            return $query->where('role', $role);
        });

        $users = $usersQuery->orderBy($sortBy, $sortDirection)

            ->paginate($perPage);

        return $users;
    }
    public function show(Request $request, $id = null)
    {
        if (!$id) {
            $user = User::with(["wallet", "checkouts", "receipts",])->find($request->user_id);
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->get(env('AUTH_API') . '/api/profile/' . $user->id, [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                ]);
            $user['activities'] = $response->json()['activities'];
            $user['profile_picture'] = $response->json()['profile_picture'];
            return response()->json($user, 200);
        } else {
            $user = User::find($id);
            if ($user->role == "student") {
                $user->load('receipts', 'wallet', 'student.presences.group.teacher.user', 'student.groups.teacher.user', 'student.level', 'student.checkouts', 'student.fee_inscription', 'student.supervisor.user', 'student.user');
            } elseif ($user->role == "teacher") {
                $user->load('receipts', 'wallet', 'teacher.groups.level', 'teacher.groups.students.user.wallet', 'teacher.groups.presence.students.user');
            } else if ($user->role == "supervisor") {
                $user->load('receipts', 'wallet', 'supervisor.students.user.profile_picture', 'supervisor.students.presences.group.teacher.user', 'supervisor.students.groups.teacher.user', 'supervisor.students.level', 'supervisor.students.checkouts', 'supervisor.students.fee_inscription',);
            } else {
                $user->load('receipts', 'wallet',);
            }
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->get(env('AUTH_API') . '/api/profile/' . $user->id, [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                ]);
            $user['profile_picture'] = $response->json()['profile_picture'];

            return response()->json($user, 200);
        }
    }
    public function users_list(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['string'],
        ]);
        $users = User::whereIn('id', $request->ids)->get();

        return response()->json($users, 200);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:10'],
            'gender' => 'required|in:male,female',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users',],
        ]);
        $request->merge([
            'role' => strtolower($request->role),
        ]);



        $user = User::create([
            'email' => $request->email,
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'role' => $request->role,
            'gender' => $request->gender,
        ]);



        $user->wallet()->save(new Wallet());

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/users/create', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
            ]);
        if ($user->role == 'teacher') {
            $user->teacher()->save(new Teacher([
                'module' => $request->module,
                'percentage' => $request->percentage,
            ]));
        } elseif ($user->role == 'student') {
            $level = Level::find($request->level_id);
            $student = new Student();
            $user->student()->save($student);
            $level->students()->save($student);
            return Student::with(['user', 'level'])->find($student->id);
        } elseif ($user->role == 'supervisor') {
            $user->supervisor()->save(new Supervisor());
        }


        $users = User::where('role', '<>', "student")
            ->where('role', '<>', "teacher")
            ->where('role', '<>', "supervisor")
            ->get();
        foreach ($users as $reciever) {
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/notifications', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'type' => "success",
                    'title' => "New Registration",
                    'content' => $user->name . " have been registred to numidia platform",
                    'displayed' => false,
                    'id' => $reciever->id,
                    'department' => env('DEPARTEMENT'),
                ]);
        }
        return response()->json(200);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:10'],
            'gender' => ['required', 'in:male,female'],
        ]);
        $user = User::find($id);

        $user->update([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,

        ]);

        return response()->json(['message' => 'User data updated successfully'], 200);
    }
    public function delete($id)
    {
        $user = User::where('id', $id)->first();
        $user->forceDelete();
        return response()->json(200);
    }
    public function student(Request $request)
    {

        $user = User::find($request->user_id);
        if ($user->role == "teacher") {
            $teacher = $user->teacher->load(["groups.students.user", "groups.students.level"]);
            $students = $teacher->groups->pluck('students')->flatten();
        } elseif ($user->role == "supervisor") {
            $students = $user->supervisor->students->load(["user", "level"]);
        }
        return response()->json($students, 200);
    }
    public function checkouts(Request $request)
    {
        $user = User::find($request->user_id);
        if ($user->role == "student") {
            $checkouts = $user->student->checkouts;
        }
        $checkouts->load(["group.level", "group.teacher.user"]);
        return response()->json($checkouts, 200);
    }
    public function exams(Request $request)
    {
        $user = User::find($request->user_id);
        if ($user->role == "student") {
            $exams = $user->student->exams;
        } elseif ($user->role == "teacher") {
            $exams = $user->teacher->exams;
        }
        $exams->load(["teacher", "questions.choices"]);


        return response()->json($exams, 200);
    }
    public function groups(Request $request)
    {
        $user = User::find($request->user_id);
        if ($user->role == "student") {
            $groups = $user->student->groups;
        } elseif ($user->role == "teacher") {
            $groups = $user->teacher->groups;
        }
        $groups->load(["level", "teacher.user"]);
        return response()->json($groups, 200);
    }
    public function receipts(Request $request)
    {
        $user = User::find($request->user_id);
        $receipts = $user->receipts;
        $receipts->load(["checkouts"]);
        return response()->json($receipts, 200);
    }
    public function sessions(Request $request)
    {
        $user = User::find($request->user_id);
        if ($user->role == "student") {
            $student = $user->student->load(['groups.sessions.exceptions', "groups.sessions.group.teacher.user"]);
            $sessions = $student->groups->pluck('sessions')->flatten();
        } elseif ($user->role == "teacher") {
            $sessions = $user->teacher->sessions->load(["exceptions", "group.teacher.user"]);
        }
        return response()->json($sessions, 200);
    }
}
