<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Expense;
use App\Models\FeeInscription;
use App\Models\Group;
use App\Models\Level;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Teacher;
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

            $paidCheckouts = $user->checkouts()->where('paid', true);
            $checkouts = $user->checkouts;
            $paidFees = $user->inscription_fees()->where('paid', true);
            $expenses = $user->expenses;
            $fees = $paidFees->get();
            $fees = $fees->load(['student.user']);
            $cumulativePrice = $paidCheckouts->sum('price') + $paidFees->sum('total') - $expenses->sum('total');

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
    public function checkouts()
    {
        $stats = Checkout::selectRaw('date, SUM(price) as total_price')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        $paid = Checkout::where('paid', true)->selectRaw('date, SUM(price) as total_price')
            ->groupBy('date',)
            ->orderBy('date', 'asc')
            ->get();
        $not_paid = Checkout::where('paid', false)->selectRaw('date, SUM(price) as total_price')
            ->groupBy('date',)
            ->orderBy('date', 'asc')
            ->get();

        $data = [
            "stats" => $stats,
            "paid" => $paid,
            "not_paid" => $not_paid,
        ];

        return response()->json($data, 200);
    }
    public function register_per_employee(Request $request)
    {
        $request->validate([
            'type' => 'nullable',
        ]);
        $type = $request->query('type');
        $users = User::with(['checkouts', 'expenses', 'inscription_fees'])
            ->get();

        $data = [];

        foreach ($users as $user) {

            $totalCheckouts = $user->checkouts->where('paid', true)->sum('price');
            $totalExpenses = $user->expenses->sum('total');
            $totalFeeInscriptions = $user->inscription_fees->where('paid', true)->sum('total');

            if ($type === "day") {
                $totalCheckouts = $user->checkouts->where('paid', true)->whereDate('date', Carbon::now())->sum('price');
                $totalExpenses = $user->expenses->whereDate('date', Carbon::now())->sum('total');
                $totalFeeInscriptions = $user->inscription_fees->where('paid', true)->whereDate('date', Carbon::now())->sum('total');
            } elseif ($type === "month") {
                $totalCheckouts = $user->checkouts->where('paid', true)->whereMonth('date', Carbon::now()->month)->sum('price');
                $totalExpenses = $user->expenses->whereMonth('date', Carbon::now()->month)->sum('total');
                $totalFeeInscriptions = $user->inscription_fees->where('paid', true)->whereMonth('date', Carbon::now()->month)->sum('total');
            } elseif ($type === "year") {
                $totalCheckouts = $user->checkouts->where('paid', true)->whereYear('date', Carbon::now()->year)->sum('price');
                $totalExpenses = $user->expenses->whereYear('date', Carbon::now()->year)->sum('total');
                $totalFeeInscriptions = $user->inscription_fees->where('paid', true)->whereYear('date', Carbon::now()->year)->sum('total');
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
    public function employee(Request $request)
    {
        $request->validate([
            'type' => 'nullable',
        ]);

        $type = $request->query('type');

        $users = User::with(['checkouts', 'expenses', 'inscription_fees'])->get();

        $data = [];

        foreach ($users as $user) {
            $checkouts = $user->checkouts;
            $expenses = $user->expenses;
            $inscription_fees = $user->inscription_fees;

            $totalCheckoutsPayed = $checkouts->where('paid', true)->sum('price');
            $totalCheckoutsNotPayed = $checkouts->where('paid', false)->sum('price');
            $totalExpenses = $expenses->sum('total');
            $totalFeeInscriptionsPayed = $inscription_fees->where('paid', true)->sum('total');
            $totalFeeInscriptionsNotPayed = $inscription_fees->where('paid', false)->sum('total');

            switch ($type) {
                case 'day':
                    $checkouts = $checkouts->whereDate('date', Carbon::now());
                    $expenses = $expenses->whereDate('date', Carbon::now());
                    $inscription_fees = $inscription_fees->whereDate('date', Carbon::now());
                    break;
                case 'month':
                    $checkouts = $checkouts->whereMonth('date', Carbon::now()->month);
                    $expenses = $expenses->whereMonth('date', Carbon::now()->month);
                    $inscription_fees = $inscription_fees->whereMonth('date', Carbon::now()->month);
                    break;
                case 'year':
                    $checkouts = $checkouts->whereYear('date', Carbon::now()->year);
                    $expenses = $expenses->whereYear('date', Carbon::now()->year);
                    $inscription_fees = $inscription_fees->whereYear('date', Carbon::now()->year);
                    break;
            }

            $totalCheckoutsPayed = $checkouts->where('paid', true)->sum('price');
            $totalCheckoutsNotPayed = $checkouts->where('paid', false)->sum('price');
            $totalExpenses = $expenses->sum('total');
            $totalFeeInscriptionsPayed = $inscription_fees->where('paid', true)->sum('total');
            $totalFeeInscriptionsNotPayed = $inscription_fees->where('paid', false)->sum('total');

            if ($totalCheckoutsPayed !== 0 || $totalExpenses !== 0 || $totalFeeInscriptionsPayed !== 0 || $totalCheckoutsNotPayed !== 0 || $totalFeeInscriptionsNotPayed !== 0) {
                $data[] = [
                    'user' => $user->only(['id', 'name', 'email', 'phone_number', 'role', 'gender']), // Include only necessary user data
                    'checkouts_paid' => $totalCheckoutsPayed,
                    'expenses' => -$totalExpenses,
                    'fees_paid' => $totalFeeInscriptionsPayed,
                    'checkouts_not_paid' => -$totalCheckoutsNotPayed,
                    'fees_not_paid' => -$totalFeeInscriptionsNotPayed,
                ];
            }
        }

        return response()->json($data, 200);
    }

    public function expense()
    {
        $expenses = Expense::selectRaw('date, SUM(total) as total_price')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        $data = [
            "expenses" => $expenses,
        ];

        return response()->json($data, 200);
    }
    public function fees()
    {
        $total = FeeInscription::selectRaw('date, SUM(total) as total_price')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        $paid = FeeInscription::where('paid', true)->selectRaw('date, SUM(total) as total_price')
            ->groupBy('date',)
            ->orderBy('date', 'asc')
            ->get();
        $not_paid = FeeInscription::where('paid', false)->selectRaw('date, SUM(total) as total_price')
            ->groupBy('date',)
            ->orderBy('date', 'asc')
            ->get();

        $data = [
            "total" => $total,
            "paid" => $paid,
            "not_paid" => $not_paid,
        ];

        return response()->json($data, 200);
    }
    public function students()
    {

        $paid_checkouts = Student::whereDoesntHave('checkouts', function ($query) {
            $query->where('paid', false);
        })->count();
        $not_paid_checkouts =  Student::whereHas('checkouts', function ($query) {
            $query->where('paid', false);
        })->count();
        $paid_fees = FeeInscription::where("paid", true)->distinct("student_id")->count();
        $not_paid_fees = FeeInscription::where("paid", false)->distinct("student_id")->count();


        $data = [
            "paid_checkouts" => $paid_checkouts,
            "not_paid_checkouts" => $not_paid_checkouts,
            "paid_fees" => $paid_fees,
            "not_paid_fees" => $not_paid_fees,
        ];

        return response()->json($data, 200);
    }

    public function stats(Request $request)
    {
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->get(env('AUTH_API') . '/api/wallet/' . $request->user["id"]);

        $parents = Supervisor::all()->count();
        $students = Student::all()->count();
        $teachers = Teacher::all()->count();
        $users = User::all()->count();
        $groups = Group::all()->count();
        $financials = $response->json();
        $levels = Level::all()->count();
        $data = [
            'financials' => $financials,
            'levels' => $levels,
            'users' => $users,
            'students' => $students,
            'parents' => $parents,
            'teachers' => $teachers,
            'groups' => $groups,
        ];
        return response()->json($data, 200);
    }
}
