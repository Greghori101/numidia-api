<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Group;
use App\Models\Level;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    //


    public function all(Request $request)
    {
        $checkouts = Checkout::with(['user.profile_picture', 'student.user', 'group.teacher.user'])
            ->get()
            ->groupBy('user_id');

        $groupedCheckouts = [];

        foreach ($checkouts as  $userCheckouts) {
            $user = $userCheckouts->first()->user;

            $paidCheckouts = $userCheckouts->where('payed', true);
            $cumulativePrice = $paidCheckouts->sum('price');

            $groupedCheckouts[] = [
                'user' => $user,
                'cumulative_price' => $cumulativePrice,
                'checkouts' => $userCheckouts,
            ];
        }

        return response()->json($groupedCheckouts, 200);
    }

    public function index(Request $request)
    {

        $perPage = $request->query('perPage', 10);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $search = $request->query('search', "");
        $groupId = $request->query('group_id');
        $studentId = $request->query('student_id');
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');
        $type = $request->query('type');

        $query = Checkout::query()
            ->with(['group.teacher.user', 'student.user'])
            ->when($groupId, function ($q) use ($groupId) {
                return $q->where('group_id', 'like', "%$groupId%");
            })
            ->when($studentId, function ($q) use ($studentId) {
                return $q->where('student_id', 'like', "%$studentId%");
            })
            ->where(function ($q) use ($search) {
                $q->orWhereHas('group.teacher.user', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                })
                    ->orWhereHas('student.user', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    })
                    ->orWhereHas('group', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });

        if ($start_date && $end_date) {
            $query = $query->whereBetween('date', [$start_date, $end_date]);
        } elseif ($start_date) {
            $query = $query->where('date', '>=', $start_date);
        } elseif ($end_date) {
            $query = $query->where('date', '<=', $end_date);
        }

        if ($type === "day") {
            $query = $query->whereDate('date', Carbon::now());
        } elseif ($type === "month") {
            $query = $query->whereMonth('date', Carbon::now()->month);
        } elseif ($type === "year") {
            $query = $query->whereYear('date', Carbon::now()->year);
        }

        $query = $query->orderBy($sortBy, $sortDirection);

        if ($perPage !== 'all') {
            $checkouts = $query->paginate($perPage);
        } else {
            $checkouts = $query->paginate(Checkout::count());
        }

        return response()->json($checkouts, 200);
    }

    public function get_stats(Request $request)
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

    public function show($id)
    {
        $checkout = Checkout::with(["student.user", "group.teacher.user"])->find($id);
        return response()->json($checkout, 200);
    }

    public function pay(Request $request)
    {

        $ids  = $request->checkouts;
        $user = User::find($request->user()->id);

        foreach ($ids as $id) {
            $checkout = Checkout::find($id);
            if (!$checkout->payed) {
                $student = $checkout->student;
                $group = $checkout->group;
                $teacher = $group->teacher;
                $admin = User::where("role", "admin")->first();

                $checkout->payed = true;
                // $checkout->pay_date = Carbon::now();
                $teacher->user->wallet->balance += ($teacher->percentage * $checkout->price) / 100;
                $admin->wallet->balance += ((100 - $teacher->percentage) * $checkout->price) / 100;
                $student->user->wallet->balance += $checkout->price;

                $checkout->save();
                $teacher->user->wallet->save();
                $student->user->wallet->save();
                $admin->wallet->save();

                $user->checkouts()->save($checkout);
            }
        }
        return response()->json(200);
    }

    public function create(Request $request)
    {
        $checkout = Checkout::create([
            'price' => $request->price,
            'date' => Carbon::now(),
        ]);
        return response()->json(200);
    }

    public function delete($id)
    {

        $checkout = Checkout::find($id);

        $checkout->delete();

        return response()->json(200);
    }
}
