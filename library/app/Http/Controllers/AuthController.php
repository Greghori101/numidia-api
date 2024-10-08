<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Admin;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/login', $data);
        if ($response->failed()) {
            $statusCode = $response->status();
            $errorBody = $response->json();
            abort($statusCode, $errorBody['message'] ?? 'Unknown error');
        }

        if ($response->serverError()) {
            abort(500, 'Server error occurred');
        }

        if ($response->clientError()) {
            abort($response->status(), 'Client error occurred');
        }
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
        if ($response->failed()) {
            $statusCode = $response->status();
            $errorBody = $response->json();
            abort($statusCode, $errorBody['message'] ?? 'Unknown error');
        }

        if ($response->serverError()) {
            abort(500, 'Server error occurred');
        }

        if ($response->clientError()) {
            abort($response->status(), 'Client error occurred');
        }
        return response()->json($response->body(), 200);
    }

    public function create_user(Request $request, $id)
    {

        return DB::transaction(function () use ($request, $id) {
            $user = User::create([
                'id' => $id,
                'email' => $request->email,
                'name' => $request->name,
                'role' => $request->role,

                'gender' => $request->gender,
            ]);

            $address = new Address([
                'city' => $request->city,
                'wilaya' => $request->wilaya,
                'street' => $request->street,
            ]);

            $user->address()->save($address);
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/users/create', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'id' => $user->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone_number' => $request->phone_number,
                    'gender' => $request->gender,

                ]);
            if ($response->failed()) {
                $statusCode = $response->status();
                $errorBody = $response->json();
                abort($statusCode, $errorBody['message'] ?? 'Unknown error');
            }

            if ($response->serverError()) {
                abort(500, 'Server error occurred');
            }

            if ($response->clientError()) {
                abort($response->status(), 'Client error occurred');
            }
        });
    }

    public function verify_user_existence(Request $request, $id)
    {
        $user = User::findOrFail($id);
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
