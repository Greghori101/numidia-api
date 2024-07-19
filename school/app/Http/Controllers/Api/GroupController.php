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
use Illuminate\Support\Facades\Http;

class GroupController extends Controller
{


    public function index(Request $request)
    {
        $request->validate([
            'perPage' => ['nullable', 'integer'],
            'sortBy' => ['nullable', 'string'],
            'sortDirection' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'level_id' => ['nullable'],
            'teacher_id' => ['nullable'],
        ]);

        $perPage = $request->query('perPage', 10);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $search = $request->query('search', "");
        $levelId = $request->query('level_id');
        $teacherId = $request->query('teacher_id');

        $teachersQuery = Teacher::with(['groups' => function ($query) use ($levelId, $search, $sortBy, $sortDirection) {
            $query->when($levelId, function ($q) use ($levelId) {
                $q->where('level_id', $levelId);
            })
                ->whereRaw('LOWER(module) LIKE ?', ["%$search%"])
                ->orderBy($sortBy, $sortDirection)
                ->with(['level']);
        }, 'user'])->has('groups');

        if ($teacherId) {
            $teachersQuery->where('id', $teacherId);
        }

        // Paginate the results by teachers
        $teachers = $teachersQuery->paginate($perPage);

        return response()->json($teachers, 200);
    }

    public function show($id)
    {
        $group = Group::with(['teacher.user', 'level', 'students.user', 'sessions.exceptions', 'students.level'])->find($id);

        return response()->json($group, 200);
    }

