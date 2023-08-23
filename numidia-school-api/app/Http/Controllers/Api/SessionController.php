<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExceptionSession;
use App\Models\Session;
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
        $session = Session::with(['group', 'exceptions'])->find($id);
        return response()->json($session, 200);
    }

    public function except(Request $request, $id)
    {
        $session = Session::find($id);
        $session->exceptions()->save(new ExceptionSession(['date' => $request->date]));
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

        return response()->json(200);
    }
}
