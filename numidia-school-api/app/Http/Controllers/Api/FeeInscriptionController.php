<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\FeeInscription;
use App\Http\Controllers\Controller;
use App\Models\Notification;
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
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');

        $feeInscriptionsQuery = FeeInscription::when($search, function ($query) use ($search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('amount', 'like', "%$search%")
                    ->orWhere('date', 'like', "%$search%");
            });
        });

        $feeInscriptions = $feeInscriptionsQuery->orderBy($sortBy, $sortDirection)->with(["student.user"])
            ->paginate($perPage);

        return $feeInscriptions;
    }

    public function show($id)
    {
        $feeInscription = FeeInscription::with(['user', 'student'])->find($id);
        return response()->json($feeInscription, 200);
    }

    public function create(Request $request)
    {
        $student = Student::find($request->student_id);
        if ($student->fee_inscription) {
            $feeInscription = $student->fee_inscription;

            $student->user->wallet->balance += $student->fee_inscription->amount -  $request->amount;
            $student->fee_inscription->update([
                'amount' => $request->amount,
                'date' => $request->date,
            ]);
            $student->user->wallet->save();
        } else {
            $feeInscription = FeeInscription::create([
                'amount' => $request->amount,
                'date' => $request->date,
            ]);
            $student->fee_inscription()->save($feeInscription);
            $student->user->wallet->balance -= $request->amount;
            $student->user->wallet->save();
        }


        $user = User::find($request->user_id);
        $user->inscription_fees()->save($feeInscription);



        return response()->json($feeInscription, 201);
    }
    public function pay(Request $request)
    {
        $student = Student::find($request->student_id);
        if ($student->fee_inscription) {
            $feeInscription = $student->fee_inscription;
            $student->user->wallet->balance += $student->fee_inscription->amount -  $request->amount;
            $student->fee_inscription->update([
                'amount' => $request->amount,
                'date' => $request->date,
            ]);
            $student->user->wallet->save();
        } else {
            $feeInscription = FeeInscription::create([
                'amount' => $request->amount,
                'date' => $request->date,
            ]);
            $student->fee_inscription()->save($feeInscription);
            $student->user->wallet->balance -= $request->amount;
            $student->user->wallet->save();
        }


        $user = User::find($request->user_id);
        $user->inscription_fees()->save($feeInscription);


        $user = $student->user;
        if ($request->type == "inscription fee") {
            $admin = User::where("role", "admin")->first();
            $admin->wallet->balance += $request->total;
            $user->wallet->balance += $request->total;
            $user->student->fee_inscription()->update([
                'payed' => true,
                'pay_date' => Carbon::now(),
            ]);
            $user->wallet->save();
            $admin->wallet->save();
        }
        Receipt::create([
            'total' => $request->total,
            'type' => $request->type,
            'id' => $user->id,
        ]);


        $users = User::where('role', "admin")
            ->get();
        foreach ($users as $reciever) {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])
                ->post(env('AUTH_API') . '/api/notifications', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'type' => "success",
                    'title' => "New Payment",
                    'content' => "The student:" . $student->user->name . " has payed the amount: " . $request->total . ".00 DA",
                    'displayed' => false,
                    'id' => $reciever->id,
                    'department' => env('DEPARTEMENT'),
                ]);
        }

        return response()->json(200);
    }

    public function delete($id)
    {
        $feeInscription = FeeInscription::find($id);

        if ($feeInscription) {
            $feeInscription->delete();
            return response()->json(null, 204);
        } else {
            return response()->json(['error' => 'Fee inscription not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $feeInscription = FeeInscription::find($id);

        if ($feeInscription) {
            $feeInscription->update([
                'amount' => $request->amount,
                'date' => $request->date,
                'id' => $request->user_id,
                'student_id' => $request->student_id,
            ]);

            return response()->json($feeInscription, 200);
        } else {
            return response()->json(['error' => 'Fee inscription not found'], 404);
        }
    }
}
