<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\FeeInscription;
use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class FeeInscriptionController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'sortBy' => 'nullable|string',
            'sortDirection' => 'nullable|in:asc,desc',
            'search' => 'nullable|string',
        ]);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $search = $request->query('search', '');

        $feeInscriptionsQuery = FeeInscription::when($search, function ($query) use ($search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('total', 'like', "%$search%")
                    ->orWhere('date', 'like', "%$search%");
            });
        });

        $feeInscriptions = $feeInscriptionsQuery->orderBy($sortBy, $sortDirection)->with(["student.user"])
            ->get();

        return $feeInscriptions;
    }

    public function show($id)
    {
        $feeInscription = FeeInscription::with(['student.user'])->findOrFail($id);
        return response()->json($feeInscription, 200);
    }

    public function create(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'total' => 'required|numeric',
            'date' => 'required|date',
        ]);
        return DB::transaction(function () use ($request) {
            $student = Student::findOrFail($request->student_id);
            if ($student->fee_inscription) {
                $feeInscription = $student->fee_inscription;

                $data = ["amount" => $student->fee_inscription->total -  $request->total, "user" => $student->user];
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);

                $student->fee_inscription()->update([
                    'total' => $request->total,
                    'date' => $request->date,
                ]);
            } else {
                $feeInscription = FeeInscription::create([
                    'total' => $request->total,
                    'date' => $request->date,
                ]);
                $student->fee_inscription()->save($feeInscription);

                $data = ["amount" => -$request->total, "user" => $student->user];
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);
            }


            $user = User::findOrFail($request->user["id"]);
            $user->inscription_fees()->save($feeInscription);



            return response()->json($feeInscription, 201);
        });
    }
    public function pay(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'total' => 'required|numeric',
            'date' => 'required|date',
            'type' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            // Fetch the student and fee inscription details
            $student = Student::findOrFail($request->student_id);

            // Check if the fee inscription exists and is not paid
            if ($student->fee_inscription && !$student->fee_inscription->paid) {
                $feeInscription = $student->fee_inscription;

                // Update wallet and fee inscription details
                $data = ["amount" => $student->fee_inscription->total - $request->total, "user" => $student->user];
                Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);

                $student->fee_inscription->update([
                    'total' => $request->total,
                    'date' => $request->date,
                ]);
            } elseif ($student->fee_inscription && $student->fee_inscription->paid) {
                // Return an error response if the fee inscription is already paid
                return response()->json(['message' => 'Fee inscription already paid'], 400);
            } else {
                // Create a new fee inscription if it doesn't exist
                $feeInscription = FeeInscription::create([
                    'total' => $request->total,
                    'date' => $request->date,
                ]);
                $student->fee_inscription()->save($feeInscription);

                // Update the student's wallet
                $data = ["amount" => -$request->total, "user" => $student->user];
                Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);
            }

            // Handle the payment for inscription fee
            $admin = User::where("role", "numidia")->first();

            // Update admin's wallet
            $data = ["amount" => $request->total, "user" => $admin];
            Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);

            // Update student's wallet and fee inscription status
            $data = ["amount" => $request->total, "user" => $student->user];
            Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);

            $student->fee_inscription()->update([
                'paid' => true,
                'pay_date' => Carbon::now(),
            ]);

            // Create a new receipt
            Receipt::create([
                'total' => $request->total,
                'type' => 'inscription fee',
                'user_id' => $student->user->id,
                'employee_id' => $request->user["id"],
            ]);

            // Send notifications to all admins
            $admins = User::where('role', "numidia")->get();
            foreach ($admins as $admin) {
                Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                    ->post(env('AUTH_API') . '/api/notifications', [
                        'client_id' => env('CLIENT_ID'),
                        'client_secret' => env('CLIENT_SECRET'),
                        'type' => "success",
                        'title' => "New Payment",
                        'content' => "The student: " . $student->user->name . " has paid the total: " . $request->total . ".00 DA",
                        'displayed' => false,
                        'id' => $admin->id,
                        'department' => env('DEPARTMENT'),
                    ]);
            }

            return response()->json(['message' => 'Payment processed successfully'], 200);
        });
    }


    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $feeInscription = FeeInscription::findOrFail($id);
            if ($feeInscription) {
                $student = Student::find($feeInscription->student_id);

                $data = ["amount" => $student->fee_inscription->total, "user" => $student->user];
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);

                $feeInscription->delete();
                return response()->json(null, 204);
            } else {
                return response()->json(['message' => 'Fee inscription not found'], 404);
            }
        });
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'total' => 'required|numeric',
            'date' => 'required|date',
        ]);
        return DB::transaction(function () use ($request, $id) {
            $feeInscription = FeeInscription::find($id);

            if ($feeInscription) {
                $student = Student::find($feeInscription->student_id);

                $data = ["amount" => $student->fee_inscription->total -  $request->total, "user" => $student->user];
                $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                    ->post(env('AUTH_API') . '/api/wallet/add', $data);

                $feeInscription->update([
                    'total' => $request->total,
                    'date' => $request->date,
                    'id' => $request->user["id"],
                    'student_id' => $request->student_id,
                ]);

                return response()->json($feeInscription, 200);
            } else {
                return response()->json(['message' => 'Fee inscription not found'], 404);
            }
        });
    }
}
