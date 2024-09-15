<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Receipt;
use App\Models\ReceiptService;
use App\Models\Student;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([]);
        return DB::transaction(function () use ($request) {
            $student = Student::findOrFail($request->student_id);
            $dawarat = Group::findOrFail($request->dawarat_id);
            $ticket = Ticket::create([
                'row' => $request->row,
                'seat' => $request->seat,
                'location' => $request->location,
                'discount' => $request->discount,
                'title' => 'dawarat: ' .  $dawarat->teacher->user->name . '\nsubject: ' . $dawarat->module . '\nlevel: ' . $dawarat->level->year . ' ' . $dawarat->level->education . ' ' . $dawarat->level->specialty,
                'date' => $dawarat->sessions()->orderBy('starts_at', 'desc')->first() ? $dawarat->sessions()->orderBy('starts_at', 'desc')->first()->starts_at : null,
                'price' => $dawarat->price_per_month,
            ]);
            $student->tickets()->save($ticket);
            $dawarat->tickets()->save($ticket);

            $data = ["amount" => - ($ticket->price - $ticket->discount), "user" => $student->user];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);
            if ($response->failed()) {
                $statusCode = $response->status();
                $errorBody = $response->json();
                abort($statusCode, $errorBody['message'] ?? 'Unknown error');
            }

            if ($response->serverError()) {
                abort(500, 'Server error occurred');
            }

            if ($response->clientError()) {
                abort($response->status(), 'Client error occurred');
            }
            return response()->json(['data' => $ticket], 201);
        });
    }

    public function index(Request $request)
    {
        $request->validate([
            'sortBy' => ['nullable', 'string'],
            'sortDirection' => ['nullable', 'string'],
            'perPage' => ['nullable', 'integer'],
            'search' => ['nullable', 'string'],
        ]);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');

        $ticketsQuery = Ticket::with(['dawarat.teacher.user', 'dawarat.level', 'student.user', 'dawarat.amphi.sections'])->when($search, function ($query) use ($search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%$search%")
                    ->orWhere('status', 'like', "%$search%");
            });
        });

        $tickets = $ticketsQuery->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);

        return $tickets;
    }
    public function getWaiting(Request $request)
    {
        $request->validate([
            'sortBy' => ['nullable', 'string'],
            'sortDirection' => ['nullable', 'string'],
            'perPage' => ['nullable', 'integer'],
            'search' => ['nullable', 'string'],
        ]);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');

        $ticketsQuery = Ticket::with(['dawarat.teacher.user', 'dawarat.level', 'student.user', 'dawarat.amphi.sections'])
            ->where('status', 'waiting')
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%$search%")
                        ->orWhere('status', 'like', "%$search%");
                });
            });

        $tickets = $ticketsQuery->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);

        return $tickets;
    }

    public function show($id)
    {
        $ticket = Ticket::findOrFail($id)->load(['dawarat.teacher.user', 'dawarat.level', 'student.user', 'dawarat.amphi.sections']);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        return response()->json(['data' => $ticket], 200);
    }

    public function update(Request $request, $id)
    {

        return DB::transaction(function () use ($request, $id) {
            $ticket = Ticket::findOrFail($id);
            if (!$ticket || $ticket->status === 'paid' || $ticket->status === 'canceled') {
                return response()->json(['message' => 'Ticket not found'], 404);
            }

            $request->validate([]);

            $data = ["amount" => (($ticket->price - $ticket->discount) - ($request->price - $request->discount)), "user" => $ticket->student->user];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);
            if ($response->failed()) {
                $statusCode = $response->status();
                $errorBody = $response->json();
                abort($statusCode, $errorBody['message'] ?? 'Unknown error');
            }

            if ($response->serverError()) {
                abort(500, 'Server error occurred');
            }

            if ($response->clientError()) {
                abort($response->status(), 'Client error occurred');
            }
            $updated = $ticket->update([
                'row' => $request->row,
                'seat' => $request->seat,
                'location' => $request->location,
                'discount' => $request->discount,
            ]);

            if ($updated) {
                return response()->json(['data' => $ticket], 200);
            }

            return response()->json(['message' => 'Failed to update Ticket'], 400);
        });
    }

    public function pay(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $ticket = Ticket::findOrFail($id);

            if (!$ticket) {
                return response()->json(['message' => 'Ticket not found'], 404);
            }

            $request->validate([]);

            $updated = $ticket->update([
                'status' => 'paid'
            ]);


            $user = $ticket->student->user;

            $total =  ($ticket->price - $ticket->discount) < 0 ? 0 : ($ticket->price - $ticket->discount);
            $receipt = Receipt::create([
                'total' => $total,
                'type' => 'dawarat',
            ]);

            $employee = User::findOrFail($request->user['id']);
            $employee->receipts()->save($receipt);

            $receipt->services()->save(ReceiptService::create([
                'discount' => $ticket->discount,
                'text' => 'dawarat: ' . $ticket->dawarat->teacher->user->name . ' ' . $ticket->dawarat->module,
                'qte' => 1,
                'price' => $ticket->price,

            ]));

            $user->receipts()->save($receipt);
            $data = ["amount" => $total, "user" => $user];
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json'])
                ->post(env('AUTH_API') . '/api/wallet/add', $data);
            if ($response->failed()) {
                $statusCode = $response->status();
                $errorBody = $response->json();
                abort($statusCode, $errorBody['message'] ?? 'Unknown error');
            }

            if ($response->serverError()) {
                abort(500, 'Server error occurred');
            }

            if ($response->clientError()) {
                abort($response->status(), 'Client error occurred');
            }
            if ($updated) {
                return response()->json($receipt, 200);
            }

            return response()->json(['message' => 'Failed to pay Ticket'], 400);
        });
    }

    public function cancel(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $ticket = Ticket::findOrFail($id);

            if (!$ticket) {
                return response()->json(['message' => 'Ticket not found'], 404);
            }

            $request->validate([]);

            if ($ticket->status !== 'paid' || $ticket->status !== 'canceled') {
                $updated = $ticket->update([
                    'status' => 'canceled'
                ]);
            } else if ($ticket->status !== 'paid') {
                return response()->json(['message' => 'Ticket already canceled'], 400);
            } else {
                return response()->json(['message' => 'Ticket is paid'], 400);
            }

            if ($updated) {
                return response()->json(['data' => $ticket], 200);
            }

            return response()->json(['message' => 'Failed to update Ticket'], 400);
        });
    }
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $ticket = Ticket::findOrFail($id);

            if (!$ticket) {
                return response()->json(['message' => 'Ticket not found'], 404);
            }

            $deleted = $ticket->delete();

            if ($deleted) {
                return response()->json(['message' => 'Ticket deleted successfully'], 204);
            }

            return response()->json(['message' => 'Failed to delete Ticket'], 400);
        });
    }
}
