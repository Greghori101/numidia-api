<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function index()
    {
        $receipts = Receipt::all();

        return response()->json($receipts);
    }

    public function show(Receipt $receipt)
    {
        return response()->json($receipt);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'total' => 'required|numeric',
            'type' => 'required|string',
            'id' => 'required|exists:users,id',
        ]);

        $receipt = Receipt::find($id);
        $receipt->update($request->all());

        return response()->json($receipt);
    }

    public function delete($id)
    {
        $receipt = Receipt::find($id);
        $receipt->delete();

        return response()->json(null, 204);
    }
}
