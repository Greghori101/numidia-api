<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Level;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{

    public function create_user_department(Request $request)
    {
        $user = User::create([
            'id' => $request->id,
            'email' => $request->email,
            'name' => $request->name,
            'role' => $request->role,
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
        ]);
        if ($user->role == 'student') {
            $level = Level::find($request->level_id);
            $student = new Student();
            $user->student()->save($student);
            $level->students()->save($student);
        } elseif ($user->role == 'supervisor') {
            $user->supervisor()->save(new Supervisor());
        }
    }

    public function create_user(Request $request,$id)
    {
        $user = User::create([
            'id' => $id,
            'email' => $request->email,
            'name' => $request->name,
            'role' => $request->role,
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
        ]);
        if ($user->role == 'student') {
            $level = Level::find($request->level_id);
            $student = new Student();
            $user->student()->save($student);
            $level->students()->save($student);
        } elseif ($user->role == 'supervisor') {
            $user->supervisor()->save(new Supervisor());
        }
    }
    public function verify_user_existence(Request $request, $id)
    {
        $user = User::find($id);
        if ($user) {
            return response()->json(['message' => "found"], 200);
        } else {
            return response()->json(['message' => "not registered"], 200);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:10'],
            'gender' => 'required|in:male,female',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users',],
            'password' => ['required', 'confirmed'],
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
        } elseif ($user->role == 'supervisor') {
            $user->supervisor()->save(new Supervisor());
        }


        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/register', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
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
                    'department' => env('DEPARTMENT'),
                ]);
        }
        return response()->json($response->body(), 200);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required',],
            'password' => ['required'],
        ]);
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/login', $data);

        $data = json_decode($response->body(), true);

        $user = User::find($data['id']);
        $data['user'] = $user;

        return response()->json($data, 200);
    }
    public function provider_login(Request $request, $provider)
    {

        $request->validate([
            'email' => ['required',],
            'token' => ['required',],
            'id' => ['required',],
        ]);
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/auth/' . $provider . '/login', $data);

        return response()->json($response->body(), 200);
    }
    public function getFile(Request $request)
    {
        $url = $request->url;
        if (Storage::exists($url)) {
            return Storage::get($url);
        } else {
            return response()->json(Response::HTTP_NOT_FOUND);
        }
    }
}
