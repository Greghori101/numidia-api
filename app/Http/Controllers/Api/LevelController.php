<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
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
            $level['department'] = $level->department;
            $level['groups'] = [];
            foreach ($level->groups as $group) {
                # code...
                array_push($level['groups'], $group);
            }

            return response()->json($level, 200);
        } else if ($request->department_id) {
            $levels = Level::where('department_id', $request->department_id);
            foreach ($levels as $level) {
                # code...
                $level['department'] = $level->department;
            }

            return response()->json($levels, 200);
        } else {
            $levels = Level::all();

            foreach ($levels as $level) {
                # code...
                $level['department'] = $level->department;
            }
            return response()->json($levels, 200);
        }
    }

    public function create(Request $request)
    {
        $level = Level::create([
            'education' => $request->education,
            'speciality' => $request->speciality,
            'year' => $request->year,

        ]);
        $department = Department::find($request->department_id);
        $level->department()->associate($department);
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
        $department = Department::find($request->department_id);
        $level->department()->associate($department);

        $level->save();

        return response()->json(200);
    }

    public function departments($id = null)
    {
        if ($id) {
            $department = Department::find($id);

            return response()->json($department, 200);
        } else {
            $departments = Department::all();


            return response()->json($departments, 200);
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
