<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        return Message::all();
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'content' => 'required',
            'from' => 'required',
            'to' => 'required',
        ]);

        return Message::create($validatedData);
    }

    public function show($id)
    {
        return Message::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'content' => 'required',
            'from' => 'required',
            'to' => 'required',
        ]);

        $message = Message::findOrFail($id);
        $message->update($validatedData);
        return $message;
    }

    public function destroy($id)
    {
        $message = Message::findOrFail($id);
        $message->delete();
        return response()->json(null, 204);
    }
}
