<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Level;
use App\Models\Plan;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    //
    public function index($id = null)
    {
        if ($id) {
            $plan = Plan::find($id);
            $plan['clients'] = [];
            $plan['level'] = $plan->level;

            foreach ($plan->clients as $client) {
                # code...
                $client['user_info'] = $client->user;
                array_push($plan['clients'], $client);
            }

            return response()->json($plan, 200);
        } else {
            $plans = Plan::all();
            foreach ($plans as  $plan) {
                # code...
                $plan['level'] = $plan->level;
                $plan['clients'] = [];
                foreach ($plan->clients as $client) {
                    # code...
                    $client['user_info'] = $client->user;
                    array_push($plan['clients'], $client);
                }
            }

            return response()->json($plans, 200);
        }
    }

    public function all(Request $request, $id = null)
    {


        if ($id) {
            $plan = Plan::find($id);
            $plan['level'] = $plan->level;
            return response()->json($plan, 200);
        } else {
            $departement = Departement::where('name', $request->departement)->first();
            $plans = [];

            foreach ($departement->levels as $level) {
                foreach ($level->plans as $plan) {
                    $plan['level'] = $plan->level;
                    $plans[] = $plan;
                }
            }
            return response()->json($plans, 200);
        }
    }

    public function create(Request $request)
    {
        $plan = Plan::create([
            'price' => $request->price,
            'duration' => $request->duration,
            'benefits' => $request->benefits,
        ]);

        $plan->level()->associate(Level::find($request->level_id))->save();
        // $plan->teacher()->associate(Teacher::find($request->teacher_id));

        $plan->save();

        return response()->json(200);
    }

    public function delete($id)
    {

        $plan = Plan::find($id);

        $plan->delete();

        return response()->json(200);
    }

    public function update(Request $request, $id)
    {

        $old_plan = Plan::find($id);
        $old_plan->delete();

        $plan = Plan::create([
            'price' => $request->price,
            'duration' => $request->duration,
            'benefits' => $request->benefits,
        ]);

        $plan->level()->associate($request->level_id)->save();
        // $plan->teacher()->save(Teacher::find($request->teacher_id));

        $plan->save();

        return response()->json(200);
    }
    public function choose_plan(Request $request)
    {
        $request->validate([

            'client_id' => ['required', 'string'],
            'plan_id' => ['required', 'string',],
        ]);
        $client = User::find($request->client_id)->student;
        $plan = Plan::find($request->plan_id);
        $plan->clients()->save($client);

        return response()->json($client, 200);
    }
}
