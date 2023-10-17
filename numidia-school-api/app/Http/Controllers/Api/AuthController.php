<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Level;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{

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
                'module' => $request->module,
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

        $user->wallet()->save(new Wallet());

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
                    'department' => env('DEPARTEMENT'),
                ]);
        }
        return response()->json($response->body(), 200);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required',],
            'password' => ['required',],
            'coordinates' => ['required',],
            'location' => ['required'],
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
    public function revoke(Request $request, $id)
    {
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->delete(env('AUTH_API') . '/api/activities/revoke/' . $id, $data);


        return response()->json($response->body(), 200);
    }
    public function clear_activities(Request $request)
    {
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->delete(env('AUTH_API') . '/api/activities/clear', $data);

        return response()->json($response->body(), 200);
    }
    public function logout(Request $request)
    {
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->get(env('AUTH_API') . '/api/logout', $data);

        return response()->json($response->body(), 200);
    }
    public function restpassword(Request $request)
    {
        $request->validate([
            'email' => ['required',],
            'password' => ['required', 'confirmed'],
            'code' => ['required',],
        ]);
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/password/reset', $data);


        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/notifications', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'type' => "info",
                'title' => "Password Changed",
                'content' => "Your password has been changed at " . Carbon::now(),
                'displayed' => false,
                'id' => $request->user_id,
                'department' => env('DEPARTEMENT'),
            ]);
        return response()->json($response->body(), 200);
    }
    public function forgotpassword(Request $request)
    {
        $request->validate([
            'email' => ['required',],
        ]);
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/password/forgot', $data);

        return response()->json($response->body(), 200);
    }
    public function verify(Request $request)
    {
        $request->validate([
            'email' => ['required',],
            'code' => ['required',],
        ]);
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/email/verify', $data);

        return response()->json($response->body(), 200);
    }
    public function resent_verification(Request $request)
    {
        $request->validate([
            'email' => ['required',],
        ]);
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/email/resent/code', $data);

        return response()->json($response->body(), 200);
    }
    public function email_verified(Request $request)
    {
        $request->validate([
            'email' => ['required',],
        ]);
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->get(env('AUTH_API') . '/api/email/isverified', $data);

        return response()->json($response->body(), 200);
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
    public function change_password(Request $request)
    {
        $request->validate([
            'old_password' => ['required',],
            'password' => ['required', 'confirmed'],
        ]);
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/password/change', $data);

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/notifications', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'type' => "info",
                'title' => "Password Changed",
                'content' => "Your password has been changed at " . Carbon::now(),
                'displayed' => false,
                'id' => $request->user_id,
                'department' => env('DEPARTEMENT'),
            ]);

        return response()->json($response->body(), 200);
    }
}
