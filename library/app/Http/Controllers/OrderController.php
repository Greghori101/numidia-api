<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('products', 'client.user')->get();
        return response()->json($orders, 200);
    }

    public function create_order_client(Request $request, $id)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|uuid',
            'products.*.qte' => 'required|integer|min:1',
        ]);

        $user = User::find($id);

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

        return response()->json(['message' => 'Order created successfully'], 201);
    }
    public function show_order_products($id)
    {
        $order = Order::with('products')->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order, 200);
    }

    public function order_status(Request $request, $id)
    {
        $action = $request->input('action');
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->status = $action;
        $order->save();

        return response()->json(['message' => 'Status updated successfully'], 200);
    }

    public function order_pay(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        } else if ($order->status != "pending") {
            return response()->json(['message' => 'check order status'], 400);
        }
        $admin = User::where("role", "=", "numidia")->first();
        $data = ["amount" => $order->total, "user" => $admin];

        $response = Http::withHeaders(['decode_content' => false, 'Accept' => 'application/json',])
            ->post(env('AUTH_API') . '/api/wallet/add', $data);

        $data = json_decode($response->body(), true);


        $order->status = "paid";
        $order->save();

        $receipt = new Receipt([
            'total' => $request->total,
            'date' => now(),
        ]);

        $receipt->order()->associate($order);

        $receipt->save();
        $order->load(["products", "client.user"]);
        return response()->json(['receipt' => $receipt, 'order' => $order, 'message' => 'Status updated successfully'], 200);
    }

    public function delete_order($id)
    {
        $order = Order::find($id);
        $order->delete();
        return response()->json(200);
    }

    public function create_client(Request $request)
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

    public function order_receipt($id)
    {
        $order = Order::with(['products','client.user'])->findOrFail($id);
        return response()->json(['receipt' => $order->receipt, 'order' => $order], 200);
    }
}
