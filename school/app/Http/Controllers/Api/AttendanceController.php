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

    public function sessions(Request $request)
    {
        $sessions = Session::with(['exceptions', 'group.teacher.user'])->get();

        $finalSessions = [];
        $start = Carbon::parse($request->starts_at);
        $end = Carbon::parse($request->ends_at);
        foreach ($sessions as $session) {
            # code...
            $finalSessions = array_merge($finalSessions, $this->checkDate($session, $start, $end));
        }
        usort($finalSessions, function ($a, $b) {
            return strtotime($a['starts_at']) - strtotime($b['starts_at']);
        });
        return response()->json($finalSessions, 200);
    }
    public function checkDate($session, $start, $end)
    {
        $sessionStart = Carbon::parse($session->starts_at);

        $repeating = $session->repeating;

        if ($start->greaterThan($end) || $sessionStart->greaterThan($end)) {
            return [];
        }

        $temp = [];
        if ($repeating == "once" && $sessionStart->betweenIncluded($start, $end)) {
            $temp[] = $session;
        } elseif ($repeating == "weekly" || $repeating == "monthly") {
            $firstSessionDate = $sessionStart->copy();

            while ($firstSessionDate->lessThan($start)) {
                if ($repeating == "weekly") {
                    $firstSessionDate->addWeek();
                } else {
                    $firstSessionDate->addMonth();
                }
            }

            while ($firstSessionDate->lessThanOrEqualTo($end)) {
                $sessionCopy = $session;
                $duration = Carbon::parse($sessionCopy['ends_at'])->diffInMilliseconds(Carbon::parse($sessionCopy['starts_at']));
                $sessionCopy['starts_at'] = $firstSessionDate->format("Y-m-d H:i");
                $sessionCopy['ends_at'] = Carbon::parse($sessionCopy['starts_at'])->addMilliseconds($duration)->format("Y-m-d H:i");
                $b = false;
                foreach ($session->exceptions as $exception) {
                    # code...
                    if ($firstSessionDate->EqualTo($exception->date)) {
                        $b = true;
                    }
                }
                if (!$b) {
                    $temp[] = $sessionCopy;
                }

                if ($repeating == "weekly") {
                    $firstSessionDate->addWeek();
                } else {
                    $firstSessionDate->addMonth();
                }
            }
        }



        return $temp;
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
                ->where(function ($query) use ($request) {
                    $query->whereHas('group', function ($groupQuery) use ($request) {
                        $groupQuery->where('module', 'like', '%' . $request->search . '%');
                    })
                        ->orWhereHas('students.user', function ($userQuery) use ($request) {
                            $userQuery->where('name', 'like', '%' . $request->search . '%');
                        });
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
