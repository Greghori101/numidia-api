<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\Admin;
use App\Models\Level;
use App\Models\File;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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


    public function show($id)
    {
        $user = User::with(["profile_picture", "posts", "wallet", "transactions", "received_notifications", "reciepts"])->find($id);
        return response()->json(200, $user);
    }

    public function users_list(Request $request)
    {
        $users = User::with(["profile_picture"])->find($request->ids);

        return response()->json(200, $users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_role' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:' . User::class,
            ],
        ]);

        $content = Storage::get('default-profile-picture.jpeg');
        $extension = 'jpeg';
        $name = 'profile picture';

        $code = Str::upper(Str::random(6));

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->user_role,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($code),
            'code' => $code,
        ]);

        $user->wallet()->save(new  Wallet());

        if ($user->role == 'teacher') {
            $teacher = new Teacher([
                'module' => $request->module,
                'percentage' => $request->percentage,
            ]);
            $user->teacher()->save($teacher);
        } elseif ($user->role == 'student') {
            $level = Level::find($request->level_id);
            $student = new Student();
            $user->student()->save($student);
            $level->students()->save($student);
        } elseif ($user->role == 'admin') {
            $user->admin()->save(new Admin());
        } elseif ($user->role == 'supervisor') {
            $user->supervisor()->save(new Supervisor());
        }
        $user->profile_picture()->save(
            new File([
                'name' => $name,
                'content' => base64_encode($content),
                'extension' => $extension,
            ])
        );

        // $user->refresh();

        try {
            //code...
            $data = [
                'name' => $user->name,
                'email' => $user->email,
                'code' => $user->code,
            ];
            Mail::to($user)->send(new VerifyEmail($data));
        } catch (\Throwable $th) {
            //throw $th;
            // abort(400);
        }

        return response()->json(200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string'],
        ]);

        $user = User::find($id);

        $user->name = $request->name;
        $user->gender = $request->gender;
        $user->phone_number = $request->phone_number;

        $user->save();

        return response()->json(200);
    }

    public function delete($id)
    {
        $user = User::where('id', $id)->first();
        $user->forceDelete();
        return response()->json(200);
    }
}
