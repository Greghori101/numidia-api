<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Level;
use App\Models\Checkout;
use App\Models\User;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    //


    public function checkout_info($id){
        $checkout = Checkout::find($id);
        $checkout['student'] = $checkout->student->user;
        $checkout['teacher'] = $checkout->group->teacher->user;
        $checkout['module'] = $checkout->group->teacher->module;

        return response()->json($checkout,200);
        

    }
    public function index(Request $request, $id = null)
    {
        if ($id) {
            $checkout = Checkout::find($id);
            $checkout['level'] = $checkout->level;
            $checkout['departement'] = $checkout->level->departement->name;
            return response()->json($checkout, 200);
        } else {
            $departements = Departement::where('name', "LIKE", "%{$request->departement}%")->get();
            $checkouts = [];
            foreach ($departements as $departement) {

                foreach ($departement->levels as $level) {
                    foreach ($level->checkouts as $checkout) {
                        $checkout['level'] = $checkout->level;
                        $checkout['departement'] = $departement->name;
                        $checkouts[] = $checkout;
                    }
                }
            }
            return response()->json($checkouts, 200);
        }
    }

    public function all(Request $request, $id = null)
    {


        if ($id) {
            $checkout = Checkout::find($id);
            $checkout['level'] = $checkout->level;
            $checkout['departement'] = $checkout->level->departement->name;
            return response()->json($checkout, 200);
        } else {
            $departements = Departement::where('name', "LIKE", "%{$request->departement}%")->get();
            $checkouts = [];
            foreach ($departements as $departement) {

                foreach ($departement->levels as $level) {
                    foreach ($level->checkouts as $checkout) {
                        $checkout['level'] = $checkout->level;
                        $checkout['departement'] = $departement->name;
                        $checkouts[] = $checkout;
                    }
                }
            }
            return response()->json($checkouts, 200);
        }
    }

    public function create(Request $request)
    {
        $checkout = Checkout::create([
            'price' => $request->price,
            'duration' => $request->duration,
            'benefits' => $request->benefits,
        ]);

        $checkout->level()->associate(Level::find($request->level_id))->save();
        // $checkout->teacher()->associate(Teacher::find($request->teacher_id));

        $checkout->save();

        return response()->json(200);
    }

    public function delete($id)
    {

        $checkout = Checkout::find($id);

        $checkout->delete();

        return response()->json(200);
    }

    public function update(Request $request, $id)
    {

        $old_checkout = Checkout::find($id);
        $old_checkout->delete();

        $checkout = Checkout::create([
            'price' => $request->price,
            'duration' => $request->duration,
            'benefits' => $request->benefits,
        ]);

        $checkout->level()->associate($request->level_id)->save();
        // $checkout->teacher()->save(Teacher::find($request->teacher_id));

        $checkout->save();

        return response()->json(200);
    }
    public function choose_checkout(Request $request)
    {
        $request->validate([

            'client_id' => ['required', 'string'],
            'checkout_id' => ['required', 'string',],
        ]);
        $client = User::find($request->client_id)->student;
        $checkout = Checkout::find($request->checkout_id);
        $checkout->clients()->save($client);

        return response()->json($client, 200);
    }
}
