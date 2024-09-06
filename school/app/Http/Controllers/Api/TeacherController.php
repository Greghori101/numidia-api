<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TeacherController extends Controller
{

    public function index(Request $request)
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

    public function show(Request $request, $id)
    {
        $month =  $request->query('month', 1);
        $teacher = Teacher::findOrFail($id);
        $teacher->load(['user', 'groups.level', 'groups.presences' => function ($query) use ($month) {
            $query->where('presences.month', $month);
        }, 'groups.students.checkouts' => function ($query) use ($month) {
            $query->where('checkouts.month', $month);
        }, 'groups.presences.students.user', 'groups.students.user', 'groups.students.presences' => function ($query) use ($month) {
            $query->where('presences.month', $month);
        }]);
        return response()->json($teacher, 200);
    }

    public function  reject_session(Request $request, $id)
    {
        $request->validate([
            'explanation' => ['required', 'string'],
        ]);
        $explanation = $request->explanation;
        $session = Session::findOrFail($id);
        $session->state = 'rejected';
    }
    public function  approve_session($id)
    {
        $session = Session::findOrFail($id);
        $session->state = 'approved';
    }

    public function all_details(Request $request)
    {
        $request->validate([
            'perPage' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string'],
            'gender' => ['nullable', 'string'],
        ]);
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');
        $levelId = $request->level_id;

        $users = User::when($search, function ($query) use ($search) {
            return $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('phone_number', '%' . $search . '%');
        })
            ->with(['teacher.groups' => function ($query) use ($levelId) {
                $query->when($levelId, function ($q) use ($levelId) {
                    $q->where('level_id', 'like', "%$levelId%");
                })->with(["sessions.exceptions", "levels"]);
            }])
            ->paginate($perPage);
        return $users;
    }
    public function teacher_details(Request $request, $id)
    {
        $request->validate([
            'search' => ['nullable', 'string'],
            'gender' => ['nullable', 'string'],
        ]);
        $search = $request->query('search', '');
        $levelId = $request->level_id;

        $users = User::with(['teacher.groups' => function ($query) use ($levelId, $search) {
            $query->when($levelId, function ($q) use ($levelId, $search) {
                $q->where('level_id', 'like', "%$levelId%")
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('main_session', 'like', '%' . $search . '%');
            })->with(["sessions.exceptions", "levels"]);
        }])->findOrFail($id);
        return $users;
    }
}
