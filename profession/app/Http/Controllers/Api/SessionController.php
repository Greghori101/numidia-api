<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExceptionSession;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = Session::with(["exceptions", "group.teacher.user", "group.level"])->get();
        return response()->json($sessions, 200);
    }

    public function show($id)
    {
        $session = Session::with(['group', 'exceptions'])->findOrFail($id);
        return response()->json($session, 200);
    }

    public function except(Request $request, $id)
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $session = Session::findOrFAil($id);
        $session->exceptions()->save(new ExceptionSession(['date' => Carbon::parse($request->date)]));
        return response()->json($session, 200);
    }

    public function delete($id)
    {
        $session = Session::findOrFail($id);

        $session->delete();

        return response()->json(200);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'classroom' => ['required'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
        ]);
        return DB::transaction(function () use ($request, $id) {
            $session = Session::findOrFail($id);
            $session->update([
                'classroom' => $request->classroom,
                'starts_at' => Carbon::parse($request->starts_at),
                'ends_at' => Carbon::parse($request->ends_at),
            ]);
            $session->save();

            foreach ($session->group->students as $student) {
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/notifications', [
                        'client_id' => env('CLIENT_ID'),
                        'client_secret' => env('CLIENT_SECRET'),
                        'type' => "info",
                        'title' => " Session updated",
                        'content' => "new session has been created at " . Carbon::parse($request->starts_at),
                        'displayed' => false,
                        'id' => $student->user->id,
                        'department' => env('DEPARTMENT'),
                    ]);
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

            return response()->json(200);
        });
    }

    public function all_details()
    {
        $sessions = Session::with(["exceptions", "group.teacher.user", "group.level"])->get();
        return response()->json($sessions, 200);
    }
}
