<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Admin;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/login', $data);

        $data = json_decode($response->body(), true);


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

        $address = new Address([
            'city' => $request->city,
            'wilaya' => $request->wilaya,
            'street' => $request->street,
        ]);

        $user->address()->save($address);
    }
}
