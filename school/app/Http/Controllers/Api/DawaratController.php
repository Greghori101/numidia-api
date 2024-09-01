<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Group;
use App\Models\Level;
use App\Models\Session;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Amphi;
use App\Models\File;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DawaratController extends Controller
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

        $teachersQuery = Teacher::with(['user', 'groups' => function ($query) use ($levelId, $search, $sortBy, $sortDirection) {
            $query->when($levelId, function ($q) use ($levelId) {
                $q->where('level_id', $levelId);
            })
                ->where('type', 'dawarat')
                ->whereRaw('LOWER(module) LIKE ?', ["%$search%"])
                ->orderBy($sortBy, $sortDirection)
                ->with(['level', 'photos', 'amphi']);
        }, 'user'])->has('groups');

        if ($teacherId) {
            $teachersQuery->where('id', $teacherId);
        }

        // Paginate the results by teachers
        $teachers = $teachersQuery->paginate($perPage);

        return response()->json($teachers, 200);
    }
    public function all(Request $request)
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

        $groupsQuery = Group::with(['level', 'teacher.user', 'sessions.exceptions', 'photos', 'amphi.sections', 'sessions.exceptions'])
            ->when($levelId, function ($q) use ($levelId) {
                $q->where('level_id', $levelId);
            })
            ->where('type', 'dawarat')
            ->whereRaw('LOWER(module) LIKE ?', ["%$search%"])
            ->orderBy($sortBy, $sortDirection);

        if ($teacherId) {
            $groupsQuery->where('teacher_id', $teacherId);
        }

        // Paginate the results by groups
        $groups = $groupsQuery->paginate($perPage);

        return response()->json($groups, 200);
    }
    public function show($id)
    {
        $group = Group::with(['teacher.user', 'level', 'students.user', 'sessions.exceptions', 'students.level', 'photos', 'amphi.sections'])->find($id);

        return response()->json($group, 200);
    }
    public function create(Request $request)
    {
        $request->validate([
            'teacher_id' => ['required'],
            'level_id' => ['required'],
            'module' => ['required', 'string'],
            'capacity' => ['required', 'integer'],
            'nb_session' => ['required', 'integer'],
            'price_per_month' => ['required', 'integer'],
        ]);

        $teacher = Teacher::find($request->teacher_id);
        $level = Level::find($request->level_id);
        $amphi = Amphi::find($request->amphi_id);
        $group = Group::create([
            'annex' => null,
            'module' => $request->module,
            'capacity' => $request->capacity,
            'price_per_month' => $request->price_per_month,
            'type' => 'dawarat',
            'nb_session' => $request->nb_session,
            'percentage' => $teacher->percentage,
            'main_session' => "",
            'current_month' => date('n'),
            'current_nb_session' => 1,
        ]);
        $teacher->groups()->save($group);
        $level->groups()->save($group);
        if ($amphi) {
            $amphi->dawarat()->save($group);
        }

        $images =  $request->file('uploaded_images');
        if ($images) {
            foreach ($images as $image) {
                $file = $image;

                $file_extension = $image->extension();

                $bytes = random_bytes(ceil(64 / 2));
                $hex = bin2hex($bytes);
                $file_name = substr($hex, 0, 64);

                $file_url = '/dawarat/' .  $file_name . '.' . $file_extension;

                Storage::put($file_url, file_get_contents($file));

                $group->photos()->create(['url' => $file_url]);
            }
        }
       
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
            'module' => ['required', 'string'],
            'capacity' => ['required', 'integer'],
            'nb_session' => ['required', 'integer'],
            'main_session' => ['required', 'string'],
            'percentage' => ['required', 'integer'],
            'price_per_month' => ['required', 'integer'],
        ]);
        $group = Group::find($id);
        $group->delete();
        $teacher = Teacher::find($request->teacher_id);
        $level = Level::find($request->level_id);
        $group = Group::create([
            'module' => $request->module,
            'price_per_month' => $request->price_per_month,
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
                $query->where(function ($query) use ($starts_at) {
                    $query->where('starts_at', '<', $starts_at)
                        ->where('ends_at', '>', $starts_at);
                })->orWhere(function ($query) use ($ends_at) {
                    $query->where('starts_at', '<', $ends_at)
                        ->where('ends_at', '>', $ends_at);
                });
            })
            ->exists();

        if ($overlappingSessions) {
            return response()->json(['message' => 'The session times overlap with an existing session.'], 422);
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
    public function students_delete($id, $student_id)
    {
        $group = Group::find($id);
        $student = $group->students()->where("student_id", $student_id)->first();

        $student->groups()->updateExistingPivot($group->id, ['status' => 'stopped', 'last_session' => $group->current_nb_session, 'last_month' => $group->current_month]);
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

        $students[$student_id] =  [
            'first_session' => $group->current_nb_session,
            'first_month' => $group->current_month,
            'debt' => $checkoutToRemove ?
                $student->groups()->where('group_id', $id)->first()->pivot->debt + $checkoutToRemove->price - $checkoutToRemove->discount
                : $student->groups()->where('group_id', $id)->first()->pivot->debt,

        ];

        $group->students()->syncWithoutDetaching($students);

        return response()->json(200);
    }
    public function teachers(Request $request)
    {
        $request->validate([
            'sortBy' => ['nullable', 'string'],
            'sortDirection' => ['nullable', 'string', 'in:asc,desc'],
            'perPage' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string'],
            'gender' => ['nullable', 'string'],
        ]);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');
        $gender = $request->query('gender', '');

        $teachersQuery = Teacher::join('users', 'teachers.user_id', '=', 'users.id')
            ->select('teachers.*', "users.$sortBy as sorted_column")
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                });
            });

        $teachersQuery->when($gender, function ($query) use ($gender) {
            return $query->where('gender', $gender);
        });

        $teachers = $teachersQuery->orderByRaw("LOWER(sorted_column) $sortDirection")
            ->with(['user'])
            ->paginate($perPage);

        return $teachers;
    }
}
