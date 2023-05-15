<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Level;
use App\Models\Module;
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


    public function modules(Request $request, $id = null)
    {
        if ($id) {
            $module = Module::find($id);
            $module['level'] = $module->level;
            $module['teachers'] = [];
            foreach ($module->teachers as $teacher) {
                # code...
                array_push($module['teachers'], $teacher->user);
            }

            return response()->json($module, 200);
        } else {
            $modules = Module::all();
            foreach ($modules as $module) {
                # code...

                $module['level'] = $module->level;
            }

            return response()->json($modules, 200);
        }
    }

    public function create_module(Request $request)
    {
        $module = Module::create([
            'name' => $request->name,

        ]);
        $level =  Level::find($request->level_id);
        $level->modules()->save($module);


        return response()->json(200);
    }

    public function delete_module($id)
    {

        $module = Module::find($id);

        $module->delete();

        return response()->json(200);
    }

    public function update_module(Request $request, $id)
    {



        $module = Module::updateOrCreate(['id' => $id], [
            'name' => $request->name,
        ]);
        $module->level()->associate(Level::find($request->level_id));


        return response()->json(200);
    }
}
