<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Expense;
use App\Models\FeeInscription;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FinancialController extends Controller
{

    public function all_per_employee()
    {
        $users = User::with(['checkouts.student.user', 'checkouts.group.teacher.user', 'inscription_fees.student.user', 'expenses'])
            ->get();

        $data = [];
        foreach ($users as  $user) {
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->get(env('AUTH_API') . '/api/profile/' . $user->id, [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                ]);
            $user['profile_picture'] = $response->json()['profile_picture'];

            $paidCheckouts = $user->checkouts()->where('payed', true);
            $checkouts = $user->checkouts;
            $paidFees = $user->inscription_fees()->where('payed', true);
            $fees = $user->inscription_fees;
            $expenses = $user->expenses;

            $cumulativePrice = $paidCheckouts->sum('price') + $paidFees->sum('amount') - $expenses->sum('amount');

            if ($cumulativePrice != 0) {
                $data[] = [
                    'user' => $user,
                    'cumulative_price' => $cumulativePrice,
                    'checkouts' => $checkouts,
                    'expenses' => $expenses,
                    'fees' => $fees,
                ];
            }
        }

        return response()->json($data, 200);
    }

    //
    public function checkouts_stats()
    {
        $stats = Checkout::selectRaw('date, SUM(price) as total_price')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        $payed = Checkout::where('payed', true)->selectRaw('date, SUM(price) as total_price')
            ->groupBy('date',)
            ->orderBy('date', 'asc')
            ->get();
        $not_payed = Checkout::where('payed', false)->selectRaw('date, SUM(price) as total_price')
            ->groupBy('date',)
            ->orderBy('date', 'asc')
            ->get();

        $data = [
            "stats" => $stats,
            "payed" => $payed,
            "not_payed" => $not_payed,
        ];

        return response()->json($data, 200);
    }
    public function students_stats()
    {

        $payed = User::where('role', "student")->whereHas('wallet', function ($query) {
            $query->where('balance', '>=', 0);
        })->count();
        $not_payed =  User::where('role', "student")->whereHas('wallet', function ($query) {
            $query->where('balance', '<', 0);
        })->count();

        $data = [
            "payed" => $payed,
            "not_payed" => $not_payed,
        ];

        return response()->json($data, 200);
    }

    public function register_per_employee(Request $request)
    {
        $request->validate([
            'type' => 'required',
        ]);
        $type = $request->query('type');
        $users = User::with(['checkouts', 'expenses', 'inscription_fees'])
            ->get();

        $data = [];

        foreach ($users as $user) {

            $totalCheckouts = $user->checkouts->where('payed', true)->sum('price');
            $totalExpenses = $user->expenses->sum('amount');
            $totalFeeInscriptions = $user->inscription_fees->where('payed', true)->sum('amount');

            if ($type === "day") {
                $totalCheckouts = $user->checkouts->where('payed', true)->whereDate('date', Carbon::now())->sum('price');
                $totalExpenses = $user->expenses->whereDate('date', Carbon::now())->sum('amount');
                $totalFeeInscriptions = $user->inscription_fees->where('payed', true)->whereDate('date', Carbon::now())->sum('amount');
            } elseif ($type === "month") {
                $totalCheckouts = $user->checkouts->where('payed', true)->whereMonth('date', Carbon::now()->month)->sum('price');
                $totalExpenses = $user->expenses->whereMonth('date', Carbon::now()->month)->sum('amount');
                $totalFeeInscriptions = $user->inscription_fees->where('payed', true)->whereMonth('date', Carbon::now()->month)->sum('amount');
            } elseif ($type === "year") {
                $totalCheckouts = $user->checkouts->where('payed', true)->whereYear('date', Carbon::now()->year)->sum('price');
                $totalExpenses = $user->expenses->whereYear('date', Carbon::now()->year)->sum('amount');
                $totalFeeInscriptions = $user->inscription_fees->where('payed', true)->whereYear('date', Carbon::now()->year)->sum('amount');
            }

            $total = $totalCheckouts + $totalFeeInscriptions - $totalExpenses;
            if ($total !== 0) {
                $data[] = [
                    'user' => $user->only(['id', 'name', 'email', 'phone_number', 'role', 'gender']), // Include only necessary user data
                    'total' => $total,
                ];
            }
        }

        return response()->json($data, 200);
    }

    public function employee_stats(Request $request)
    {
        $request->validate([
            'type' => 'required',
        ]);
        $type = $request->query('type');
        $users = User::with(['checkouts', 'expenses', 'inscription_fees'])
            ->get();

        $data = [];

        foreach ($users as $user) {

            $totalCheckoutsPayed = $user->checkouts->where('payed', true)->sum('price');
            $totalCheckoutsNotPayed = $user->checkouts->where('payed', false)->sum('price');
            $totalExpenses = $user->expenses->sum('amount');
            $totalFeeInscriptionsPayed = $user->inscription_fees->where('payed', true)->sum('amount');
            $totalFeeInscriptionsNotPayed = $user->inscription_fees->where('payed', false)->sum('amount');

            if ($type === "day") {
                $totalCheckoutsPayed = $user->checkouts->where('payed', true)->whereDate('date', Carbon::now())->sum('price');
                $totalCheckoutsNotPayed = $user->checkouts->where('payed', false)->whereDate('date', Carbon::now())->sum('price');
                $totalExpenses = $user->expenses->whereDate('date', Carbon::now())->sum('amount');
                $totalFeeInscriptionsPayed = $user->inscription_fees->where('payed', true)->whereDate('date', Carbon::now())->sum('amount');
                $totalFeeInscriptionsNotPayed = $user->inscription_fees->where('payed', false)->whereDate('date', Carbon::now())->sum('amount');
            } elseif ($type === "month") {
                $totalCheckoutsPayed = $user->checkouts->where('payed', true)->whereMonth('date', Carbon::now()->month)->sum('price');
                $totalCheckoutsNotPayed = $user->checkouts->where('payed', false)->whereMonth('date', Carbon::now()->month)->sum('price');
                $totalExpenses = $user->expenses->whereMonth('date', Carbon::now()->month)->sum('amount');
                $totalFeeInscriptionsPayed = $user->inscription_fees->where('payed', true)->whereMonth('date', Carbon::now()->month)->sum('amount');
                $totalFeeInscriptionsNotPayed = $user->inscription_fees->where('payed', false)->whereMonth('date', Carbon::now()->month)->sum('amount');
            } elseif ($type === "year") {
                $totalCheckoutsPayed = $user->checkouts->where('payed', true)->whereYear('date', Carbon::now()->year)->sum('price');
                $totalCheckoutsNotPayed = $user->checkouts->where('payed', false)->whereYear('date', Carbon::now()->year)->sum('price');
                $totalExpenses = $user->expenses->whereYear('date', Carbon::now()->year)->sum('amount');
                $totalFeeInscriptionsPayed = $user->inscription_fees->where('payed', true)->whereYear('date', Carbon::now()->year)->sum('amount');
                $totalFeeInscriptionsNotPayed = $user->inscription_fees->where('payed', false)->whereYear('date', Carbon::now()->year)->sum('amount');
            }

            if ($totalCheckoutsPayed !== 0 || $totalExpenses !== 0 || $totalFeeInscriptionsPayed !== 0 || $totalCheckoutsNotPayed !== 0 || $totalFeeInscriptionsNotPayed !== 0) {
                $data[] = [
                    'user' => $user->only(['id', 'name', 'email', 'phone_number', 'role', 'gender']), // Include only necessary user data
                    'checkouts_payed' => $totalCheckoutsPayed,
                    'expenses' => $totalExpenses,
                    'fees_payed' => $totalFeeInscriptionsPayed,
                    'checkouts_not_payed' => $totalCheckoutsNotPayed,
                    'fees_not_payed' => $totalFeeInscriptionsNotPayed,
                ];
            }
        }

        return response()->json($data, 200);
    }

    public function expense_stats()
    {
        $expenses = Expense::selectRaw('date, SUM(amount) as total_price')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        $data = [
            "expenses" => $expenses,
        ];

        return response()->json($data, 200);
    }

    public function fees_stats()
    {
        $total = FeeInscription::selectRaw('date, SUM(amount) as total_price')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        $payed = FeeInscription::where('payed', true)->selectRaw('date, SUM(amount) as total_price')
            ->groupBy('date',)
            ->orderBy('date', 'asc')
            ->get();
        $not_payed = FeeInscription::where('payed', false)->selectRaw('date, SUM(amount) as total_price')
            ->groupBy('date',)
            ->orderBy('date', 'asc')
            ->get();

        $data = [
            "total" => $total,
            "payed" => $payed,
            "not_payed" => $not_payed,
        ];

        return response()->json($data, 200);
    }
}
