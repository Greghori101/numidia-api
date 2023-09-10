<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Presence;
use App\Models\Session;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function students(Request $request)
    {

        $search = $request->search;
        $query = Presence::query()
            ->where('group_id', $request->input('group_id'))
            ->where('starts_at', $request->input('starts_at'))
            ->where('ends_at', $request->input('ends_at'));

        $students = $query->with(["group", "students.user"])->get();

        if ($students->isEmpty()) {
            $group = Group::find($request->input('group_id'));
            $students = [];
            foreach ($group->students as $student) {
                $students[] = [
                    'starts_at' => $request->input('starts_at'),
                    'ends_at' => $request->input('ends_at'),
                    'status' => 'pending',
                    'student.user' => $student->user,
                ];
                Presence::create([
                    'group_id' => $group->id,
                    'student_id' => $student->id,
                    'starts_at' => Carbon::parse($request->input('starts_at')),
                    'ends_at' => Carbon::parse($request->input('ends_at')),
                    'status' => 'present',
                ]);
            }
        }
        $students = collect($students)->filter(function ($studentData) use ($search) {
            return empty($search) || stripos($studentData['student']['user']['name'], $search) !== false;
        })->values()->all();

        return response()->json($students, 200);
    }
    public function sessions(Request $request)
    {

        $startDate = $request->input('starts_at');
        $endDate = $request->input('ends_at');
        $search = $request->input('search');

        $query = Session::with(['exceptions', 'group.teacher.user']);

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->orWhereHas('group', function ($groupQuery) use ($search) {
                    $groupQuery->where('module', 'like', '%' . $search . '%');
                })->orWhereHas('group.students.user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        $sessions = $query->with(['group'])->get();
        if ($startDate && $endDate) {
            $sessions = $sessions->filter(function ($session) use ($startDate, $endDate) {
                if (
                    $session->repeating === 'once' &&
                    Carbon::parse($session->starts_at)->isBetween(Carbon::parse($startDate), Carbon::parse($endDate))
                ) {
                    return true;
                } elseif (
                    $session->repeating === 'weekly' &&
                    Carbon::parse($session->starts_at)->dayOfWeek >= Carbon::parse($startDate)->dayOfWeek &&
                    Carbon::parse($session->starts_at)->dayOfWeek <= Carbon::parse($endDate)->dayOfWeek &&
                    Carbon::parse($startDate)->hour <= Carbon::parse($session->starts_at)->hour &&
                    Carbon::parse($endDate)->hour >= Carbon::parse($session->starts_at)->hour
                ) {
                    return true;
                } elseif (
                    $session->repeating === 'monthly' &&
                    Carbon::parse($session->starts_at)->day >= Carbon::parse($startDate)->day &&
                    Carbon::parse($session->starts_at)->day <= Carbon::parse($endDate)->day &&
                    Carbon::parse($startDate)->hour <= Carbon::parse($session->starts_at)->hour &&
                    Carbon::parse($endDate)->hour >= Carbon::parse($session->starts_at)->hour
                ) {
                    return true;
                }
                return false;
            })->values();
        }
        $distinctGroups = $sessions->pluck('group')->unique();
        $groups = Group::whereIn('id', $distinctGroups->pluck('id'))->with(['teacher.user'])->get();

        return response()->json(["sessions" => $sessions, "groups" => $groups], 200);
    }
    public function mark_presence(Request $request)
    {
        $presence = Presence::find($request->presence);
        $studentId = $request->student;

        if ($presence) {
            $presence->students()->syncWithoutDetaching([$studentId => ['status' => 'present']]);
        }

        return response()->json([], 200);
    }
    public function remove_presence(Request $request)
    {
        $presence = Presence::find($request->presence);
        $studentId = $request->student;

        if ($presence) {
            $presence->students()->syncWithoutDetaching([$studentId => ['status' => 'absent']]);
        }

        return response()->json([], 200);
    }

    public function presence_sheets(Request $request)
    {

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
        $group_id = $request->group_id;
        $starts_at = Carbon::parse($request->starts_at);
        $ends_at = Carbon::parse($request->ends_at);

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

            $students = Group::find($group_id)->students;

            $presence->students()->attach($students, ['status' => 'absent']);
        }

        $presence->load('group.teacher.user', 'students.user');


        return response()->json($presence, 200);
    }
}
