<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Group;
use App\Models\Receipt;
use App\Models\Service;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CheckoutController extends Controller
{
    public function all()
    {
        $checkouts = Checkout::with(['student.user', 'group.teacher.user'])
            ->get();

        return response()->json($checkouts, 200);
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

    public function pay_debt(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'checkouts' => ['array'],
        ]);

        // Initialize variables
        $ids = $request->checkouts;
        $user = User::find($request->user["id"]);
        $total = 0;

        // Create a new receipt
        $receipt = new Receipt([
            "total" => $total,
            "type" => "debt",
            'employee_id' => $user->id,
        ]);

        // Process each checkout
        foreach ($ids as $checkout) {
            $id = $checkout['id'];
            $paid_price = $checkout['paid_price'];

            $checkout = Checkout::find($id);

            // Check if the checkout is not paid
            if ($checkout->status !== "paid") {
                $student = $checkout->student;
                $group = $checkout->group;
                $teacher = $group->teacher;
                $admin = User::where("role", "numidia")->first();

                // Update checkout details
                $checkout->paid_price += $paid_price;
                $checkout->pay_date = Carbon::now();

                if ($checkout->paid_price >= ($checkout->price - $checkout->discount)) {
                    $checkout->status = 'paid';
                }else{
                    $checkout->status = 'paying';
                }

                // Update teacher's wallet
                $data = ["amount" => ($checkout->teacher_percentage * $paid_price) / 100, "user" => $teacher->user];
                Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);

                // Update admin's wallet
                $data = ["amount" => ((100 - $checkout->teacher_percentage) * $paid_price) / 100, "user" => $admin];
                Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);

                // Update student's wallet
                $data = ["amount" => $paid_price, "user" => $student->user];
                Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);

                // Save the checkout
                $checkout->save();

                // Update the total amount paid
                $total += $paid_price;

                // Save the receipt and related services
                $receipt->services()->save(new Service([
                    'text' => $group->name . ' ' . $teacher->name . ' ' . $group->type,
                    'price' => $paid_price,
                ]));

                // Update the student's debt for the group
                $student->groups()->updateExistingPivot($group->id, [
                    'debt' => $student->groups()->where('group_id', $group->id)->first()->pivot->debt - $paid_price
                ]);
            }
        }

        // If total is non-zero, finalize the receipt and send notifications
        if ($total != 0) {
            $receipt->total = $total;
            $receipt->user_id = $student->user->id;
            $receipt->employee_id = $user->id;
            $receipt->save();
            $receipt->load(['user', 'employee', 'services']);

            // Notify admins about the payment
            $admins = User::where('role', "numidia")->get();
            foreach ($admins as $receiver) {
                Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                    ->post(env('AUTH_API') . '/api/notifications', [
                        'client_id' => env('CLIENT_ID'),
                        'client_secret' => env('CLIENT_SECRET'),
                        'type' => "success",
                        'title' => "New Payment",
                        'content' => "The student: " . $student->user->name . " has paid the total: " . $total . ".00 DA",
                        'displayed' => false,
                        'id' => $receiver->id,
                        'department' => env('DEPARTMENT'),
                    ]);
            }

            // Return the receipt as JSON response
            return response()->json($receipt, 200);
        }

        // Return a response indicating no payments were made
        return response()->json(['message' => 'No payments were made'], 400);
    }


    public function pay_by_sessions(Request $request, $student_id)
    {
        // Validate the incoming request
        $request->validate([
            'groups' => ['array'],
        ]);

        // Initialize variables
        $user = User::find($request->user["id"]);
        $total = 0;
        $hasPayment = false;

        $student = Student::findOrFail($student_id);
        // Create a new receipt
        $receipt = new Receipt([
            "total" => $total,
            "type" => "sessions",
            'employee_id' => $user->id,
            'user_id' => $student->user->id,
        ]);

        // Process each group
        foreach ($request->groups as $groupData) {
            $paid_price = $groupData['paid_price'];
            $nb_paid_session = $groupData['nb_paid_session'];
            $group = Group::findOrFail($groupData['id']);

            // Check if the student has no remaining debt for the group
            $pivot = $student->groups()->where('group_id', $group->id)->first()->pivot;
            if ($pivot->debt <= 0) {
                $hasPayment = true;

                // Update the student's wallet
                $data = ["amount" => $paid_price, "user" => $student->user];
                Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);

                // Update the receipt and total
                $total += $paid_price;
                $receipt->services()->save(new Service([
                    'text' => $group->name,
                    'price' => $paid_price,
                    'qte' => $nb_paid_session,
                ]));

                // Update student's group pivot table
                $student->groups()->updateExistingPivot($group->id, [
                    'nb_paid_session' => $pivot->nb_paid_session + $nb_paid_session,
                ]);
            }
        }

        // Finalize receipt and send notifications if any payments were made
        if ($hasPayment) {
            $receipt->total = $total;
            $receipt->save();
            $receipt->load('user', 'services'); // Load related user and services

            // Notify admins about the payment
            $admins = User::where('role', "numidia")->get();
            foreach ($admins as $receiver) {
                Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                    ->post(env('AUTH_API') . '/api/notifications', [
                        'client_id' => env('CLIENT_ID'),
                        'client_secret' => env('CLIENT_SECRET'),
                        'type' => "success",
                        'title' => "New Payment",
                        'content' => "The student: " . $student->user->name . " has paid the total: " . $total . ".00 DA",
                        'displayed' => false,
                        'id' => $receiver->id,
                        'department' => env('DEPARTMENT'),
                    ]);
            }

            // Return the receipt as JSON response
            return response()->json($receipt, 200);
        }

        // Return a response indicating no payments were made
        return response()->json(['message' => 'No payments were made'], 400);
    }


    public function update(Request $request, $id)
    {

        $request->validate([
            'status' => ['required', 'string'],
            'discount' => ['required', 'string'],
            'price' => ['required', 'string'],
            'pay_date' => ['required', 'string'],
            'teacher_percentage' => ['required', 'string'],
            'notes' => ['required', 'string'],
        ]);
        $checkout = Checkout::findOrFail($id);
        $checkout->update([
            'status' => $request->status,
            'discount' => $request->discount,
            'price' => $request->price,
            'pay_date' => $request->pay_date,
            'teacher_percentage' => $request->teacher_percentage,
            'notes' => $request->notes,
        ]);

        return response()->json(200);
    }
}
