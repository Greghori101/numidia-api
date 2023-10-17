<?php

namespace App\Http\Controllers;

// use Twilio\Rest\Client;
use App\Http\Controllers\Controller;
use App\Models\User;
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




        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/users/create', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
            ]);


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
}
