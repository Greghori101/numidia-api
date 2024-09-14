<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'role' => ['nullable', 'string'],
            'sortBy' => ['nullable', 'string'],
            'sortDirection' => ['nullable', 'string', 'in:asc,desc'],
            'perPage' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string'],
            'gender' => ['nullable', 'string'],
        ]);
        $role = $request->query('role', "");
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');
        $gender = $request->query('gender', '');

        $usersQuery = User::query()->where('id', '<>', $request->user["id"]);

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
            $user = User::findOrFail($request->user["id"]);
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->get(env('AUTH_API') . '/api/profile/' . $user->id, [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                ]);
            if ($response->failed()) {
                $statusCode = $response->status();
                $errorBody = $response->json();
                abort($statusCode, $errorBody['message'] ?? 'Unknown error');
            }

            if ($response->serverError()) {
                abort(500, 'Server error occurred');
            }

            if ($response->clientError()) {
                abort($response->status(), 'Client error occurred');
            }
            $user['activities'] = $response->json()['activities'];
            $user['profile_picture'] = $response->json()['profile_picture'];
            $user['wallet'] = $response->json()['wallet'];

            return response()->json($user, 200);
        } else {
            $user = User::findOrFail($id);
            if ($user->role == "student") {
                $user->load("receipts.services",  'student.groups', 'student.groups.teacher.user', 'student.level', 'student.checkouts', 'student.fee_inscription', 'student.supervisor.user', 'student.user');
                foreach ($user->student->groups as $group) {
                    $group['presences'] = $user->student->presences()->where('group_id', $group->id)->get()->filter(function ($presence) {
                        return $presence->status === 'ended';
                    });
                }
            } elseif ($user->role == "teacher") {
                $user->load("receipts.services",  'teacher.groups.level',  'teacher.groups.presences.students.user', 'teacher.groups.students.user');
            } else if ($user->role == "supervisor") {
                $user->load("receipts.services",  'supervisor.students.user',  'supervisor.students.groups.teacher.user', 'supervisor.students.level', 'supervisor.students.checkouts', 'supervisor.students.fee_inscription',);
                foreach ($user->supervisor->students as $student) {
                    foreach ($student->groups as $group) {
                        $group['presences'] = $student->presences()->where('group_id', $group->id)->get()->filter(function ($presence) {
                            return $presence->status === 'ended';
                        });
                    }
                }
            } else {
                $user->load("receipts.services",);
            }
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->get(env('AUTH_API') . '/api/profile/' . $user->id, [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                ]);
            if ($response->failed()) {
                $statusCode = $response->status();
                $errorBody = $response->json();
                abort($statusCode, $errorBody['message'] ?? 'Unknown error');
            }

            if ($response->serverError()) {
                abort(500, 'Server error occurred');
            }

            if ($response->clientError()) {
                abort($response->status(), 'Client error occurred');
            }
            $user['profile_picture'] = $response->json()['profile_picture'];
            $user['wallet'] = $response->json()['wallet'];

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

        return DB::transaction(function () use ($request) {
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'role' => ['required', 'string', 'max:255'],
                'phone_number' => ['required', 'string', 'max:10'],
                'gender' => 'required|in:male,female',
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users',],
            ];
            $request->merge([
                'role' => strtolower($request->role),
            ]);
            if ($request->role === "student") {
                $rules['level_id'] = ['required', 'exists:levels,id'];
            }
            $request->validate($rules);

            $user = User::create([
                'email' => $request->email,
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'role' => $request->role,
                'gender' => $request->gender,
            ]);

            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/users/create', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'id' => $user->id,
                    'email' => $request->email,
                    'name' => $request->name,
                    'phone_number' => $request->phone_number,
                    'role' => $request->role,
                    'gender' => $request->gender,
                    'permissions' => $request->permissions,

                ]);
            if ($response->failed()) {
                $statusCode = $response->status();
                $errorBody = $response->json();
                abort($statusCode, $errorBody['message'] ?? 'Unknown error');
            }

            if ($response->serverError()) {
                abort(500, 'Server error occurred');
            }

            if ($response->clientError()) {
                abort($response->status(), 'Client error occurred');
            }
            if ($user->role == 'teacher') {
                $user->teacher()->save(new Teacher([
                    'modules' => implode("|", $request->modules),
                    'levels' => implode("|", $request->levels),
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
            foreach ($users as $receiver) {
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/notifications', [
                        'client_id' => env('CLIENT_ID'),
                        'client_secret' => env('CLIENT_SECRET'),
                        'type' => "success",
                        'title' => "New Registration",
                        'content' => $user->name . " have been registered to numidia platform",
                        'displayed' => false,
                        'id' => $receiver->id,
                        'department' => env('DEPARTMENT'),
                    ]);
                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorBody = $response->json();
                    abort($statusCode, $errorBody['message'] ?? 'Unknown error');
                }

                if ($response->serverError()) {
                    abort(500, 'Server error occurred');
                }

                if ($response->clientError()) {
                    abort($response->status(), 'Client error occurred');
                }
            }
            return response()->json(200);
        });
    }
    public function verify_user(Request $request)
    {
        $user = User::findOrFail($request->user["id"]);

        if ($user) {
            return response()->json(true, 200);
        } else {
            return response()->json(false, 200);
        }
    }
    public function create(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $user = User::find($request->user["id"]);
            if ($user) {
                return response()->json(200);
            }
            $rules = [

                'name' => ['required', 'string', 'max:255'],
                'role' => ['required', 'string', 'max:255'],
                'phone_number' => ['required', 'string', 'max:10'],
                'gender' => 'required|in:male,female',
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users',],
            ];
            $request->merge([
                'role' => strtolower($request->role),
            ]);
            if ($request->role === "student") {
                $rules['level_id'] = ['required', 'exists:levels,id'];
            }
            $request->validate($rules);

            $user = User::create([
                'id' => $request->user["id"],
                'email' => $request->email,
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'role' => $request->role,
                'gender' => $request->gender,
            ]);


            if ($user->role == 'student') {
                $level = Level::findOrFail($request->level_id);
                $student = new Student();
                $user->student()->save($student);
                $level->students()->save($student);
                return Student::with(['user', 'level'])->find($student->id);
            } elseif ($user->role == 'supervisor') {
                $user->supervisor()->save(new Supervisor());
            } else {
                return response()->json(false, 200);
            }

            $users = User::where('role', '<>', "student")
                ->where('role', '<>', "teacher")
                ->where('role', '<>', "supervisor")
                ->get();
            foreach ($users as $receiver) {
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/notifications', [
                        'client_id' => env('CLIENT_ID'),
                        'client_secret' => env('CLIENT_SECRET'),
                        'type' => "success",
                        'title' => "New Registration",
                        'content' => $user->name . " have been registered to numidia platform",
                        'displayed' => false,
                        'id' => $receiver->id,
                        'department' => env('DEPARTMENT'),
                    ]);
                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorBody = $response->json();
                    abort($statusCode, $errorBody['message'] ?? 'Unknown error');
                }

                if ($response->serverError()) {
                    abort(500, 'Server error occurred');
                }

                if ($response->clientError()) {
                    abort($response->status(), 'Client error occurred');
                }
            }
            return response()->json(true, 200);
        });
    }
    public function update(Request $request, $id = null)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:10'],
            'gender' => ['required', 'in:male,female'],
        ]);

        return DB::transaction(function () use ($request, $id) {
            if ($id) {
                $user = User::find($id);
            } else {
                $user = User::find($request->user["id"]);
            }
            if (!$user) {
                abort(404);
            }
            $user->update([
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
            ]);
            if ($user->role == 'teacher') {
                $user->teacher()->update([
                    'modules' => implode("|", $request->modules),
                    'levels' => implode("|", $request->levels),
                    'percentage' => $request->percentage,
                ]);
            } elseif ($user->role == 'student') {
                $level = Level::findOrFail($request->level_id);
                $student = $user->student;
                $level->students()->save($student);
                return Student::with(['user', 'level'])->findOrFail($student->id);
            }

            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json', 'Authorization' => 'Bearer ' . $request->bearerToken()])
                ->put(env('AUTH_API') . '/api/users/' . $id, [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'id' => $user->id,
                    'email' => $request->email,
                    'name' => $request->name,
                    'phone_number' => $request->phone_number,
                    'role' => $request->role,
                    'gender' => $request->gender,
                    'permissions' => $request->permissions,

                ]);
            if ($response->failed()) {
                $statusCode = $response->status();
                $errorBody = $response->json();
                abort($statusCode, $errorBody['message'] ?? 'Unknown error');
            }

            if ($response->serverError()) {
                abort(500, 'Server error occurred');
            }

            if ($response->clientError()) {
                abort($response->status(), 'Client error occurred');
            }

            return response()->json(['message' => 'User data updated successfully'], 200);
        });
    }
    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(200);
    }
    public function student(Request $request)
    {

        $user = User::findOrFail($request->user["id"]);
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
        $user = User::findOrFail($request->user["id"]);
        if ($user->role == "student") {
            $checkouts = $user->student->checkouts;
        }
        $checkouts->load(["group.level", "group.teacher.user"]);
        return response()->json($checkouts, 200);
    }
    public function exams(Request $request)
    {
        $user = User::findOrFail($request->user["id"]);
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
        $user = User::findOrFail($request->user["id"]);
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
        $user = User::findOrFail($request->user["id"]);
        $receipts = $user->receipts;
        $receipts->load(["services"]);
        return response()->json($receipts, 200);
    }
    public function sessions(Request $request)
    {
        $user = User::findOrFail($request->user["id"]);
        if ($user->role == "student") {
            $student = $user->student->load(['groups.sessions.exceptions', "groups.sessions.group.teacher.user"]);
            $sessions = $student->groups->pluck('sessions')->flatten();
        } elseif ($user->role == "teacher") {
            $sessions = $user->teacher->sessions->load(["exceptions", "group.teacher.user"]);
        }
        return response()->json($sessions, 200);
    }
}
