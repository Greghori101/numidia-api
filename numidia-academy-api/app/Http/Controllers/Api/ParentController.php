<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\File;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');
        $gender = $request->query('gender', '');

        $parentsQuery = Supervisor::join('users', 'supervisors.user_id', '=', 'users.id')
            ->select('supervisors.*', "users.$sortBy as sorted_column")
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                });
            });

        $parentsQuery->when($gender, function ($query) use ($gender) {
            return $query->where('gender', $gender);
        });

        $parents = $parentsQuery->orderByRaw("LOWER(sorted_column) $sortDirection")
            ->with(['user'])
            ->paginate($perPage);

        return $parents;
    }
    public function show($id)
    {
        $parent = Supervisor::where('id', $id)->first();
        $temp = [];
        foreach ($parent->students as $student) {
            $temp[] = $student->user;
            # code...
        }
        $parent['students'] = $temp;
        return $parent;
    }

    public function add_student(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $supervisor = $user->supervisor();
        $password = Str::random(10);

        $content = Storage::get('default-profile-picture.jpeg');
        $extension = 'jpeg';
        $name = 'profile picture';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'student',
            'gender' => $request->gender,
            'code' => Str::random(10),
            'password' => Hash::make($password),
        ]);

        $student = new Student();
        $user->student()->save($student);
        $supervisor->students()->save($student);

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
            abort(400);
        }

        return response()->json(200);
    }

    public function students()
    {
        $user = User::find(Auth::user()->id);
        $supervisor = $user->supervisor;
        $students = $supervisor->students;
        foreach ($students as $key => $value) {
            # code...
            $students[$key] = $value->user;
        }

        return $students;
    }
}
