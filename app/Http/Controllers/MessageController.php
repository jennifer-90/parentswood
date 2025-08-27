<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'text' => 'required|string|max:1000',
        ]);

        Message::create([
            'event_id' => $event->id,
            'user_id'  => auth()->id(),
            'text'     => $data['text'],
        ]);

        return back()->with('flash', ['success' => 'Message ajouté']);
    }
}
