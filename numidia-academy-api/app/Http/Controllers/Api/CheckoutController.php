<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    //



    public function index(Request $request)
    {

        $checkouts = Checkout::with(['student.user', 'group.teacher.user'])->get();
        return response()->json($checkouts, 200);
    }

    public function get_stats(Request $request)
    {
        $stats = Checkout::selectRaw('date, SUM(price) as total_price')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        $payed = Checkout::where('payed',true)->selectRaw('date, SUM(price) as total_price')
            ->groupBy('date', )
            ->orderBy('date', 'asc')
            ->get();
            $not_payed = Checkout::where('payed',false)->selectRaw('date, SUM(price) as total_price')
            ->groupBy('date', )
            ->orderBy('date', 'asc')
            ->get();

        $data = [
            "stats" => $stats,
            "payed" => $payed,
            "not_payed"=>$not_payed,
        ];

        return response()->json($data, 200);
    }

    public function show($id)
    {
        $checkout = Checkout::with(["student.user", "group.teacher.user"])->find($id);
        return response()->json($checkout, 200);
    }



    public function create(Request $request)
    {
        $checkout = Checkout::create([
            'price' => $request->price,
            'date' => Carbon::now(),
        ]);



        return response()->json(200);
    }

    public function delete($id)
    {

        $checkout = Checkout::find($id);

        $checkout->delete();

        return response()->json(200);
    }
}
