<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordEmail;
use App\Mail\VerifyEmail;
use App\Mail\WelcomeEmail;
use App\Models\Activity;
use App\Models\File;
use App\Models\Notification;
use Laravel\Passport\Token;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Response;


class AuthController extends Controller
{

    public function login(Request $request)
    {
        if (
            Auth::attempt([
                'email' => $request->email,
                'password' => $request->password,
            ])
        ) {
            $user = User::where('email', $request->email)->first();

            $remember = $request->remember_me;
            Auth::login($user, $remember);
            $accessToken = $user->createToken('API Token');
            $token = $accessToken->token;
            $data = [
                'id' => $user->id,
                'wallet' => $user->wallet,
                'verified' => $user->hasVerifiedEmail(),
                'token' => $accessToken->accessToken,
                'access_token_id' => $token->id,
                'profile_picture' => $user->profile_picture,
                'wallet' => $user->wallet,
            ];
            $user->activities()->save(Activity::create([
                'details' => "login",
                'user_agent' => $request->userAgent,
                'ip_address' => $request->ip(),
                'location' => $request->location,
                'access_token_id' => $token->id,
            ]));

            return response()->json($data, 200);
        } else {
            return abort(403);
        }
    }
    public function show($id)
    {
        $user = User::with(['activities', 'profile_picture', 'wallet'])->find($id);
        return response()->json($user, 200);
    }
    public function revoke(Request $request, $id)
    {
        $user_id = $request->user()->id;

        Activity::where('access_token_id', $id)->where('user_id', $user_id)->update(['status' => "revoked"]);
        $token = Token::find($id);

        if (!$token) {
            return response()->json(['message' => 'Token not found'], 404);
        }

        $token->revoke();

        return response()->json(['message' => 'Token has been revoked'], 200);
    }
    public function clear_activities(Request $request)
    {
        $user_id = $request->user()->id;

        Activity::where('status', '!=', 'active')->where('user_id', $user_id)->delete();

        return response()->json(['message' => 'Token has been revoked'], 200);
    }
    public function register(Request $request)
    {
        $user = User::create([
            'id' => $request->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'code' => Str::upper(Str::random(6)),
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
        ]);

        $content = Storage::get('default-profile-picture.jpeg');


        $bytes = random_bytes(ceil(64 / 2));
        $hex = bin2hex($bytes);
        $file_name = substr($hex, 0, 64);
        $file_url = '/avatars/' .  $file_name . '.jpeg';
        Storage::put($file_url, $content);
        $user->profile_picture()->update(['url' => $file_url]);

        $user->wallet()->save(new Wallet());
        try {
            $data = [
                'url' =>
                env('APP_URL') .
                    '/api/email/verify?id=' .
                    $user->id .
                    '&code=' .
                    $user->code,
                'name' => $user->name,
                'email' => $user->email,
                'code' => $user->code,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
            ];
            Mail::to($user)->queue(new VerifyEmail($data));
        } catch (\Throwable $th) {
        }
        return response()->json($data, 200);
    }
    public function create(Request $request)
    {
        $password = Str::upper(Str::random(6));
        $user = User::create([
            'id' => $request->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
        ]);

        $content = Storage::get('default-profile-picture.jpeg');


        $bytes = random_bytes(ceil(64 / 2));
        $hex = bin2hex($bytes);
        $file_name = substr($hex, 0, 64);
        $file_url = '/avatars/' .  $file_name . '.jpeg';
        Storage::put($file_url, $content);
        $user->profile_picture()->update(['url' => $file_url]);


        $user->wallet()->save(new Wallet());
        try {
            $data = [
                'url' =>
                env('APP_URL') .
                    '/api/email/verify?id=' .
                    $user->id .
                    '&code=' .
                    $user->password,
                'name' => $user->name,
                'email' => $user->email,
                'code' => $password,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
            ];
            $user->save();
            Mail::to($user)->queue(new WelcomeEmail($data));
        } catch (\Throwable $th) {
        }
        return response()->json(200);
    }
    public function update(Request $request, $id = null)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:10'],
            'gender' => ['required', 'in:male,female'],
        ]);
        if ($id) {
            $user = User::find($id);
        } else {
            $user = User::find($request->user()->id);
        }
        if (!$user) {
            abort(404);
        }
        $user->update([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
        ]);

        return response()->json(200);
    }
    public function logout(Request $request)
    {
        $user = User::find($request->user()->id);
        if ($user) {
            Activity::where('user_id', $user->id)->update(['status' => "revoked"]);
            $user->tokens()->delete();
            return response(200);
        } else {
            abort(403);
        }
    }
    public function restpassword(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'email' => 'required|string',
            'password' => ['required', 'confirmed'],
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            abort(404);
        }
        if ($request->code != $user->code) {
            $message = 'wrong code';
            abort(401, $message);
        } else {
            $user->markEmailAsVerified();
            $user->code = null;
            $user->password = Hash::make($request->input('password'));
            $user->save();
        }

        return response(200);
    }
    public function forgotpassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            abort(404);
        }
        $url = '';
        $user->code = Str::random(6);

        $user->save();
        try {
            //code...
            // Email the user new password
            $data = [
                'name' => $user->name,
                'email' => $user->email,
                'code' => $user->code,
                'url' => $url,
            ];
            Mail::to($user)->queue(new ForgotPasswordEmail($data));
        } catch (\Throwable $th) {
        }

        return response(200);
    }
    public function verify(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string'],
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
                $data = [
                    'message' => 'verified',
                ];
                return response()->json($data, 200);
            } else {
                return response()->json(
                    'the code you have entered is wrong',
                    403
                );
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
                    'url' =>
                    env('APP_URL') .
                        '/api/email/verify?id=' .
                        $user->id .
                        '&code=' .
                        $user->code,
                    'name' => $user->name,
                    'email' => $user->email,
                    'code' => $user->code,
                ];
                Mail::to($user)->queue(new VerifyEmail($data));
            } catch (\Throwable $th) {
            }
            return response()->json('Code sent', 200);
        }
    }
    public function email_verified(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $user = User::where('id', $request->id)->first();
        }
        if (!$user) {
            abort(404);
        }

        return response()->json(['verified' => $user->hasVerifiedEmail()], 200);
    }
    public function provider_login(Request $request, $provider)
    {
        $user = User::where('email', $request->email)->first();
        $remember = $request->remember_me;
        Auth::login($user, $remember);
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        if ($provider == 'google') {
            if (!$user->google_id) {
                $user->google = $request->id;
            }
        } elseif ($provider == 'facebook') {
            if (!$user->facebook_id) {
                $user->facebook_id = $request->id;
            }
        }

        $data = [
            'id' => $user->id,
            'verified' => $user->hasVerifiedEmail(),
            'token' => $user->createToken('API Token')->accessToken,
        ];

        return response()->json($data, 200);
    }
    public function change_password(Request $request)
    {
        $user = User::find($request->user()->id);

        $request->validate([
            'old_password' => 'required',
            'password' => 'required|confirmed',
        ]);

        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 401);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        Notification::create([
            'type' => "info",
            'title' => "Password Changed",
            'content' => "Your password has been changed at " . Carbon::now(),
            'displayed' => false,
            'id' => $user->id,
        ]);

        return response()->json(['message' => 'Password changed successfully'], 200);
    }
    public function verify_token(Request $request)
    {
        $data = ['user' => User::with('permissions')->find($request->user()->id)];
        return response()->json($data, 200);
    }
    public function change_profile_picture(Request $request, $id = null)
    {
        if (!$id) {
            $id = $request->user()->id;
        }
        $user = User::find($id);
        if ($user->profile_picture && Storage::exists($user->profile_picture->url)) {
            Storage::delete($user->profile_picture->url);
        }

        $file = $request->file("profile_picture");

        if ($file) {
            $bytes = random_bytes(ceil(64 / 2));
            $hex = bin2hex($bytes);
            $file_name = substr($hex, 0, 64);
            $file_url = '/avatars/' .  $file_name . $file->extension();
            Storage::put($file_url, file_get_contents($file));
            $user->profile_picture()->update(['url' => $file_url]);
            return response()->json(['message' => 'Profile picture updated successfully'], Response::HTTP_OK);
        } else {

            return response()->json(['message' => 'Profile picture was missing, please upload image'], Response::HTTP_BAD_REQUEST);
        }
        return response()->json(['message' => 'Profile picture changed successfully'], 200);
    }
    public function users(Request $request)
    {
        $users = User::with(['profile_picture', 'wallet'])->whereIn('id', $request->ids)->get();
        return response()->json($users, 200);
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
