<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Session;
use App\Models\Teacher;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    //
    public function index()
    {
        $sessions = Session::all();
        foreach ($sessions as $session) {
            # code...
            $session['teacher'] = $session->teacher->user;
            $session['group'] = $session->group;
        }

        return response()->json($sessions, 200);
    }

    public function show($id)
    {
        $session = Session::find($id);
        $session['teacher'] = $session->teacher->user;
        $session['group'] = $session->group;
        return response()->json($session, 200);
    }

    public function create(Request $request)
    {
        $session = Session::create([
            'classroom' => $request->classroom,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
        ]);

        $group = Group::find($request->group_id);
        $teacher = Teacher::find($request->teacher_id);
        $session
            ->group()
            ->associate($group)
            ->save();
        $session
            ->teacher()
            ->associate($teacher)
            ->save();

        return response()->json(200);
    }

    public function delete($id)
    {
        $session = Session::find($id);

        $session->delete();

        return response()->json(200);
    }

    public function update(Request $request, $id)
    {
        $session = Session::find($id);

        $session->update([
            'classroom' => $request->classroom,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
        ]);

        $session->group()->associate(Group::find($request->group_id));
        $session->teacher()->associate(Teacher::find($request->teacher_id));

        $session->save();

        return response()->json(200);
    }
}
