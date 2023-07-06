<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Level;
use App\Models\Checkout;
use App\Models\User;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    //


    public function checkout_info($id)
    {
        $checkout = Checkout::find($id);
        $checkout['student'] = $checkout->student->user;
        $checkout['teacher'] = $checkout->group->teacher->user;
        $checkout['module'] = $checkout->group->teacher->module;

        return response()->json($checkout, 200);
    }
    public function index(Request $request, $id = null)
    {
        
            $departments = Department::where('name', "LIKE", "%{$request->department}%")->get();
            $checkouts = [];
            foreach ($departments as $department) {

                foreach ($department->levels as $level) {
                    foreach ($level->checkouts as $checkout) {
                        $checkout['level'] = $checkout->level;
                        $checkout['department'] = $department->name;
                        $checkouts[] = $checkout;
                    }
                }
            }
            return response()->json($checkouts, 200);
        
    }

    public function show($id){
        $checkout = Checkout::find($id);
        $checkout['level'] = $checkout->level;
        $checkout['department'] = $checkout->level->department->name;
        return response()->json($checkout, 200);
    }

    public function all(Request $request, $id = null)
    {


        if ($id) {
            $checkout = Checkout::find($id);
            $checkout['level'] = $checkout->level;
            $checkout['department'] = $checkout->level->department->name;
            return response()->json($checkout, 200);
        } else {
            $departments = Department::where('name', "LIKE", "%{$request->department}%")->get();
            $checkouts = [];
            foreach ($departments as $department) {

                foreach ($department->levels as $level) {
                    foreach ($level->checkouts as $checkout) {
                        $checkout['level'] = $checkout->level;
                        $checkout['department'] = $department->name;
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

        $level = Level::find($request->level_id);
        $checkout->level()->associate($level)->save();
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

        $level = Level::find($request->level_id);
        $checkout->level()->associate($level)->save();
        // $checkout->teacher()->save(Teacher::find($request->teacher_id));

        $checkout->save();

        return response()->json(200);
    }
    
}
