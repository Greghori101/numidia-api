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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FinancialController extends Controller
{

    public function all_per_employee(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);

        $startsAt = $request->input('starts_at') ? Carbon::parse($request->input('starts_at')) : null;
        $endsAt = $request->input('ends_at') ? Carbon::parse($request->input('ends_at')) : null;

        // Fetch users with relationships
        $users = User::with([
            'employee_receipts' => function ($query) use ($startsAt, $endsAt) {
                if ($startsAt) {
                    $query->where('date', '>=', $startsAt);
                }
                if ($endsAt) {
                    $query->where('date', '<=', $endsAt);
                }
            },
            'employee_receipts.user',
            'employee_receipts.services',
            'receipts' => function ($query) use ($startsAt, $endsAt) {
                if ($startsAt) {
                    $query->where('date', '>=', $startsAt);
                }
                if ($endsAt) {
                    $query->where('date', '<=', $endsAt);
                }
            },
            'receipts.user',
            'receipts.services',
            'expenses' => function ($query) use ($startsAt, $endsAt) {
                if ($startsAt) {
                    $query->where('date', '>=', $startsAt);
                }
                if ($endsAt) {
                    $query->where('date', '<=', $endsAt);
                }
            }
        ])->get();

        $data = [];

        foreach ($users as $user) {
            // Fetch profile picture
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                ->get(env('AUTH_API') . '/api/profile/' . $user->id, [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                ]);
            $user['profile_picture'] = $response->json()['profile_picture'];

            // Categorize employee receipts
            $employeeReceiptsByType = $user->employee_receipts->groupBy('type');

            // Calculate cumulative price
            $positiveReceipts = $employeeReceiptsByType->filter(function ($value, $key) {
                return in_array($key, ['debt', 'sessions', 'fee_inscription', 'deposit']);
            })->flatten();

            $negativeReceipts = $employeeReceiptsByType->get('withdraw', collect());

            $cumulativePrice = $positiveReceipts->sum('total') - $user->expenses->sum('total') - $negativeReceipts->sum('total');

            if ($cumulativePrice != 0) {
                $data[] = [
                    'user' => $user,
                    'cumulative_price' => $cumulativePrice,
                    'receipts' => $user->receipts,
                    'expenses' => $user->expenses,
                    'employee_receipts' => [
                        'debt' => $employeeReceiptsByType->get('debt', collect())->values(),
                        'sessions' => $employeeReceiptsByType->get('sessions', collect())->values(),
                        'fee_inscription' => $employeeReceiptsByType->get('fee_inscription', collect())->values(),
                        'withdraw' => $employeeReceiptsByType->get('withdraw', collect())->values(),
                        'deposit' => $employeeReceiptsByType->get('deposit', collect())->values(),
                    ]
                ];
            }
        }

        return response()->json($data, 200);
    }
    public function get_employee_receipts(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);

        $startsAt = $request->input('starts_at') ? Carbon::parse($request->input('starts_at')) : null;
        $endsAt = $request->input('ends_at') ? Carbon::parse($request->input('ends_at')) : null;

        // Fetch users with relationships
        $user = User::with([
            'employee_receipts' => function ($query) use ($startsAt, $endsAt) {
                if ($startsAt) {
                    $query->where('date', '>=', $startsAt);
                }
                if ($endsAt) {
                    $query->where('date', '<=', $endsAt);
                }
            },
            'employee_receipts.user',
            'employee_receipts.services',
            'receipts' => function ($query) use ($startsAt, $endsAt) {
                if ($startsAt) {
                    $query->where('date', '>=', $startsAt);
                }
                if ($endsAt) {
                    $query->where('date', '<=', $endsAt);
                }
            },
            'receipts.user',
            'receipts.services',
            'expenses' => function ($query) use ($startsAt, $endsAt) {
                if ($startsAt) {
                    $query->where('date', '>=', $startsAt);
                }
                if ($endsAt) {
                    $query->where('date', '<=', $endsAt);
                }
            }
        ])->findOrFail($request->user["id"]);

        $data = [];

        if ($user) {
            // Fetch profile picture
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                ->get(env('AUTH_API') . '/api/profile/' . $user->id, [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                ]);
            $user['profile_picture'] = $response->json()['profile_picture'];

            // Categorize employee receipts
            $employeeReceiptsByType = $user->employee_receipts->groupBy('type');

            // Calculate cumulative price
            $positiveReceipts = $employeeReceiptsByType->filter(function ($value, $key) {
                return in_array($key, ['debt', 'sessions', 'fee_inscription', 'deposit']);
            })->flatten();

            $negativeReceipts = $employeeReceiptsByType->get('withdraw', collect());

            $cumulativePrice = $positiveReceipts->sum('total') - $user->expenses->sum('total') - $negativeReceipts->sum('total');

            if ($cumulativePrice != 0) {
                $data[] = [
                    'user' => $user,
                    'cumulative_price' => $cumulativePrice,
                    'receipts' => $user->receipts,
                    'expenses' => $user->expenses,
                    'employee_receipts' => [
                        'debt' => $employeeReceiptsByType->get('debt', collect())->values(),
                        'sessions' => $employeeReceiptsByType->get('sessions', collect())->values(),
                        'fee_inscription' => $employeeReceiptsByType->get('fee_inscription', collect())->values(),
                        'withdraw' => $employeeReceiptsByType->get('withdraw', collect())->values(),
                        'deposit' => $employeeReceiptsByType->get('deposit', collect())->values(),
                    ]
                ];
            }
        }

        return response()->json($data, 200);
    }
    public function paid_sessions(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);

        $startsAt = $request->input('starts_at') ? Carbon::parse($request->input('starts_at')) : null;
        $endsAt = $request->input('ends_at') ? Carbon::parse($request->input('ends_at')) : null;

        // Fetch users with relationships
        $user = User::with([
            'employee_receipts' => function ($query) use ($startsAt, $endsAt) {
                if ($startsAt) {
                    $query->where('date', '>=', $startsAt);
                }
                if ($endsAt) {
                    $query->where('date', '<=', $endsAt);
                }
                $query->where('type', 'sessions');
            },
            'employee_receipts.user',
            'employee_receipts.services',
        ])->findOrFail($request->user["id"]);

        $data = [];

        if ($user) {
            $data = $user->employee_receipts;
        }

        return response()->json($data, 200);
    }
    public function transactions(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);

        $startsAt = $request->input('starts_at') ? Carbon::parse($request->input('starts_at')) : null;
        $endsAt = $request->input('ends_at') ? Carbon::parse($request->input('ends_at')) : null;

        // Fetch users with relationships
        $user = User::with([
            'employee_receipts' => function ($query) use ($startsAt, $endsAt) {
                if ($startsAt) {
                    $query->where('date', '>=', $startsAt);
                }
                if ($endsAt) {
                    $query->where('date', '<=', $endsAt);
                }
                $query->where('type', 'deposit')->orWhere('type', 'withdraw');
            },
            'employee_receipts.user',
            'employee_receipts.services',
        ])->findOrFail($request->user["id"]);

        $data = [];

        if ($user) {
            $data = $user->employee_receipts;
        }

        return response()->json($data, 200);
    }
    public function checkouts()
    {
        $stats = Checkout::selectRaw('pay_date, SUM(price-discount) as total_price')
            ->groupBy('pay_date')
            ->orderBy('pay_date', 'asc')
            ->get();
        $paid = Checkout::selectRaw('pay_date, SUM(paid_price) as total_price')
            ->groupBy('pay_date',)
            ->orderBy('pay_date', 'asc')
            ->get();
        $not_paid = Checkout::selectRaw('pay_date, SUM(price-discount-paid_price) as total_price')
            ->groupBy('pay_date',)
            ->orderBy('pay_date', 'asc')
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
        $users = User::with(['employee_receipts', 'expenses'])
            ->get();

        $data = [];

        foreach ($users as $user) {
            $total = $user->employee_receipts->sum(function ($receipt) {
                return $receipt->total - $receipt->discount;
            }) - $user->expenses->sum('total');

            if ($type === "day") {
                $total = $user->employee_receipts->whereDate('created_at', Carbon::now())->sum(function ($receipt) {
                    return $receipt->total - $receipt->discount;
                }) - $user->expenses->whereDate('date', Carbon::now())->sum('total');
            } elseif ($type === "month") {
                $total = $user->employee_receipts->whereMonth('created_at', Carbon::now()->month)->sum(function ($receipt) {
                    return $receipt->total - $receipt->discount;
                }) - $user->expenses->whereMonth('date', Carbon::now()->month)->sum('total');
            } elseif ($type === "year") {
                $total = $user->employee_receipts->whereYear('created_at', Carbon::now()->year)->sum(function ($receipt) {
                    return $receipt->total - $receipt->discount;
                }) - $user->expenses->whereYear('date', Carbon::now()->year)->sum('total');
            }

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

        $users = User::with(['employee_receipts', 'employee_receipts.services', 'expenses'])->get();

        $data = [];

        foreach ($users as $user) {
            $checkouts = $user->employee_receipts->where('type', 'debt');
            $paid_sessions = $user->employee_receipts->where('type', 'sessions');
            $inscription_fees = $user->employee_receipts->where('type', 'inscription fee');
            $expenses = $user->expenses;

            $totalCheckoutsPaid = $checkouts->sum('total');
            $totalPaidSessions = $paid_sessions->sum('total');
            $totalExpenses = $expenses->sum('total');
            $totalPaidInscriptionFees = $inscription_fees->sum('total');



            if ($totalCheckoutsPaid !== 0 || $totalExpenses !== 0 || $totalPaidInscriptionFees !== 0) {
                $data['employees'][] = [
                    'user' => $user->only(['id', 'name', 'email', 'phone_number', 'role', 'gender']),
                    'checkouts_paid' => $totalCheckoutsPaid,
                    'expenses' => -$totalExpenses,
                    'fees_paid' => $totalPaidInscriptionFees,
                    'paid_sessions' => $totalPaidSessions,
                ];
            }
        }
        $totalCheckoutsNotPaid = Checkout::selectRaw('SUM(price - discount - paid_price) as total')
            ->value('total');
        $totalFeeInscriptionsNotPaid = FeeInscription::where('paid', false)
            ->sum('total');
        $data['checkouts_not_paid'] = -$totalCheckoutsNotPaid;
        $data['fees_not_paid'] = -$totalFeeInscriptionsNotPaid;
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
        $paid_checkouts = DB::table('group_student')
            ->selectRaw('COUNT(DISTINCT student_id) as paid_checkouts')
            ->where('debt', 0)
            ->value('paid_checkouts');

        $not_paid_checkouts = DB::table('group_student')
            ->selectRaw('COUNT(DISTINCT student_id) as not_paid_checkouts')
            ->where('debt', '>', 0)
            ->value('not_paid_checkouts');

        $paid_fees = FeeInscription::where("paid", true)
            ->distinct("student_id")
            ->count();

        $not_paid_fees = FeeInscription::where("paid", false)
            ->distinct("student_id")
            ->count();

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
        $groups = Group::whereNot('type', 'dawarat')->count();
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
