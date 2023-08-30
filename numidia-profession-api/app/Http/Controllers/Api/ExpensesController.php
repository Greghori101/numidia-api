<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;

class ExpensesController extends Controller
{

    public function all()
    {
        $expenses = Expense::all();
        return $expenses;
    }

    public function index(Request $request)
    {
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');

        $expensesQuery = Expense::when($search, function ($query) use ($search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('amount', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%")
                    ->orWhere('date', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        });

        $expenses = $expensesQuery->orderBy($sortBy, $sortDirection)
            ->with(["user"])
            ->paginate($perPage);

        return $expenses;
    }

    public function show($id)
    {
        $expense = Expense::with(['user'])->find($id);
        return response()->json($expense, 200);
    }

    public function create(Request $request)
    {

        $expense = Expense::create([
            'amount' => $request->amount,
            'type' => $request->type,
            'date' => $request->date,
            'description' => $request->description,

        ]);
        $user = User::find($request->user()->id);
        $user->expenses()->save($expense);

        $admin = User::where("role","admin")->first();
        $admin->wallet->balance -= $request->amount;
        $admin->wallet->save();

        return response()->json($expense, 201);
    }

    public function delete($id)
    {
        $expense = Expense::find($id);

        if ($expense) {
            $expense->delete();
            return response()->json(null, 204);
        } else {
            return response()->json(['error' => 'Expense not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::find($id);

        if ($expense) {
            $expense->update([
                'amount' => $request->amount,
                'type' => $request->type,
                'date' => $request->date,
                'description' => $request->description,
            ]);

            return response()->json($expense, 200);
        } else {
            return response()->json(['error' => 'Expense not found'], 404);
        }
    }
}
