<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\FeeInscription;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;

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

        $feeInscriptions = $feeInscriptionsQuery->orderBy($sortBy, $sortDirection)
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
        $feeInscription = FeeInscription::create([
            'amount' => $request->amount,
            'date' => $request->date,
          ]);

        $user = User::find($request->user()->id);
        $user->inscription_fees()->save($feeInscription);
        $student = Student::find($request->student_id);
        $student->fee_inscription()->save($feeInscription);

        return response()->json($feeInscription, 201);
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
                'user_id' => $request->user_id,
                'student_id' => $request->student_id,
            ]);

            return response()->json($feeInscription, 200);
        } else {
            return response()->json(['error' => 'Fee inscription not found'], 404);
        }
    }
}
