<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function add(Request $request)
    {
        $user = User::find($request->user["id"]);
        $user->wallet->balance += $request['amount'];
        $user->wallet->save();

        $user->transactions()->save(new Transaction([
            "amount" => $request['amount'],
        ]));
    }
    public function show($id)
    {
        $user = User::find($id);
        return response()->json($user->wallet, 200);
    }

    public function transactions(Request $request, $id = null)
    {
        // Determine the user based on the provided ID or the authenticated user
        if ($id) {
            $user = User::find($id);
        } else {
            $user = $request->user();
        }
        
        // Determine the period and grouping from the request
        $period = $request->input('period');
        $grouping = $request->input('grouping');
    
        // Create a base query to retrieve transactions
        $query = $user->transactions();
    
        // Apply period filtering
        if ($period === 'day') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($period === 'week') {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month);
        }
    
        // Apply grouping and aggregation based on the specified period
        if ($grouping === '1h') {
            $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as date')
                ->groupBy('date');
        } elseif ($grouping === 'day') {
            $query->selectRaw('DATE(created_at) as date')
                ->groupBy('date');
        } elseif ($grouping === 'week') {
            $query->selectRaw('CONCAT(DATE_FORMAT(created_at, "%Y-%m-week:"), WEEK(created_at)) as date')
                ->groupBy('date');
        } elseif ($grouping === 'month') {
            $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as date')
                ->groupBy('date');
        } else {
            // Default to grouping individually if no valid grouping is provided
            $query->selectRaw('created_at as date')
                ->groupBy('date');
        }
    
        // Sum the amounts within each group
        $transactions = $query->selectRaw('SUM(amount) as total')
            ->orderBy('date')
            ->get();
    
        
        return response()->json($transactions, 200);
    }
}
