<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Notification;
use App\Models\Presence;
use App\Models\Session;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function students(Request $request)
    {

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable',],
            'ends_at' => ['nullable',],
            'group_id' => ['nullable',],
        ]);
        $search = $request->search;
        $starts_at = Carbon::parse($request->starts_at);
        $ends_at = Carbon::parse($request->ends_at);
        $group_id = $request->group_id;


        return response()->json(200);
    }
    public function sessions(Request $request)
    {

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = $request->input('search');

        $query = Session::with(['exceptions', 'group.teacher.user']);

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->orWhereHas('group', function ($groupQuery) use ($search) {
                    $groupQuery->where('module', 'like', '%' . $search . '%');
                })->orWhereHas('group.teacher.user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        $sessions = $query->with(['group'])->get();
        $distinctGroups = $sessions->pluck('group')->unique();
        $groups = Group::whereIn('id', $distinctGroups->pluck('id'))->with(['teacher.user'])->get();

        return response()->json(["sessions" => $sessions, "groups" => $groups], 200);
    }
    public function mark_presence(Request $request)
    {
        $request->validate([
            'presence' => ['required'],
            'student' => ['required'],
        ]);
        $presence = Presence::find($request->presence);

        if ($presence) {
            $presence->students()->syncWithoutDetaching([$request->student => ['status' => 'present']]);
        }


        return response()->json([], 200);
    }
    public function remove_presence(Request $request)
    {
        $request->validate([
            'presence' => ['required'],
            'student' => ['required'],
        ]);
        $presence = Presence::find($request->presence);

        if ($presence) {
            $presence->students()->syncWithoutDetaching([$request->student => ['status' => 'absent']]);
        }


        return response()->json([], 200);
    }
    public function presence_sheets(Request $request)
    {
        $request->validate([
            'ids' => ['nullable'],
            'search' => ['nullable'],
        ]);

        if ($request->ids) {
            $presence_sheets = Presence::with(['group.teacher.user', 'students.user'])
                ->whereIn('id', $request->ids)
                ->whereHas('students.user', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                })
                ->get();
        } else {
            $presence_sheets = [];
        }

        return response()->json($presence_sheets, 200);
    }
    public function create_presence(Request $request)
    {
        $request->validate([
            'group_id' => ['required'],
            'starts_at' => ['required'],
            'ends_at' => ['required'],
        ]);
        $group_id = $request->group_id;
        $starts_at = Carbon::parse($request->starts_at);
        $ends_at = Carbon::parse($request->ends_at);
        $group = Group::find($group_id);
        $presence = Presence::where([
            'group_id' => $group_id,
            'starts_at' => $starts_at,
            'ends_at' => $ends_at,
        ])->first();

        if (!$presence) {
            $presence = Presence::create([
                'group_id' => $group_id,
                'starts_at' => $starts_at,
                'ends_at' => $ends_at,
            ]);
        }
        $presence->students()->sync($group->students, ['status' => 'absent']);

        $presence->load('group.teacher.user', 'students.user');


        return response()->json($presence, 200);
    }
}
