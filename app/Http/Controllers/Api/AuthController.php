<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordEmail;
use App\Mail\VerifyEmail;
use App\Models\Admin;
use App\Models\File;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = User::where('email',$request->email)->first();
            if($user->role==="student"){

                $active  = $user->student->active;
            }else{
                $active =false;
            }
            $remember = $request->remember_me;
            Auth::login($user, $remember);
            $data = [
                'id' => $user->id,
                'role' => $user->role,
                'profile_picture' => $user->profile_picture,
                'verified'=>$user->hasVerifiedEmail(),
                'active'=>$active,
                'token' => $user->createToken('API Token')->accessToken,
            ];

            return response()->json($data, 200);
        } else {
            return abort(403);
        }
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:10'],
            'gender' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed',],
        ]);
        $request->merge([
            "role" =>  strtolower($request->role),
        ]);


        $content = Storage::get('default-profile-picture.jpeg');
        $extension = 'jpeg';
        $name = "profile picture";

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'role' => $request->role,
            'gender' => $request->gender,
            'password' => Hash::make($request->password),
            'code' => Str::upper(Str::random(6)),
        ]);


        if ($user->role == 'teacher') {
            $user->teacher()->save(new Teacher());
        } else if ($user->role == 'student') {
            $user->student()->save(new Student());
        } else if ($user->role == 'admin') {
            $user->admin()->save(new Admin());
        } else if ($user->role == 'supervisor') {
            $user->supervisor()->save(new Supervisor());
        }




        $user->profile_picture()->save(new File([
            'name' => $name,
            'content' => base64_encode($content),
            'extension' => $extension,
        ]));

        // $user->refresh();

        try {
            //code...
            $data = [
                'url' => env('APP_URL') . '/api/email/verify?id=' . $user->id . '&code=' . $user->code,
                'name' => $user->name,
                'email' => $user->email,
                'code' => $user->code,
            ];
            Mail::to($user)->send(new VerifyEmail($data));
        } catch (\Throwable $th) {
            //throw $th;
            abort(400);
        }
        if($user->role==="student"){

            $active  = $user->student->active;
        }else{
            $active =false;
        }
        Auth::login($user);
        $data = [
            'id' => $user->id,
            'role' => $user->role,
            'profile_picture' => $user->profile_picture,
            'verified'=>$user->hasVerifiedEmail(),
            'active'=>$active,
            'token' => $user->createToken('API Token')->accessToken,
        ];
        return response()->json($data, 200);
    }

    public function logout()
    {

        $user = Auth::user();
        if (Auth::check($user)) {
            $user->token()->revoke();
            $user->tokens()->delete();
            return response(200);
        } else {
            abort(403);
        }
    }

    public function forgotpassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string'
        ]);
        $user = User::where('email', $request['email'])->first();
        if (!$user) {
            abort(404);
        }
        $url = "";
        try {
            //code...
            // Email the user new password
            $data = [
                'name' => $user->name,
                'email' => $user->email,
                'url' => $url,
            ];
            Mail::to($user)->send(new ForgotPasswordEmail($data));
        } catch (\Throwable $th) {
            //throw $th;
            abort(400);
        }

        return response(200);
    }

    public function verify(Request $request)
    {
        $request->validate([

            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string',],
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            abort(404);
        } else {
            if ($user->hasVerifiedEmail()) {
                return response()->json('Email Already Verified', 200);
            } elseif ($request->code == $user->code) {
                $user->markEmailAsVerified();
                $user->code = null;
                $user->save();
                // Auth::login($user);
                $data = [
                    // 'id' => $user->id,
                    // 'role' => $user->role,
                    // 'token' => $user->createToken('API Token')->accessToken,
                    'message' => 'verified',
                ];
                return response()->json($data, 200);
            } else {
                return response()->json('the code you have entered is wrong', 403);
            }
        }
    }

    public function resent_verification(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            abort(404);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json('Email Already Verified', 200);
        } else {
            try {
                //code...
                $user->code = Str::upper(Str::random(6));
                $user->save();
                $data = [
                    'url' => env('APP_URL') . '/api/email/verify?id=' . $user->id . '&code=' . $user->code,
                    'name' => $user->name,
                    'email' => $user->email,
                    'code' => $user->code,
                ];
                Mail::to($user)->send(new VerifyEmail($data));
            } catch (\Throwable $th) {
                //throw $th;
                abort(400);
            }
            return response()->json('Code sent', 200);
        }
    }
    public function email_verified(Request $request)
    {

        $user = User::where('id', $request->id)->first();
        return response()->json(["verified" => $user->hasVerifiedEmail()], 200);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:10'],
        ]);
        $user = User::find($id);
        if (!$user) {
            abort(404);
        }
        $data = [
            'name' => $request->name,
            'gender' => $request->gender,
        ];
        if ($request->file('profile_picture')) {
            $file = $request->file('profile_picture');
            $content = $file->get();
            $extension = $file->extension();
            $user->profile_picture()->update([
                'name' => 'profile picture',
                'content' => base64_encode($content),
                'extension' => $extension,
            ]);
        }

        $message = null;
        if ($request->password) {
            $request->validate([
                'old_password' => ['required',],
                'password' => ['required', 'confirmed',],
            ]);
            if (Hash::check($request->old_password, $user->password)) {
                $data['password'] = Hash::make($request->password);
                $message = 'password changed successfuly';
            } else {
                $message = 'the old password you had entered is wrong';
            }
        } else {
            $message = 'you did not change the password';
        }

        $user->refresh();

        User::where('id', $id)->update($data);


        $file = $user->profile_picture;

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'gender' => $user->gender,
            'profile_picture' => $file,
            'role' => $user->role,
            'message' => $message,
        ];

        return response()->json($file, 200);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            abort(404);
        }

        $file = $user->profile_picture;

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'gender' => $user->gender,
            'profile_picture' => $file,
            'role' => $user->role,
        ];

        return response()->json($data, 200);
    }

    public function provider_login(Request $request, $provider)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $remember = $request->remember_me;
            Auth::login($user, $remember);
            $data = [
                'id' => $user->id,
                'role' => $user->role,
                'token' => $user->createToken('API Token')->accessToken,
            ];

            return response()->json($data, 200);
        } else {
            return abort(403);
        }
    }


    public function provider_store(Request $request, $provider)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:10'],
            'gender' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed',],
        ]);
        $request->merge([
            "role" =>  strtolower($request->role),
        ]);


        $content = Storage::get('default-profile-picture.jpeg');
        $extension = 'jpeg';
        $name = "profile picture";

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'role' => $request->role,
            'gender' => $request->gender,
            'password' => Hash::make($request->password),
            'code' => Str::random(6),
        ]);


        if ($user->role == 'teacher') {
            $user->teacher()->save(new Teacher());
        } else if ($user->role == 'student') {
            $user->student()->save(new Student());
        } else if ($user->role == 'admin') {
            $user->admin()->save(new Admin());
        } else if ($user->role == 'supervisor') {
            $user->supervisor()->save(new Supervisor());
        }




        $user->profile_picture()->save(new File([
            'name' => $name,
            'content' => base64_encode($content),
            'extension' => $extension,
        ]));

        // $user->refresh();

        try {
            //code...
            $data = [
                'url' => env('APP_URL') . '/api/email/verify?id=' . $user->id . '&code=' . $user->code,
                'name' => $user->name,
                'email' => $user->email,
                'code' => $user->code,
            ];
            Mail::to($user)->send(new VerifyEmail($data));
        } catch (\Throwable $th) {
            //throw $th;
            abort(400);
        }
        Auth::login($user);
        $data = [
            'id' => $user->id,
            'role' => $user->role,
            'token' => $user->createToken('API Token')->accessToken,
        ];
        return response()->json($data, 200);
    }
}
