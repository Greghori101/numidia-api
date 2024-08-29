<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExpensesController extends Controller
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

        $expensesQuery = Expense::when($search, function ($query) use ($search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('total', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%")
                    ->orWhere('date', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        });

        $expenses = $expensesQuery->orderBy($sortBy, $sortDirection)
            ->with(["user"])
            ->get();

        return $expenses;
    }

    public function show($id)
    {
        $expense = Expense::with(['user'])->find($id);
        return response()->json($expense, 200);
    }

    public function create(Request $request)
    {
        $request->validate([
            'total' => 'required|numeric',
            'type' => 'required|string',
            'date' => 'required|date',
            'description' => 'required|string',
        ]);
        $expense = Expense::create([
            'total' => $request->total,
            'type' => $request->type,
            'date' => $request->date,
            'description' => $request->description,

        ]);
        $user = User::find($request->user["id"]);
        $user->expenses()->save($expense);

        $admin = User::where("role", "numidia")->first();

        $data = ["amount" => -$request->total, "user" => $admin];
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/wallet/add', $data);



        $users = User::where('role', "numidia")
            ->get();
        foreach ($users as $receiver) {
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/notifications', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'type' => "info",
                    'title' => "New Chargers",
                    'content' => "The user:" . $user->name . " has charged: " . $request->total . ".00 DA",
                    'displayed' => false,
                    'id' => $receiver->id,
                    'department' => env('DEPARTMENT'),
                ]);
        }

        return response()->json($expense, 201);
    }

    public function delete($id)
    {
        $expense = Expense::find($id);

        if ($expense) {
            $expense->delete();
            return response()->json(null, 204);
        } else {
            return response()->json(['message' => 'Expense not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::find($id);
        $request->validate([
            'total' => 'required|numeric',
            'type' => 'required|string',
            'date' => 'required|date',
            'description' => 'required|string',
        ]);

        if ($expense) {
            $expense->update([
                'total' => $request->total,
                'type' => $request->type,
                'date' => $request->date,
                'description' => $request->description,
            ]);

            return response()->json($expense, 200);
        } else {
            return response()->json(['message' => 'Expense not found'], 404);
        }
    }
}
