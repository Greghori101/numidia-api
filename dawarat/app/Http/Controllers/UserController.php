<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function create_user(Request $request)
    {
        $user = User::create([
            'id' => $request->id,
            'email' => $request->email,
            'name' => $request->name,
            'role' => $request->role,
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
        ]);
    }
    public function index(Request $request)
    {
        $request->validate([
            'role' => 'string',
            'sortBy' => 'string',
            'sortDirection' => 'string|in:asc,desc',
            'perPage' => 'integer|min:1',
            'search' => 'string',
            'gender' => 'string',
        ]);

        $role = $request->query('role', '');
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

    public function all()
    {
        $users = User::all();

        return response()->json(['data' => $users], 200);
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }


        return response()->json(['data' => $user], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'phone_number' => 'required|string',
            'role' => 'required|in:teacher,student',
            'gender' => 'required|in:male,female',
        ]);

        $user = User::create([
            'id' => $request->user->id,
            'email' => $request->email,
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'role' => $request->role,
            'gender' => $request->gender,
        ]);

        if ($request->input('role') === 'teacher') {
            Teacher::create(['user_id' => $user->id]);
        } elseif ($request->input('role') === 'student') {
            Student::create(['user_id' => $user->id]);
        }

        return response()->json(['data' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'phone_number' => 'required|string',
            'role' => 'required|in:teacher,student',
            'gender' => 'required|in:male,female',
        ]);

        $user->update($request->all());

        return response()->json(['data' => $user], 200);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted'], 200);
    }
}
