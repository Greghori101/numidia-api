<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'title' => 'string|required',
            'type' => 'string|required',
            'content' => 'string|required',
        ]);

        $notification = Notification::create([
            'title' => $request['title'],
            'type' => $request['type'],
            'content' => $request['content'],
            'user_id' => $request['id'],
            'displayed' => false,
            'department' => $request['department'],
        ]);


        return response()->json($notification, 200);
    }

    public function index(Request $request)
    {
        $user = User::findOrFail($request->user()->id);
        $notify = $user->received_notifications->where('displayed', 0)->count() > 0;
        $notifications = $user->received_notifications()->where('displayed', 0)->get();
        $data = ['notifications' => $notifications, 'notify' => $notify];
        return response()->json($data, 200);
    }
    public function show($id)
    {
        $notification = Notification::findOrFail($id);
        return $notification;
    }
    public function all(Request $request)
    {
        $user = User::findOrFail($request->user()->id);
        $notifications = $user->received_notifications->all();

        return response()->json($notifications,200);
    }
    public function seen($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['displayed' => true]);

        return Response(200);
    }

    public function seen_all(Request $request)
    {
        $user = User::findOrFail($request->user()->id);
        $user->received_notifications()->update(['displayed' => true]);
        return response()->json(200);
    }

    public function delete($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return Response(200);
    }
    public function delete_all(Request $request)
    {
        $user = User::findOrFail($request->user()->id);
        $user->received_notifications()->where('displayed', 1)->delete();

        return Response(200);
    }
}
