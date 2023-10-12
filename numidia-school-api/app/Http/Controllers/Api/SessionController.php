<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExceptionSession;
use App\Models\Notification;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SessionController extends Controller
{
    //
    public function index()
    {
        $sessions = Session::with(["exceptions", "group.teacher.user"])->get();
        return response()->json($sessions, 200);
    }

    public function show($id)
    {
        $session = Session::with(['group', 'exceptions'])->find($id);
        return response()->json($session, 200);
    }

    public function except(Request $request, $id)
    {
        $session = Session::find($id);
        $session->exceptions()->save(new ExceptionSession(['date' => Carbon::parse($request->date)]));
        return response()->json($session, 200);
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
            "repeating" => $request->repeating,
        ]);
        $session->save();



        foreach ($session->group->students as $student) {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])
                ->post(env('AUTH_API') . '/api/notifications', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'type' => "info",
                    'title' => " Session updated",
                    'content' => "new session has been created at " . Carbon::parse($request->starts_at),
                    'displayed' => false,
                    'id' => $student->user->id,
                    'department' => env('DEPARTEMENT'),
                ]);
        }

        return response()->json(200);
    }
    public function all()
    {
        $sessions = Session::with(["exceptions", "group.teacher.user","group.level"])->get();
        return response()->json($sessions, 200);
    }
}
