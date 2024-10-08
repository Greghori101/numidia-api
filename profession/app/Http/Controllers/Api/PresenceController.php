<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Checkout;
use App\Models\ExceptionSession;
use App\Models\Group;
use App\Models\Presence;
use App\Models\Session;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;

use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
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
        return DB::transaction(function () use ($request) {
            $presence = Presence::findOrFail($request->presence);

            if ($presence) {
                $presence->students()->syncWithoutDetaching([$request->student => ['status' => 'present']]);
            }

            $student = Student::with('groups')->findOrFail($request->student,);
            foreach ($student->groups as $group) {
                if ($group->pivot->debt > 0) {
                    return response()->json(['message' => 'this student :' . $student->user->name . ' has debt'], 200);
                }
            }
            $status = $student->presences()
                ->orderBy('created_at', 'desc')
                ->take(2)
                ->get()
                ->filter(function ($presence) use ($group) {
                    return $presence->pivot->status === 'absent' && $presence->group_id === $group->id;
                })
                ->count() !== 2;
            if (!$status) {
                return response()->json(['message' => 'this student :' . $student->user->name . ' has too many absence'], 200);
            }
            $group = $student->groups()->where($presence->group_id)->first();
            if ($group->nb_session = 1) {
                return response()->json(['message' => 'this student :' . $student->user->name . ' left with the last the session in group :' . $group->module], 200);
            } else if ($group->nb_session == 0) {
                return response()->json(['message' => 'this student :' . $student->user->name . ' has no sessions left in group :' . $group->module], 200);
            } else if ($group->nb_session < 0) {
                return response()->json(['message' => 'this student :' . $student->user->name . ' has debt in group :' . $group->module], 200);
            }

            return response()->json(200);
        });
    }
    public function remove_presence(Request $request)
    {
        $request->validate([
            'presence' => ['required'],
            'student' => ['required'],
        ]);
        return DB::transaction(function () use ($request) {
            $presence = Presence::findOrFail($request->presence);

            if ($presence) {
                $presence->students()->syncWithoutDetaching([$request->student => ['status' => 'absent']]);
            }


            return response()->json([], 200);
        });
    }

    public function cancel_session(Request $request, $session_id)
    {
        $request->validate([
            'group_id' => ['required'],
            'starts_at' => ['required'],
            'ends_at' => ['required'],
        ]);

        return DB::transaction(function () use ($request, $session_id) {
            $session = Session::findOrFail($session_id);
            $starts_at = Carbon::parse($request->starts_at);
            $ends_at = Carbon::parse($request->ends_at);


            $presence = Presence::where([
                'group_id' => $request->group_id,
                'starts_at' => $starts_at,
                'ends_at' => $ends_at,
            ])->first();

            if (!$presence) {
                if ($session->repeating != "once") {
                    $session->exceptions()->save(new ExceptionSession(['date' => $starts_at]));
                    $group = Group::findOrFail($request->group_id);
                    $session = Session::create([
                        "classroom" => $session->classroom,
                        "starts_at" => $starts_at,
                        "ends_at" => $ends_at,
                        "repeating" => "once",
                        "status" => "canceled",
                    ]);
                    $group->sessions()->save($session);
                } else {
                    $session->update(["status" => "canceled"]);
                }
            } else if ($presence->status != "ended") {
                if ($session->repeating != "once") {
                    $session->exceptions()->save(new ExceptionSession(['date' => $starts_at]));
                    $group = Group::findOrFail($request->group_id);
                    $session = Session::create([
                        "classroom" => $session->classroom,
                        "starts_at" => $starts_at,
                        "ends_at" => $ends_at,
                        "repeating" => "once",
                        "status" => "canceled",
                    ]);
                    $group->sessions()->save($session);
                } else {
                    $session->update(["status" => "canceled"]);
                }
                $presence->update(['status' => 'canceled', "session_id" => $session->id]);
            }

            return response()->json(200);
        });
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
                    $query->whereHas('students.user', function ($userQuery) use ($request) {
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
            'session_id' => ['required'],
            'starts_at' => ['required'],
            'ends_at' => ['required'],
        ]);
        return DB::transaction(function () use ($request) {
            $group_id = $request->group_id;
            $session_id = $request->session_id;
            $session = Session::find($session_id);
            $starts_at = Carbon::parse($request->starts_at);
            $ends_at = Carbon::parse($request->ends_at);
            $group = Group::find($group_id);
            $presence = Presence::where('starts_at', $starts_at)
                ->where('ends_at', $ends_at)
                ->where('group_id', $group_id)
                ->first();

            if (!$presence) {
                $presence = Presence::create([
                    'group_id' => $group_id,
                    'session_id' => $session_id,
                    'starts_at' => $starts_at,
                    'ends_at' => $ends_at,
                    'month' => $group->current_month,
                    'session_number' => $group->current_nb_session,
                    'type' => $group->type === "revision" || $group->type === "dawarat" ? $group->type  : 'normal',
                    'status' => $session->status,
                ]);
            }

            foreach ($group->students as $student) {
                if ($student->pivot->status === "active") {
                    $group = $student->groups()->where('group_id', $group_id)->first();
                    $type =  $group->pivot->status === "active"  ? ($presence->type === "free" ? "free" : ($group->pivot->debt > 0 ? 'in debt' : 'normal')) : "stopped";
                    $student->presences()->attach([$presence->id => ['status' => 'absent', 'type' => $type]]);
                }
            }

            $presence->load('group.teacher.user', 'students.user');

            return response()->json($presence, 200);
        });
    }
}
