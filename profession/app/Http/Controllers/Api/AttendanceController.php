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


        return DB::transaction(function () use ($request) {
            $presence = Presence::findOrFail($request->presence);
            if ($presence) {
                $presence->students()->syncWithoutDetaching([$request->student => ['status' => 'present']]);
            }

            $student = Student::with('groups')->find($request->student,);
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

    public function presences()
    {
        $presences = Presence::all();

        return DB::transaction(function () use ($presences) {
            foreach ($presences as $presence) {
                if ($presence->status != "canceled" && $presence->status != "ended") {
                    if ($presence->starts_at <= Carbon::now() && Carbon::now() <= $presence->ends_at) {
                        $presence->update(["status" => "started"]);
                    } elseif (Carbon::now() > $presence->ends_at) {
                        $presence->update(["status" => "ended"]);

                        $group = Group::find($presence->group_id);
                        if ($presence->type !== "free") {
                            $group->update([
                                "current_nb_session" => $group->current_nb_session + 1,
                            ]);
                        }

                        foreach ($group->students as $student) {

                            $status = $student->presences()
                                ->orderBy('created_at', 'desc')
                                ->take(2)
                                ->get()
                                ->filter(function ($presence) use ($group) {
                                    return $presence->pivot->status === 'absent' && $presence->group_id === $group->id;
                                })
                                ->count() !== 2;

                            if ($student->pivot->status === "active" && $status) {

                                if ($presence->type !== "free") {
                                    $group->students()->updateExistingPivot($student->id, [
                                        "nb_session" => $student->pivot->nb_session - 1,
                                    ]);
                                }

                                if ($group->current_nb_session > $group->nb_session) {
                                    $group->update([
                                        "current_nb_session" =>  $group->type === "revision" || $group->type === "dawarat" ? $group->current_nb_session : 1,
                                        'current_month' => $group->type === "revision" || $group->type === "dawarat" ? $group->current_month : $group->current_month + 1,
                                    ]);
                                    if ($group->type !== 'revision' && $group->type !== "dawarat") {
                                        $rest_session = $group->nb_session - $group->current_nb_session + 1;
                                        $checkout = Checkout::create([
                                            'paid_price' => 0,
                                            'price' => ($group->price_per_month - $group->discount) / $group->nb_session * $rest_session,
                                            'month' => $group->current_month,
                                            'discount' => $student->pivot->discount,
                                            'teacher_percentage' => $group->percentage,
                                            'nb_session' => $rest_session,
                                        ]);

                                        $price = 0;
                                        if ($student->pivot->nb_paid_session >= $rest_session) {
                                            $checkout->paid_price += $rest_session * ($group->price_per_month - $group->discount) / $group->nb_session;
                                            $checkout->status = "paid";
                                            $price = -$rest_session * ($group->price_per_month - $group->discount) / $group->nb_session;
                                            $group->students()->updateExistingPivot($student->id, [
                                                "nb_session" => $student->pivot->nb_session - 1,
                                            ]);
                                        } elseif ($student->pivot->nb_paid_session > 0) {
                                            $student->groups()->updateExistingPivot($group->id, [
                                                'debt' => $student->groups()->where('group_id', $group->id)->first()->pivot->debt + ($rest_session - $student->pivot->nb_paid_session) *  ($group->price_per_month - $group->discount) / $group->nb_session
                                            ]);
                                            $checkout->paid_price += $student->pivot->nb_paid_session * ($group->price_per_month - $group->discount) / $group->nb_session;
                                            $price = -$rest_session * ($group->price_per_month - $group->discount) / $group->nb_session;
                                            $checkout->status = "paying";
                                        } else {
                                            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                                                ->get(env('AUTH_API') . '/api/wallet/' . $student->user->id);
                                                if ($response->failed()) {
                                                    $statusCode = $response->status();
                                                    $errorBody = $response->json();
                                                    abort($statusCode, $errorBody['message'] ?? 'Unknown error');
                                                }
                                        
                                                if ($response->serverError()) {
                                                    abort(500, 'Server error occurred');
                                                }
                                        
                                                if ($response->clientError()) {
                                                    abort($response->status(), 'Client error occurred');
                                                }
                                            if ($response) {
                                                $balance = json_decode($response->body(), true)['balance'];

                                                if ($balance >= $checkout->price - $checkout->discount - $checkout->paid_price) {
                                                    $checkout->paid_price += $checkout->price - $checkout->discount - $checkout->paid_price;
                                                    $price = $checkout->price - $checkout->discount - $checkout->paid_price;
                                                    $checkout->status = "paid";
                                                    $group->students()->updateExistingPivot($student->id, [
                                                        "nb_session" => $student->pivot->nb_session - 1,
                                                    ]);
                                                } elseif ($balance > 0) {
                                                    $student->groups()->updateExistingPivot($group->id, [
                                                        'debt' => $student->groups()->where('group_id', $group->id)->first()->pivot->debt +  ($checkout->price - $checkout->discount - $balance)
                                                    ]);
                                                    $checkout->paid_price += $balance;
                                                    $price = $balance;
                                                    $checkout->status = "paying";
                                                } else {
                                                    $price = -$checkout->price + $checkout->discount;
                                                    $student->groups()->updateExistingPivot($group->id, [
                                                        'debt' => $student->groups()->where('group_id', $group->id)->first()->pivot->debt +  ($checkout->price - $checkout->discount)
                                                    ]);
                                                }
                                            } else {
                                                $price = -$checkout->price + $checkout->discount;
                                                $student->groups()->updateExistingPivot($group->id, [
                                                    'debt' => $student->groups()->where('group_id', $group->id)->first()->pivot->debt +  ($checkout->price - $checkout->discount)
                                                ]);
                                            }
                                        }
                                        $checkout->save();
                                        if ($price > 0) {
                                            $admin = User::where("role", "admin")->first();
                                            // Update teacher's wallet
                                            $data = ["amount" => ($checkout->teacher_percentage * $price) / 100, "user" => $checkout->group->teacher->user];
                                            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                                                ->post(env('AUTH_API') . '/api/wallet/add', $data);
                                                if ($response->failed()) {
                                                    $statusCode = $response->status();
                                                    $errorBody = $response->json();
                                                    abort($statusCode, $errorBody['message'] ?? 'Unknown error');
                                                }
                                        
                                                if ($response->serverError()) {
                                                    abort(500, 'Server error occurred');
                                                }
                                        
                                                if ($response->clientError()) {
                                                    abort($response->status(), 'Client error occurred');
                                                }
                                            // Update admin's wallet
                                            $data = ["amount" => ((100 - $checkout->teacher_percentage) * $price) / 100, "user" => $admin];
                                            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                                                ->post(env('AUTH_API') . '/api/wallet/add', $data);
                                                if ($response->failed()) {
                                                    $statusCode = $response->status();
                                                    $errorBody = $response->json();
                                                    abort($statusCode, $errorBody['message'] ?? 'Unknown error');
                                                }
                                        
                                                if ($response->serverError()) {
                                                    abort(500, 'Server error occurred');
                                                }
                                        
                                                if ($response->clientError()) {
                                                    abort($response->status(), 'Client error occurred');
                                                }
                                        }
                                        $data = ["amount" => $price, "user" => $student->user];
                                        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                                            ->post(env('AUTH_API') . '/api/wallet/add', $data);
                                            if ($response->failed()) {
                                                $statusCode = $response->status();
                                                $errorBody = $response->json();
                                                abort($statusCode, $errorBody['message'] ?? 'Unknown error');
                                            }
                                    
                                            if ($response->serverError()) {
                                                abort(500, 'Server error occurred');
                                            }
                                    
                                            if ($response->clientError()) {
                                                abort($response->status(), 'Client error occurred');
                                            }
                                        $student->checkouts()->save($checkout);
                                        $group->checkouts()->save($checkout);
                                    }
                                }
                            } else if (!$status) {
                                $student->groups()->updateExistingPivot($group->id, ['status' => 'stopped', 'last_session' => $group->current_nb_session, 'last_month' => $group->current_month]);
                            }
                        }
                    }
                }
            }

            return response()->json(200);
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
                    $group = Group::find($request->group_id);
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
                    $group = Group::find($request->group_id);
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

        return DB::transaction(function () use ($request) {
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
        });
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
            $session = Session::findOrFail($session_id);
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
