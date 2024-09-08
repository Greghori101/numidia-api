<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeEmail;
use App\Models\Address;
use App\Models\Client;
use App\Models\File;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\DB;

class UserController extends Controller
{


    public function index(Request $request)
    {
        $request->validate([
            'sortBy' => ['nullable', 'string'],
            'sortDirection' => ['nullable', 'string', 'in:asc,desc'],
            'perPage' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string'],
        ]);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');

        $usersQuery = User::with('address')->where('role', 'client');

        $usersQuery->when($search, function ($query) use ($search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        });
        $users = $usersQuery->orderBy($sortBy, $sortDirection)

            ->paginate($perPage);

        return $users;
    }

    public function create(Request $request)
    {

        return DB::transaction(function () use ($request) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                return response()->json($user, 201);
            }
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'unique:' . User::class,
                ],
            ]);

            $client = Client::create([]);
            $user = User::create([
                'email' => $request->email,
                'name' => $request->name,
                'gender' => $request->gender,
                'role' => 'client',
                'phone_number' => $request->phone_number
            ]);
            $user->client()->save($client);


            $address = new Address([
                'city' => $request->city,
                'wilaya' => $request->wilaya,
                'street' => $request->street,
            ]);

            $user->address()->save($address);
            $client->save();

            $order = Order::create([
                'status' => 'pending',
            ]);

            if ($user['role'] == 'client') {
                $user->client->orders()->save($order);
            }
            $total = 0;

            foreach ($request->products as $product) {
                $product_data = Product::find($product['id']);

                if (!$product_data) {
                    return response()->json(['message' => 'Product not found'], 404);
                }

                $qte = $product['qte'];
                $price = $product_data->price;

                $order->products()->attach($product_data->id, [
                    'qte' => $qte,
                    'price' => $price,
                ]);

                $total += $qte * $price;
            }

            $order->total = $total;
            $order->save();
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/users/create', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'id' => $user->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'role' => $request->role,
                    'phone_number' => $request->phone_number,
                    'gender' => $request->gender,
                ]);
            return response()->json(200);
        });
    }
    public function store(Request $request)
    {

        return DB::transaction(function () use ($request) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                return response()->json($user, 201);
            }
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'unique:' . User::class,
                ],
            ]);

            $client = Client::create([]);
            $password = Str::upper(Str::random(6));
            $user = User::create([
                'email' => $request->email,
                'name' => $request->name,
                'role' => 'client',
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
            ]);
            $user->client()->save($client);


            $address = new Address([
                'city' => $request->city,
                'wilaya' => $request->wilaya,
                'street' => $request->street,
            ]);

            $user->address()->save($address);
            $client->save();
            $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
                ->post(env('AUTH_API') . '/api/users/create', [
                    'client_id' => env('CLIENT_ID'),
                    'client_secret' => env('CLIENT_SECRET'),
                    'id' => $user->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'role' => $request->role,
                    'phone_number' => $request->phone_number,
                    'gender' => $request->gender,
                ]);

            return response()->json(200);
        });
    }

    public function show($id)
    {
        $client = User::with(['address', 'client.receipts', 'client.orders.products.pictures','client.orders.client'])->findOrFail($id);
        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }
        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->get(env('AUTH_API') . '/api/profile/' . $client->id, [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
            ]);
        $client['profile_picture'] = $response->json()['profile_picture'];
        return response()->json($client);
    }

    public function update(Request $request, $id)
    {

        return DB::transaction(function () use ($request, $id) {
            $client = User::findOrFail($id)->client;

            if (!$client) {
                return response()->json(['message' => 'Client not found'], 404);
            }
            $client->update([]);

            $client->user->address()->update([
                'city' => $request->city,
                'wilaya' => $request->wilaya,
                'street' => $request->street,
            ]);

            $client->user()->update([
                'email' => $request->email,
                'name' => $request->name,
                'role' => 'client',
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
            ]);
            $client->save();
            return response()->json($client);
        });
    }

    public function delete($id)
    {
        $client = User::findOrFail($id);

        $client->delete();
        return response()->json(null, 204);
    }

    public function getOrders(Request $request)
    {
        $client = User::findOrFail($request->user["id"])->client;
        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }
        $orders = $client->orders;
        return response()->json($orders);
    }

    public function getReceipts(Request $request)
    {
        $client = User::findOrFail($request->user["id"])->client;

        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }
        $receipts = $client->receipts;
        return response()->json($receipts);
    }
}