    public function create(Request $request)
    {
        $request->validate([
            'teacher_id' => ['required'],
            'level_id' => ['required'],
            'annex' => ['required', 'string'],
            'type' => ['required', 'string'],
            'module' => ['required', 'string'],
            'capacity' => ['required', 'integer'],
            'nb_session' => ['required', 'integer'],
            'price_per_month' => ['required', 'integer'],
        ]);

        $teacher = Teacher::find($request->teacher_id);
        $level = Level::find($request->level_id);
        $group = Group::create([
            'annex' => $request->annex,
            'module' => $request->module,
            'capacity' => $request->capacity,
            'price_per_month' => $request->price_per_month,
            'type' => $request->type,
            'nb_session' => $request->nb_session,
            'percentage' => $teacher->percentage,
            'main_session' => "",
        ]);
        $teacher->groups()->save($group);
        $level->groups()->save($group);
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/notifications', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'type' => "info",
                'title' => "New Group",
                'content' => "new group has been created",
                'displayed' => false,
                'id' => $teacher->user->id,
                'department' => env('DEPARTMENT'),
            ]);


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
            'annex' => ['required', 'string'],
            'module' => ['required', 'string'],
            'capacity' => ['required', 'integer'],
            'nb_session' => ['required', 'integer'],
            'main_session' => ['required', 'string'],
            'percentage' => ['required', 'integer'],
            'price_per_month' => ['required', 'integer'],
            'type' => ['required', 'string'],
        ]);
        $group = Group::find($id);
        $group->delete();
        $teacher = Teacher::find($request->teacher_id);
        $level = Level::find($request->level_id);
        $group = Group::create([
            'module' => $request->module,
            'annex' => $request->annex,
            'price_per_month' => $request->price_per_month,
            'type' => $request->type,
            'capacity' => $request->capacity,
            'nb_session' => $request->nb_session,
            'main_session' => $request->main_session,
            'percentage' => $request->percentage,
        ]);
        $teacher->groups()->save($group);
        $level->groups()->save($group);

        $group->save();

        return response()->json(200);
    }

    public function students_create(Request $request, $id)
    {
        $request->validate([
            'students' => ['array'],
            'students.*' => ['string'],
        ]);
        $group = Group::find($id);
        $studentsToRemove = collect($group->students()->pluck('student_id')->toArray())->diff($request->students);

        $students = [];
        foreach ($request->students as $studentId) {
            $student = Student::find($studentId);
            $rest_session = $group->nb_session - $group->current_nb_session + 1;
            if (!$group->students->contains($studentId)) {
                $checkout = Checkout::create([
                    'price' => $group->price_per_month / $group->nb_session * $rest_session,
                    'month' => $group->current_month,
                    'teacher_percentage' => $group->percentage,
                ]);

                $data = ["amount" => - ($checkout->price - $checkout->discount), "user" => $student->user];
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);

                $student->checkouts()->save($checkout);
                $group->checkouts()->save($checkout);
                $students[] = [$studentId => [
                    'first_session' => $group->current_nb_session,
                    'first_month' => $group->current_month,
                    'debt' => $student->groups()->where('group_id', $id)->first()->pivot->debt + $checkout->price - $checkout->discount,
                ]];
            } else {
                $students[] = [$studentId => [
                    'first_session' => $group->current_nb_session,
                    'first_month' => $group->current_month,
                    'debt' => $student->groups()->where('group_id', $id)->first()->pivot->debt,
                ]];
            }
        }

        foreach ($studentsToRemove as $studentIdToRemove) {
            $checkoutToRemove = Checkout::where('student_id', $studentIdToRemove)
                ->where('group_id', $group->id)
                ->where('status', 'pending')
                ->first();

            if ($checkoutToRemove) {
                $data = ["amount" => $checkoutToRemove->price - $checkoutToRemove->discount, "user" => $student->user];
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);
                $checkoutToRemove->delete();
            }
            $students[] = [$studentIdToRemove => [
                'last_session' => $group->current_nb_session,
                'last_month' => $group->current_month,
                'status' => 'stopped',
                'debt' => $checkoutToRemove ?
                    $student->groups()->where('group_id', $id)->first()->pivot->debt + $checkoutToRemove->price - $checkoutToRemove->discount
                    : $student->groups()->where('group_id', $id)->first()->pivot->debt,
            ]];
        }

        if ($students) {
            $group->students()->sync($students);
        }
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/notifications', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'type' => "info",
                'title' => "Group members",
                'content' => "the group members has been updated",
                'displayed' => false,
                'id' => $group->teacher->user->id,
                'department' => env('DEPARTMENT'),
            ]);

        return response()->json(200);
    }

    public function students_delete($id, $student_id)
    {
        $group = Group::find($id);
        $student = $group->students()->where("student_id", $student_id)->first();

        $student->groups()->updateExistingPivot([$group->id => ['status' => 'stopped']]);
        $rest_session = $group->nb_session - $group->current_nb_session + 1;

        $checkoutToRemove = Checkout::where('student_id', $student_id)
            ->where('group_id', $group->id)
            ->where('status', 'pending')
            ->where('paid_price', 0)
            ->first();


        if ($checkoutToRemove) {
            $data = ["amount" => $checkoutToRemove->price - $checkoutToRemove->discount, "user" => $student->user];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);
            $checkoutToRemove->delete();
        }

        $students[] = [$student_id => [
            'first_session' => $group->current_nb_session,
            'first_month' => $group->current_month,
            'debt' => $checkoutToRemove ?
                $student->groups()->where('group_id', $id)->first()->pivot->debt + $checkoutToRemove->price - $checkoutToRemove->discount
                : $student->groups()->where('group_id', $id)->first()->pivot->debt,
        ]];

        $group->students()->syncWithoutDetaching($students);

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
        $group = Group::with(["sessions.exceptions", "sessions.group.level", "sessions.group.teacher.user"])->find($id);
        $sessions = $group->sessions;
        return response()->json($sessions, 200);
    }
    public function sessions_create(Request $request, $id)
    {
        $request->validate([
            'classroom' => ['required', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'repeating' => ['required', 'string'],
        ]);

        $group = Group::find($id);

        // Parse the start and end times
        $starts_at = Carbon::parse($request->starts_at);
        $ends_at = Carbon::parse($request->ends_at);

        // Check for overlapping sessions
        $overlappingSessions = $group->sessions()
            ->where(function ($query) use ($starts_at, $ends_at) {
                $query->whereBetween('starts_at', [$starts_at, $ends_at])
                    ->orWhereBetween('ends_at', [$starts_at, $ends_at])
                    ->orWhere(function ($query) use ($starts_at, $ends_at) {
                        $query->where('starts_at', '<', $starts_at)
                            ->where('ends_at', '>', $ends_at);
                    });
            })
            ->exists();

        if ($overlappingSessions) {
            return response()->json(['error' => 'The session times overlap with an existing session.'], 422);
        }

        // Create the new session
        $session = Session::create([
            "classroom" => $request->classroom,
            "starts_at" => $starts_at,
            "ends_at" => $ends_at,
            "repeating" => $request->repeating,
        ]);
        $group->sessions()->save($session);

        // Notify students
        foreach ($group->students as $student) {
            Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                ->post(env('AUTH_API') . '/api/notifications', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'type' => "info",
                    'title' => "New Session",
                    'content' => "A new session has been created at " . $starts_at->toDateTimeString(),
                    'displayed' => false,
                    'id' => $student->user->id,
                    'department' => env('DEPARTMENT'),
                ]);
        }

        return response()->json($session, 200);
    }


    public function all()
    {
        $groups  = Group::with(['level', 'teacher.user'])->get();
        return response()->json($groups, 200);
    }
    public function all_details()
    {
        $groups  = Group::with(['teacher.user', 'level', 'sessions.exceptions'])->get();
        return response()->json($groups, 200);
    }
}
