<?php

namespace App\Http\Controllers\Api;

// use Twilio\Rest\Client;
use AfricasTalking\SDK\AfricasTalking as SDKAfricasTalking;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WebSocketController;
use App\Models\File;
use App\Models\Level;
use App\Models\Notification;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
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

    public function show(Request $request, $id = null)
    {
        if (!$id) {
            $user = User::with(["profile_picture", "wallet", "checkouts", "received_notifications", "receipts",])->find($request->user_id);
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])
                ->get(env('AUTH_API') . '/api/profile/' . $user->id, [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                ]);
            $user['activities'] = $response->json()['activities'];
            return response()->json($user, 200);
        } else {
            $user = User::find($id);
            if ($user->role == "student") {
                $user->load('receipts', 'profile_picture', 'wallet', 'student.presences.group.teacher.user', 'student.groups.teacher.user', 'student.level', 'student.checkouts', 'student.fee_inscription', 'student.supervisor.user', 'student.user');
            } elseif ($user->role == "teacher") {
                $user->load('receipts', 'profile_picture', 'wallet', 'teacher.groups.level', 'teacher.groups.students.user.wallet', 'teacher.groups.presence.students.user');
            } else if ($user->role == "supervisor") {
                $user->load('receipts', 'profile_picture', 'wallet', 'supervisor.students.user.profile_picture', 'supervisor.students.presences.group.teacher.user', 'supervisor.students.groups.teacher.user', 'supervisor.students.level', 'supervisor.students.checkouts', 'supervisor.students.fee_inscription',);
            } else {
                $user->load('receipts', 'profile_picture', 'wallet',);
            }
            return response()->json($user, 200);
        }
    }

    public function users_list(Request $request)
    {
        $users = User::with(["profile_picture"])->whereIn('id', $request->ids)->get();

        return response()->json($users, 200);
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

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])
            ->post(env('AUTH_API') . '/api/users/create', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
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
            return Student::with(['user', 'level'])->find($student->id);
        } elseif ($user->role == 'supervisor') {
            $user->supervisor()->save(new Supervisor());
        }


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
        return response()->json(200);
    }

    public function change_profile_picture(Request $request, $id = null)
    {
        if (!$id) {
            $user = User::find($request->user_id);
        } else {
            $user = User::find($id);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        $user->update([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,

        ]);

        return response()->json(['message' => 'User data updated successfully'], 200);
    }
    public function profile_update(Request $request)
    {
        $user = User::find($request->user_id);

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
