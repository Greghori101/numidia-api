<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
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
    public function logout(Request $request)
    {
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->get(env('AUTH_API') . '/api/logout', $data);

        return response()->json($response->body(), 200);
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
    }
}
