<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Receipt;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
class ReceiptController extends Controller
{
    public function create_receipt(Request $request)
    {

        return DB::transaction(function () use ($request) {
             $order = Order::findOrFail($request->order_id);
        $receipt = new Receipt([
            'total' => $request->total,
            'date' => now(),
        ]);

        $receipt->order()->associate($order);

        $receipt->save();

        return response()->json(['message' => 'Receipt generated successfully'], 201);
        });
       
    }

    public function show_receipt($receipt_id)
    {
        $receipt = Receipt::with('order.products')->findOrFail($receipt_id);

        return response()->json($receipt, 200);
    }

    public function delete_receipt($receipt_id)
    {
        $receipt = Receipt::findOrFail($receipt_id);

        $receipt->delete();

        return response()->json(['message' => 'Receipt deleted successfully'], 200);
    }
}
