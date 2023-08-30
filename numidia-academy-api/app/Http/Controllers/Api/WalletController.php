<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function deposit(Request $request, $user_id)
    {
        $user = User::find($user_id);
        $user->wallet()->update(["balance" => $request->balance]);
        $user->reciept()->save(new Receipt([
            'total' => $request->total,
            'type' => "deposit",
        ]));
    }
    public function withdraw(Request $request, $user_id)
    {
        $user = User::find($user_id);
        $user->wallet()->update(["balance" => $request->balance]);
        $user->reciept()->save(new Receipt([
            'total' => $request->total,
            'type' => "withdraw",
        ]));
    }
}
