<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class NotificationController extends Controller
{

    public function send_intern(Notification $notification,$to,$from)
    {

        $from = User::find($from);
        $to = User::find($to);
        $to->received_notifications()->save($notification);
        $from->sent_notifications()->save($notification);


        // open websoket
        // send new notifications
    }

    public function send(Request $request)
    {
        $request->validate([
            'title' => 'string|required',
            'type' => 'string|required',
            'content' => 'string|required',
            'to' => 'required',
            'from' => 'required'
        ]);

        $notification = Notification::create([
            'title' => $request['title'],
            'type' => $request['type'],
            'content' => $request['content'],
        ]);

        $from = User::find($request->from);
        $to = User::find($request->to);
        $to->received_notifications()->save($notification);
        $from->sent_notifications()->save($notification);

        // open websoket
        // send new notifications

        return Response(200);
    }

    public function notifications($id = null)
    {
        $user = Auth::user();

        if ($id) {
            $notifications = Notification::find($id);
        } else {
            $notifications = $user->received_notifications->where('displayed', 0);
        }
        $list = [];
        foreach ($notifications as $notification) {
            $notification['from'] = User::find($notification->to);
            array_push($list, $notification);
        }
        return $list;
    }
    public function all()
    {
        $user = Auth::user();

        $notifications = $user->received_notifications->all();

        $list = [];
        foreach ($notifications as $notification) {
            $notification['from'] = User::find($notification->to);
            array_push($list, $notification);
        }
        return $list;
    }

    public function seen($id = null)
    {
        if (!$id) {
            $user = Auth::user();
            $notifications = $user->received_notifications;

            foreach ($notifications as $notification) {

                $notification->update(['displayed' => true]);
            }
            return Response(200);
        } else {

            $notification = Notification::find($id);
            if (!$notification) {
                abort(404);
            }
            $notification->update(['displayed' => true]);
            return Response(200);
        }
    }

    public function delete($id = null)
    {

        if (!$id) {
            $user = Auth::user();
            $notifications = $user->received_notifications;

            foreach ($notifications as $notification) {

                $notification->delete();
            }
            return Response(200);
        } else {
            $notification = Notification::find($id);
            $notification->delete();

            return Response(200);
        }
    }
}