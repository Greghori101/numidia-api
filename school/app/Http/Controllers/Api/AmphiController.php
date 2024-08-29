<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Amphi;
use App\Models\Section;
use Illuminate\Http\Request;

class AmphiController extends Controller
{
    

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

        $amphisQuery = Amphi::with(['sections'])->when($search, function ($query) use ($search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%$search%")
                    ->orWhere('location', 'like', "%$search%");
            });
        });

        $amphis = $amphisQuery->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);

        return $amphis;
    }

    public function show($id)
    {
        $amphi = Amphi::find($id);

        if (!$amphi) {
            return response()->json(['message' => 'Amphi not found'], 404);
        }

        return response()->json(['data' => $amphi], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'capacity' => 'required|integer',
            'location' => 'required|string',
            'name' => 'required|string|unique:amphis,name',
            'nb_columns' => 'required|integer',
            'nb_rows' => 'required|integer',
            'sections' => 'array',
            'sections.*.ending_row' => 'required|integer',
            'sections.*.ending_column' => 'required|integer',
            'sections.*.starting_row' => 'required|integer',
            'sections.*.starting_column' => 'required|integer',
        ]);

        // Create the Amphi
        $amphi = Amphi::create($request->except('sections'));

        // Create the associated Sections
        $sectionsData = $request->input('sections');
        $sections = [];

        foreach ($sectionsData as $sectionData) {
            $sections[] = new Section($sectionData);
        }

        $amphi->sections()->saveMany($sections);

        return response()->json(['data' => $amphi], 201);
    }

    public function update(Request $request, $id)
    {
        $amphi = Amphi::find($id);

        if (!$amphi) {
            return response()->json(['message' => 'Amphi not found'], 404);
        }

        $request->validate([
            'capacity' => 'required|integer',
            'location' => 'required|string',
            'name' => ['required', 'string',],
            'nb_columns' => 'required|integer',
            'nb_rows' => 'required|integer',
            'sections' => 'array', // Make sure sections is an array
            'sections.*.id' => 'sometimes|required|exists:sections,id',
            'sections.*.nb_rows' => 'required|integer',
            'sections.*.nb_seats' => 'required|integer',
            'sections.*.name' => 'required|string',
        ]);

        $amphi->fill($request->except('sections'));
        $amphi->save();

        $sectionsData = $request->input('sections');

        foreach ($sectionsData as $sectionData) {
            if (isset($sectionData['id'])) {
                $section = Section::find($sectionData['id']);
                $section->update($sectionData);
            } else {
                $amphi->sections()->create($sectionData);
            }
        }

        return response()->json(['data' => $amphi], 200);
    }


    public function destroy($id)
    {
        $amphi = Amphi::find($id);

        if (!$amphi) {
            return response()->json(['message' => 'Amphi not found'], 404);
        }

        $deleted = $amphi->delete();

        if ($deleted) {
            return response()->json(['message' => 'Amphi deleted successfully'], 204);
        }

        return response()->json(['message' => 'Failed to delete Amphi'], 400);
    }
}
