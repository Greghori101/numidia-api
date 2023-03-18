<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Level;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    //
    public function index(Request $request, $id = null)
    {
        if ($id) {
            $level = Level::find($id);
            $level['departement'] = $level->departement;
            $level['groups'] = [];
            foreach ($level->groups as $group) {
                # code...
                array_push($level['groups'], $group);
            }

            return response()->json($level, 200);
        } else if ($request->departement_id) {
            $levels = Level::where('departement_id', $request->departement_id);
            foreach ($levels as $level) {
                # code...
                $level['departement'] = $level->departement;
            }

            return response()->json($levels, 200);
        } else {
            $levels = Level::all();

            foreach ($levels as $level) {
                # code...
                $level['departement'] = $level->departement;
            }
            return response()->json($levels, 200);
        }
    }

    public function create(Request $request)
    {
        $level = Level::create([
            'education' => $request->education,
            'specialty' => $request->specialty,
            'year' => $request->year,

        ]);
        $departement = Departement::find($request->departement_id);
        $level->departement()->associate($departement);
        $level->save();

        return response()->json(200);
    }

    public function delete($id)
    {

        $level = Level::find($id);

        $level->delete();

        return response()->json(200);
    }

    public function update(Request $request, $id)
    {



        $level = Level::updateOrCreate(['id' => $id], [
            'education' => $request->education,
            'sepciality' => $request->sepciality,
            'year' => $request->year,
        ]);
        $departement = Departement::find($request->departement_id);
        $level->departement()->associate($departement);

        $level->save();

        return response()->json(200);
    }

    public function departements($id = null)
    {
        if ($id) {
            $departement = Departement::find($id);

            return response()->json($departement, 200);
        } else {
            $departements = Departement::all();


            return response()->json($departements, 200);
        }
    }
}
