<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'sortBy' => ['nullable', 'string'],
            'sortDirection' => ['nullable', 'string'],
            'perPage' => ['nullable', 'integer'],
            'search' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:male,female'],
        ]);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');
        $gender = $request->query('gender', '');

        $parentsQuery = Supervisor::join('users', 'supervisors.user_id', '=', 'users.id')
            ->select('supervisors.*', "users.$sortBy as sorted_column")
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                });
            });

        $parentsQuery->when($gender, function ($query) use ($gender) {
            return $query->where('gender', $gender);
        });

        $parents = $parentsQuery->orderByRaw("LOWER(sorted_column) $sortDirection")
            ->with(['user'])
            ->paginate($perPage);

        return $parents;
    }
    public function show($id)
    {
        $parent = Supervisor::with(['students.user'])->findOrFail($id);

        return $parent;
    }

    public function add_student(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:10'],
            'gender' => ['required', 'in:male,female'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        ]);

        return DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'role' => "student",
                'gender' => $request->gender,
            ]);
            $user->student()->save(new Student());


            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/users/create', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'id' => $user->id,
                    'name' => $request->name,
                    'email' => $request->email,
                ]);


            return response()->json(200);
        });
    }

    public function students()
    {
        $user = User::find(Auth::user()->id);
        $supervisor = $user->supervisor;
        $students = $supervisor->students;
        foreach ($students as $key => $value) {
            # code...
            $students[$key] = $value->user;
        }

        return $students;
    }
}
