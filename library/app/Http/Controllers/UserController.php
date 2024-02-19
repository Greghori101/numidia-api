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

class UserController extends Controller
{

    
    public function index()
    {
        $users = User::with(['profile_picture'])->all();
        return response()->json($users, 200);
    }

    public function create(Request $request)
    {

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


        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/register', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'role' => $request->role,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
            ]);


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

        return response()->json(200);
    }
    public function store(Request $request)
    {

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
            ->post(env('AUTH_API') . '/api/register', [
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'role' => $request->role,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
            ]);

        return response()->json(200);
    }

    public function show($id)
    {
        $client = User::with(['address', 'client.receipts', 'client.orders'])->findOrFail($id);
        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }
        return response()->json($client);
    }

    public function update(Request $request, $id)
    {
        $client = User::find($id)->client;

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
            'password' => $request->password,
            'name' => $request->name,
            'role' => 'client',
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
        ]);
        $client->save();
        return response()->json($client);
    }

    public function delete($id)
    {
        $client = User::find($id);

        $client->delete();
        return response()->json(null, 204);
    }

    public function getOrders(Request $request)
    {
        $client = User::find($request->user["id"])->client;
        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }
        $orders = $client->orders;
        return response()->json($orders);
    }

    public function getReciepts(Request $request)
    {
        $client = User::find($request->user["id"])->client;

        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }
        $reciepts = $client->reciepts;
        return response()->json($reciepts);
    }
}
