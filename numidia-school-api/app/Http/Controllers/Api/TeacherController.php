<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    //
    public function all()
    {
        $teachers = Teacher::with(['user'])->get();
        return $teachers;
    }

    public function index(Request $request)
    {
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

    public function show($id)
    {
        $teacher = Teacher::where('id', $id)->first()->user;
        return $teacher;
    }



    public function  reject_session(Request $request, $id)
    {
        $explanation = $request->explanation;
        $session = Session::find($id);
        $session->state = 'rejected';
    }
    public function  approve_session($id)
    {
        $session = Session::find($id);
        $session->state = 'approved';
    }
}
