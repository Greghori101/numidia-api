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
    //
    public function all()
    {
        $teachers = Teacher::with(['user'])->get();
        return $teachers;
    }

    public function index(Request $request)
    {
        $request->validate([
            'sortBy' => ['nullable','string'],
            'sortDirection' => ['nullable','string', 'in:asc,desc'],
            'perPage' => ['nullable','integer', 'min:1'],
            'search' => ['nullable','string'],
            'gender' => ['nullable','string'],
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

    public function show($id)
    {
        $teacher = Teacher::where('id', $id)->first()->user;
        return $teacher;
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


    public function all_details()
    {
        $users = User::with(['teacher', "groups.level"])->get();
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->get(env('AUTH_API') . '/api/users', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'ids' => $users->pluck('id'),
            ]);

        $users = $users->concat($response->json());
        $users = $users->groupBy('id');

        return $users;
    }
}
