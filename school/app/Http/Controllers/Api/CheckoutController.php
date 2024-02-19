<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Receipt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CheckoutController extends Controller
{
    public function all()
    {
        $checkouts = Checkout::with(['user.profile_picture', 'student.user', 'group.teacher.user'])
            ->get()
            ->groupBy('user_id');

        $groupedCheckouts = [];

        foreach ($checkouts as  $userCheckouts) {
            $user = $userCheckouts->first()->user;

            $paidCheckouts = $userCheckouts->where('paid', true);
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

        $request->validate([
            'group_id' => ['nullable', 'string'],
            'student_id' => ['nullable', 'string'],
            'sortBy' => ['nullable', 'string'],
            'sortDirection' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'start_date' => ['nullable', 'string'],
            'end_date' => ['nullable', 'string'],
        ]);
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
                        $q->where('module', 'like', "%$search%");
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

        $checkouts = $query->get();

        return response()->json($checkouts, 200);
    }

    public function show($id)
    {
        $checkout = Checkout::with(["student.user", "group.teacher.user"])->find($id);
        return response()->json($checkout, 200);
    }

    public function pay(Request $request)
    {

        $request->validate([
            'checkouts.*' => ['string'],
        ]);
        $ids  = $request->checkouts;
        $user = User::find($request->user["id"]);

        $total = 0;
        $receipt = Receipt::create([
            "total" => $total,
            "type" => "checkouts",
        ]);

        foreach ($ids as $id) {
            $checkout = Checkout::find($id);
            if (!$checkout->paid) {
                $student = $checkout->student;
                $group = $checkout->group;
                $teacher = $group->teacher;
                $admin = User::where("role", "admin")->first();
                $checkout->paid = true;
                $checkout->pay_date = Carbon::now();

                $data = ["amount" => ($teacher->percentage * $checkout->price) / 100, "user" => $teacher->user];
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);

                $data = ["amount" => ((100 - $teacher->percentage) * $checkout->price) / 100, "user" => $admin];
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);

                $data = ["amount" => $checkout->price - $checkout->discount, "user" => $student->user];
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);
                $checkout->save();

                $total += $checkout->price;

                $user->checkouts()->save($checkout);
                $student->user->receipts()->save($receipt);
                $receipt->checkouts()->save($checkout);
            }
        }

        $receipt->total = $total;
        $receipt->save();
        $receipt->load('user', 'checkouts.group.teacher.user');



        $users = User::where('role', "admin")
            ->get();
        foreach ($users as $receiver) {
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/notifications', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'type' => "success",
                    'title' => "New Payment",
                    'content' => "The student:" . $student->user->name . " has paid the total: " . $total . ".00 DA",
                    'displayed' => false,
                    'id' => $receiver->id,
                    'department' => env('DEPARTMENT'),
                ]);
        }

        return response()->json($receipt, 200);
    }
}
