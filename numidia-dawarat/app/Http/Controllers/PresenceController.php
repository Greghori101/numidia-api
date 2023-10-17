<?php

namespace App\Http\Controllers;

use App\Models\Presence;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'status' => 'required|string',
    ]);

    $presence = Presence::create($request->all());
    return response()->json(['data' => $presence], 201);
}

public function index()
{
    $presences = Presence::all();
    return response()->json(['data' => $presences], 200);
}

public function show($id)
{
    $presence = Presence::find($id);

    if (!$presence) {
        return response()->json(['error' => 'Presence not found'], 404);
    }

    return response()->json(['data' => $presence], 200);
}

public function update(Request $request, $id)
{
    $presence = Presence::find($id);

    if (!$presence) {
        return response()->json(['error' => 'Presence not found'], 404);
    }

    $request->validate([
        'status' => 'required|string',
    ]);

    $updated = $presence->update($request->all());

    if ($updated) {
        return response()->json(['data' => $presence], 200);
    }

    return response()->json(['error' => 'Failed to update Presence'], 400);
}

public function destroy($id)
{
    $presence = Presence::find($id);

    if (!$presence) {
        return response()->json(['error' => 'Presence not found'], 404);
    }

    $deleted = $presence->delete();

    if ($deleted) {
        return response()->json(['message' => 'Presence deleted successfully'], 204);
    }

    return response()->json(['error' => 'Failed to delete Presence'], 400);
}
}
