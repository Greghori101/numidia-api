<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WalletController extends Controller
{
    public function deposit(Request $request)
    {
        $request->validate([
            'client_id' => ['required', 'string'],
            'total' => ['required', 'numeric', 'min:0'],
        ]);
        $user = User::find($request->client_id);
        $user->wallet->balance += $request->total;
        $user->receipts()->save(new Receipt([
            'total' => $request->total,
            'type' => "deposit",
        ]));

        $user->wallet->save();
        $users = User::where('role', "admin")
            ->get();
        foreach ($users as $reciever) {
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/notifications', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'type' => "info",
                    'title' => "Deposit",
                    'content' => "New successful deposit operation with amount: " . $request->total . ".00 DA",
                    'displayed' => false,
                    'id' => $reciever->id,
                    'department' => env('DEPARTEMENT'),
                ]);
        }
    }
    public function withdraw(Request $request)
    {
        $request->validate([
            'client_id' => ['required', 'string'],
            'total' => ['required', 'numeric', 'min:0'],
        ]);
        $user = User::find($request->client_id);
        $user->wallet->balance -= $request->total;
        $user->receipts()->save(new Receipt([
            'total' => $request->total,
            'type' => "withdraw",
        ]));

        $user->wallet->save();

        $users = User::where('role', "admin")
            ->get();
        foreach ($users as $reciever) {
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/notifications', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'type' => "info",
                    'title' => "Withdraw",
                    'content' => "New successful withdraw operation with amount: " . $request->total . ".00 DA",
                    'displayed' => false,
                    'id' => $reciever->id,
                    'department' => env('DEPARTEMENT'),
                ]);
        }
    }
}
