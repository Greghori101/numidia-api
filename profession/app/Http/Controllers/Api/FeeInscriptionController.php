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

class FeeInscriptionController extends Controller
{
    public function all()
    {
        $feeInscriptions = FeeInscription::all();
        return $feeInscriptions;
    }

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
        $feeInscription = FeeInscription::with(['user', 'student'])->find($id);
        return response()->json($feeInscription, 200);
    }

    public function create(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'total' => 'required|numeric',
            'date' => 'required|date',
        ]);
        $student = Student::find($request->student_id);
        if ($student->fee_inscription) {
            $feeInscription = $student->fee_inscription;

            $data = ["amount" => $student->fee_inscription->total -  $request->total, "user" => $student->user];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);

            $student->fee_inscription->update([
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


        $user = User::find($request->user["id"]);
        $user->inscription_fees()->save($feeInscription);



        return response()->json($feeInscription, 201);
    }
    public function pay(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'total' => 'required|numeric',
            'date' => 'required|date',
            'type' => 'required|string',
        ]);
        $student = Student::find($request->student_id);
        if ($student->fee_inscription) {
            $feeInscription = $student->fee_inscription;

            $data = ["amount" => $student->fee_inscription->total -  $request->total, "user" => $student->user];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);

            $student->fee_inscription->update([
                'total' => $request->total,
                'date' => $request->date,
            ]);
        } else {
            $feeInscription = FeeInscription::create([
                'total' => $request->total,
                'date'
                => $request->date,
            ]);
            $student->fee_inscription()->save($feeInscription);

            $data = ["amount" => -$request->total, "user" => $student->user];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);
        }


        $user = User::find($request->user["id"]);
        $user->inscription_fees()->save($feeInscription);


        $user = $student->user;
        if ($request->type == "inscription fee") {
            $admin = User::where("role", "admin")->first();

            $data = ["amount" => $request->total, "user" => $admin];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);

            $data = ["amount" => $request->total, "user" => $user];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);
            $user->student->fee_inscription()->update([
                'paid' => true,
                'pay_date' => Carbon::now(),
            ]);
        }
        Receipt::create([
            'total' => $request->total,
            'type' => $request->type,
            'user_id' => $user->id,
        ]);


        $users = User::where('role', "admin")
            ->get();
        foreach ($users as $receiver) {
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/notifications', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'type' => "success",
                    'title' => "New Payment",
                    'content' => "The student:" . $student->user->name . " has paid the total: " . $request->total . ".00 DA",
                    'displayed' => false,
                    'id' => $receiver->id,
                    'department' => env('DEPARTMENT'),
                ]);
        }

        return response()->json(200);
    }

    public function delete($id)
    {
        $feeInscription = FeeInscription::find($id);

        if ($feeInscription) {
            $student = Student::find($feeInscription->student_id);

            $data = ["amount" => $student->fee_inscription->total, "user" => $student->user];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);

            $feeInscription->delete();
            return response()->json(null, 204);
        } else {
            return response()->json(['error' => 'Fee inscription not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'total' => 'required|numeric',
            'date' => 'required|date',
        ]);
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
            return response()->json(['error' => 'Fee inscription not found'], 404);
        }
    }
}
