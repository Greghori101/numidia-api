<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;

class UserActivityController extends Controller
{
    public function all()
    {
        $activities = Activity::all();
        return $activities;
    }

    public function index(Request $request)
    {
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');

        $activitiesQuery = Activity::when($search, function ($query) use ($search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%$search%")
                    ->orWhere('details', 'like', "%$search%")
                    ->orWhere('ip_address', 'like', "%$search%")
                    ->orWhere('location', 'like', "%$search%")
                    ->orWhere('coordinates', 'like', "%$search%")
                    ->orWhere('device', 'like', "%$search%");
            });
        });

        $activities = $activitiesQuery->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);

        return $activities;
    }

    public function show($id)
    {
        $activity = Activity::with(['user'])->find($id);
        return response()->json($activity, 200);
    }

    public function create(Request $request)
    {
        $activity = Activity::create([
            'title' => $request->title,
            'details' => $request->details,
            'ip_address' => $request->ip_address,
            'location' => $request->location,
            'coordinates' => $request->coordinates,
            'device' => $request->device,// Assuming you have a 'user_id' field in the table
        ]);
        $user = User::find($request->user()->id);
        $user->activities()->save($activity);

        return response()->json($activity, 201);
    }

    public function delete($id)
    {
        $activity = Activity::find($id);

        if ($activity) {
            $activity->delete();
            return response()->json(null, 204);
        } else {
            return response()->json(['error' => 'Activity not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $activity = Activity::find($id);

        if ($activity) {
            $activity->update([
                'title' => $request->title,
                'details' => $request->details,
                'ip_address' => $request->ip_address,
                'location' => $request->location,
                'coordinates' => $request->coordinates,
                'device' => $request->device,
            ]);

            return response()->json($activity, 200);
        } else {
            return response()->json(['error' => 'Activity not found'], 404);
        }
    }
}
