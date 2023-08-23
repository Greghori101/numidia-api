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
    public function transactions($user_id)
    {
        $transactions = User::with(["transactions.to", "transactions.from"])->find($user_id)->transactions;
        return response()->json($transactions, 200);
    }
    public function transfer(Request $request, $from, $to)
    {

        $user_from = User::find($from);
        $user_to = User::find($to);

        $transaction = new Transaction([
            "amount" => $request->amount,
            "status" => $request->status,
        ]);
        $transaction->from()->save($user_from->wallet);
        $transaction->to()->save($user_to->wallet);
    }
}
