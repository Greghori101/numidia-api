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
        $teacher = Teacher::find($id);
        $teacher->load(['user','groups.level','groups.presence' => function ($query) use ($month) {
            $query->where('presences.month', $month);
        }, 'groups.students.checkouts' => function ($query) use ($month) {
            $query->where('checkouts.month', $month);
        },'groups.presence.students.user','groups.students.user']);
        return response()->json($teacher, 200);
    }

    public function  reject_session(Request $request, $id)
    {
        $request->validate([
            'explanation' => ['required', 'string'],
        ]);
        $explanation = $request->explanation;
        $session = Session::find($id);
        $session->state = 'rejected';
    }
    public function  approve_session($id)
    {
        $session = Session::find($id);
        $session->state = 'approved';
    }

    public function all()
    {
        $teachers = Teacher::with(['groups.level', 'user'])->get();
        return $teachers;
    }
    public function all_details()
    {
        $users = User::with(['teacher.groups.sessions.exceptions', 'teacher.groups.level'])->get();
        return $users;
    }
}
