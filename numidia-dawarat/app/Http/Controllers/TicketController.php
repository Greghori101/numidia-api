<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            // Add your validation rules for the 'Ticket' model here.
            // Example: 'attribute' => 'validation_rule',
        ]);
    
        $ticket = Ticket::create($request->all());
        return response()->json(['data' => $ticket], 201);
    }
    
    public function index()
    {
        $tickets = Ticket::all();
        return response()->json(['data' => $tickets], 200);
    }
    
    public function show($id)
    {
        $ticket = Ticket::find($id);
    
        if (!$ticket) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }
    
        return response()->json(['data' => $ticket], 200);
    }
    
    public function update(Request $request, $id)
    {
        $ticket = Ticket::find($id);
    
        if (!$ticket) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }
    
        $request->validate([
            // Add your validation rules for the 'Ticket' model here.
            // Example: 'attribute' => 'validation_rule',
        ]);
    
        $updated = $ticket->update($request->all());
    
        if ($updated) {
            return response()->json(['data' => $ticket], 200);
        }
    
        return response()->json(['error' => 'Failed to update Ticket'], 400);
    }
    
    public function destroy($id)
    {
        $ticket = Ticket::find($id);
    
        if (!$ticket) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }
    
        $deleted = $ticket->delete();
    
        if ($deleted) {
            return response()->json(['message' => 'Ticket deleted successfully'], 204);
        }
    
        return response()->json(['error' => 'Failed to delete Ticket'], 400);
    }
    
}
