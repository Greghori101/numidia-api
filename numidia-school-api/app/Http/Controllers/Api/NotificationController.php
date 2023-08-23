<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'title' => 'string|required',
            'type' => 'string|required',
            'content' => 'string|required',
            'to' => 'required',
        ]);

        $notification = Notification::create([
            'title' => $request['title'],
            'type' => $request['type'],
            'content' => $request['content'],
        ]);

        $to = User::find($request->to);
        $to->received_notifications()->save($notification);

        return Response(200);
    }

    public function index()
    {
        $user = Auth::user();
        $notifications = $user->received_notifications->where('displayed', 0);
        return $notifications;
    }
    public function show($id)
    {
        $notification = Notification::find($id);
        return $notification;
    }
    public function all()
    {
        $user = Auth::user();
        $notifications = $user->received_notifications->all();

        return $notifications;
    }
    public function seen($id)
    {
        $notification = Notification::find($id);
        $notification->update(['displayed' => true]);

        return Response(200);
    }

    public function seen_all()
    {
        $user = Auth::user();
        $notifications = $user->received_notifications;

        foreach ($notifications as $notification) {
            $notification->update(['displayed' => true]);
        }
        return Response(200);
    }

    public function delete($id)
    {
        $notification = Notification::find($id);
        $notification->delete();

        return Response(200);
    }
    public function delete_all()
    {
        $user = Auth::user();
        $notifications = $user->received_notifications;

        foreach ($notifications as $notification) {
            $notification->delete();
        }
        return Response(200);
    }
}
