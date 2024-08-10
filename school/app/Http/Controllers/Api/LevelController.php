<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class LevelController extends Controller
{
    public function all()
    {
        $levels = Level::all();
        return $levels;
    }
    public function index(Request $request)
    {
        $request->validate([
            'sortBy' => ['nullable', 'string'],
            'sortDirection' => ['nullable', 'string'],
            'perPage' => ['nullable', 'integer'],
            'search' => ['nullable', 'string'],
        ]);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');

        $levelsQuery = Level::when($search, function ($query) use ($search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('education', 'like', "%$search%")
                    ->orWhere('specialty', 'like', "%$search%")
                    ->orWhere('year', 'like', "%$search%");
            });
        });

        $levels = $levelsQuery->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);

        return $levels;
    }
    public function show($id)
    {
        $level = Level::with(['groups'])->find($id);

        return response()->json($level, 200);
    }

    public function create(Request $request)
    {
        $request->validate([
            'education' => ['required', 'string'],
            'specialty' => ['nullable', 'string'],
            'year' => ['required', 'integer'],
            Rule::unique('levels')->where(function ($query) use ($request) {
                return $query->where('education', $request->education)
                             ->where('year', $request->year)
                             ->where('specialty', $request->specialty);
            }),
        ]);

        $level = Level::create([
            'education' => $request->education,
            'specialty' => $request->specialty,
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
        $request->validate([
            'education' => ['required', 'string'],
            'specialty' => ['nullable', 'string'],
            'year' => ['required', 'integer'],
            Rule::unique('levels')->where(function ($query) use ($request) {
                return $query->where('education', $request->education)
                             ->where('year', $request->year)
                             ->where('specialty', $request->specialty);
            }),
        ]);
        $level = Level::updateOrCreate(
            ['id' => $id],
            [
                'education' => $request->education,
                'specialty' => $request->specialty,
                'year' => $request->year,
            ]
        );

        $level->save();

        return response()->json(200);
    }
}
