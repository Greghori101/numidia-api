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






        $response = Http::withHeaders([
            'Accept' => 'application/json',

        ])
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
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])
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
    public function user_create(Request $request)
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
            'id' => $request->id,
            'email' => $request->email,
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'role' => $request->role,
            'gender' => $request->gender,
        ]);



        $user->wallet()->save(new Wallet());

        // $username = env('AFRICASTALKING_USERNAME');
        // $apiKey = env('AFRICASTALKING_API_KEY');

        // $AT = new SDKAfricasTalking($username, $apiKey);

        // // Create an instance of the SMS class
        // $sms = $AT->sms();

        // // Define the message and recipients
        // $message = "Hello, this is a test SMS from Laravel!";


        // // Send the SMS
        // try {
        //     $result = $sms->send([
        //         'to' => "+213674680780",
        //         'message' => $message,
        //     ]);
        //     return $result;
        // } catch (\Exception $e) {
        //     return "Error: " . $e->getMessage();
        // }
        // $twilio_sid = env('TWILIO_SID');
        // $twilio_token = env('TWILIO_AUTH_TOKEN');
        // $twilio_phone_number = env('TWILIO_PHONE_NUMBER');

        // $client = new Client($twilio_sid, $twilio_token);

        // try {
        //     $client->messages->create(
        //         '+213674680780', // Recipient's phone number
        //         [
        //             'from' => $twilio_phone_number,
        //             'body' => 'Hello, this is a test SMS from Laravel!'
        //         ]
        //     );

        // } catch (\Exception $e) {
        //     return $e;
        // }

        return response()->json(200);
    }
    public function login(Request $request)
    {
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');

        $response = Http::withHeaders($request->header())
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

        $response = Http::withHeaders([
            'Accept' => 'application/json',

        ])
            ->delete(env('AUTH_API') . '/api/activities/revoke/' . $id, $data);


        return response()->json($response->body(), 200);
    }
    public function clear_activities(Request $request)
    {
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');
        $response = Http::withHeaders([
            'Accept' => 'application/json',

        ])
            ->delete(env('AUTH_API') . '/api/activities/clear', $data);

        return response()->json($response->body(), 200);
    }
    public function logout(Request $request)
    {
        $data = $request->all();
        $data['client_id'] = env('CLIENT_ID');
        $data['client_secret'] = env('CLIENT_SECRET');
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])
            ->get(env('AUTH_API') . '/api/logout', $data);

        return response()->json($response->body(), 200);
    }
    public function restpassword(Request $request)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',

        ])
            ->post(env('AUTH_API') . '/api/password/reset', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                $request->all(),
            ]);
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])
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
        $response = Http::withHeaders([
            'Accept' => 'application/json',

        ])
            ->post(env('AUTH_API') . '/api/password/forgot', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                $request->all(),
            ]);

        return response()->json($response->body(), 200);
    }
    public function verify(Request $request)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',

        ])
            ->post(env('AUTH_API') . '/api/email/verify', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                $request->all(),
            ]);

        return response()->json($response->body(), 200);
    }
    public function resent_verification(Request $request)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',

        ])
            ->post(env('AUTH_API') . '/api/email/resent/code', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                $request->all(),
            ]);

        return response()->json($response->body(), 200);
    }
    public function email_verified(Request $request)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',

        ])
            ->get(env('AUTH_API') . '/api/email/isverified', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                $request->all(),
            ]);

        return response()->json($response->body(), 200);
    }
    public function provider_login(Request $request, $provider)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',

        ])
            ->post(env('AUTH_API') . '/auth/' . $provider . '/login', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                $request->all(),
            ]);

        return response()->json($response->body(), 200);
    }
    public function change_password(Request $request)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',

        ])
            ->post(env('AUTH_API') . '/api/password/change', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                $request->all(),
            ]);
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])
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
