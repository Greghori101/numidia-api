<?php

namespace App\Http\Controllers;

use App\Models\Dawarat;
use Illuminate\Http\Request;

class DawaratController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|unique:dawarats,title',
            'date' => 'required|date',
            'price' => 'required|numeric',
        ]);

        $dawarat = Dawarat::create($request->all());
        return response()->json(['data' => $dawarat], 201);
    }

    public function index()
    {
        $dawarats = Dawarat::all();
        return response()->json(['data' => $dawarats], 200);
    }

    public function show($id)
    {
        $dawarat = Dawarat::find($id);

        if (!$dawarat) {
            return response()->json(['error' => 'Dawarat not found'], 404);
        }

        return response()->json(['data' => $dawarat], 200);
    }

    public function update(Request $request, $id)
    {
        $dawarat = Dawarat::find($id);

        if (!$dawarat) {
            return response()->json(['error' => 'Dawarat not found'], 404);
        }

        $request->validate([
            'title' => ['required', 'string'],
            'date' => 'required|date',
            'price' => 'required|numeric',
        ]);

        $updated = $dawarat->update($request->all());

        if ($updated) {
            return response()->json(['data' => $dawarat], 200);
        }

        return response()->json(['error' => 'Failed to update Dawarat'], 400);
    }

    public function destroy($id)
    {
        $dawarat = Dawarat::find($id);

        if (!$dawarat) {
            return response()->json(['error' => 'Dawarat not found'], 404);
        }

        $deleted = $dawarat->delete();

        if ($deleted) {
            return response()->json(['message' => 'Dawarat deleted successfully'], 204);
        }

        return response()->json(['error' => 'Failed to delete Dawarat'], 400);
    }
}
