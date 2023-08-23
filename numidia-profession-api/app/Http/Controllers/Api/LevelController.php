<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    //
    public function index(Request $request, $id = null)
    {
        
            $levels = Level::all();
            return response()->json($levels, 200);
        
    }
    public function show($id)
    {
        $level = Level::find($id);
        $level['groups'] = [];
        foreach ($level->groups as $group) {
            # code...
            array_push($level['groups'], $group);
        }

        return response()->json($level, 200);
    }

    public function create(Request $request)
    {
        $level = Level::create([
            'education' => $request->education,
            'speciality' => $request->speciality,
            'year' => $request->year,
        ]);
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
        $level = Level::updateOrCreate(
            ['id' => $id],
            [
                'education' => $request->education,
                'sepciality' => $request->sepciality,
                'year' => $request->year,
            ]
        );

        $level->save();

        return response()->json(200);
    }
}
